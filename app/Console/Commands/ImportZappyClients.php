<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zappy → Augusta Adviser | FASE 1: importação de clientes.
 *
 * Corre SEMPRE primeiro em dry-run (sem --commit) e confirma o resumo antes
 * de gravar. Os campos opcionais (gender, birth_date, nif, address, notes,
 * is_presencial) só são preenchidos se a coluna existir mesmo na tabela
 * `clients` — não foi possível confirmar o schema exato a partir daqui, por
 * isso o script verifica em runtime com Schema::hasColumn() em vez de
 * assumir. Se alguma coisa não for preenchida que devia, é porque o nome da
 * coluna na tua BD é diferente do que o script tentou.
 *
 * ATENÇÃO: os exports mais recentes do Zappy vieram com delimitador ';' e
 * encoding Windows-1252 em vez de ',' /UTF-8. readCsv() aqui ainda assume só
 * vírgula + UTF-8 — confirma o formato do ficheiro que vais usar antes de
 * confiar no dry-run.
 *
 * Uso:
 *   php artisan zappy:import-clients /caminho/Clientes.csv                # dry-run
 *   php artisan zappy:import-clients /caminho/Clientes.csv --commit       # grava a sério
 */
class ImportZappyClients extends Command
{
    protected $signature = 'zappy:import-clients
        {csv : Caminho para o Clientes.csv exportado do Zappy}
        {--commit : Sem esta opção corre em modo simulação, sem gravar nada}';

    protected $description = 'Importa clientes do export Zappy (Clientes.csv) para a tabela clients, evitando duplicados';

    public function handle(): int
    {
        $path = $this->argument('csv');
        if (!is_file($path)) {
            $this->error("Ficheiro não encontrado: {$path}");
            return self::FAILURE;
        }

        $commit = (bool) $this->option('commit');
        $this->info($commit
            ? '>>> MODO REAL — vai gravar na base de dados <<<'
            : '>>> MODO SIMULAÇÃO (dry-run) — nada é gravado. Repete com --commit quando confirmares o resumo. <<<');

        $optionalColumns = collect(['gender', 'birth_date', 'nif', 'address', 'notes', 'is_presencial', 'active'])
            ->filter(fn ($col) => Schema::hasColumn('clients', $col))
            ->values()->all();

        if ($commit) {
            $this->comment('Colunas opcionais detetadas em `clients`: ' . (implode(', ', $optionalColumns) ?: '(nenhuma)'));
        }

        $rows = $this->readCsv($path);

        $toCreate = [];
        $skippedExisting = [];
        $skippedEmpty = 0;

        foreach ($rows as $row) {
            $name = trim($row['name'] ?? '');

            // O Zappy inclui sempre registos de demonstração (nome contém
            // "(exemplo)", email @zappysoftware.com ou o email da própria
            // Marta usado como conta de testes) — ignorar.
            $isDemo = $name === ''
                || str_contains(mb_strtolower($name), '(exemplo)')
                || str_contains(mb_strtolower(trim($row['email'] ?? '')), '@zappysoftware.com')
                || mb_strtolower(trim($row['email'] ?? '')) === 'martamacedo@mmgroup.pt';
            if ($isDemo) {
                $skippedEmpty++;
                continue;
            }

            $email = trim($row['email'] ?? '') ?: null;
            $phone = $this->cleanPhone($row['mobile'] ?? '');

            $existing = null;
            if ($email) {
                $existing = Client::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
            }
            if (!$existing && $phone) {
                $existing = Client::where('phone', $phone)->first();
            }
            if ($existing) {
                $skippedExisting[] = "{$name} — já existe como cliente #{$existing->id}";
                continue;
            }

            $data = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
            ];

            if (in_array('gender', $optionalColumns, true)) {
                $data['gender'] = match (mb_strtolower(trim($row['gender'] ?? ''))) {
                    'f' => 'Feminino',
                    'm' => 'Masculino',
                    default => null,
                };
            }

            if (in_array('birth_date', $optionalColumns, true)) {
                $y = (int) ($row['birth_year'] ?? 0);
                $mo = (int) ($row['birth_month'] ?? 0);
                $d = (int) ($row['birth_day'] ?? 0);
                $data['birth_date'] = ($y > 1900 && $mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31)
                    ? sprintf('%04d-%02d-%02d', $y, $mo, $d)
                    : null;
            }

            if (in_array('nif', $optionalColumns, true)) {
                $data['nif'] = trim($row['vat_number'] ?? '') ?: null;
            }

            if (in_array('address', $optionalColumns, true)) {
                $parts = array_filter([trim($row['address'] ?? ''), trim($row['zipcode'] ?? ''), trim($row['city'] ?? '')]);
                $data['address'] = $parts ? implode(', ', $parts) : null;
            }

            if (in_array('notes', $optionalColumns, true)) {
                $parts = array_filter([trim($row['obs_1'] ?? ''), trim($row['obs_2'] ?? ''), trim($row['obs_3'] ?? '')]);
                $data['notes'] = $parts ? '[Zappy] ' . implode(' | ', $parts) : '[Zappy] importado';
            }

            if (in_array('is_presencial', $optionalColumns, true)) {
                $data['is_presencial'] = false; // todos têm email e/ou telefone no export
            }

            if (in_array('active', $optionalColumns, true)) {
                $data['active'] = true;
            }

            $toCreate[] = $data;
        }

        $this->newLine();
        $this->info('Resumo:');
        $this->line('  A criar: ' . count($toCreate));
        $this->line('  Já existentes (ignorados): ' . count($skippedExisting));
        $this->line('  Linhas vazias/exemplo ignoradas: ' . $skippedEmpty);

        if ($skippedExisting) {
            $this->newLine();
            $this->warn('Não recriados (já existem por email/telefone):');
            foreach ($skippedExisting as $s) {
                $this->line("  - {$s}");
            }
        }

        if (!$commit) {
            $this->newLine();
            $this->comment('Nada foi gravado. Confirma a lista acima e corre novamente com --commit.');
            return self::SUCCESS;
        }

        if (!$toCreate) {
            $this->info('Nada para importar.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Vou criar ' . count($toCreate) . ' clientes novos. Confirmas?', true)) {
            $this->comment('Cancelado.');
            return self::SUCCESS;
        }

        $errors = [];
        $created = 0;
        DB::transaction(function () use ($toCreate, &$errors, &$created) {
            foreach ($toCreate as $data) {
                try {
                    Client::create($data);
                    $created++;
                } catch (\Throwable $e) {
                    $errors[] = "{$data['name']}: {$e->getMessage()}";
                }
            }
        });

        $this->info("{$created} clientes importados com sucesso.");
        if ($errors) {
            $this->newLine();
            $this->error('Falhas (revê e corre estas à mão):');
            foreach ($errors as $e) {
                $this->line("  - {$e}");
            }
        }

        return self::SUCCESS;
    }

    private function cleanPhone(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }
        // Formato Excel/Zappy: ="+351966518238"
        $clean = ltrim(trim($raw), '=');
        $clean = trim($clean, '"');
        return $clean ?: null;
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Não foi possível abrir {$path}");
        }

        // Remover BOM UTF-8 se presente
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === 1 && $data[0] === null) {
                continue; // linha em branco no fim do ficheiro
            }
            if (count($data) !== count($header)) {
                $data = array_pad(array_slice($data, 0, count($header)), count($header), null);
            }
            $rows[] = array_combine($header, $data);
        }
        fclose($handle);

        return $rows;
    }
}
