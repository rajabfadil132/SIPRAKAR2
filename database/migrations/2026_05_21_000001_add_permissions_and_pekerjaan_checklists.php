<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->unique()->constrained('roles')->cascadeOnDelete();
                $table->json('permissions')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pekerjaan_checklists')) {
            Schema::create('pekerjaan_checklists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pekerjaan_id')->constrained('pekerjaans')->cascadeOnDelete();
                $table->string('deskripsi');
                $table->boolean('is_done')->default(false);
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pekerjaan_checklists');
        Schema::dropIfExists('role_permissions');
    }
};
