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
    ];

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
