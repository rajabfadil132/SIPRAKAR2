<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $activeProgramStatuses = [
        'RAB Diajukan',
        'RAB Direvisi',
        'RAB Disetujui',
        'Siap Dijadikan Pekerjaan',
    ];

    private array $finalProgramStatuses = [
        'Dijadikan Pekerjaan',
        'Selesai',
        'Dibatalkan',
    ];

    public function up(): void
    {
        $this->normalizePekerjaanStatuses();
        $this->normalizePekerjaanCodes();
        $this->syncRabProgramRelation();
        $this->normalizeProgramStatuses();
    }

    public function down(): void
    {
        // Normalisasi status mengikuti alur final aplikasi dan tidak perlu dibalik.
    }

    private function normalizePekerjaanStatuses(): void
    {
        if (! Schema::hasTable('pekerjaans')) {
            return;
        }

        DB::table('pekerjaans')
            ->whereIn('status', ['Pending', 'Belum dilaksanakan'])
            ->update(['status' => 'Belum Diproses', 'status_key' => 'not_started']);

        DB::table('pekerjaans')
            ->whereIn('status', ['Sedang berjalan', 'Berjalan', 'Tertunda'])
            ->update(['status' => 'Diproses', 'status_key' => 'in_progress']);

        if (Schema::hasTable('progress_pekerjaans')) {
            DB::table('progress_pekerjaans')
                ->whereIn('status', ['Pending', 'Belum dilaksanakan'])
                ->update(['status' => 'Belum Diproses']);

            DB::table('progress_pekerjaans')
                ->whereIn('status', ['Sedang berjalan', 'Berjalan', 'Tertunda'])
                ->update(['status' => 'Diproses']);
        }
    }


    private function normalizePekerjaanCodes(): void
    {
        if (! Schema::hasTable('pekerjaans') || ! Schema::hasTable('program_kerjas')) {
            return;
        }

        $jobs = DB::table('pekerjaans')
            ->whereNotNull('program_kerja_id')
            ->get(['id', 'kode_pekerjaan', 'program_kerja_id']);

        foreach ($jobs as $job) {
            $targetCode = null;


            if (! $targetCode) {
                $targetCode = DB::table('program_kerjas')->where('id', $job->program_kerja_id)->value('kode_program');
            }

            if (! $targetCode || $targetCode === $job->kode_pekerjaan) {
                continue;
            }

            $alreadyUsed = DB::table('pekerjaans')
                ->where('kode_pekerjaan', $targetCode)
                ->where('id', '!=', $job->id)
                ->exists();

            if (! $alreadyUsed) {
                DB::table('pekerjaans')->where('id', $job->id)->update(['kode_pekerjaan' => $targetCode]);
            }
        }
    }

    private function syncRabProgramRelation(): void
    {
        if (! Schema::hasTable('rabs') || ! Schema::hasTable('pekerjaans')) {
            return;
        }

        $rabs = DB::table('rabs')
            ->whereNull('program_kerja_id')
            ->whereNotNull('pekerjaan_id')
            ->get(['id', 'pekerjaan_id']);

        foreach ($rabs as $rab) {
            $programId = DB::table('pekerjaans')->where('id', $rab->pekerjaan_id)->value('program_kerja_id');
            if ($programId) {
                DB::table('rabs')->where('id', $rab->id)->update(['program_kerja_id' => $programId]);
            }
        }
    }

    private function normalizeProgramStatuses(): void
    {
        if (! Schema::hasTable('program_kerjas')) {
            return;
        }

        DB::table('program_kerjas')
            ->where('status', 'Tertunda')
            ->update(['status' => 'Dibatalkan', 'status_key' => 'cancelled']);

        if (Schema::hasTable('pekerjaans')) {
            $jobs = DB::table('pekerjaans')
                ->whereNotNull('program_kerja_id')
                ->get(['id', 'program_kerja_id', 'status', 'created_at']);

            foreach ($jobs as $job) {
                $program = DB::table('program_kerjas')->where('id', $job->program_kerja_id)->first();
                if (! $program) {
                    continue;
                }

                $programStatus = match ($job->status) {
                    'Selesai' => 'Selesai',
                    'Dibatalkan' => 'Dibatalkan',
                    default => 'Dijadikan Pekerjaan',
                };

                $before = $program->status_before_conversion
                    ?: (in_array($program->status, array_merge($this->activeProgramStatuses, ['Berjalan']), true) ? $program->status : null);

                DB::table('program_kerjas')->where('id', $program->id)->update([
                    'status_before_conversion' => $before,
                    'status' => $programStatus,
                    'status_key' => match ($programStatus) {
                        'Selesai' => 'completed',
                        'Dibatalkan' => 'cancelled',
                        default => 'converted',
                    },
                    'converted_to_pekerjaan_id' => $program->converted_to_pekerjaan_id ?: $job->id,
                    'converted_at' => $program->converted_at ?: $job->created_at,
                ]);
            }
        }

        $activePrograms = DB::table('program_kerjas')
            ->whereNull('converted_to_pekerjaan_id')
            ->whereNotIn('status', $this->finalProgramStatuses)
            ->get(['id', 'status', 'needs_rab', 'estimasi_anggaran']);

        foreach ($activePrograms as $program) {
            $needsRab = (bool) $program->needs_rab || (float) ($program->estimasi_anggaran ?? 0) > 0;

            if ($needsRab) {
                $rabStatus = Schema::hasTable('rabs')
                    ? DB::table('rabs')->where('program_kerja_id', $program->id)->orderByDesc('id')->value('status_rab')
                    : null;

                $status = match ($rabStatus) {
                    'Disetujui' => 'RAB Disetujui',
                    'Direvisi' => 'RAB Direvisi',
                    'Diajukan' => 'RAB Diajukan',
                    default => in_array($program->status, ['RAB Diajukan', 'RAB Direvisi', 'RAB Disetujui'], true) ? $program->status : 'RAB Diajukan',
                };
            } elseif (! in_array($program->status, $this->activeProgramStatuses, true)) {
                $status = 'Siap Dijadikan Pekerjaan';
            } else {
                $status = $program->status;
            }

            DB::table('program_kerjas')->where('id', $program->id)->update([
                'needs_rab' => $needsRab,
                'status' => $status,
                'status_key' => match ($status) {
                    'RAB Diajukan' => 'rab_submitted',
                    'RAB Direvisi' => 'rab_revision',
                    'RAB Disetujui' => 'rab_approved',
                    'Dijadikan Pekerjaan' => 'converted',
                    'Selesai' => 'completed',
                    'Dibatalkan' => 'cancelled',
                    default => 'ready_for_work',
                },
            ]);
        }
    }

};
