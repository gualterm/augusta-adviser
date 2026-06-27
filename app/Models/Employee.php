<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'role',
        'phone',
        'email',
        'nif',
        'default_commission_percentage',
        'active',
    ];
    // ─── Relationships ───────────────────────────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
    public function commissions(): HasMany
    {
        return $this->hasMany(EmployeeCommission::class);
    }
    // ─── Helpers ─────────────────────────────────────────────────────────────
    public function commissionPercentageFor(?string $category): float
    {
        if ($category) {
            $override = $this->commissions->firstWhere('category', $category);
            if ($override) {
                return (float) $override->percentage;
            }
        }
        return (float) ($this->default_commission_percentage ?? 0);
    }
}