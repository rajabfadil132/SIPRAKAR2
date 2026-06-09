<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nama_role')->unique();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_cabang');
            $table->string('kode', 3)->unique();
            $table->text('alamat')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role_id')) $table->foreignId('role_id')->nullable()->after('password')->constrained('roles')->nullOnDelete();
            if (! Schema::hasColumn('users', 'cabang_id')) $table->foreignId('cabang_id')->nullable()->after('role_id')->constrained('cabangs')->nullOnDelete();
            if (! Schema::hasColumn('users', 'phone')) $table->string('phone')->nullable()->after('cabang_id');
            if (! Schema::hasColumn('users', 'status')) $table->string('status')->default('active')->after('phone');
            if (! Schema::hasColumn('users', 'created_by')) $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            if (! Schema::hasColumn('users', 'updated_by')) $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            if (! Schema::hasColumn('users', 'deleted_by')) $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            if (! Schema::hasColumn('users', 'deleted_at')) $table->softDeletes();
        });

        Schema::create('kategori_pekerjaans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori');
            $table->text('keterangan')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gedungs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabangs')->cascadeOnDelete();
            $table->string('nama_gedung');
            $table->text('keterangan')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['cabang_id', 'nama_gedung']);
        });

        Schema::create('lantais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gedung_id')->constrained('gedungs')->cascadeOnDelete();
            $table->unsignedSmallInteger('nomor_lantai')->default(0);
            $table->string('nama_lantai')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['gedung_id', 'nomor_lantai']);
        });

        Schema::create('ruangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lantai_id')->constrained('lantais')->cascadeOnDelete();
            $table->string('nama_ruang');
            $table->string('kode_ruang', 30)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['lantai_id', 'nama_ruang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruangs');
        Schema::dropIfExists('lantais');
        Schema::dropIfExists('gedungs');
        Schema::dropIfExists('kategori_pekerjaans');
        Schema::table('users', function (Blueprint $table) {
            foreach (['role_id', 'cabang_id', 'created_by', 'updated_by', 'deleted_by'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
            foreach (['phone', 'status'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::dropIfExists('cabangs');
        Schema::dropIfExists('roles');
    }
};
