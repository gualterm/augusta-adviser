<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'response',
        'responded_at',
    ];
    protected $casts = ['responded_at' => 'datetime'];

    public const SUBJECTS = [
        'marcacoes' => 'Marcações',
        'informacoes_gerais' => 'Informações Gerais',
        'promocoes' => 'Informações sobre Promoções',
        'outros' => 'Outros',
    ];

    public const STATUSES = [
        'novo' => 'Novo',
        'lido' => 'Lido',
        'respondido' => 'Respondido',
    ];
}
