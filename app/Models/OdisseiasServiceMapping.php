<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mapeamento entre o nome de produto na Odisseias e o serviço interno.
 * Gerido pela Marta via Configurações → Mapeamentos Odisseias no painel.
 * Complementa (e tem prioridade sobre) os overrides estáticos de config/odisseias.php.
 */
class OdisseiasServiceMapping extends Model
{
    protected $fillable = ['odisseias_name', 'service_id', 'notes'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}