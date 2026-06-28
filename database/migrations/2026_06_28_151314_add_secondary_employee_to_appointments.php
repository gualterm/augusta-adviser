<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table("appointments", function (Blueprint $table) {
            $table->foreignId("secondary_employee_id")->nullable()->after("employee_id")->constrained("employees")->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table("appointments", function (Blueprint $table) {
            $table->dropForeign(["secondary_employee_id"]);
            $table->dropColumn("secondary_employee_id");
        });
    }
};