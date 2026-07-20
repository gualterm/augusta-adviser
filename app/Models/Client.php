<?php
namespace App\Models;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\ClientConsent;
class Client extends Authenticatable implements MustVerifyEmail {
    use Notifiable, MustVerifyEmailTrait;
    protected $fillable = ['name','gender','phone','email','birth_date','nif','address','notes','active',
        'is_presencial','password_changed_at','password','email_verified_at','marketing_consent', 'localidade', 'codigo_postal', 'morada','data_consent_at'];
    protected $hidden = ['password','remember_token'];
    protected $casts = ['password'=>'hashed','password_changed_at'=>'datetime','email_verified_at'=>'datetime','marketing_consent'=>'boolean','data_consent_at'=>'datetime'];
    public function sendEmailVerificationNotification(): void { $this->notify(new \App\Notifications\ClientVerifyEmailNotification); }
    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
    public function consents()
    {
        return $this->hasMany(ClientConsent::class);
    }
}