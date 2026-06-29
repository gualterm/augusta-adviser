<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    protected $fillable = ['day_of_week', 'open_time', 'close_time', 'is_open', 'lunch_start', 'lunch_end'];

    protected $casts = ['is_open' => 'boolean'];

    public const DAY_NAMES = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];

    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES[$this->day_of_week] ?? "Dia {$this->day_of_week}";
    }
}