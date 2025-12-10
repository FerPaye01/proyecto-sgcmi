<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $schemas = ['admin', 'portuario', 'terrestre', 'aduanas', 'analytics', 'audit', 'reports'];
        
        foreach ($schemas as $schema) {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schema}");
        }
    }

    public function down(): void
    {
        $schemas = ['reports', 'audit', 'analytics', 'aduanas', 'terrestre', 'portuario', 'admin'];
        
        foreach ($schemas as $schema) {
            DB::statement("DROP SCHEMA IF EXISTS {$schema} CASCADE");
        }
    }
};
