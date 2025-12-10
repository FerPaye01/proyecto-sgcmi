<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ship Particulars (información detallada de la nave)
        if (!Schema::hasTable('portuario.ship_particulars')) {
            Schema::create('portuario.ship_particulars', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vessel_call_id')->constrained('portuario.vessel_call')->onDelete('cascade');
                $table->decimal('loa', 10, 2)->nullable()->comment('Length Overall (metros)');
                $table->decimal('beam', 10, 2)->nullable()->comment('Manga (metros)');
                $table->decimal('draft', 10, 2)->nullable()->comment('Calado (metros)');
                $table->decimal('grt', 12, 2)->nullable()->comment('Gross Register Tonnage');
                $table->decimal('nrt', 12, 2)->nullable()->comment('Net Register Tonnage');
                $table->decimal('dwt', 12, 2)->nullable()->comment('Deadweight Tonnage');
                $table->jsonb('ballast_report')->nullable()->comment('Reporte de lastre');
                $table->jsonb('dangerous_cargo')->nullable()->comment('Mercancías peligrosas');
                $table->timestamps();
                
                $table->index('vessel_call_id');
                $table->index('created_at');
            });
        }

        // Loading Plan (plan de carga/descarga)
        if (!Schema::hasTable('portuario.loading_plan')) {
            Schema::create('portuario.loading_plan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vessel_call_id')->constrained('portuario.vessel_call')->onDelete('cascade');
                $table->string('operation_type', 20)->comment('CARGA, DESCARGA, REESTIBA');
                $table->integer('sequence_order');
                $table->decimal('estimated_duration_h', 6, 2)->nullable();
                $table->jsonb('equipment_required')->nullable();
                $table->integer('crew_required')->nullable();
                $table->string('status', 50)->default('PLANIFICADO')->comment('PLANIFICADO, EN_EJECUCION, COMPLETADO');
                $table->timestamps();
                
                $table->index('vessel_call_id');
                $table->index('status');
                $table->index('created_at');
            });
        }

        // Resource Allocation (asignación de recursos)
        if (!Schema::hasTable('portuario.resource_allocation')) {
            Schema::create('portuario.resource_allocation', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vessel_call_id')->constrained('portuario.vessel_call')->onDelete('cascade');
                $table->string('resource_type', 50)->comment('EQUIPO, CUADRILLA, GAVIERO');
                $table->string('resource_name', 255);
                $table->integer('quantity')->default(1);
                $table->string('shift', 20)->nullable()->comment('MAÑANA, TARDE, NOCHE');
                $table->timestamp('allocated_at')->nullable();
                $table->foreignId('created_by')->constrained('admin.users');
                $table->timestamps();
                
                $table->index('vessel_call_id');
                $table->index('resource_type');
                $table->index('allocated_at');
                $table->index('created_at');
            });
        }

        // Operations Meeting (junta de operaciones)
        if (!Schema::hasTable('portuario.operations_meeting')) {
            Schema::create('portuario.operations_meeting', function (Blueprint $table) {
                $table->id();
                $table->date('meeting_date');
                $table->time('meeting_time');
                $table->jsonb('attendees')->nullable();
                $table->text('agreements')->nullable();
                $table->jsonb('next_24h_schedule')->nullable();
                $table->foreignId('created_by')->constrained('admin.users');
                $table->timestamps();
                
                $table->index('meeting_date');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portuario.operations_meeting');
        Schema::dropIfExists('portuario.resource_allocation');
        Schema::dropIfExists('portuario.loading_plan');
        Schema::dropIfExists('portuario.ship_particulars');
    }
};
