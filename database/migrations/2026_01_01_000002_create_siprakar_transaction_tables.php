<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('program_kerjas', function (Blueprint $table) {
            $table->id();
            $table->string('kode_program')->unique();
            $table->year('tahun');
            $table->string('nama_program');
            $table->text('deskripsi')->nullable();
            $table->foreignId('cabang_id')->nullable()->constrained('cabangs')->nullOnDelete();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori_pekerjaans')->nullOnDelete();
            $table->string('prioritas')->default('Sedang');
            $table->date('target_mulai')->nullable();
            $table->date('target_selesai')->nullable();
            $table->decimal('estimasi_anggaran', 16, 2)->default(0);
            $table->string('status')->default('Siap Dijadikan Pekerjaan');
            $table->string('status_key', 50)->default('ready_for_work')->index();
            $table->string('source_type', 20)->default('PROKER');
            $table->boolean('needs_rab')->default(false);
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pekerjaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_kerja_id')->constrained('program_kerjas')->restrictOnDelete();
            $table->string('kode_pekerjaan')->unique();
            $table->string('nama_pekerjaan');
            $table->text('deskripsi')->nullable();
            $table->foreignId('cabang_id')->nullable()->constrained('cabangs')->nullOnDelete();
            $table->foreignId('lokasi_id')->nullable()->constrained('ruangs')->nullOnDelete();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori_pekerjaans')->nullOnDelete();
            $table->foreignId('penanggung_jawab_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal_mulai')->nullable();
            $table->date('target_selesai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status')->default('Belum Diproses');
            $table->string('status_key', 50)->default('not_started')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->boolean('is_rab')->default(false);
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_kerja_id')->nullable()->unique()->constrained('program_kerjas')->cascadeOnDelete();
            $table->foreignId('pekerjaan_id')->nullable()->unique()->constrained('pekerjaans')->cascadeOnDelete();
            $table->string('nomor_rab')->unique();
            $table->date('tanggal_rab')->nullable();
            $table->decimal('total_rab', 16, 2)->default(0);
            $table->string('status_rab')->default('Diajukan');
            $table->string('status_rab_key', 50)->default('submitted')->index();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rab_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rab_id')->constrained('rabs')->cascadeOnDelete();
            $table->string('nama_item');
            $table->decimal('jumlah_item', 12, 2)->default(1);
            $table->decimal('harga_satuan', 16, 2)->default(0);
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('progress_pekerjaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pekerjaan_id')->constrained('pekerjaans')->cascadeOnDelete();
            $table->date('tanggal_update');
            $table->unsignedTinyInteger('progress');
            $table->string('status');
            $table->text('catatan')->nullable();
            $table->text('kendala')->nullable();
            $table->text('solusi')->nullable();
            $table->string('foto_sebelum')->nullable();
            $table->string('foto_proses')->nullable();
            $table->string('foto_sesudah')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('jadwal_pemeliharaans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jadwal');
            $table->foreignId('cabang_id')->nullable()->constrained('cabangs')->nullOnDelete();
            $table->foreignId('lokasi_id')->nullable()->constrained('ruangs')->nullOnDelete();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori_pekerjaans')->nullOnDelete();
            $table->string('frekuensi');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_berikutnya')->nullable();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('Aktif');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('nama_vendor');
            $table->string('jenis_vendor')->nullable();
            $table->string('kontak')->nullable();
            $table->string('email')->nullable();
            $table->text('alamat')->nullable();
            $table->string('pic')->nullable();
            $table->string('bidang_pekerjaan')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dokumen_administrasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_kerja_id')->nullable()->constrained('program_kerjas')->cascadeOnDelete();
            $table->foreignId('pekerjaan_id')->nullable()->constrained('pekerjaans')->cascadeOnDelete();
            $table->string('jenis_dokumen');
            $table->string('nomor_dokumen')->nullable();
            $table->date('tanggal_dokumen')->nullable();
            $table->string('file_dokumen')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_administrasis');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('jadwal_pemeliharaans');
        Schema::dropIfExists('progress_pekerjaans');
        Schema::dropIfExists('rab_details');
        Schema::dropIfExists('rabs');
        Schema::dropIfExists('pekerjaans');
        Schema::dropIfExists('program_kerjas');
    }
};
