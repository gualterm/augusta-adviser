<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Service;
use App\Models\Workstation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Importação manual do export "reports" do Zappy, para uso direto pela Marta
 * no backoffice (sem terminal) — ver App\Filament\Pages\ImportZappy.
 *
 * Substitui o antigo App\Console\Commands\ImportZappyAppointments, que tinha
 * bugs reais nunca corrigidos (parseDate() nunca implementado, delimitador e
 * preço assumidos no formato errado). Os bugs foram corrigidos aqui com base
 * no export real de 2026-07-02 (novas marcacoes.zip → reports (3).csv):
 * delimitador ';', UTF-8 com BOM, preço em euros com vírgula decimal
 * ("60,00"), data+hora num único campo "Y-m-d H:i:s".
 *
 * Deduplicação: por cliente (nome normalizado) + data + hora, contra
 * QUALQUER marcação já existente (de qualquer origem) — decisão explícita do
 * Gualter (2026-07-03): assim as marcações já trazidas automaticamente pela
 * sincronização da Odisseias nunca são duplicadas aqui, sem depender de a
 * Marta colar o "Código de Reserva" nas notas do Zappy.
 */
class ZappyImportService
{
    private const EXPECTED_HEADER = [
        'date', 'status', 'client_name', 'item_name', 'online', 'category',
        'service_provider', 'price_base', 'discount', 'price_final',
        'payment_date', 'cupao_id', 'updated_on', 'notes', 'Obs.3', 'cancel_reason',
    ];

    private const STATUSES_TO_IMPORT = ['confirmada', 'concluida', 'concluída', 'realizada'];

    /**
     * Lê e analisa o ficheiro sem gravar nada — usado para a pré-visualização
     * no backoffice antes da Marta confirmar.
     *
     * @return array{rows: array<int, array>, summary: array<string, int>}
     */
    public function preview(string $path): array
    {
        return $this->process($path, commit: false);
    }

    /**
     * Repete a mesma análise e grava as marcações novas.
     *
     * @return array{rows: array<int, array>, summary: array<string, int>}
     */
    public function commit(string $path): array
    {
        return $this->process($path, commit: true);
    }

