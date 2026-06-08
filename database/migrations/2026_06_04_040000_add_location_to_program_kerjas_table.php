<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            $table->foreignId('lokasi_id')->nullable()->after('kategori_id')->constrained('ruangs')->nullOnDelete();
            $table->string('nama_gedung', 150)->nullable()->after('lokasi_id');
            $table->string('nama_lantai', 100)->nullable()->after('nama_gedung');
            $table->string('nama_ruang', 100)->nullable()->after('nama_lantai');
            $table->string('no_ruang', 30)->nullable()->after('nama_ruang');
            $table->unsignedTinyInteger('lantai')->nullable()->after('no_ruang');
            $table->string('location_text', 255)->nullable()->after('lantai');
        });

        Schema::table('pekerjaans', function (Blueprint $table) {
            $table->string('nama_gedung', 150)->nullable()->after('lokasi_id');
            $table->string('nama_lantai', 100)->nullable()->after('nama_gedung');
            $table->string('nama_ruang', 100)->nullable()->after('nama_lantai');
            $table->string('no_ruang', 30)->nullable()->after('nama_ruang');
            $table->unsignedTinyInteger('lantai')->nullable()->after('no_ruang');
            $table->string('location_text', 255)->nullable()->after('lantai');
        });
    }

    public function down(): void
    {
        Schema::table('pekerjaans', function (Blueprint $table) {
            $columns = ['nama_gedung', 'nama_lantai', 'nama_ruang', 'no_ruang', 'lantai', 'location_text'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('pekerjaans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('program_kerjas', function (Blueprint $table) {
            $columns = ['lokasi_id', 'nama_gedung', 'nama_lantai', 'nama_ruang', 'no_ruang', 'lantai', 'location_text'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('program_kerjas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};