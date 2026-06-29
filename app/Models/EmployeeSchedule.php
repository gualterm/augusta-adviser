<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSchedule extends Model
{
    protected $fillable = ['employee_id', 'day_of_week', 'start_time', 'end_time', 'is_working'];

    protected $casts = ['is_working' => 'boolean'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}