<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_type');
                $table->string('source')->default('admin');
                $table->string('actor_name');
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->text('description');
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index('event_type');
                $table->index('client_id');
            });
            return;
        }
        if (!Schema::hasColumn('activity_logs', 'client_id')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('client_id')->nullable()->after('actor_name');
                $table->index('client_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('activity_logs', 'client_id')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropColumn('client_id');
            });
        }
    }
};
