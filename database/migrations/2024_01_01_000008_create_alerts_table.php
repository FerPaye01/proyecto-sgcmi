<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('analytics.alerts')) {
            Schema::create('analytics.alerts', function (Blueprint $table) {
                $table->id();
                $table->string('alert_id')->unique();
                $table->string('tipo'); // CONGESTIÓN_MUELLE, ACUMULACIÓN_CAMIONES
                $table->string('nivel'); // VERDE, AMARILLO, ROJO
                $table->bigInteger('entity_id')->nullable(); // berth_id o company_id
                $table->string('entity_type')->nullable(); // 'berth' o 'company'
                $table->string('entity_name')->nullable();
                $table->decimal('valor', 12, 4);
                $table->decimal('umbral', 12, 4);
                $table->string('unidad');
                $table->text('descripción');
                $table->json('acciones_recomendadas')->nullable();
                $table->integer('citas_afectadas')->nullable();
                $table->timestamp('detected_at');
                $table->timestamp('resolved_at')->nullable();
                $table->string('estado')->default('ACTIVA'); // ACTIVA, RESUELTA, IGNORADA
                $table->timestamps();
                $table->index(['tipo', 'nivel', 'detected_at']);
                $table->index(['entity_type', 'entity_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics.alerts');
    }
};
