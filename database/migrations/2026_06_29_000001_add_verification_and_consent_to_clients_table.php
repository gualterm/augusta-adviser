<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        Schema::table('clients', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->boolean('marketing_consent')->default(false)->after('active');
        });
        DB::table('clients')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }
    public function down(): void {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at','marketing_consent']);
        });
    }
};