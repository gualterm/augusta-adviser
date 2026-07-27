<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionServiceDiscount extends Model
{
    protected $fillable = ['promotion_id', 'service_id', 'discount_percent'];

    protected $casts = ['discount_percent' => 'decimal:2'];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
