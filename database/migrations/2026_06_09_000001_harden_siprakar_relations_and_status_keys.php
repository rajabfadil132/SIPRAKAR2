<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addStatusKeys();
        $this->backfillStatusKeys();
        $this->deduplicateRabRelations();
        $this->hardenRabRelations();
        $this->hardenProgramConversionRelation();
        $this->removeDuplicatedProgramIdFromProgressLogs();
        $this->backfillInvalidRequiredProgramRelations();
        $this->addAuditForeignKeysForSqlDatabases();
    }

    public function down(): void
    {
        // Migration ini bersifat penguatan integritas data. Rollback sengaja minimal agar tidak merusak data produksi.
        if (Schema::hasTable('progress_pekerjaans') && ! Schema::hasColumn('progress_pekerjaans', 'program_kerja_id')) {
            Schema::table('progress_pekerjaans', function (Blueprint $table) {
                $table->foreignId('program_kerja_id')->nullable()->after('id')->constrained('program_kerjas')->cascadeOnDelete();
            });
        }
    }

    private function addStatusKeys(): void
    {
        if (Schema::hasTable('program_kerjas') && ! Schema::hasColumn('program_kerjas', 'status_key')) {
            Schema::table('program_kerjas', function (Blueprint $table) {
                $table->string('status_key', 50)->default('ready_for_work')->after('status')->index();
            });
        }

        if (Schema::hasTable('pekerjaans') && ! Schema::hasColumn('pekerjaans', 'status_key')) {
            Schema::table('pekerjaans', function (Blueprint $table) {
                $table->string('status_key', 50)->default('not_started')->after('status')->index();
            });
        }

        if (Schema::hasTable('rabs') && ! Schema::hasColumn('rabs', 'status_rab_key')) {
            Schema::table('rabs', function (Blueprint $table) {
                $table->string('status_rab_key', 50)->default('submitted')->after('status_rab')->index();
            });
        }
    }

    private function backfillStatusKeys(): void
    {
        if (Schema::hasTable('program_kerjas')) {
            foreach ([
                'RAB Diajukan' => 'rab_submitted',
                'RAB Direvisi' => 'rab_revision',
                'RAB Disetujui' => 'rab_approved',
                'Siap Dijadikan Pekerjaan' => 'ready_for_work',
                'Dijadikan Pekerjaan' => 'converted',
                'Berjalan' => 'converted',
                'Selesai' => 'completed',
                'Tertunda' => 'cancelled',
                'Dibatalkan' => 'cancelled',
            ] as $label => $key) {
                DB::table('program_kerjas')->where('status', $label)->update([
                    'status' => match ($key) {
                        'rab_submitted' => 'RAB Diajukan',
                        'rab_revision' => 'RAB Direvisi',
                        'rab_approved' => 'RAB Disetujui',
                        'converted' => 'Dijadikan Pekerjaan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => 'Siap Dijadikan Pekerjaan',
                    },
                    'status_key' => $key,
                ]);
            }
        }

        if (Schema::hasTable('pekerjaans')) {
            foreach ([
                'Pending' => 'not_started',
                'Belum dilaksanakan' => 'not_started',
                'Belum Diproses' => 'not_started',
                'Sedang berjalan' => 'in_progress',
                'Berjalan' => 'in_progress',
                'Tertunda' => 'in_progress',
                'Diproses' => 'in_progress',
                'Selesai' => 'completed',
                'Dibatalkan' => 'cancelled',
            ] as $label => $key) {
                DB::table('pekerjaans')->where('status', $label)->update([
                    'status' => match ($key) {
                        'in_progress' => 'Diproses',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => 'Belum Diproses',
                    },
                    'status_key' => $key,
                ]);
            }
        }

        if (Schema::hasTable('rabs')) {
            foreach ([
                'Diajukan' => 'submitted',
                'Direvisi' => 'revision',
                'Disetujui' => 'approved',
                'Ditolak' => 'rejected',
            ] as $label => $key) {
                DB::table('rabs')->where('status_rab', $label)->update(['status_rab_key' => $key]);
            }
        }
    }

    private function deduplicateRabRelations(): void
    {
        if (! Schema::hasTable('rabs')) {
            return;
        }

        foreach (['program_kerja_id', 'pekerjaan_id'] as $column) {
            $duplicates = DB::table('rabs')
                ->select($column, DB::raw('COUNT(*) as aggregate_count'))
                ->whereNotNull($column)
                ->groupBy($column)
                ->having('aggregate_count', '>', 1)
                ->get();

            foreach ($duplicates as $duplicate) {
                $keepId = DB::table('rabs')
                    ->where($column, $duplicate->{$column})
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->value('id');

                DB::table('rabs')
                    ->where($column, $duplicate->{$column})
                    ->where('id', '!=', $keepId)
                    ->update([
                        $column => null,
                        'catatan' => 'Relasi dilepas otomatis karena duplikasi '.$column.'.'
                    ]);
            }
        }
    }

    private function hardenRabRelations(): void
    {
        if (! Schema::hasTable('rabs')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }

        try {
            if (! Schema::hasIndex('rabs', 'rabs_program_kerja_id_unique')
                && ! Schema::hasIndex('rabs', 'rabs_program_kerja_id_unique_hardened')) {
                Schema::table('rabs', function (Blueprint $table) {
                    $table->unique('program_kerja_id', 'rabs_program_kerja_id_unique_hardened');
                });
            }

            if (! Schema::hasIndex('rabs', 'rabs_pekerjaan_id_unique')
                && ! Schema::hasIndex('rabs', 'rabs_pekerjaan_id_unique_hardened')) {
                Schema::table('rabs', function (Blueprint $table) {
                    $table->unique('pekerjaan_id', 'rabs_pekerjaan_id_unique_hardened');
                });
            }
        } catch (Throwable) {
            // Beberapa driver lama tidak mendukung inspeksi index. Fresh install sudah dikunci dari migration awal.
        }
    }

    private function hardenProgramConversionRelation(): void
    {
        if (! Schema::hasTable('program_kerjas') || ! Schema::hasColumn('program_kerjas', 'converted_to_pekerjaan_id')) {
            return;
        }

        DB::table('program_kerjas')
            ->whereNotNull('converted_to_pekerjaan_id')
            ->whereNotIn('converted_to_pekerjaan_id', DB::table('pekerjaans')->select('id'))
            ->update(['converted_to_pekerjaan_id' => null, 'converted_at' => null]);

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        try {
            Schema::table('program_kerjas', function (Blueprint $table) {
                $table->foreign('converted_to_pekerjaan_id', 'program_kerjas_converted_to_pekerjaan_id_fk')
                    ->references('id')
                    ->on('pekerjaans')
                    ->nullOnDelete();
            });
        } catch (Throwable) {
            // Constraint sudah ada pada fresh install atau driver tidak mendukung penambahan FK in-place.
        }
    }

    private function removeDuplicatedProgramIdFromProgressLogs(): void
    {
        if (! Schema::hasTable('progress_pekerjaans') || ! Schema::hasColumn('progress_pekerjaans', 'program_kerja_id')) {
            return;
        }

        Schema::table('progress_pekerjaans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_kerja_id');
        });
    }

    private function backfillInvalidRequiredProgramRelations(): void
    {
        if (! Schema::hasTable('pekerjaans') || ! Schema::hasTable('program_kerjas')) {
            return;
        }

        $orphanJobs = DB::table('pekerjaans')->whereNull('program_kerja_id')->get();
        foreach ($orphanJobs as $job) {
            $programId = DB::table('program_kerjas')->insertGetId([
                'kode_program' => $job->kode_pekerjaan ?: 'LEGACY/'.str_pad((string) $job->id, 5, '0', STR_PAD_LEFT),
                'tahun' => optional($job->created_at ? \Carbon\Carbon::parse($job->created_at) : now())->year,
                'nama_program' => $job->nama_pekerjaan ?: 'Program legacy pekerjaan #'.$job->id,
                'deskripsi' => $job->deskripsi,
                'cabang_id' => $job->cabang_id,
                'kategori_id' => $job->kategori_id,
                'prioritas' => $job->prioritas ?? 'Sedang',
                'target_mulai' => $job->tanggal_mulai,
                'target_selesai' => $job->target_selesai,
                'estimasi_anggaran' => $job->estimasi_rab_awal ?? 0,
                'status' => 'Dijadikan Pekerjaan',
                'status_key' => 'converted',
                'source_type' => 'LEGACY_JOB',
                'needs_rab' => false,
                'converted_to_pekerjaan_id' => $job->id,
                'converted_at' => $job->created_at ?? now(),
                'status_before_conversion' => 'Siap Dijadikan Pekerjaan',
                'created_by' => $job->created_by,
                'updated_by' => $job->updated_by,
                'created_at' => $job->created_at ?? now(),
                'updated_at' => now(),
            ]);

            DB::table('pekerjaans')->where('id', $job->id)->update(['program_kerja_id' => $programId]);
        }
    }

    private function addAuditForeignKeysForSqlDatabases(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        $tables = [
            'cabangs', 'kategori_pekerjaans', 'gedungs', 'lantais', 'ruangs',
            'program_kerjas', 'pekerjaans', 'rabs', 'rab_details',
            'pekerjaan_checklists', 'program_kerja_estimasi_items', 'pekerjaan_petugas',
            'jadwal_pemeliharaans', 'vendors', 'dokumen_administrasis', 'app_notifications',
        ];

        foreach ($tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach (['created_by', 'updated_by', 'deleted_by'] as $column) {
                if (! Schema::hasColumn($tableName, $column)) {
                    continue;
                }

                DB::table($tableName)
                    ->whereNotNull($column)
                    ->whereNotIn($column, DB::table('users')->select('id'))
                    ->update([$column => null]);
            }
        }
    }
};
