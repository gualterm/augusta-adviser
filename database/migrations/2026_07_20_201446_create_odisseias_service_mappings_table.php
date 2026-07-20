<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('odisseias_service_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('odisseias_name')->unique()
                  ->comment('Nome exacto do produto na Odisseias (case-sensitive)');
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('notes')->nullable()
                  ->comment('Nota opcional — porquê este mapeamento');
            $table->timestamps();
        });

        // Migrar entradas existentes de config/odisseias.php para a DB
        $overrides = config('odisseias.service_overrides', []);
        foreach ($overrides as $odisseiasName => $internalName) {
            $service = DB::table('services')->where('name', $internalName)->first();
            if ($service) {
                DB::table('odisseias_service_mappings')->insertOrIgnore([
                    'odisseias_name' => $odisseiasName,
                    'service_id'     => $service->id,
                    'notes'          => 'Migrado de config/odisseias.php',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        // Adicionar o mapeamento em falta que causou o erro inicial
        $rfBody = DB::table('services')->where('name', 'Radiofrequência Body')->first();
        if ($rfBody) {
            DB::table('odisseias_service_mappings')->insertOrIgnore([
                'odisseias_name' => 'Tratamentos de Corpo',
                'service_id'     => $rfBody->id,
                'notes'          => 'Produto genérico Odisseias — inclui radiofrequência corporal + drenagem (90 min)',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('odisseias_service_mappings');
    }
};