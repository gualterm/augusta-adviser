<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suporte para resolução real de conflitos (pedido de Gualter, 2026-07-03):
 * em vez de "Confirmar mesmo assim" às cegas, a Marta precisa de ver QUAL
 * marcação está em conflito e escolher — cancelar a existente e confirmar
 * esta, ou ignorar esta reserva e manter a existente. `conflict_appointment_id`
 * guarda a marcação concreta em conflito (antes só existia como texto dentro
 * de `conflict_note`). `ignored_at` marca uma reserva como resolvida por
 * decisão manual (mantida a marcação existente, esta não é confirmada) —
 * linhas ignoradas saem da vista por omissão, tal como as já confirmadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_bookings', function (Blueprint $table) {
            $table->foreignId('conflict_appointment_id')->nullable()->after('conflict_note')->constrained('appointments')->nullOnDelete();
            $table->timestamp('ignored_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('external_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conflict_appointment_id');
            $table->dropColumn('ignored_at');
        });
    }
};
