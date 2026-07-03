<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Importação manual do export Zappy
    |--------------------------------------------------------------------------
    |
    | O Zappy não tem API nem exportação automática acessível (login com 2FA
    | do qual não temos a password) — por isso este é um upload manual feito
    | pela própria Marta no backoffice (Operações → Importar Zappy), não uma
    | sincronização contínua como a da Odisseias.
    |
    | O ficheiro esperado é o export "reports" do Zappy: CSV com ';' como
    | delimitador, UTF-8 com BOM, colunas:
    | date;status;client_name;item_name;online;category;service_provider;
    | price_base;discount;price_final;payment_date;cupao_id;updated_on;notes;
    | Obs.3;cancel_reason
    |
    */

    // Posto (workstation) a usar quando o profissional não tiver um
    // preferred_workstation_id definido. Se vazio, usa o primeiro posto ativo.
    'default_workstation_id' => env('ZAPPY_DEFAULT_WORKSTATION_ID'),

    // Duração por omissão (minutos) quando o serviço não tiver duration_minutes.
    'default_duration_minutes' => 30,

    /*
    | Nomes de serviço do Zappy que não correspondem 1:1 a `services.name`.
    | Baseado no export real de 2026-07-02 — adiciona novas entradas conforme
    | aparecerem serviços novos ou nomes diferentes.
    */
    'service_overrides' => [
        "Massagem de Relaxamento 60'" => "Relaxamento 60'",
        "Massagem de Relaxamento a 2" => "Relaxamento a 4 Mãos",
        'Limpeza de Pele' => 'Limpeza Pele',
        'Massagem Terapêutica 30\'' => "Relaxamento 30'",
    ],

    /*
    | Nomes de profissional do Zappy que não correspondem 1:1 a `employees.name`
    | (ex.: espaços a mais, "." a mais). Ajusta conforme necessário.
    */
    'provider_overrides' => [
        'Marta  Macedo' => 'Marta Macedo',
    ],
];
