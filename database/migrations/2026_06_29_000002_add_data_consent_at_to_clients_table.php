<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('clients', function (Blueprint $table) {
            $table->timestamp('data_consent_at')->nullable()->after('marketing_consent');
        });
    }
    public function down(): void {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('data_consent_at');
        });
    }
};