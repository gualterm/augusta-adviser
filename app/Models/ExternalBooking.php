<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reserva vinda de um canal externo (hoje só 'odisseias') ainda não
 * confirmada — ou já confirmada, via `appointment_id` — para a agenda real
 * em `appointments`. Ver App\Services\OdisseiasClient (o conector do canal
 * Odisseias) e App\Services\ExternalBookingConfirmer (lógica partilhada de
 * confirmação, reutilizável por qualquer canal futuro).
 */
class ExternalBooking extends Model
{
    protected $casts = [
        'appointment_date' => 'date',
        'has_conflict' => 'boolean',
        'confirmed_at' => 'datetime',
        'synced_at' => 'datetime',
        'price_net' => 'decimal:2',
    ];

    protected $fillable = [
        'channel',
        'reserva_number',
        'voucher_number',
        'client_name',
        'client_phone',
        'client_email',
        'product',
        'inclui',
        'appointment_date',
        'appointment_time',
        'external_status',
        'price_net',
        'cancellation_deadline',
        'has_conflict',
        'conflict_note',
        'appointment_id',
        'confirmed_at',
        'synced_at',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function isConfirmed(): bool
    {
        return $this->appointment_id !== null;
    }
}
