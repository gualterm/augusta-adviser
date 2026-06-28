<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $casts = ['two_employees' => 'boolean'];

    protected $fillable = [
        'name',
        'category',
        'workstation_type',
        'two_employees',
        'price',
        'duration_minutes',
        'description',
        'active',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function areas(): BelongsToMany { return $this->belongsToMany(Area::class, "service_area"); }
    public function employees(): BelongsToMany { return $this->belongsToMany(Employee::class, "service_area", "service_id", "employee_id"); }
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'service_equipment');
    }
}
