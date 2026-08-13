<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Employee;
use App\Models\ExternalBooking;
use App\Models\OdisseiasSetting;
use App\Models\Workstation;
use App\Services\ExternalBookingConfirmer;
use App\Services\OdisseiasClient;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncOdisseiasBookings extends Command
{
    protected $signature = 'odisseias:sync
        {--commit : Sem esta opção corre em modo simulação, sem gravar nada}
        {--auto-confirm : Força modo automático nesta corrida, independentemente do toggle guardado}
        {--default-employee= : ID do profissional a usar quando uma reserva for confirmada}
        {--default-workstation= : ID do posto a usar quando uma reserva for confirmada}';

    protected $description = 'Sincroniza reservas do portal de parceiros da Odisseias para a área "Marcações Externas"';

    private const CHANNEL = 'odisseias';

    public function handle(OdisseiasClient $client, ExternalBookingConfirmer $confirmer): int
    {
        $commit = (bool) $this->option('commit');

        $this->info($commit
            ? '>>> MODO REAL — vai gravar na base de dados <<<'
            : '>>> MODO SIMULAÇÃO (dry-run) — nada é gravado. Repete com --commit para aplicar. <<<');

        try {
            $this->line('A autenticar no portal da Odisseias...');
            if (!$client->login()) {
                $this->error('Login falhou — confirma ODISSEIAS_USERNAME/ODISSEIAS_PASSWORD no .env.');
                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error('Erro ao ligar à Odisseias: ' . $e->getMessage());
            return self::FAILURE;
        }

        $from = now();
        $to = now()->addMonths((int) config('odisseias.sync_months_ahead', 4));

        $this->line("A obter marcações de {$from->format('d-m-Y')} a {$to->format('d-m-Y')}...");
        $rows = $client->fetchBookings($from, $to);
        $this->line('Reservas encontradas no portal: ' . count($rows));

        $autoConfirm = $this->option('auto-confirm') || OdisseiasSetting::current()->auto_confirm;
        $this->line('Modo automático nesta corrida: ' . ($autoConfirm ? 'LIGADO' : 'desligado'));

        $employee = $this->option('default-employee')
            ? Employee::find($this->option('default-employee'))
            : (config('odisseias.default_employee_id') ? Employee::find(config('odisseias.default_employee_id')) : Employee::first());
        $workstation = $this->option('default-workstation')
            ? Workstation::find($this->option('default-workstation'))
            : (config('odisseias.default_workstation_id') ? Workstation::find(config('odisseias.default_workstation_id')) : Workstation::where('active', true)->first());

        $novas = 0;
        $atualizadas = 0;
        $confirmadasAuto = 0;
        $ligadasAJaExistentes = 0;
        $sinalizadasConflito = 0;
        $canceladasOdisseias = 0;
        $erros = [];

        foreach ($rows as $row) {
            try {
                $start = Carbon::createFromFormat('d-m-Y H:i', trim($row['data']) . ' ' . trim($row['hora']));
            } catch (\Throwable) {
                $erros[] = "{$row['cliente']} — data/hora ilegível: {$row['data']} {$row['hora']}";
                continue;
            }

            $estado = mb_strtoupper(trim($row['estado']));
            $existing = ExternalBooking::where('channel', self::CHANNEL)
                ->where('reserva_number', $row['reserva'])
                ->first();

            $comparableAttrs = [
                'voucher_number' => $row['voucher'] ?: null,
                'client_name' => $row['cliente'],
                'appointment_date' => $start->toDateString(),
                'appointment_time' => $start->format('H:i:s'),
                'external_status' => $estado,
            ];

            if (!$existing) {
                $novas++;
                if ($commit) {
                    $details = $client->fetchDetails($row['data_id'], $row['data_type']);
                    $attrs = $comparableAttrs + [
                        'channel' => self::CHANNEL,
                        'reserva_number' => $row['reserva'],
                        'client_phone' => $details['telefone'],
                        'client_email' => $details['email'],
                        'product' => $details['produto'],
                        'inclui' => $details['inclui'],
                        'cancellation_deadline' => $details['prazo_cancelamento'],
                        'price_net' => $details['preco_net'],
                        'synced_at' => now(),
                    ];
                    $existing = ExternalBooking::create($attrs);
                }
            } else {
                $mudou = false;
                foreach ($comparableAttrs as $key => $value) {
                    $current = $key === 'appointment_date'
                        ? $existing->appointment_date?->toDateString()
                        : $existing->{$key};
                    if ((string) $current !== (string) $value) {
                        $mudou = true;
                        break;
                    }
                }
                if ($mudou) {
                    $atualizadas++;
                    if ($commit) {
                        $existing->update($comparableAttrs + ['synced_at' => now()]);
                    }
                }
            }

            if (!$commit || !$existing) {
                continue;
            }

            if ($existing->ignored_at) {
                continue;
            }

                        if ($estado === 'ANULADA' && $existing->appointment_id && $existing->appointment?->status !== 'completed') {
                $canceladasOdisseias++;
                if ($commit) {
                    $appt = $existing->appointment;
                    if ($appt && !in_array($appt->status, ['cancelled', 'completed'])) {
                        $appt->update(['status' => 'cancelled']);
                        $existing->update([
                            'has_conflict'            => false,
                            'conflict_note'           => null,
                            'conflict_appointment_id' => null,
                        ]);
                        ActivityLog::create([
                            'event_type'   => 'odisseias.cancelled',
                            'source'       => 'external',
                            'actor_name'   => 'Sync Odisseias',
                            'subject_type' => 'appointment',
                            'subject_id'   => $appt->id,
                            'description'  => 'Odisseias \u2192 Cancelada: ' . $existing->client_name . ' \u2014 ' . $existing->product . ' em ' .
                                              Carbon::parse($existing->appointment_date)->format('d/m/Y') . ' ' .
                                              substr($existing->appointment_time, 0, 5) .
                                              ' (reserva ' . $existing->reserva_number . ')',
                            'created_at'   => now(),
                        ]);
                    }
                }
                continue;
            }

            if ($existing->appointment_id) {
                continue;
            }

            if ($estado === 'ANULADA') {
                if ($existing->has_conflict) {
                    $existing->update(['has_conflict' => false, 'conflict_note' => null, 'conflict_appointment_id' => null]);
                }
                continue;
            }

            $jaImportada = Appointment::where('notes', 'like', "%{$existing->reserva_number}%")->first();
            if ($jaImportada) {
                $existing->update([
                    'appointment_id' => $jaImportada->id,
                    'confirmed_at' => $jaImportada->created_at,
                    'has_conflict' => false,
                    'conflict_note' => null,
                    'conflict_appointment_id' => null,
                ]);
                $ligadasAJaExistentes++;
                continue;
            }

            $conflict = $confirmer->detectConflict($existing, $employee, $workstation);

            if ($conflict && $existing->voucher_number) {
                $conflictEB = ExternalBooking::where('appointment_id', $conflict->id)
                    ->where('channel', self::CHANNEL)
                    ->first();
                if ($conflictEB && $conflictEB->voucher_number === $existing->voucher_number) {
                    $conflict = null;
                }
            }

            $existing->update([
                'has_conflict' => $conflict !== null,
                'conflict_note' => $conflict ? $confirmer->conflictNote($existing, $conflict) : null,
                'conflict_appointment_id' => $conflict?->id,
            ]);

            if ($conflict) {
                $sinalizadasConflito++;

                // Log conflito
                ActivityLog::create([
                    'event_type'   => 'odisseias.conflict',
                    'source'       => 'system',
                    'actor_name'   => 'Sync Odisseias',
                    'subject_type' => 'external_booking',
                    'subject_id'   => $existing->id,
                    'description'  => "Conflito: {$existing->client_name} — {$existing->product} em " .
                                      Carbon::parse($existing->appointment_date)->format('d/m/Y') . ' ' .
                                      substr($existing->appointment_time, 0, 5),
                    'created_at'   => now(),
                ]);

                continue;
            }

            if ($autoConfirm && in_array($estado, ['CONFIRMADA', 'REALIZADA'], true)) {
                $result = $confirmer->confirm($existing, $employee, $workstation);
                if ($result['appointment']) {
                    $confirmadasAuto++;
                    $appt = $result['appointment'];

                    // Log confirmação automática
                    ActivityLog::create([
                        'event_type'   => 'odisseias.confirmed',
                        'source'       => 'external',
                        'actor_name'   => 'Sync Odisseias',
                        'subject_type' => 'appointment',
                        'subject_id'   => $appt->id,
                        'description'  => "Odisseias → Agenda: {$existing->client_name} — {$existing->product} em " .
                                          Carbon::parse($existing->appointment_date)->format('d/m/Y') . ' ' .
                                          substr($existing->appointment_time, 0, 5) .
                                          " (reserva {$existing->reserva_number})",
                        'created_at'   => now(),
                    ]);
                } else {
                    $erros[] = "Reserva {$existing->reserva_number} ({$existing->client_name}): {$result['error']}";
                }
            }
        }

        $this->newLine();
        $this->info('Resumo:');
        $this->line("  Reservas novas: {$novas}");
        $this->line("  Reservas atualizadas: {$atualizadas}");
        $this->line("  Confirmadas automaticamente para a agenda: {$confirmadasAuto}");
        $this->line("  Ligadas a marcações já existentes: {$ligadasAJaExistentes}");
        $this->line("  Sinalizadas com conflito de horário: {$sinalizadasConflito}");
        $this->line("  Canceladas automaticamente (anuladas na Odisseias): {$canceladasOdisseias}");

        // Log resumo do sync (sempre que corre em modo --commit)
        if ($commit) {
            $temMovimento = $novas + $atualizadas + $confirmadasAuto + $sinalizadasConflito + $canceladasOdisseias > 0;
            ActivityLog::create([
                'event_type'   => 'odisseias.sync',
                'source'       => 'system',
                'actor_name'   => 'Sync Odisseias',
                'subject_type' => 'system',
                'subject_id'   => 0,
                'description'  => $temMovimento
                    ? "Sync: {$novas} novas · {$atualizadas} atualizadas · {$confirmadasAuto} → agenda · {$sinalizadasConflito} conflitos"
                    : 'Sync: sem alterações',
                'created_at'   => now(),
            ]);
        }

        if ($erros) {
            $this->newLine();
            $this->warn('Erros:');
            foreach ($erros as $e) {
                $this->line("  - {$e}");
            }
        }

        if (!$commit) {
            $this->newLine();
            $this->comment('Nada foi gravado. Corre novamente com --commit para aplicar.');
        }

        return self::SUCCESS;
    }
}
