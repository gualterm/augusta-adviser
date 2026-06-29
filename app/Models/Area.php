<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Area extends Model
{
    protected $fillable = ['name', 'workstation_type', 'max_concurrent'];

    protected $casts = ['max_concurrent' => 'integer'];

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_area');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_area', 'area_id', 'service_id');
    }
}