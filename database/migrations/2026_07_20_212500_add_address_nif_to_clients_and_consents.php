<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Campos de morada + NIF na tabela clients
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'nif')) {
                $table->string('nif', 9)->nullable()->after('marketing_consent');
            }
            if (!Schema::hasColumn('clients', 'morada')) {
                $table->string('morada')->nullable()->after('nif');
            }
            if (!Schema::hasColumn('clients', 'codigo_postal')) {
                $table->string('codigo_postal', 8)->nullable()->after('morada');
            }
            if (!Schema::hasColumn('clients', 'localidade')) {
                $table->string('localidade', 100)->nullable()->after('codigo_postal');
            }
        });

        // Mesmos campos em client_consents (guarda o que foi preenchido no formulário)
        Schema::table('client_consents', function (Blueprint $table) {
            if (!Schema::hasColumn('client_consents', 'nif')) {
                $table->string('nif', 9)->nullable()->after('birth_date');
            }
            if (!Schema::hasColumn('client_consents', 'morada')) {
                $table->string('morada')->nullable()->after('nif');
            }
            if (!Schema::hasColumn('client_consents', 'codigo_postal')) {
                $table->string('codigo_postal', 8)->nullable()->after('morada');
            }
            if (!Schema::hasColumn('client_consents', 'localidade')) {
                $table->string('localidade', 100)->nullable()->after('codigo_postal');
            }
        });
    }

    public function down(): void
    {
        foreach (['nif', 'morada', 'codigo_postal', 'localidade'] as $col) {
            if (Schema::hasColumn('clients', $col)) {
                Schema::table('clients', fn ($t) => $t->dropColumn($col));
            }
            if (Schema::hasColumn('client_consents', $col)) {
                Schema::table('client_consents', fn ($t) => $t->dropColumn($col));
            }
        }
    }
};