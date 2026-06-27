<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'morada'))        $table->string('morada', 255)->nullable()->after('nif');
            if (!Schema::hasColumn('users', 'codigo_postal')) $table->string('codigo_postal', 10)->nullable()->after('morada');
            if (!Schema::hasColumn('users', 'localidade'))    $table->string('localidade', 100)->nullable()->after('codigo_postal');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            foreach (['morada','codigo_postal','localidade'] as $col)
                if (Schema::hasColumn('users', $col)) $table->dropColumn($col);
        });
    }
};