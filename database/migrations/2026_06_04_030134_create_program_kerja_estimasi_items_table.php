<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_kerja_estimasi_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_kerja_id')->constrained('program_kerjas')->onDelete('cascade');
            $table->string('nama_item', 255);
            $table->decimal('jumlah_item', 12, 2)->default(1);
            $table->decimal('harga_satuan', 16, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->string('keterangan', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_kerja_estimasi_items');
    }
};