<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'name', 'email', 'password', 'role',
        'phone', 'nif',
        'morada', 'codigo_postal', 'localidade',
    ];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }
    public function employee(): HasOne { return $this->hasOne(Employee::class); }
    public function isAdmin(): bool        { return $this->role === 'admin'; }
    public function isProfissional(): bool { return $this->role === 'profissional'; }
    public function isRececionista(): bool { return $this->role === 'rececionista'; }
    public static function roles(): array
    {
        return ['admin' => 'Administrador', 'profissional' => 'Profissional', 'rececionista' => 'Rececionista'];
    }
    public function getRoleLabelAttribute(): string
    {
        return static::roles()[$this->role] ?? ucfirst($this->role);
    }
}