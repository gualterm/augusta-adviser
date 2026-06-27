<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        // NIF nos profissionais
        if (!Schema::hasColumn('employees', 'nif')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('nif', 20)->nullable()->unique()->after('email');
            });
        }
        // Telefone e NIF nos utilizadores
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 50)->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'nif')) {
                $table->string('nif', 20)->nullable()->unique()->after('phone');
            }
        });
    }
    public function down(): void {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'nif')) $table->dropColumn('nif');
        });
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone')) $table->dropColumn('phone');
            if (Schema::hasColumn('users', 'nif')) $table->dropColumn('nif');
        });
    }
};