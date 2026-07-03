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
 * Odisseias → Augusta Adviser | importa as marcações que estão no portal de
 * parceiros da Odisseias mas que a Marta ainda não tinha transcrito no Zappy
 * (nem, portanto, na Augusta) — o "gap" identificado em 2026-07-02 ao cruzar
 * o histórico completo da Odisseias com o novo export do Zappy pelo código
 * de reserva.
 *
 * IMPORTANTE sobre o preço: `preco_net_eur` no CSV é o valor NET que a
 * Odisseias paga à Marta (já descontada a comissão deles), não o preço de
 * tabela pago pelo cliente. Cada marcação criada é etiquetada nas notas como
 * "[Odisseias NET]" precisamente para não se misturar sem mais com o preço
 * de marcações orgânicas quando alguém for ler relatórios de faturação.
 *
 * IMPORTANTE sobre serviços: os nomes de produto da Odisseias (ex.: "Massagem
 * com Pedras Quentes ou Velas", "Massagem Localizada") não correspondem 1:1
 * aos nomes em `services`. Preenche $serviceNameOverrides com o mapeamento
 * certo depois de veres o relatório "Serviços não encontrados" no dry-run —
 * deixei sugestões comentadas como ponto de partida, mas confirma o preço e
 * a duração reais desses serviços antes de destrancar o --commit.
 *
 * Uso:
 *   php artisan odisseias:import /caminho/odisseias_missing_from_zappy.csv                          # dry-run
 *   php artisan odisseias:import /caminho/odisseias_missing_from_zappy.csv --commit                  # grava
 *   php artisan odisseias:import /caminho/odisseias_missing_from_zappy.csv --commit --default-employee=1 --default-workstation=1
 */
class ImportOdisseiasBookings extends Command
{
    protected $signature = 'odisseias:import
        {csv : Caminho para o odisseias_missing_from_zappy.csv}
        {--commit : Sem esta opção corre em modo simulação, sem gravar nada}
        {--default-employee= : ID do profissional a usar quando não for possível determinar automaticamente}
        {--default-workstation= : ID do posto a usar quando não for possível determinar automaticamente}';

    protected $description = 'Importa clientes + marcações do CSV de gap Odisseias (reservas ausentes do Zappy)';

    /** Confirmado por Gualter em 2026-07-02 contra a lista real de `services` (via tinker). */
    private array $serviceNameOverrides = [
        'Massagem com Pedras Quentes ou Velas' => "Relaxamento 60'",
        'Massagem Relaxante com Pedras ou Velas Quentes' => "Relaxamento 60'",
        'Massagem Localizada' => "Relaxamento 60'",
        'Limpeza de Pele' => 'Limpeza Pele',
        'Massagem Relaxante para Grávida' => 'Grávidas',
    ];

    public function handle(): int
    {
        $path = $this->argument('csv');
        if (!is_file($path)) {
            $this->error("Ficheiro não encontrado: {$path}");
            return self::FAILURE;
        }

        $commit = (bool) $this->option('commit');
        $defaultEmployeeId = $this->option('default-employee');
        $defaultWorkstationId = $this->option('default-workstation');

        $this->info($commit
            ? '>>> MODO REAL — vai gravar na base de dados <<<'
            : '>>> MODO SIMULAÇÃO (dry-run) — nada é gravado. Repete com --commit quando confirmares o resumo. <<<');

        $optionalClientColumns = collect(['address', 'notes', 'is_presencial', 'active', 'data_consent_at'])
            ->filter(fn ($col) => Schema::hasColumn('clients', $col))
            ->values()->all();

        $hasNotes = Schema::hasColumn('appointments', 'notes');
        $hasEndTime = Schema::hasColumn('appointments', 'end_time');
        $hasWorkstation = Schema::hasColumn('appointments', 'workstation_id');
        $hasPrice = Schema::hasColumn('appointments', 'price');

        $rows = $this->readCsv($path);

        $clientsByEmail = Client::whereNotNull('email')->get()->keyBy(fn ($c) => mb_strtolower($c->email));
        $clientsByPhone = Client::whereNotNull('phone')->get()->keyBy(fn ($c) => $c->phone);
        $services = Service::all()->keyBy(fn ($s) => $this->normalize($s->name));
        $fallbackEmployee = $defaultEmployeeId
            ? Employee::find($defaultEmployeeId)
            : Employee::first();
        $fallbackWorkstation = $defaultWorkstationId
            ? Workstation::find($defaultWorkstationId)
            : Workstation::where('active', true)->first();

        $clientsToCreate = [];
        $appointmentsToCreate = [];
        $unresolvedService = [];
        $skippedCancelled = 0;
        $skippedDuplicate = 0;
        $newClientsCount = 0;
        $matchedExistingClients = 0;
        $scheduleConflicts = [];

        // Cache de clientes "a criar nesta corrida" para não duplicar dentro do próprio CSV
        $pendingClientKey = [];

        foreach ($rows as $row) {
            $estado = mb_strtoupper(trim($row['estado'] ?? ''));
            if (!in_array($estado, ['CONFIRMADA', 'REALIZADA'], true)) {
                $skippedCancelled++;
                continue;
            }

            $name = trim($row['nome'] ?? '');
            $email = trim($row['email'] ?? '') ?: null;
            $phone = trim($row['telefone'] ?? '') ?: null;
            $emailKey = $email ? mb_strtolower($email) : null;

            $client = null;
            if ($emailKey && $clientsByEmail->has($emailKey)) {
                $client = $clientsByEmail->get($emailKey);
            } elseif ($phone && $clientsByPhone->has($phone)) {
                $client = $clientsByPhone->get($phone);
            }

            if ($client) {
                $matchedExistingClients++;
            } else {
                // Ainda não existe — verifica se já o vamos criar nesta mesma corrida (linha repetida, ex.: Marlene Pereira 2x)
                $dedupeKey = $emailKey ?: $phone ?: $this->normalize($name);
                if (!isset($pendingClientKey[$dedupeKey])) {
                    $clientData = [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                    ];
                    if (in_array('notes', $optionalClientColumns, true)) {
                        $clientData['notes'] = '[Odisseias] importado — cliente vindo do gap Odisseias/Zappy (2026-07-02)';
                    }
                    if (in_array('is_presencial', $optionalClientColumns, true)) {
                        $clientData['is_presencial'] = false;
                    }
                    if (in_array('active', $optionalClientColumns, true)) {
                        $clientData['active'] = true;
                    }
                    // Consentimento de campanhas por omissão a false — só a Marta pode
                    // pedir consentimento explícito a estes clientes depois, não veio da Odisseias.
                    if (in_array('data_consent_at', $optionalClientColumns, true)) {
                        $clientData['data_consent_at'] = null;
                    }
                    $clientsToCreate[$dedupeKey] = $clientData;
                    $pendingClientKey[$dedupeKey] = true;
                    $newClientsCount++;
                }
            }

            $rawService = trim($row['servico'] ?? '');
            $serviceLookupName = $this->serviceNameOverrides[$rawService] ?? $rawService;
            $service = $services->get($this->normalize($serviceLookupName));
            if (!$service) {
                $unresolvedService[] = $rawService;
            }

            try {
                $start = Carbon::createFromFormat('d-m-Y H:i', trim($row['data']) . ' ' . trim($row['hora']));
            } catch (\Throwable) {
                $unresolvedService[] = "{$name} — data/hora ilegível: {$row['data']} {$row['hora']}";
                continue;
            }

            $duration = (int) ($row['duracao_min'] ?? 60) ?: 60;
            $end = $start->copy()->addMinutes($duration);
            $price = (float) str_replace(',', '.', $row['preco_net_eur'] ?? '0');
            $reserva = trim($row['reserva_odisseias'] ?? '');
            $voucher = trim($row['voucher'] ?? '');

            // Deteção de duplicado: já existe marcação com este código de reserva nas notas
            // (evita reimportar se o script já correu antes para este ficheiro).
            $duplicate = false;
            if ($hasNotes && $reserva !== '') {
                $duplicate = Appointment::where('notes', 'like', "%{$reserva}%")->exists();
            }
            if ($duplicate) {
                $skippedDuplicate++;
                continue;
            }

            // Deteção de conflito de horário: o profissional/posto por omissão pode já
            // estar ocupado nesta data/hora com outra marcação (Odisseias ou não). A
            // marcação é criada na mesma — não se perde o cliente — mas fica sinalizada
            // aqui e no relatório final para correção manual (mudar hora/profissional).
            if ($fallbackEmployee || $fallbackWorkstation) {
                $conflict = Appointment::where('appointment_date', $start->toDateString())
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($q) use ($fallbackEmployee, $fallbackWorkstation) {
                        if ($fallbackEmployee) {
                            $q->orWhere('employee_id', $fallbackEmployee->id);
                        }
                        if ($fallbackWorkstation) {
                            $q->orWhere('workstation_id', $fallbackWorkstation->id);
                        }
                    })
                    ->where('appointment_time', '<', $end->format('H:i:s'))
                    ->where('end_time', '>', $start->format('H:i:s'))
                    ->with('client')
                    ->first();

                if ($conflict) {
                    $scheduleConflicts[] = "{$name} ({$start->format('d/m/Y H:i')}) choca com "
                        . ($conflict->client->name ?? '?') . " (marcação #{$conflict->id}, mesmo profissional/posto)";
                }
            }

            $appointmentData = [
                'client_key' => $emailKey ?: $phone ?: $this->normalize($name), // resolvido depois de criar clientes
                'service_id' => $service?->id,
                'service_name_raw' => $rawService,
                'employee_id' => $fallbackEmployee?->id,
                'appointment_date' => $start->toDateString(),
                'appointment_time' => $start->format('H:i:s'),
                // Enum real de `appointments.status` é inglês: scheduled/confirmed/completed/cancelled.
                // Vem da Odisseias já confirmada (CONFIRMADA/REALIZADA), por isso nunca é 'scheduled'.
                'status' => $start->isPast() ? 'completed' : 'confirmed',
                'source' => 'Odisseias',
            ];

            if ($hasEndTime) {
                $appointmentData['end_time'] = $end->format('H:i:s');
            }
            if ($hasPrice) {
                $appointmentData['price'] = $price;
            }
            if ($hasNotes) {
                $appointmentData['notes'] = "[Odisseias NET] Reserva: {$reserva}"
                    . ($voucher !== '' ? " / Voucher: {$voucher}" : '')
                    . " / Produto Odisseias: {$rawService}"
                    . " / Preço NET: {$price} EUR";
            }
            if ($hasWorkstation) {
                $appointmentData['workstation_id'] = $fallbackWorkstation?->id;
            }

            $appointmentsToCreate[] = $appointmentData;
        }

        $this->newLine();
        $this->info('Resumo:');
        $this->line('  Clientes novos a criar: ' . count($clientsToCreate));
        $this->line('  Clientes já existentes (correspondência por email/telefone): ' . $matchedExistingClients);
        $this->line('  Marcações a criar: ' . count($appointmentsToCreate));
        $this->line('  Marcações já importadas antes (ignoradas): ' . $skippedDuplicate);
        $this->line('  Linhas anuladas/estado inválido (ignoradas): ' . $skippedCancelled);
        $this->line('  Serviços Odisseias sem correspondência em `services`: ' . count(array_unique($unresolvedService)));
        $this->line('  Conflitos de horário (profissional/posto já ocupado): ' . count($scheduleConflicts));

        if ($scheduleConflicts) {
            $this->newLine();
            $this->warn('CONFLITOS DE HORÁRIO — a marcação vai ser criada, mas precisa de correção manual (hora/profissional):');
            foreach ($scheduleConflicts as $c) {
                $this->line("  - {$c}");
            }
        }

        if ($unresolvedService) {
            $this->newLine();
            $this->warn('Serviços não encontrados (adiciona a $serviceNameOverrides e corre novamente):');
            foreach (array_unique($unresolvedService) as $s) {
                $this->line("  - {$s}");
            }
        }

        if ($fallbackEmployee) {
            $this->comment("Profissional usado por omissão em todas as marcações: #{$fallbackEmployee->id} ({$fallbackEmployee->name}). Usa --default-employee=ID para escolher outro, ou corrige à mão depois.");
        } else {
            $this->warn('Nenhum profissional disponível para atribuir por omissão — usa --default-employee=ID.');
        }

        if ($hasWorkstation) {
            if ($fallbackWorkstation) {
                $this->comment("Posto usado por omissão em todas as marcações: #{$fallbackWorkstation->id} ({$fallbackWorkstation->name}).");
            } else {
                $this->warn('Nenhum posto ativo disponível para atribuir por omissão — usa --default-workstation=ID.');
            }
        }

        if (!$commit) {
            $this->newLine();
            $this->comment('Nada foi gravado. Confirma os mapeamentos de serviço acima e corre novamente com --commit.');
            return self::SUCCESS;
        }

        if (!$appointmentsToCreate && !$clientsToCreate) {
            $this->info('Nada para importar.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Vou criar ' . count($clientsToCreate) . ' clientes e ' . count($appointmentsToCreate) . ' marcações. Confirmas?', true)) {
            $this->comment('Cancelado.');
            return self::SUCCESS;
        }

        $errors = [];
        $createdClients = 0;
        $createdAppointments = 0;

        DB::transaction(function () use (
            $clientsToCreate,
            $appointmentsToCreate,
            &$errors,
            &$createdClients,
            &$createdAppointments
        ) {
            $keyToClientId = [];

            foreach ($clientsToCreate as $key => $data) {
                try {
                    $client = Client::create($data);
                    $keyToClientId[$key] = $client->id;
                    $createdClients++;
                } catch (\Throwable $e) {
                    $errors[] = "Cliente {$data['name']}: {$e->getMessage()}";
                }
            }

            // Recarrega clientes já existentes (email/telefone) para resolver os que não foram criados agora
            $existingByEmail = Client::whereNotNull('email')->get()->keyBy(fn ($c) => mb_strtolower($c->email));
            $existingByPhone = Client::whereNotNull('phone')->get()->keyBy(fn ($c) => $c->phone);

            foreach ($appointmentsToCreate as $data) {
                $key = $data['client_key'];
                $clientId = $keyToClientId[$key]
                    ?? optional($existingByEmail->get($key))->id
                    ?? optional($existingByPhone->get($key))->id;

                if (!$clientId) {
                    $errors[] = "Marcação {$data['appointment_date']} {$data['appointment_time']} ({$data['service_name_raw']}): não foi possível resolver o cliente (key: {$key})";
                    continue;
                }

                if (!$data['service_id']) {
                    $errors[] = "Marcação {$data['appointment_date']} {$data['appointment_time']} para cliente #{$clientId}: serviço '{$data['service_name_raw']}' não mapeado, marcação NÃO criada — corrige \$serviceNameOverrides e corre outra vez só para esta linha.";
                    continue;
                }

                unset($data['client_key'], $data['service_name_raw']);
                $data['client_id'] = $clientId;

                try {
                    Appointment::create($data);
                    $createdAppointments++;
                } catch (\Throwable $e) {
                    $errors[] = json_encode($data) . " — {$e->getMessage()}";
                }
            }
        });

        $this->info("{$createdClients} clientes e {$createdAppointments} marcações importados com sucesso.");
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
