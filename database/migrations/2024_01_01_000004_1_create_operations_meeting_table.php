<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('portuario.operations_meeting')) {
            Schema::create('portuario.operations_meeting', function (Blueprint $table) {
                $table->id();
                $table->date('meeting_date');
                $table->time('meeting_time');
                $table->integer('attendees')->default(0);
                $table->text('agreements')->nullable();
                $table->json('next_24h_schedule')->nullable();
                $table->foreignId('created_by')->constrained('admin.users');
                $table->foreignId('updated_by')->nullable()->constrained('admin.users');
                $table->timestamps();
                
                $table->index('meeting_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portuario.operations_meeting');
    }
};
