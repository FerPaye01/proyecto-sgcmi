<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('portuario.berth')) {
            Schema::create('portuario.berth', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->integer('capacity_teorica')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('portuario.vessel')) {
            Schema::create('portuario.vessel', function (Blueprint $table) {
                $table->id();
                $table->string('imo')->unique();
                $table->string('name');
                $table->string('flag_country')->nullable();
                $table->string('type')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('portuario.vessel_call')) {
            Schema::create('portuario.vessel_call', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vessel_id')->constrained('portuario.vessel');
                $table->string('viaje_id')->nullable();
                $table->foreignId('berth_id')->nullable()->constrained('portuario.berth');
                $table->timestamp('eta')->nullable();
                $table->timestamp('etb')->nullable();
                $table->timestamp('ata')->nullable();
                $table->timestamp('atb')->nullable();
                $table->timestamp('atd')->nullable();
                $table->string('estado_llamada')->default('PROGRAMADA');
                $table->text('motivo_demora')->nullable();
                $table->timestamps();
                $table->index(['vessel_id', 'eta']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portuario.vessel_call');
        Schema::dropIfExists('portuario.vessel');
        Schema::dropIfExists('portuario.berth');
    }
};
