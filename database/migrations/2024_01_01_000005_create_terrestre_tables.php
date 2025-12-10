<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('terrestre.company')) {
            Schema::create('terrestre.company', function (Blueprint $table) {
                $table->id();
                $table->string('ruc')->unique();
                $table->string('name');
                $table->string('tipo')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('terrestre.truck')) {
            Schema::create('terrestre.truck', function (Blueprint $table) {
                $table->id();
                $table->string('placa')->unique();
                $table->foreignId('company_id')->constrained('terrestre.company');
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('terrestre.gate')) {
            Schema::create('terrestre.gate', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('terrestre.appointment')) {
            Schema::create('terrestre.appointment', function (Blueprint $table) {
                $table->id();
                $table->foreignId('truck_id')->constrained('terrestre.truck');
                $table->foreignId('company_id')->constrained('terrestre.company');
                $table->foreignId('vessel_call_id')->nullable()->constrained('portuario.vessel_call');
                $table->timestamp('hora_programada');
                $table->timestamp('hora_llegada')->nullable();
                $table->string('estado')->default('PROGRAMADA');
                $table->text('motivo')->nullable();
                $table->timestamps();
                $table->index(['hora_programada', 'estado']);
            });
        }

        if (!Schema::hasTable('terrestre.gate_event')) {
            Schema::create('terrestre.gate_event', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gate_id')->constrained('terrestre.gate');
                $table->foreignId('truck_id')->constrained('terrestre.truck');
                $table->string('action');
                $table->timestamp('event_ts')->useCurrent();
                $table->foreignId('cita_id')->nullable()->constrained('terrestre.appointment');
                $table->json('extra')->nullable();
                $table->index(['event_ts', 'gate_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('terrestre.gate_event');
        Schema::dropIfExists('terrestre.appointment');
        Schema::dropIfExists('terrestre.gate');
        Schema::dropIfExists('terrestre.truck');
        Schema::dropIfExists('terrestre.company');
    }
};
