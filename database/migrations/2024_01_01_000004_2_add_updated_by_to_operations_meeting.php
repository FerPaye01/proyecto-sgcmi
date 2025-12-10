<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portuario.operations_meeting', function (Blueprint $table) {
            if (!Schema::hasColumn('portuario.operations_meeting', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('admin.users')->after('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('portuario.operations_meeting', function (Blueprint $table) {
            if (Schema::hasColumn('portuario.operations_meeting', 'updated_by')) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            }
        });
    }
};
