<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registo de consentimento RGPD + autorização de tratamento estético.
 * Criado quando o cliente assina o formulário digital em /consentimento/.
 */
class ClientConsent extends Model
{
    protected $fillable = [
        'client_id', 'name', 'email', 'phone', 'birth_date',
        'marketing_consent', 'ip_address', 'signature_data', 'consented_at',
    ];

    protected $casts = [
        'birth_date'        => 'date',
        'marketing_consent' => 'boolean',
        'consented_at'      => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}