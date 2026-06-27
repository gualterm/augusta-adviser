<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {

            $table->decimal('market_min', 10, 2)
                ->nullable()
                ->after('price');

            $table->decimal('market_avg', 10, 2)
                ->nullable()
                ->after('market_min');

            $table->decimal('market_max', 10, 2)
                ->nullable()
                ->after('market_avg');

            $table->decimal('recommended_price', 10, 2)
                ->nullable()
                ->after('market_max');

            $table->string('pricing_status')
                ->nullable()
                ->after('recommended_price');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {

            $table->dropColumn([
                'market_min',
                'market_avg',
                'market_max',
                'recommended_price',
                'pricing_status',
            ]);
        });
    }
};
