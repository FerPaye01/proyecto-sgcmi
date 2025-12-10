<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('analytics.actor')) {
            Schema::create('analytics.actor', function (Blueprint $table) {
                $table->id();
                $table->string('ref_table');
                $table->bigInteger('ref_id');
                $table->string('tipo');
                $table->string('name');
                $table->timestamps();
                $table->unique(['ref_table', 'ref_id']);
            });
        }

        if (!Schema::hasTable('analytics.kpi_definition')) {
            Schema::create('analytics.kpi_definition', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('analytics.kpi_value')) {
            Schema::create('analytics.kpi_value', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kpi_id')->constrained('analytics.kpi_definition');
                $table->date('periodo');
                $table->decimal('valor', 12, 4);
                $table->decimal('meta', 12, 4)->nullable();
                $table->string('fuente')->nullable();
                $table->json('extra')->nullable();
                $table->timestamps();
                $table->index(['kpi_id', 'periodo']);
            });
        }

        if (!Schema::hasTable('analytics.sla_definition')) {
            Schema::create('analytics.sla_definition', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->decimal('umbral', 12, 4);
                $table->string('comparador')->default('<=');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('analytics.sla_measure')) {
            Schema::create('analytics.sla_measure', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sla_id')->constrained('analytics.sla_definition');
                $table->foreignId('actor_id')->nullable()->constrained('analytics.actor');
                $table->date('periodo');
                $table->decimal('valor', 12, 4);
                $table->boolean('cumplio')->default(false);
                $table->json('extra')->nullable();
                $table->timestamps();
                $table->index(['sla_id', 'periodo', 'actor_id']);
            });
        }

        if (!Schema::hasTable('analytics.settings')) {
            Schema::create('analytics.settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('value');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics.settings');
        Schema::dropIfExists('analytics.sla_measure');
        Schema::dropIfExists('analytics.sla_definition');
        Schema::dropIfExists('analytics.kpi_value');
        Schema::dropIfExists('analytics.kpi_definition');
        Schema::dropIfExists('analytics.actor');
    }
};