    private function process(string $path, bool $commit): array
    {
        $csvRows = $this->readCsv($path);

        $clients = Client::all()->keyBy(fn ($c) => $this->normalize($c->name));
        $employees = Employee::all()->keyBy(fn ($e) => $this->normalize($e->name));
        $services = Service::all()->keyBy(fn ($s) => $this->normalize($s->name));
        $fallbackWorkstation = config('zappy.default_workstation_id')
            ? Workstation::find(config('zappy.default_workstation_id'))
            : Workstation::where('active', true)->first();

        $serviceOverrides = config('zappy.service_overrides', []);
        $providerOverrides = config('zappy.provider_overrides', []);

        $rows = [];
        $summary = [
            'novas' => 0,
            'duplicadas' => 0,
            'clientes_novos' => 0,
            'ignoradas_estado' => 0,
            'sem_servico' => 0,
            'sem_profissional' => 0,
            'sem_posto' => 0,
            'erros' => 0,
        ];

        // clientes criados durante este processamento (para deduplicar dentro
        // do próprio ficheiro, mesmo antes de gravar em BD no dry-run)
        $newClientsThisRun = [];

        DB::beginTransaction();

        try {
            foreach ($csvRows as $raw) {
                $statusRaw = trim($raw['status'] ?? '');
                $statusKey = $this->normalize($statusRaw);
                $clientName = trim($raw['client_name'] ?? '');
                $serviceRaw = trim($raw['item_name'] ?? '');
                $providerRaw = trim($raw['service_provider'] ?? '');

                $line = [
                    'client' => $clientName ?: '(vazio)',
                    'service' => $serviceRaw,
                    'employee' => $providerRaw,
                    'date' => null,
                    'time' => null,
                    'price' => null,
                    'status' => 'erro',
                    'note' => '',
                ];

                if (!in_array($statusKey, self::STATUSES_TO_IMPORT, true)) {
                    $summary['ignoradas_estado']++;
                    $line['status'] = 'ignorada';
                    $line['note'] = "Estado \"{$statusRaw}\" não é importado (só Confirmada/Concluída/Realizada).";
                    $rows[] = $line;
                    continue;
                }

                try {
                    $start = Carbon::createFromFormat('Y-m-d H:i:s', trim($raw['date'] ?? ''));
                } catch (\Throwable) {
                    $summary['erros']++;
                    $line['note'] = "Data ilegível: \"{$raw['date']}\".";
                    $rows[] = $line;
                    continue;
                }

                $line['date'] = $start->format('d/m/Y');
                $line['time'] = $start->format('H:i');

                // Serviço
                $serviceLookup = $serviceOverrides[$serviceRaw] ?? $serviceRaw;
                $service = $services->get($this->normalize($serviceLookup));
                if (!$service) {
                    $summary['sem_servico']++;
                    $line['status'] = 'erro';
                    $line['note'] = "Serviço \"{$serviceRaw}\" não corresponde a nenhum serviço em Serviços. Adiciona um mapeamento em config/zappy.php (service_overrides) ou cria o serviço.";
                    $rows[] = $line;
                    continue;
                }

                // Profissional
                $providerLookup = $providerOverrides[$providerRaw] ?? $providerRaw;
                $employee = $employees->get($this->normalize($providerLookup));
                if (!$employee) {
                    $summary['sem_profissional']++;
                    $line['status'] = 'erro';
                    $line['note'] = "Profissional \"{$providerRaw}\" não corresponde a nenhum profissional em Utilizadores. Adiciona um mapeamento em config/zappy.php (provider_overrides).";
                    $rows[] = $line;
                    continue;
                }

                // Posto
                $workstation = $employee->preferred_workstation_id
                    ? ($employee->preferredWorkstation ?? $fallbackWorkstation)
                    : $fallbackWorkstation;
                if (!$workstation) {
                    $summary['sem_posto']++;
                    $line['status'] = 'erro';
                    $line['note'] = 'Não existe nenhum posto ativo para atribuir a esta marcação.';
                    $rows[] = $line;
                    continue;
                }

                // Cliente (find-or-create — "cliente presencial", só com nome)
                $clientKey = $this->normalize($clientName);
                $client = $clients->get($clientKey) ?? ($newClientsThisRun[$clientKey] ?? null);
                $isNewClient = false;
                if (!$client) {
                    if ($clientName === '') {
                        $summary['erros']++;
                        $line['note'] = 'Nome do cliente vazio.';
                        $rows[] = $line;
                        continue;
                    }
                    $clientData = ['name' => $clientName];
                    if (Schema::hasColumn('clients', 'is_presencial')) {
                        $clientData['is_presencial'] = true;
                    }
                    if (Schema::hasColumn('clients', 'active')) {
                        $clientData['active'] = true;
                    }
                    if (Schema::hasColumn('clients', 'notes')) {
                        $clientData['notes'] = '[Zappy] importado automaticamente pelo upload de marcações';
                    }
                    $client = Client::create($clientData);
                    $newClientsThisRun[$clientKey] = $client;
                    $clients->put($clientKey, $client);
                    $isNewClient = true;
                    $summary['clientes_novos']++;
                }

                // Duplicado? por cliente + data + hora, qualquer origem
                $duplicate = Appointment::where('client_id', $client->id)
                    ->where('appointment_date', $start->toDateString())
                    ->where('appointment_time', $start->format('H:i:s'))
                    ->exists();

                if ($duplicate) {
                    $summary['duplicadas']++;
                    $line['status'] = 'duplicada';
                    $line['note'] = 'Já existe uma marcação para este cliente nesta data/hora — ignorada.';
                    $rows[] = $line;
                    continue;
                }

                $priceFinal = $this->parsePrice($raw['price_final'] ?? $raw['price_base'] ?? '0');
                $duration = $service->duration_minutes ?? config('zappy.default_duration_minutes', 30);
                $end = $start->copy()->addMinutes((int) $duration);
                $notes = trim($raw['notes'] ?? '');

                $data = [
                    'client_id' => $client->id,
                    'employee_id' => $employee->id,
                    'workstation_id' => $workstation->id,
                    'service_id' => $service->id,
                    'appointment_date' => $start->toDateString(),
                    'appointment_time' => $start->format('H:i:s'),
                    'end_time' => $end->format('H:i:s'),
                    'status' => $start->isPast() ? 'completed' : 'scheduled',
                    'price' => $priceFinal,
                    'source' => 'Direto',
                    'notes' => '[Zappy] ' . ($notes !== '' ? $notes : $serviceRaw),
                ];

                $line['price'] = number_format($priceFinal, 2, ',', '') . ' €';
                $line['status'] = 'nova';
                $line['note'] = $isNewClient ? 'Cliente novo criado automaticamente (só com nome).' : '';

                if ($commit) {
                    Appointment::create($data);
                }

                $summary['novas']++;
                $rows[] = $line;
            }

            if ($commit) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['rows' => $rows, 'summary' => $summary];
    }

    private function parsePrice(string $raw): float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 0.0;
        }
        // "60,00" → 60.00 ; remove milhares "1.234,56" → 1234.56
        $normalized = str_replace('.', '', $raw);
        $normalized = str_replace(',', '.', $normalized);
        return (float) $normalized;
    }

    private function normalize(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));
        $name = rtrim($name, '.');
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }

    /**
     * Lê o CSV do Zappy: delimitador ';', normalmente UTF-8 com BOM.
     * Se o cabeçalho não bater certo com o esperado, lança logo um erro claro
     * em vez de silenciosamente misturar colunas erradas.
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Não foi possível abrir o ficheiro.");
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, escape: '\\', separator: ';');
        if ($header === false) {
            fclose($handle);
            throw new \RuntimeException('Ficheiro vazio ou ilegível.');
        }

        $header = array_map('trim', $header);
        $missing = array_diff(['date', 'status', 'client_name', 'item_name', 'service_provider', 'price_final'], $header);
        if ($missing) {
            fclose($handle);
            throw new \RuntimeException(
                'O ficheiro não parece ser um export de marcações do Zappy (faltam colunas: ' . implode(', ', $missing) . '). '
                . 'Confirma que exportaste o relatório de marcações correto.'
            );
        }

        $rows = [];
        while (($data = fgetcsv($handle, escape: '\\', separator: ';')) !== false) {
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
