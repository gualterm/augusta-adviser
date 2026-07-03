<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync horário do portal de parceiros da Odisseias — pedido explícito da
// Marta/Gualter (2026-07-03): atraso máximo aceitável de 1h, com o botão
// "Sincronizar agora" no Filament para quando ela precisar de atualizar na
// hora. --commit sempre ligado aqui (é o sync automático); o modo automático
// de CONFIRMAR sozinho na agenda é controlado à parte pelo toggle guardado em
// odisseias_settings.auto_confirm, lido dentro do próprio comando.
Schedule::command('odisseias:sync --commit')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
