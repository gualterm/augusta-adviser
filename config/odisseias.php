<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Portal de parceiros Odisseias
    |--------------------------------------------------------------------------
    |
    | Credenciais NUNCA hardcoded no código — só aqui, lidas do .env de cada
    | container (dev/prod têm .env próprios, git-ignored). Ver App\Services\
    | OdisseiasClient para o fluxo de login/scraping.
    |
    */

    'base_url' => env('ODISSEIAS_BASE_URL', 'https://parceiros.odisseias.com'),
    'username' => env('ODISSEIAS_USERNAME'),
    'password' => env('ODISSEIAS_PASSWORD'),

    // Usados quando uma reserva Odisseias é confirmada para a agenda real —
    // a Odisseias não diz qual profissional/posto vai atender.
    'default_employee_id' => env('ODISSEIAS_DEFAULT_EMPLOYEE_ID'),
    'default_workstation_id' => env('ODISSEIAS_DEFAULT_WORKSTATION_ID'),

    // Quantos meses para a frente o sync vai buscar (a partir de hoje).
    'sync_months_ahead' => env('ODISSEIAS_SYNC_MONTHS_AHEAD', 4),

    /*
    | Nomes de produto da Odisseias que não correspondem 1:1 a `services.name`.
    | Confirmado por Gualter em 2026-07-02 contra a lista real de `services`
    | (via tinker) para o import inicial — reaproveitado aqui para o sync
    | contínuo. Adiciona novas entradas conforme aparecerem produtos novos.
    */
    'service_overrides' => [
        'Massagem com Pedras Quentes ou Velas' => "Relaxamento 60'",
        'Massagem Relaxante com Pedras ou Velas Quentes' => "Relaxamento 60'",
        'Massagem Localizada' => "Relaxamento 60'",
        'Limpeza de Pele' => 'Limpeza Pele',
        'Massagem Relaxante para Grávida' => 'Grávidas',
        // Assunção do Gualter (2026-07-06), a confirmar com a Marta: produtos
        // "Massagem em [Localidade]" da Odisseias mapeiam sempre para
        // Relaxamento 60'. Se a Odisseias começar a vender massagens de
        // duração diferente sob este mesmo nome, isto vai mapear errado.
        'Massagem em Vila do Conde' => "Relaxamento 60'",
    ],
];
