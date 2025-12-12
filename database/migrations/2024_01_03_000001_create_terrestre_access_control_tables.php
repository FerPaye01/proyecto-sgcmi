<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Digital Pass table
        if (!Schema::hasTable('terrestre.digital_pass')) {
            Schema::create('terrestre.digital_pass', function (Blueprint $table) {
                $table->id();
                $table->string('pass_code', 50)->unique();
                $table->text('qr_code')->nullable(); // Base64 encoded QR
                $table->string('pass_type', 50); // PERSONAL, VEHICULAR
                $table->string('holder_name', 255);
                $table->string('holder_dni', 20)->nullable();
                $table->foreignId('truck_id')->nullable()->constrained('terrestre.truck');
                $table->timestamp('valid_from');
                $table->timestamp('valid_until');
                $table->string('status', 50)->default('ACTIVO'); // ACTIVO, VENCIDO, REVOCADO
                $table->foreignId('created_by')->nullable()->constrained('admin.users');
                $table->timestamps();
                
                // Indexes
                $table->index('pass_code');
                $table->index('truck_id');
                $table->index(['status', 'valid_until']);
            });
        }

        // Access Permit table
        if (!Schema::hasTable('terrestre.access_permit')) {
            Schema::create('terrestre.access_permit', function (Blueprint $table) {
                $table->id();
                $table->foreignId('digital_pass_id')->constrained('terrestre.digital_pass');
                $table->string('permit_type', 50); // SALIDA, INGRESO
                $table->foreignId('cargo_item_id')->nullable()->constrained('portuario.cargo_item');
                $table->foreignId('authorized_by')->nullable()->constrained('admin.users');
                $table->timestamp('authorized_at')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->string('status', 50)->default('PENDIENTE'); // PENDIENTE, USADO, VENCIDO
                $table->timestamps();
                
                // Indexes
                $table->index('digital_pass_id');
                $table->index('cargo_item_id');
                $table->index(['status', 'authorized_at']);
            });
        }

        // Antepuerto Queue table
        if (!Schema::hasTable('terrestre.antepuerto_queue')) {
            Schema::create('terrestre.antepuerto_queue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('truck_id')->constrained('terrestre.truck');
                $table->foreignId('appointment_id')->nullable()->constrained('terrestre.appointment');
                $table->timestamp('entry_time')->nullable();
                $table->timestamp('exit_time')->nullable();
                $table->string('zone', 50); // ANTEPUERTO, ZOE
                $table->string('status', 50)->default('EN_ESPERA'); // EN_ESPERA, AUTORIZADO, RECHAZADO
                $table->timestamps();
                
                // Indexes
                $table->index('truck_id');
                $table->index('appointment_id');
                $table->index(['zone', 'status', 'entry_time']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('terrestre.antepuerto_queue');
        Schema::dropIfExists('terrestre.access_permit');
        Schema::dropIfExists('terrestre.digital_pass');
    }
};
