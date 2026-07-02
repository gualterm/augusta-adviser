<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separa a origem de cada marcação para a Marta conseguir ver quanto vem
 * da Odisseias (preço NET, já com a comissão deles descontada) vs quanto é
 * negócio direto/orgânico — sem isto, tudo fica misturado numa só faturação
 * e não dá para perceber o peso real da Odisseias (~40% do volume dela).
 *
 * Todas as marcações existentes ficam 'Direto' por omissão (é o que já eram,
 * geridas manualmente antes desta separação existir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('source')->default('Direto')->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
