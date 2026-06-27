<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign('appointments_employee_id_foreign');
            $table->unsignedBigInteger('employee_id')->nullable()->change();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign('appointments_employee_id_foreign');
            \Illuminate\Support\Facades\DB::statement('DELETE FROM appointments WHERE employee_id IS NULL');
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }
};