<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    protected $fillable = [
        'title',
        'service_id',
        'discount_percentage',
        'type',
        'valid_from',
        'valid_to',
        'active',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to'   => 'date',
        'active'     => 'boolean',
        'discount_percentage' => 'decimal:2',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** Promoções ativas hoje ou no futuro próximo */
    public function scopeActive($query)
    {
        return $query->where('active', true)
            ->where('valid_from', '<=', now()->addDays(7))
            ->where('valid_to', '>=', now()->format('Y-m-d'));
    }

    /** Promoção cobre esta data? */
    public function coversDate(string $date): bool
    {
        return $this->valid_from->format('Y-m-d') <= $date
            && $this->valid_to->format('Y-m-d') >= $date;
    }

    /** Preço com desconto */
    public function discountedPrice(float $originalPrice): float
    {
        return round($originalPrice * (1 - $this->discount_percentage / 100), 2);
    }

    /** Cria promoção diária (para amanhã) */
    public static function createDaily(array $data): self
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        return self::create(array_merge($data, [
            'type'       => 'daily',
            'valid_from' => $tomorrow,
            'valid_to'   => $tomorrow,
        ]));
    }

    /** Cria promoção semanal (para a próxima semana) */
    public static function createWeekly(array $data): self
    {
        $nextMonday = Carbon::now()->next('Monday')->format('Y-m-d');
        $nextSunday = Carbon::now()->next('Monday')->addDays(6)->format('Y-m-d');
        return self::create(array_merge($data, [
            'type'       => 'weekly',
            'valid_from' => $nextMonday,
            'valid_to'   => $nextSunday,
        ]));
    }
}
