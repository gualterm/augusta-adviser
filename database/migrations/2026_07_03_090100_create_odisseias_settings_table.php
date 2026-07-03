<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de configuração de uma única linha (id=1) para o interruptor
 * "modo automático" do sync da Odisseias — pedido da Marta/Gualter
 * (2026-07-03): quando ligado, marcações sem conflito são confirmadas
 * sozinhas para a agenda a cada sincronização horária, e a lista em
 * Filament passa a poder filtrar para mostrar só os erros/conflitos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odisseias_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('auto_confirm')->default(false);
            $table->timestamps();
        });

        DB::table('odisseias_settings')->insert([
            'auto_confirm' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('odisseias_settings');
    }
};
