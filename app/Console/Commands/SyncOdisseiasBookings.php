<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Employee;
use App\Models\ExternalBooking;
use App\Models\OdisseiasSetting;
use App\Models\Workstation;
use App\Services\ExternalBookingConfirmer;
use App\Services\OdisseiasClient;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Sincroniza o portal de parceiros da Odisseias com a área "Marcações
 * Externas" (tabela `external_bookings`, channel='odisseias') — pedido da
 * Marta/Gualter (2026-07-03): já não chega um import manual pontual, é
 * preciso manter isto sempre atualizado (sync horário automático + botão
 * "Sincronizar agora" no Filament).
 *
 * Este comando NUNCA apaga/cancela marcações reais sozinho — quando uma
 * reserva aparece ANULADA na Odisseias mas já tinha sido confirmada para a
 * agenda, fica sinalizada como conflito para a Marta decidir à mão.
 *
 * Modo automático: controlado por `odisseias_settings.auto_confirm` (toggle
 * no Filament) — quando ligado, reservas CONFIRMADA/REALIZADA sem conflito
 * de horário são criadas sozinhas na agenda real a cada sync. Quando
 * desligado, todas ficam à espera de confirmação manual na lista.
 * --auto-confirm força o modo automático só nesta corrida (útil para testar).
 *
 * Uso:
 *   php artisan odisseias:sync                    # dry-run
 *   php artisan odisseias:sync --commit            # grava (upsert das reservas + auto-confirma se ligado)
 *   php artisan odisseias:sync --commit --auto-confirm
 */
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
        $sinalizadasAnulada = 0;
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

            $attrs = [
                'voucher_number' => $row['voucher'] ?: null,
                'client_name' => $row['cliente'],
                'appointment_date' => $start->toDateString(),
                'appointment_time' => $start->format('H:i:s'),
                'external_status' => $estado,
                'synced_at' => now(),
            ];

            if (!$existing) {
                $novas++;
                if ($commit) {
                    $details = $client->fetchDetails($row['data_id'], $row['data_type']);
                    $attrs['channel'] = self::CHANNEL;
                    $attrs['reserva_number'] = $row['reserva'];
                    $attrs['client_phone'] = $details['telefone'];
                    $attrs['client_email'] = $details['email'];
                    $attrs['product'] = $details['produto'];
                    $attrs['inclui'] = $details['inclui'];
                    $attrs['cancellation_deadline'] = $details['prazo_cancelamento'];
                    $attrs['price_net'] = $details['preco_net'];
                    $existing = ExternalBooking::create($attrs);
                }
            } else {
                $atualizadas++;
                if ($commit) {
                    $existing->update($attrs);
                }
            }

            if (!$commit || !$existing) {
                continue;
            }

            // Reserva anulada no canal depois de já estar confirmada na agenda real
            // — nunca cancela sozinho, só sinaliza para decisão manual.
            if ($estado === 'ANULADA' && $existing->appointment_id && $existing->appointment?->status !== 'completed') {
                $existing->update([
                    'has_conflict' => true,
                    'conflict_note' => 'Anulada na Odisseias mas já está na agenda — cancelar/rever manualmente.',
                ]);
                $sinalizadasAnulada++;
                continue;
            }

            if ($existing->appointment_id) {
                continue; // já confirmada, nada mais a fazer
            }

            // Esta reserva pode já ter sido importada manualmente antes de o sync
            // existir (ex.: o comando odisseias:import correu em 2026-07-02 para o
            // "gap" inicial) — essa marcação guarda o nº de reserva dentro de
            // `notes`. Se encontrarmos essa marcação, é a MESMA reserva, não um
            // conflito: ligamos em vez de sinalizar ou tentar criar outra vez.
            $jaImportada = Appointment::where('notes', 'like', "%{$existing->reserva_number}%")->first();
            if ($jaImportada) {
                $existing->update([
                    'appointment_id' => $jaImportada->id,
                    'confirmed_at' => $jaImportada->created_at,
                    'has_conflict' => false,
                    'conflict_note' => null,
                ]);
                $ligadasAJaExistentes++;
                continue;
            }

            $conflictNote = $confirmer->detectConflict($existing, $employee, $workstation);
            $existing->update([
                'has_conflict' => $conflictNote !== null,
                'conflict_note' => $conflictNote,
            ]);

            if ($conflictNote) {
                $sinalizadasConflito++;
                continue;
            }

            if ($autoConfirm && in_array($estado, ['CONFIRMADA', 'REALIZADA'], true)) {
                $result = $confirmer->confirm($existing, $employee, $workstation);
                if ($result['appointment']) {
                    $confirmadasAuto++;
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
        $this->line("  Ligadas a marcações já existentes (importadas antes do sync existir): {$ligadasAJaExistentes}");
        $this->line("  Sinalizadas com conflito de horário: {$sinalizadasConflito}");
        $this->line("  Sinalizadas: anuladas na Odisseias já na agenda: {$sinalizadasAnulada}");

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
