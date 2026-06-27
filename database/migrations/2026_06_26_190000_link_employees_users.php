<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        // Garantir colunas nif/phone se a migration anterior não correu
        if (!Schema::hasColumn('employees', 'nif')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('nif', 20)->nullable()->unique()->after('email');
            });
        }
        if (!Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 50)->nullable()->unique()->after('email');
            });
        }
        if (!Schema::hasColumn('users', 'nif')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nif', 20)->nullable()->unique()->after('phone');
            });
        }
        // Link: employees.user_id -> users.id
        if (!Schema::hasColumn('employees', 'user_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->unique()
                      ->after('id')
                      ->constrained('users')->nullOnDelete();
            });
        }
    }
    public function down(): void {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('employees', 'nif')) $table->dropColumn('nif');
        });
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone')) $table->dropColumn('phone');
            if (Schema::hasColumn('users', 'nif')) $table->dropColumn('nif');
        });
    }
};