<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Linha única (id=1) que guarda o interruptor "modo automático" do sync da
 * Odisseias. Ver migration create_odisseias_settings_table para o porquê.
 */
class OdisseiasSetting extends Model
{
    protected $table = 'odisseias_settings';

    protected $casts = [
        'auto_confirm' => 'boolean',
    ];

    protected $fillable = ['auto_confirm'];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['auto_confirm' => false]);
    }
}
