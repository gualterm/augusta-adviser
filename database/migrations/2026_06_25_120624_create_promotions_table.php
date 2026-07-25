<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Esta migration é duplicada de 2026_06_23_000010 — guard para evitar conflito
        if (Schema::hasTable('promotions')) {
            return;
        }

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['daily', 'weekly']);
            $table->decimal('discount_percentage', 5, 2);
            $table->date('valid_from');
            $table->date('valid_to');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Não apagar — a tabela é gerida por 2026_06_23_000010
    }
};
