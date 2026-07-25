<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->after('actor_name')->index();
        });
    }
    public function down(): void {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('client_id');
        });
    }
};