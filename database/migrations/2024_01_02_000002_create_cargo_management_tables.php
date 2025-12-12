<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cargo Manifest (manifiesto de carga)
        if (!Schema::hasTable('portuario.cargo_manifest')) {
            Schema::create('portuario.cargo_manifest', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vessel_call_id')->constrained('portuario.vessel_call')->onDelete('cascade');
                $table->string('manifest_number', 50)->unique();
                $table->date('manifest_date');
                $table->integer('total_items')->default(0);
                $table->decimal('total_weight_kg', 15, 2)->default(0);
                $table->string('document_url', 500)->nullable();
                $table->timestamps();
                
                $table->index('vessel_call_id');
                $table->index('manifest_date');
            });
        }

        // Yard Location (ubicación en patio)
        if (!Schema::hasTable('portuario.yard_location')) {
            Schema::create('portuario.yard_location', function (Blueprint $table) {
                $table->id();
                $table->string('zone_code', 20);
                $table->string('block_code', 20)->nullable();
                $table->string('row_code', 10)->nullable();
                $table->integer('tier')->nullable();
                $table->string('location_type', 50);
                $table->integer('capacity_teu')->nullable();
                $table->boolean('occupied')->default(false);
                $table->boolean('active')->default(true);
                $table->timestamps();
                
                $table->index(['zone_code', 'block_code', 'row_code', 'tier']);
                $table->index(['location_type', 'occupied']);
            });
        }

        // Cargo Item (ítem de carga)
        if (!Schema::hasTable('portuario.cargo_item')) {
            Schema::create('portuario.cargo_item', function (Blueprint $table) {
                $table->id();
                $table->foreignId('manifest_id')->constrained('portuario.cargo_manifest')->onDelete('cascade');
                $table->string('item_number', 50);
                $table->text('description')->nullable();
                $table->string('cargo_type', 50);
                $table->string('container_number', 20)->nullable();
                $table->string('seal_number', 50)->nullable();
                $table->decimal('weight_kg', 12, 2)->nullable();
                $table->decimal('volume_m3', 12, 2)->nullable();
                $table->string('bl_number', 50)->nullable();
                $table->string('consignee', 255)->nullable();
                $table->foreignId('yard_location_id')->nullable()->constrained('portuario.yard_location')->onDelete('set null');
                $table->string('status', 50)->default('EN_TRANSITO');
                $table->timestamps();
                
                $table->index('manifest_id');
                $table->index('yard_location_id');
                $table->index('container_number');
                $table->index('status');
            });
        }

        // Tarja Note (nota de tarja)
        if (!Schema::hasTable('portuario.tarja_note')) {
            Schema::create('portuario.tarja_note', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cargo_item_id')->constrained('portuario.cargo_item')->onDelete('cascade');
                $table->string('tarja_number', 50)->unique();
                $table->timestamp('tarja_date');
                $table->string('inspector_name', 255);
                $table->text('observations')->nullable();
                $table->string('condition', 50);
                $table->json('photos')->nullable();
                $table->foreignId('created_by')->constrained('admin.users')->onDelete('restrict');
                $table->timestamps();
                
                $table->index('cargo_item_id');
                $table->index('tarja_date');
            });
        }

        // Weigh Ticket (ticket de balanza)
        if (!Schema::hasTable('portuario.weigh_ticket')) {
            Schema::create('portuario.weigh_ticket', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cargo_item_id')->constrained('portuario.cargo_item')->onDelete('cascade');
                $table->string('ticket_number', 50)->unique();
                $table->timestamp('weigh_date');
                $table->decimal('gross_weight_kg', 12, 2);
                $table->decimal('tare_weight_kg', 12, 2);
                $table->decimal('net_weight_kg', 12, 2);
                $table->string('scale_id', 50)->nullable();
                $table->string('operator_name', 255)->nullable();
                $table->timestamps();
                
                $table->index('cargo_item_id');
                $table->index('weigh_date');
                
                // Add constraint: net_weight_kg = gross_weight_kg - tare_weight_kg
                // This will be enforced at the application level via model mutator
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portuario.weigh_ticket');
        Schema::dropIfExists('portuario.tarja_note');
        Schema::dropIfExists('portuario.cargo_item');
        Schema::dropIfExists('portuario.yard_location');
        Schema::dropIfExists('portuario.cargo_manifest');
    }
};
