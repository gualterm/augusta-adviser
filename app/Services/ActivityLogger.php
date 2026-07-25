<?php
namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Registo de actividade da Augusta Adviser.
 * Usar os métodos helper em vez de log() directamente.
 */
class ActivityLogger
{
    // ── Clientes ─────────────────────────────────────────────────────────────

    public static function clientCreated(Client $client, string $source, ?string $actorName = null): void
    {
        self::log(
            eventType:   'client.created',
            source:      $source,
            actorName:   $actorName,
            subjectType: 'client',
            subjectId:   $client->id,
            description: "Cliente \"{$client->name}\" registado",
            metadata:    [
                'email'           => $client->email,
                'referral_source' => $client->referral_source ?? null,
            ]
        );
    }

    // ── Marcações ─────────────────────────────────────────────────────────────

    public static function appointmentCreated(Appointment $appointment, string $source, ?string $actorName = null): void
    {
        $appointment->loadMissing(['service', 'employee']);
        $service  = $appointment->service?->name  ?? '—';
        $employee = $appointment->employee?->name ?? '—';
        $date     = $appointment->appointment_date?->format('d/m/Y') ?? '—';
        $time     = $appointment->appointment_time ? substr($appointment->appointment_time, 0, 5) : '—';

        self::log(
            eventType:   'appointment.created',
            source:      $source,
            actorName:   $actorName,
            subjectType: 'appointment',
            subjectId:   $appointment->id,
            description: "{$service} — {$date} às {$time} com {$employee}",
            metadata:    [
                'service_id'  => $appointment->service_id,
                'service'     => $service,
                'employee'    => $employee,
                'date'        => $appointment->appointment_date?->format('Y-m-d'),
                'time'        => $time,
                'price'       => $appointment->price,
                'client_id'   => $appointment->client_id,
            ]
        );
    }

    public static function appointmentCancelled(Appointment $appointment, string $source, ?string $actorName = null, ?string $reason = null): void
    {
        $appointment->loadMissing(['service', 'employee']);
        $service  = $appointment->service?->name  ?? '—';
        $date     = $appointment->appointment_date?->format('d/m/Y') ?? '—';

        self::log(
            eventType:   'appointment.cancelled',
            source:      $source,
            actorName:   $actorName,
            subjectType: 'appointment',
            subjectId:   $appointment->id,
            description: "{$service} — {$date} cancelada" . ($reason ? " ({$reason})" : ''),
            metadata:    [
                'service'    => $service,
                'date'       => $appointment->appointment_date?->format('Y-m-d'),
                'reason'     => $reason,
                'client_id'  => $appointment->client_id,
            ]
        );
    }

    public static function appointmentRescheduled(Appointment $appointment, string $oldDate, string $oldTime, string $source, ?string $actorName = null): void
    {
        $appointment->loadMissing(['service', 'employee']);
        $service  = $appointment->service?->name  ?? '—';
        $newDate  = $appointment->appointment_date?->format('d/m/Y') ?? '—';
        $newTime  = $appointment->appointment_time ? substr($appointment->appointment_time, 0, 5) : '—';

        self::log(
            eventType:   'appointment.rescheduled',
            source:      $source,
            actorName:   $actorName,
            subjectType: 'appointment',
            subjectId:   $appointment->id,
            description: "{$service} remarcada de {$oldDate} para {$newDate} às {$newTime}",
            metadata:    [
                'service'   => $service,
                'old_date'  => $oldDate,
                'old_time'  => $oldTime,
                'new_date'  => $appointment->appointment_date?->format('Y-m-d'),
                'new_time'  => $newTime,
                'client_id' => $appointment->client_id,
            ]
        );
    }

    // ── Core ──────────────────────────────────────────────────────────────────

    public static function log(
        string  $eventType,
        string  $source,
        string  $description,
        ?string $actorName   = null,
        ?string $subjectType = null,
        ?int    $subjectId   = null,
        array   $metadata    = []
    ): void {
        try {
            DB::table('activity_logs')->insert([
                'event_type'   => $eventType,
                'source'       => $source,
                'actor_name'   => $actorName,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'description'  => $description,
                'metadata'     => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            // O log nunca deve quebrar a aplicação
            Log::warning('[ActivityLogger] Falhou: ' . $e->getMessage());
        }
    }
}