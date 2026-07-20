<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->text('signature_data')->nullable(); // base64 PNG
            $table->timestamp('consented_at');
            $table->timestamps();
        });

        // Adicionar campo consented_at à tabela clients (se ainda não existir)
        if (!Schema::hasColumn('clients', 'consented_at')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->timestamp('consented_at')->nullable()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_consents');
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'consented_at')) {
                $table->dropColumn('consented_at');
            }
        });
    }
};