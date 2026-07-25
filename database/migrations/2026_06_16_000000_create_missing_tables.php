<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('areas')) {
            Schema::create('areas', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('workstation_type', 50)->nullable();
                $table->tinyInteger('max_concurrent')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('business_hours')) {
            Schema::create('business_hours', function (Blueprint $table) {
                $table->id();
                $table->tinyInteger('day_of_week')->unique();
                $table->time('open_time')->nullable();
                $table->time('close_time')->nullable();
                $table->time('lunch_start')->nullable();
                $table->time('lunch_end')->nullable();
                $table->boolean('is_open')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('service_area')) {
            Schema::create('service_area', function (Blueprint $table) {
                $table->unsignedBigInteger('service_id');
                $table->unsignedBigInteger('area_id');
                $table->primary(['service_id', 'area_id']);
                $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
                $table->foreign('area_id')->references('id')->on('areas')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('service_equipment')) {
            Schema::create('service_equipment', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_id');
                $table->unsignedBigInteger('equipment_id');
                $table->timestamps();
                $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
                $table->foreign('equipment_id')->references('id')->on('equipment')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('employee_area')) {
            Schema::create('employee_area', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('area_id');
                $table->unsignedTinyInteger('priority')->default(1);
                $table->primary(['employee_id', 'area_id']);
                $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('area_id')->references('id')->on('areas')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('employee_schedules')) {
            Schema::create('employee_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->tinyInteger('day_of_week');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->boolean('is_working')->default(true);
                $table->timestamps();
                $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('client_consents')) {
            Schema::create('client_consents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('name');
                $table->string('email');
                $table->string('phone', 30)->nullable();
                $table->date('birth_date')->nullable();
                $table->string('nif', 9)->nullable();
                $table->string('morada')->nullable();
                $table->string('codigo_postal', 8)->nullable();
                $table->string('localidade', 100)->nullable();
                $table->boolean('marketing_consent')->default(false);
                $table->string('ip_address', 45)->nullable();
                $table->text('signature_data')->nullable();
                $table->timestamp('consented_at')->useCurrent();
                $table->timestamps();
                $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_consents');
        Schema::dropIfExists('employee_schedules');
        Schema::dropIfExists('employee_area');
        Schema::dropIfExists('service_equipment');
        Schema::dropIfExists('service_area');
        Schema::dropIfExists('business_hours');
        Schema::dropIfExists('areas');
    }
};
