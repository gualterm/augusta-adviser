<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false; // só usamos created_at (sem updated_at)

    protected $fillable = [
        'event_type',
        'source',
        'actor_name',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
        'client_id',
        'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    // Labels legíveis para o Filament
    public static function eventLabels(): array
    {
        return [
            'client.created'            => 'Cliente criado',
            'appointment.created'       => 'Marcação criada',
            'appointment.cancelled'     => 'Marcação cancelada',
            'appointment.rescheduled'   => 'Marcação remarcada',
            'appointment.completed'     => 'Marcação concluída',
            'odisseias.sync'            => 'Sync Odisseias',
            'odisseias.confirmed'       => 'Odisseias → Agenda',
            'odisseias.conflict'        => 'Conflito Odisseias',
        ];
    }

    public static function sourceLabels(): array
    {
        return [
            'portal'   => 'Portal (cliente)',
            'admin'    => 'Admin (Filament)',
            'rgpd'     => 'Formulário RGPD',
            'external' => 'Reserva externa',
            'staff'    => 'Profissional',
            'system'   => 'Sistema',
        ];
    }
}