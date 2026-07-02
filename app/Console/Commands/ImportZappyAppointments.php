<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Service;
use App\Models\Workstation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zappy → Augusta Adviser | FASE 2: importação de marcações.
 *
 * Corre DEPOIS da Fase 1 (zappy:import-clients), porque cada linha precisa
 * de encontrar o cliente já criado pelo nome.
 *
 * O que o script NÃO consegue adivinhar sozinho, porque o Zappy não tem
 * esse conceito, é o POSTO (workstation) de cada marcação — usa
 * --default-workstation=ID ou a primeira estação ativa encontrada, e avisa
 * sempre no relatório para poderes corrigir à mão se for preciso.
 *
 * Se algum nome de serviço ou de profissional no Zappy não bater certo
 * (ex.: acentuação, "." a mais) com o que está em `services`/`employees`,
 * adiciona um mapeamento manual nos arrays $serviceNameOverrides /
 * $providerNameOverrides no topo da classe e corre outra vez.
 *
 * ATENÇÃO — INCOMPLETO: este script ainda chama $this->parseDate(), que não
 * está implementado nesta versão (só existe o placeholder). Os exports mais
 * recentes do Zappy também vieram com delimitador ';' e encoding
 * Windows-1252 em vez de ',' /UTF-8, e preço em euros com vírgula decimal em
 * vez de cêntimos inteiros — readCsv() e o parsing de preço aqui ASSUMEM
 * ainda o formato antigo (vírgula, UTF-8, cêntimos). NÃO correr com --commit
 * sem antes terminar isto — falha já no dry-run com "Call to undefined
 * method parseDate()".
 *
 * Uso:
 *   php artisan zappy:import-appointments /caminho/Marcações.csv                        # dry-run
 *   php artisan zappy:import-appointments /caminho/Marcações.csv --commit                # grava
 *   php artisan zappy:import-appointments /caminho/Marcações.csv --commit --default-workstation=1
 */
class ImportZappyAppointments extends Command
{
    protected $signature = 'zappy:import-appointments
        {csv : Caminho para o Marcações.csv exportado do Zappy}
        {--commit : Sem esta opção corre em modo simulação, sem gravar nada}
        {--default-workstation= : ID do posto a usar quando não for possível determinar automaticamente}';

    protected $description = 'Importa marcações do export Zappy (Marcações.csv) para a tabela appointments';

    /** Ajusta se o nome do serviço no Zappy não bater certo com services.name */
    private array $serviceNameOverrides = [
        // 'Depilação Homem Zona Grande.' => 'Depilação Homem Zona Grande',
    ];

    /** Ajusta se o nome do profissional no Zappy não bater certo com employees.name */
    private array $providerNameOverrides = [
        // 'Marta  Macedo' => 'Marta Macedo',
    ];

    private int $defaultDurationMinutes = 30;

