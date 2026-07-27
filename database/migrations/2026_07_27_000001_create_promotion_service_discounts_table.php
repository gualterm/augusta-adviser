<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('promotion_service_discounts')) {
            Schema::create('promotion_service_discounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();
                $table->decimal('discount_percent', 5, 2);
                $table->unique(['promotion_id', 'service_id']);
                $table->timestamps();
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('promotion_service_discounts');
    }
};
