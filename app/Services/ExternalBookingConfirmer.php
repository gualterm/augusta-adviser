<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ExternalBooking;
use App\Models\Service;
use App\Models\Workstation;
use Illuminate\Support\Facades\Schema;

/**
 * Lógica partilhada para transformar uma linha de `external_bookings` numa
 * marcação real em `appointments` (source = mesmo valor do `channel`) —
 * usada tanto pelo comando de sync (modo automático) como pela ação em
 * massa "Confirmar selecionadas" no Filament (modo manual), e desenhada para
 * ser reutilizada por qualquer canal futuro (não só Odisseias), para nunca
 * haver duas versões divergentes da mesma regra.
 */
class ExternalBookingConfirmer
{
    public function findOrCreateClient(ExternalBooking $booking): Client
    {
        $emailKey = $booking->client_email ? mb_strtolower($booking->client_email) : null;

        $client = null;
        if ($emailKey) {
            $client = Client::whereRaw('LOWER(email) = ?', [$emailKey])->first();
        }
        if (!$client && $booking->client_phone) {
            $client = Client::where('phone', $booking->client_phone)->first();
        }

        if ($client) {
            return $client;
        }

        $data = [
            'name' => $booking->client_name,
            'email' => $booking->client_email,
            'phone' => $booking->client_phone,
        ];
        if (Schema::hasColumn('clients', 'is_presencial')) {
            $data['is_presencial'] = false;
        }
        if (Schema::hasColumn('clients', 'active')) {
            $data['active'] = true;
        }
        if (Schema::hasColumn('clients', 'notes')) {
            $data['notes'] = "[{$booking->channel}] importado automaticamente pelo sync";
        }

        return Client::create($data);
    }

    /**
     * O produto vem em formatos INCONSISTENTES vindos da Odisseias — descoberto
     * em 2026-07-03 ao ver uma falha real (Tania Lopes): normalmente é
     * "Unidade | Nome do serviço | N Pessoas" (3 partes), mas por vezes vem só
     * "Nome do serviço | Unidade" (2 partes, ordem diferente, sem contagem de
     * pessoas). Assumir uma posição fixa (ex.: "o 2º segmento") falha nesses
     * casos. Em vez disso: tenta o texto completo primeiro, depois cada
     * segmento separado por "|", ignorando os que claramente são a unidade
     * (só existe uma, "Augusta Beauty Advisor") ou a contagem de pessoas.
     */
    public function resolveService(?string $productName): ?Service
    {
        if (!$productName) {
            return null;
        }

        $overrides = config('odisseias.service_overrides', []);
        $normalize = fn (?string $s) => preg_replace('/\s+/', ' ', rtrim(mb_strtolower(trim((string) $s)), '.'));
        $services = Service::all();

        $tryMatch = function (string $candidate) use ($overrides, $normalize, $services): ?Service {
            $lookup = $overrides[$candidate] ?? $candidate;
            return $services->first(fn ($s) => $normalize($s->name) === $normalize($lookup));
        };

        if ($match = $tryMatch(trim($productName))) {
            return $match;
        }

        $unitName = 'Augusta Beauty Advisor'; // única unidade da Marta na Odisseias por agora
        foreach (explode('|', $productName) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || $normalize($segment) === $normalize($unitName)) {
                continue;
            }
            if (preg_match('/^\d+\s*pessoa/iu', $segment)) {
                continue;
            }
            if ($match = $tryMatch($segment)) {
                return $match;
            }
        }

        return null;
    }

    /**
     * Verifica se o profissional/posto por omissão já está ocupado nesta
     * data/hora — mesma lógica usada no import inicial (ImportOdisseiasBookings).
     *
     * Devolve a marcação em conflito (não só uma nota de texto) para que a
     * UI possa oferecer decisões reais — ver a marcação, cancelá-la, ou
     * ignorar esta reserva — em vez de só "confirmar às cegas".
     */
    public function detectConflict(ExternalBooking $booking, ?Employee $employee, ?Workstation $workstation, int $durationMinutes = 60): ?Appointment
    {
        if (!$employee && !$workstation) {
            return null;
        }

        $start = $booking->appointment_date->copy()->setTimeFromTimeString($booking->appointment_time);
        $end = $start->copy()->addMinutes($durationMinutes);

        return Appointment::where('appointment_date', $start->toDateString())
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $booking->appointment_id ?? 0)
            ->where(function ($q) use ($employee, $workstation) {
                if ($employee) {
                    $q->orWhere('employee_id', $employee->id);
                }
                if ($workstation) {
                    $q->orWhere('workstation_id', $workstation->id);
                }
            })
            ->where('appointment_time', '<', $end->format('H:i:s'))
            ->where('end_time', '>', $start->format('H:i:s'))
            ->with('client')
            ->first();
    }

    public function conflictNote(ExternalBooking $booking, Appointment $conflict): string
    {
        return "Choca com marcação #{$conflict->id} de " . ($conflict->client->name ?? '?')
            . ' (' . $conflict->appointment_date->format('d/m/Y') . ' ' . substr($conflict->appointment_time, 0, 5) . '), mesmo profissional/posto.';
    }

    /**
     * Cria a marcação real para uma linha ainda não confirmada. Não faz
     * validação de conflito aqui (deve ser chamado depois de detectConflict()
     * confirmar que está tudo livre, ou explicitamente por decisão manual da
     * Marta mesmo havendo conflito sinalizado).
     *
     * @return array{appointment: ?Appointment, error: ?string}
     */
    public function confirm(ExternalBooking $booking, ?Employee $employee = null, ?Workstation $workstation = null): array
    {
        if ($booking->appointment_id) {
            return ['appointment' => $booking->appointment, 'error' => null];
        }

        $employee ??= config('odisseias.default_employee_id') ? Employee::find(config('odisseias.default_employee_id')) : Employee::first();
        $workstation ??= config('odisseias.default_workstation_id') ? Workstation::find(config('odisseias.default_workstation_id')) : Workstation::where('active', true)->first();

        $service = $this->resolveService($booking->product);
        if (!$service) {
            return ['appointment' => null, 'error' => "Serviço '{$booking->product}' sem correspondência em services — mapeia em config/odisseias.php (service_overrides) antes de confirmar."];
        }

        $client = $this->findOrCreateClient($booking);
        $start = $booking->appointment_date->copy()->setTimeFromTimeString($booking->appointment_time);

        $data = [
            'client_id' => $client->id,
            'employee_id' => $employee?->id,
            'workstation_id' => $workstation?->id,
            'service_id' => $service->id,
            'appointment_date' => $start->toDateString(),
            'appointment_time' => $start->format('H:i:s'),
            'end_time' => $start->copy()->addMinutes(60)->format('H:i:s'),
            'status' => $booking->external_status === 'REALIZADA' ? 'completed' : 'confirmed',
            'price' => $booking->price_net,
            'notes' => "[{$booking->channel} NET] Reserva: {$booking->reserva_number}"
                . ($booking->voucher_number ? " / Voucher: {$booking->voucher_number}" : '')
                . " / Produto: {$booking->product}"
                . " / Preço NET: {$booking->price_net} EUR",
            'source' => ucfirst($booking->channel),
        ];

        try {
            $appointment = Appointment::create($data);
        } catch (\Throwable $e) {
            return ['appointment' => null, 'error' => $e->getMessage()];
        }

        $booking->update([
            'appointment_id' => $appointment->id,
            'confirmed_at' => now(),
        ]);

        return ['appointment' => $appointment, 'error' => null];
    }
}
