<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit.audit_log')) {
            Schema::create('audit.audit_log', function (Blueprint $table) {
                $table->id();
                $table->timestamp('event_ts')->useCurrent();
                $table->string('actor_user')->nullable();
                $table->string('action');
                $table->string('object_schema')->nullable();
                $table->string('object_table')->nullable();
                $table->bigInteger('object_id')->nullable();
                $table->json('details')->nullable();
                $table->index(['event_ts', 'actor_user']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit.audit_log');
    }
};
