<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Área separada (staging) das marcações vindas de canais externos — hoje só
 * a Odisseias, mas desenhada de propósito para qualquer canal futuro
 * (Google Calendar, Outlook, WhatsApp, outro marketplace) reaproveitar a
 * mesma tabela/UI em vez de precisar de uma tabela nova por integração.
 * Decisão tomada em 2026-07-03 ao discutir a evolução da Augusta Adviser
 * para um "hub" — ver memória do projeto para o contexto completo. Gualter
 * confirmou explicitamente que NÃO vamos para multi-tenant/SaaS agora, isto
 * é só sobre manter os nomes prontos para mais canais, mantendo o foco na
 * Marta.
 *
 * A coluna `channel` identifica a origem ('odisseias' por agora). Esta
 * tabela é sempre alimentada por um sync automático, nunca editada à mão.
 * `appointment_id` só é preenchido quando a linha é confirmada (manual ou
 * automaticamente) e passa a existir como marcação real com
 * `appointments.source` a refletir o mesmo canal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->default('odisseias');
            $table->string('reserva_number')->unique();
            $table->string('voucher_number')->nullable();
            $table->string('client_name');
            $table->string('client_phone')->nullable();
            $table->string('client_email')->nullable();
            $table->string('product')->nullable();
            $table->text('inclui')->nullable();
            $table->date('appointment_date');
            $table->time('appointment_time');
            // Estado tal como vem do canal de origem (ex.: CONFIRMADA/REALIZADA/ANULADA
            // no caso da Odisseias), não o enum inglês de `appointments.status`.
            $table->string('external_status');
            $table->decimal('price_net', 8, 2)->nullable();
            $table->string('cancellation_deadline')->nullable();
            $table->boolean('has_conflict')->default(false);
            $table->string('conflict_note')->nullable();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_bookings');
    }
};
