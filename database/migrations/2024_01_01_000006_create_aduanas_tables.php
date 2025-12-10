<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('aduanas.entidad')) {
            Schema::create('aduanas.entidad', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('aduanas.tramite')) {
            Schema::create('aduanas.tramite', function (Blueprint $table) {
                $table->id();
                $table->string('tramite_ext_id')->unique();
                $table->foreignId('vessel_call_id')->nullable()->constrained('portuario.vessel_call');
                $table->string('regimen');
                $table->string('subpartida')->nullable();
                $table->string('estado')->default('INICIADO');
                $table->timestamp('fecha_inicio');
                $table->timestamp('fecha_fin')->nullable();
                $table->foreignId('entidad_id')->nullable()->constrained('aduanas.entidad');
                $table->timestamps();
                $table->index(['estado', 'fecha_inicio']);
            });
        }

        if (!Schema::hasTable('aduanas.tramite_event')) {
            Schema::create('aduanas.tramite_event', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tramite_id')->constrained('aduanas.tramite')->onDelete('cascade');
                $table->timestamp('event_ts')->useCurrent();
                $table->string('estado');
                $table->text('motivo')->nullable();
                $table->index(['tramite_id', 'event_ts']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('aduanas.tramite_event');
        Schema::dropIfExists('aduanas.tramite');
        Schema::dropIfExists('aduanas.entidad');
    }
};
