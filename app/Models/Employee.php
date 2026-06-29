<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
    public function areas(): BelongsToMany { return $this->belongsToMany(Area::class, "employee_area"); }
    public function services(): BelongsToMany { return $this->belongsToMany(Service::class, "service_area", "employee_id", "service_id"); }
    public function commissions(): HasMany
    {
        return $this->hasMany(EmployeeCommission::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class)->orderBy('day_of_week');
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