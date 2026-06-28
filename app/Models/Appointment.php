<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $casts = ['appointment_date' => 'date', 'two_employees' => 'boolean'];

    protected $fillable = [
        'client_id',
        'employee_id',
        'secondary_employee_id',
        'reschedule_count',
        'workstation_id',
        'service_id',
        'appointment_date',
        'appointment_time',
        'end_time',
        'status',
        'price',
        'notes',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function secondaryEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'secondary_employee_id');
    }

    public function workstation(): BelongsTo
    {
        return $this->belongsTo(Workstation::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
