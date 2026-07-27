<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = [
        'title', 'service_id', 'discount_percentage', 'type',
        'valid_from', 'valid_to', 'active', 'excluded_service_ids',
    ];

    protected $casts = [
        'valid_from'           => 'date',
        'valid_to'             => 'date',
        'active'               => 'boolean',
        'excluded_service_ids' => 'array',
        'discount_percentage'  => 'decimal:2',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceDiscounts(): HasMany
    {
        return $this->hasMany(PromotionServiceDiscount::class, 'promotion_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true)
            ->where('valid_from', '<=', now()->addDays(7))
            ->where('valid_to', '>=', now()->format('Y-m-d'));
    }

    public function coversDate(string $date): bool
    {
        return $this->valid_from->format('Y-m-d') <= $date
            && $this->valid_to->format('Y-m-d') >= $date;
    }

    public function appliesToService(int $serviceId): bool
    {
        if ($this->service_id !== null) {
            return (int) $this->service_id === $serviceId;
        }
        $excluded = $this->excluded_service_ids ?? [];
        return !in_array($serviceId, array_map('intval', $excluded));
    }

    /**
     * Desconto efectivo (%) para um serviço. Null = serviço excluído.
     * Chamar $promo->load('serviceDiscounts') antes de usar em loop.
     */
    public function getEffectiveDiscount(int $serviceId): ?float
    {
        if (!$this->appliesToService($serviceId)) {
            return null;
        }
        $override = $this->serviceDiscounts->firstWhere('service_id', $serviceId);
        if ($override) {
            return (float) $override->discount_percent;
        }
        return (float) $this->discount_percentage;
    }

    public function discountedPrice(float $originalPrice): float
    {
        return round($originalPrice * (1 - $this->discount_percentage / 100), 2);
    }

    public function discountedPriceForService(float $originalPrice, int $serviceId): float
    {
        $pct = $this->getEffectiveDiscount($serviceId);
        if ($pct === null) return $originalPrice;
        return round($originalPrice * (1 - $pct / 100), 2);
    }

    public static function createDaily(array $data): self
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        return self::create(array_merge($data, ['type' => 'daily', 'valid_from' => $tomorrow, 'valid_to' => $tomorrow]));
    }

    public static function createWeekly(array $data): self
    {
        $nextMonday = Carbon::now()->next('Monday')->format('Y-m-d');
        $nextSunday = Carbon::now()->next('Monday')->addDays(6)->format('Y-m-d');
        return self::create(array_merge($data, ['type' => 'weekly', 'valid_from' => $nextMonday, 'valid_to' => $nextSunday]));
    }
}
