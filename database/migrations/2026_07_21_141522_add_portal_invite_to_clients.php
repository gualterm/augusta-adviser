<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('portal_invite_token', 64)->nullable()->after('consented_at');
            $table->timestamp('portal_invite_sent_at')->nullable()->after('portal_invite_token');
        });
    }
    public function down(): void {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['portal_invite_token', 'portal_invite_sent_at']);
        });
    }
};