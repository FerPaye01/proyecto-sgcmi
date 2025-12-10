<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin.users')) {
            Schema::create('admin.users', function (Blueprint $table) {
                $table->id();
                $table->string('username')->unique();
                $table->string('email')->unique();
                $table->string('password');
                $table->string('full_name');
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('company_id')->nullable();
                $table->rememberToken();
                $table->timestamps();
                
                // Foreign key will be added after terrestre.company table is created
            });
        }

        if (!Schema::hasTable('admin.roles')) {
            Schema::create('admin.roles', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('admin.permissions')) {
            Schema::create('admin.permissions', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('admin.user_roles')) {
            Schema::create('admin.user_roles', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('admin.users')->onDelete('cascade');
                $table->foreignId('role_id')->constrained('admin.roles')->onDelete('cascade');
                $table->primary(['user_id', 'role_id']);
            });
        }

        if (!Schema::hasTable('admin.role_permissions')) {
            Schema::create('admin.role_permissions', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained('admin.roles')->onDelete('cascade');
                $table->foreignId('permission_id')->constrained('admin.permissions')->onDelete('cascade');
                $table->primary(['role_id', 'permission_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin.role_permissions');
        Schema::dropIfExists('admin.user_roles');
        Schema::dropIfExists('admin.permissions');
        Schema::dropIfExists('admin.roles');
        Schema::dropIfExists('admin.users');
    }
};
