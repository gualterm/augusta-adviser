<?php
namespace App\Observers;

use App\Models\Appointment;
use App\Services\ActivityLogger;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        [$source, $actorName] = $this->resolveSourceAndActor($appointment);
        ActivityLogger::appointmentCreated($appointment, $source, $actorName);
    }

    public function updated(Appointment $appointment): void
    {
        if (!$appointment->isDirty('status')) {
            return;
        }

        [$source, $actorName] = $this->resolveSourceAndActor($appointment);

        if ($appointment->status === 'cancelled') {
            ActivityLogger::appointmentCancelled($appointment, $source, $actorName);
        } elseif ($appointment->status === 'completed') {
            // (opcional) marcar como concluída
        }
    }

    private function resolveSourceAndActor(Appointment $appointment): array
    {
        // Admin logado via Filament
        if (auth()->check() && auth()->user() instanceof \App\Models\User) {
            return ['admin', auth()->user()->name];
        }

        // Cliente logado no portal
        if (auth('client')->check()) {
            return ['portal', auth('client')->user()?->name];
        }

        // Source vem do campo da BD (ex: 'external')
        $src = $appointment->source ?? 'system';
        return [$src, null];
    }
}