<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pekerjaan_petugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pekerjaan_id')->constrained('pekerjaans')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role_text')->nullable();
            $table->string('nama_petugas_manual')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['pekerjaan_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pekerjaan_petugas');
    }
};
