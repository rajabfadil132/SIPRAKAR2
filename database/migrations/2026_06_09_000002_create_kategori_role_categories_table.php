<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_role_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_pekerjaan_id')
 ->constrained('kategori_pekerjaans')
                ->cascadeOnDelete();
            $table->foreignId('role_category_id')
                ->constrained('role_categories')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['kategori_pekerjaan_id', 'role_category_id'], 'kategori_role_categories_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_role_categories');
    }
};