    public function handle(): int
    {
        $path = $this->argument('csv');
        if (!is_file($path)) {
            $this->error("Ficheiro não encontrado: {$path}");
            return self::FAILURE;
        }

        $commit = (bool) $this->option('commit');
        $defaultWorkstationId = $this->option('default-workstation');

        $this->info($commit
            ? '>>> MODO REAL — vai gravar na base de dados <<<'
            : '>>> MODO SIMULAÇÃO (dry-run) — nada é gravado. Repete com --commit quando confirmares o resumo. <<<');

        $hasNotes = Schema::hasColumn('appointments', 'notes');
        $hasEndTime = Schema::hasColumn('appointments', 'end_time');
        $hasWorkstation = Schema::hasColumn('appointments', 'workstation_id');
        $hasPrice = Schema::hasColumn('appointments', 'price');

        $rows = $this->readCsv($path);

        // Pré-carrega catálogos para matching por nome (evita 1 query por linha)
        $clients = Client::all()->keyBy(fn ($c) => $this->normalize($c->name));
        $employees = Employee::all()->keyBy(fn ($e) => $this->normalize($e->name));
        $services = Service::all()->keyBy(fn ($s) => $this->normalize($s->name));
        $fallbackWorkstation = $defaultWorkstationId
            ? Workstation::find($defaultWorkstationId)
            : Workstation::where('active', true)->first();

        $toCreate = [];
        $unresolvedClient = [];
        $unresolvedService = [];
        $unresolvedProvider = [];
        $skippedCancelled = 0;
        $skippedDuplicate = 0;
        $fromOdisseias = 0;

        foreach ($rows as $row) {
            if (($row['status'] ?? '') !== 'Confirmada' || trim($row['cancel_reason'] ?? '') !== '') {
                $skippedCancelled++;
                continue;
            }

            $clientKey = $this->normalize($row['client_name'] ?? '');
            $client = $clients->get($clientKey);
            if (!$client) {
                $unresolvedClient[] = $row['client_name'] ?? '(vazio)';
                continue;
            }

            $rawService = trim($row['item_name'] ?? '');
            $serviceLookupName = $this->serviceNameOverrides[$rawService] ?? $rawService;
            $service = $services->get($this->normalize($serviceLookupName));
            if (!$service) {
                $unresolvedService[] = "{$rawService} (categoria Zappy: " . ($row['category'] ?? '?') . ')';
                continue;
            }

            $rawProvider = trim($row['service_provider'] ?? '');
            $providerLookupName = $this->providerNameOverrides[$rawProvider] ?? $rawProvider;
            $employee = $employees->get($this->normalize($providerLookupName));
            if (!$employee) {
                $unresolvedProvider[] = $rawProvider;
                continue;
            }

            try {
                $start = $this->parseDate($row['date'] ?? '');
            } catch (\Throwable) {
                $unresolvedClient[] = "{$row['client_name']} — data ilegível: {$row['date']}";
                continue;
            }

            $duration = $service->duration ?? $service->duration_minutes ?? $this->defaultDurationMinutes;
            $end = $start->copy()->addMinutes((int) $duration);

            // Evitar duplicar se o script já correu antes para este ficheiro
            $duplicate = Appointment::where('client_id', $client->id)
                ->where('service_id', $service->id)
                ->whereDate('appointment_date', $start->toDateString())
                ->where('appointment_time', $start->format('H:i:s'))
                ->exists();
            if ($duplicate) {
                $skippedDuplicate++;
                continue;
            }

            $priceCents = (int) ($row['price_final'] ?? $row['price_base'] ?? 0);
            $original = trim($row['notes'] ?? '');

            // O Zappy junta o texto de confirmação da Odisseias dentro das notas quando
            // a Marta copia/cola a reserva ("Código de Reserva: H2... / Parceiro: ...
            // Odisseias..."). Detetar isso aqui é o que permite separar a faturação
            // Odisseias da faturação direta no dashboard (ver OdisseiasRevenueStats).
            $isOdisseias = stripos($original, 'odisseias') !== false
                || stripos($original, 'código de reserva') !== false;
            if ($isOdisseias) {
                $fromOdisseias++;
            }

            $data = [
                'client_id' => $client->id,
                'employee_id' => $employee->id,
                'service_id' => $service->id,
                'appointment_date' => $start->toDateString(),
                'appointment_time' => $start->format('H:i:s'),
                // Enum real de `appointments.status` é inglês: scheduled/confirmed/completed/cancelled.
                'status' => $start->isPast() ? 'completed' : 'scheduled',
                'source' => $isOdisseias ? 'Odisseias' : 'Direto',
            ];

            if ($hasEndTime) {
                $data['end_time'] = $end->format('H:i:s');
            }
            if ($hasPrice) {
                $data['price'] = round($priceCents / 100, 2);
            }
            if ($hasNotes) {
                $data['notes'] = '[Zappy import] ' . ($original !== '' ? $original : $rawService);
            }
            if ($hasWorkstation) {
                $data['workstation_id'] = $fallbackWorkstation?->id;
                if (!$fallbackWorkstation) {
                    $unresolvedProvider[] = "{$row['client_name']} / {$rawService} — sem posto disponível (cria um Posto ou usa --default-workstation=ID)";
                    continue;
                }
            }

            $toCreate[] = $data;
        }

        $this->newLine();
        $this->info('Resumo:');
        $this->line('  A criar: ' . count($toCreate));
        $this->line('    ...das quais com origem Odisseias (detetado nas notas): ' . $fromOdisseias);
        $this->line('  Já existentes (ignoradas): ' . $skippedDuplicate);
        $this->line('  Canceladas/sem estado Confirmada (ignoradas): ' . $skippedCancelled);
        $this->line('  Clientes não encontrados: ' . count($unresolvedClient));
        $this->line('  Serviços não encontrados: ' . count(array_unique($unresolvedService)));
        $this->line('  Profissionais não encontrados / sem posto: ' . count(array_unique($unresolvedProvider)));

        foreach ([
            'Clientes não encontrados' => $unresolvedClient,
            'Serviços não encontrados (nome Zappy)' => array_unique($unresolvedService),
            'Profissionais não encontrados / problemas de posto' => array_unique($unresolvedProvider),
        ] as $label => $list) {
            if ($list) {
                $this->newLine();
                $this->warn("{$label}:");
                foreach ($list as $l) {
                    $this->line("  - {$l}");
                }
            }
        }

        if ($fallbackWorkstation) {
            $this->comment("Posto usado por omissão em todas as marcações: #{$fallbackWorkstation->id} ({$fallbackWorkstation->name}). Ajusta manualmente as que precisarem de outro.");
        }

        if (!$commit) {
            $this->newLine();
            $this->comment('Nada foi gravado. Corrige os "não encontrados" acima (nomes, serviços ainda por criar) e corre novamente com --commit.');
            return self::SUCCESS;
        }

        if (!$toCreate) {
            $this->info('Nada para importar.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Vou criar ' . count($toCreate) . ' marcações. Confirmas?', true)) {
            $this->comment('Cancelado.');
            return self::SUCCESS;
        }

        $errors = [];
        $created = 0;
        DB::transaction(function () use ($toCreate, &$errors, &$created) {
            foreach ($toCreate as $data) {
                try {
                    Appointment::create($data);
                    $created++;
                } catch (\Throwable $e) {
                    $errors[] = json_encode($data) . " — {$e->getMessage()}";
                }
            }
        });

        $this->info("{$created} marcações importadas com sucesso.");
        if ($errors) {
            $this->newLine();
            $this->error('Falhas (revê e corre estas à mão):');
            foreach ($errors as $e) {
                $this->line("  - {$e}");
            }
        }

        return self::SUCCESS;
    }

    private function normalize(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));
        $name = rtrim($name, '.');
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Não foi possível abrir {$path}");
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === 1 && $data[0] === null) {
                continue;
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
