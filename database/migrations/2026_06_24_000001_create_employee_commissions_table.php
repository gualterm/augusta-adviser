<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('default_commission_percentage', 5, 2)->default(0)->after('role');
        });

        Schema::create('employee_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->decimal('percentage', 5, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_commissions');
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('default_commission_percentage');
        });
    }
};
