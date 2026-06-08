<?php

namespace App\Services\Pekerjaan;

use App\Models\Pekerjaan;
use App\Models\PekerjaanChecklist;
use App\Models\ProgramKerja;
use App\Models\User;
use App\Services\AppNotificationService;
use App\Services\SequentialCodeGenerator;
use Illuminate\Support\Facades\DB;

class PekerjaanService
{
    public array $statuses = ['Belum Diproses', 'Diproses', 'Selesai', 'Dibatalkan'];
    public array $editableStatuses = ['Belum Diproses', 'Diproses', 'Dibatalkan'];
    public array $legacyStatuses = ['Pending', 'Belum dilaksanakan', 'Sedang berjalan', 'Berjalan', 'Tertunda'];

    public function __construct(
        private readonly AppNotificationService $notificationService,
        private readonly SequentialCodeGenerator $codeGenerator,
    ) {}

    public function create(array $data, User $user): Pekerjaan
    {
        return DB::transaction(function () use ($data, $user) {
            if ($user->roleKey() !== 'superadmin') {
                $data['cabang_id'] = $user->cabang_id;
            }

            $this->ensureRelatedDataVisible($data);
            $program = $this->loadAvailableProgramForPekerjaan($data['program_kerja_id'] ?? null);
            if ($program) {
                $data = $this->applyProgramDataToPekerjaanPayload($data, $program);
            }
            $data = $this->normalizeLocationPayload($data);

            $checklists = $this->normalizeChecklist($data['checklists'] ?? []);
            $assignees = $this->normalizeAssignees($data['assignees'] ?? []);
            unset($data['checklists'], $data['assignees']);

            $firstAssignedUserId = collect($assignees)->first(fn ($row) => ! empty($row['user_id']))['user_id'] ?? null;
            $data['petugas_id'] = $data['petugas_id'] ?: $firstAssignedUserId;
            $this->assertAssignmentUsersCanReceiveWork($data['petugas_id'] ?? null, $assignees);
            if (! $program) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'program_kerja_id' => 'Data Pekerjaan wajib berasal dari Program Kerja.',
                ]);
            }
            $data['kode_pekerjaan'] = $program->kode_program;
            $this->ensurePekerjaanCodeIsUnique($data['kode_pekerjaan']);
            $data['progress'] = 0;
            $data['prioritas'] = $data['prioritas'] ?? 'Sedang';
            $data['estimasi_rab_awal'] = $data['estimasi_rab_awal'] ?? 0;
            $data['is_rab'] = false;
            $data['created_by'] = $user->id;

            $pekerjaan = Pekerjaan::create($data);
            $this->markProgramAsConverted($program, $pekerjaan, $user->id);
            $this->syncAssignees($pekerjaan, $assignees, $user->id);
            $this->syncChecklists($pekerjaan, $checklists, $user->id);
            $this->recalculateProgressFromChecklist($pekerjaan, $user->id, false);

            return $pekerjaan->fresh(['cabang', 'petugasTambahan']);
        });
    }

    public function createFromProgramKerja(ProgramKerja $program, array $data, User $user): Pekerjaan
    {
        abort_unless($program->canBecomePekerjaan(), 403, 'Program kerja tidak memenuhi syarat untuk dijadikan Data Pekerjaan.');

        return $this->create(array_merge($data, [
            'program_kerja_id' => $program->id,
            'nama_pekerjaan' => $program->nama_program,
            'deskripsi' => $program->deskripsi,
            'kategori_id' => $program->kategori_id,
            'prioritas' => $program->prioritas,
            'tanggal_mulai' => $data['tanggal_mulai'] ?? $program->target_mulai?->toDateString(),
            'target_selesai' => $data['target_selesai'] ?? $program->target_selesai?->toDateString(),
            'estimasi_rab_awal' => $program->estimasi_total,
            'is_rab' => $program->hasApprovedRab(),
        ]), $user);
    }

    public function update(Pekerjaan $pekerjaan, array $data, User $user): Pekerjaan
    {
        $previousStatus = $pekerjaan->status;

        return DB::transaction(function () use ($pekerjaan, $data, $user, $previousStatus) {
            $checklists = $this->normalizeChecklist($data['checklists'] ?? []);
            $assignees = $this->normalizeAssignees($data['assignees'] ?? []);
            unset($data['checklists'], $data['assignees']);

            if (empty($data['program_kerja_id']) && $pekerjaan->program_kerja_id) {
                $data['program_kerja_id'] = $pekerjaan->program_kerja_id;
            }
            $oldProgram = $pekerjaan->programKerja()->lockForUpdate()->first();
            $program = $this->loadAvailableProgramForPekerjaan($data['program_kerja_id'] ?? null, $pekerjaan);
            if ($program) {
                $data = $this->applyProgramDataToPekerjaanPayload($data, $program);
                $data['kode_pekerjaan'] = $program->kode_program;
                $this->ensurePekerjaanCodeIsUnique($data['kode_pekerjaan'], $pekerjaan->id);
            }
            $data = $this->normalizeLocationPayload($data);

            $firstAssignedUserId = collect($assignees)->first(fn ($row) => ! empty($row['user_id']))['user_id'] ?? null;
            $data['petugas_id'] = $data['petugas_id'] ?: $firstAssignedUserId;
            $this->assertAssignmentUsersCanReceiveWork($data['petugas_id'] ?? null, $assignees);
            $data['updated_by'] = $user->id;
            $data['prioritas'] = $data['prioritas'] ?? $pekerjaan->prioritas ?? 'Sedang';
            $data['estimasi_rab_awal'] = $data['estimasi_rab_awal'] ?? $pekerjaan->estimasi_rab_awal ?? 0;
            $data['is_rab'] = $pekerjaan->rab()->exists();

            $pekerjaan->update($data);
            $this->syncProgramConversionAfterUpdate($oldProgram, $program, $pekerjaan, $user->id);
            $this->syncAssignees($pekerjaan, $assignees, $user->id);
            $this->syncChecklists($pekerjaan, $checklists, $user->id);
            $this->recalculateProgressFromChecklist($pekerjaan, $user->id, false);

            return $pekerjaan;
        });
    }

    public function softDelete(Pekerjaan $pekerjaan, string $reason, int $userId): void
    {
        DB::transaction(function () use ($pekerjaan, $reason, $userId) {
            $pekerjaan->update([
                'deleted_by' => $userId,
                'delete_reason' => $reason,
            ]);
            $pekerjaan->checklists()->update(['deleted_by' => $userId]);
            $pekerjaan->delete();
        });
    }

    public function restore(Pekerjaan $pekerjaan, int $userId): void
    {
        DB::transaction(function () use ($pekerjaan, $userId) {
            $pekerjaan->restore();
            $pekerjaan->checklists()->onlyTrashed()->restore();
            $pekerjaan->update(['deleted_by' => null, 'delete_reason' => null, 'updated_by' => $userId]);
            $pekerjaan->checklists()->update(['deleted_by' => null, 'updated_by' => $userId]);
        });
    }

    public function forceDelete(Pekerjaan $pekerjaan): void
    {
        DB::transaction(function () use ($pekerjaan) {
            $pekerjaan->checklists()->withTrashed()->forceDelete();
            $rab = $pekerjaan->rab()->withTrashed()->first();
            if ($rab) {
                $rab->update(['pekerjaan_id' => null]);
            }
            $this->releaseProgramConversion($pekerjaan->programKerja()->first(), $pekerjaan->id);
            $pekerjaan->forceDelete();
        });
    }

    public function toggleChecklist(PekerjaanChecklist $checklist, bool $isDone, int $userId): void
    {
        DB::transaction(function () use ($checklist, $isDone, $userId) {
            $checklist->update([
                'is_done' => $isDone,
                'completed_by' => $isDone ? $userId : null,
                'completed_at' => $isDone ? now() : null,
                'updated_by' => $userId,
            ]);
            $this->recalculateProgressFromChecklist($checklist->pekerjaan, $userId, true);
        });
    }

    public function recalculateProgressFromChecklist(Pekerjaan $pekerjaan, int $userId, bool $writeLog, bool $forceProcessing = false): void
    {
        $pekerjaan->refresh();
        $currentStatus = $pekerjaan->status;
        $lockedStatuses = ['Dibatalkan'];
        $total = $pekerjaan->checklists()->count();
        $done = $pekerjaan->checklists()->where('is_done', true)->count();
        // CRITICAL FIX: pekerjaan tanpa checklist dianggap otomatis selesai
        $progress = $total > 0 ? (int) round(($done / $total) * 100) : 100;

        if (in_array($currentStatus, $lockedStatuses, true)) {
            $status = $currentStatus;
        } elseif ($progress >= 100) {
            $status = 'Selesai';
        } elseif ($forceProcessing || $progress > 0 || $currentStatus === 'Diproses') {
            $status = 'Diproses';
        } else {
            $status = 'Belum Diproses';
        }

        $tanggalSelesai = $status === 'Selesai' && $progress >= 100
            ? ($pekerjaan->tanggal_selesai?->toDateString() ?: now()->toDateString())
            : null;

        $pekerjaan->update([
            'progress' => $progress,
            'status' => $status,
            'tanggal_selesai' => $tanggalSelesai,
            'updated_by' => $userId,
        ]);

        if ($pekerjaan->program_kerja_id) {
            $programStatus = $status === 'Selesai' ? 'Selesai' : ($status === 'Dibatalkan' ? 'Dibatalkan' : 'Dijadikan Pekerjaan');
            $pekerjaan->programKerja?->update(['status' => $programStatus, 'updated_by' => $userId]);
        }

        $freshPekerjaan = $pekerjaan->fresh(['cabang', 'petugasTambahan']);
        $this->notificationService->pekerjaanStatusChanged($freshPekerjaan, $currentStatus, $status, $userId);

        if ($writeLog) {
            $pekerjaan->progressLogs()->create([
                'tanggal_update' => now(),
                'progress' => $progress,
                'status' => $status,
                'catatan' => 'Progress diperbarui otomatis dari checklist pekerjaan.',
                'updated_by' => $userId,
            ]);
        }
    }

    private function loadAvailableProgramForPekerjaan(mixed $programId, ?Pekerjaan $current = null): ?ProgramKerja
    {
        if (blank($programId)) {
            return null;
        }

        $program = ProgramKerja::query()
            ->forCurrentUser()
            ->whereKey((int) $programId)
            ->lockForUpdate()
            ->first();

        if (! $program) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'program_kerja_id' => 'Program kerja tidak ditemukan atau tidak dapat diakses.',
            ]);
        }

        if ($program->converted_to_pekerjaan_id && (! $current || (int) $program->converted_to_pekerjaan_id !== (int) $current->id)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'program_kerja_id' => 'Program kerja ini sudah dipindahkan menjadi data pekerjaan lain.',
            ]);
        }

        $program->loadMissing('rab');
        if (! $program->canBecomePekerjaan() && (! $current || (int) $program->id !== (int) $current->program_kerja_id)) {
            $message = $program->needs_rab
                ? 'Program ini membutuhkan RAB dan baru bisa dijadikan Data Pekerjaan setelah RAB disetujui.'
                : 'Program ini belum berstatus Siap Dijadikan Pekerjaan.';
            throw \Illuminate\Validation\ValidationException::withMessages([
                'program_kerja_id' => $message,
            ]);
        }

        return $program;
    }

    private function applyProgramDataToPekerjaanPayload(array $data, ProgramKerja $program): array
    {
        $data['program_kerja_id'] = $program->id;
        $data['cabang_id'] = $program->cabang_id;
        $data['nama_pekerjaan'] = trim((string) ($data['nama_pekerjaan'] ?? '')) ?: $program->nama_program;
        $data['deskripsi'] = trim((string) ($data['deskripsi'] ?? '')) ?: $program->deskripsi;
        $data['kategori_id'] = $data['kategori_id'] ?: $program->kategori_id;
        $data['prioritas'] = $data['prioritas'] ?: $program->prioritas;
        $data['tanggal_mulai'] = $data['tanggal_mulai'] ?: optional($program->target_mulai)->toDateString();
        $data['target_selesai'] = $data['target_selesai'] ?: optional($program->target_selesai)->toDateString();

        if (! filled($data['estimasi_rab_awal'] ?? null)) {
            $data['estimasi_rab_awal'] = $program->estimasi_anggaran ?? 0;
        }

        // Ambil lokasi dari Program Kerja jika field form masih kosong.
        $data['lokasi_id'] = filled($data['lokasi_id'] ?? null) ? $data['lokasi_id'] : $program->lokasi_id;
        $data['nama_gedung'] = filled($data['nama_gedung'] ?? null) ? $data['nama_gedung'] : $program->nama_gedung;
        $data['nama_lantai'] = filled($data['nama_lantai'] ?? null) ? $data['nama_lantai'] : $program->nama_lantai;
        $data['nama_ruang'] = filled($data['nama_ruang'] ?? null) ? $data['nama_ruang'] : $program->nama_ruang;
        $data['no_ruang'] = filled($data['no_ruang'] ?? null) ? $data['no_ruang'] : $program->no_ruang;
        $data['lantai'] = filled($data['lantai'] ?? null) ? $data['lantai'] : $program->lantai;
        $data['location_text'] = filled($data['location_text'] ?? null) ? $data['location_text'] : $program->location_text;

        return $data;
    }

    private function ensurePekerjaanCodeIsUnique(string $code, ?int $ignoreId = null): void
    {
        $exists = Pekerjaan::query()
            ->withTrashed()
            ->where('kode_pekerjaan', $code)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'program_kerja_id' => "Kode pekerjaan {$code} sudah digunakan. Pilih program kerja lain atau cek arsip pekerjaan.",
            ]);
        }
    }

    private function markProgramAsConverted(?ProgramKerja $program, Pekerjaan $pekerjaan, int $userId): void
    {
        if (! $program) {
            return;
        }

        $program->loadMissing(['rab']);
        $program->update([
            'status_before_conversion' => $program->status_before_conversion ?: $program->status,
            'status' => 'Dijadikan Pekerjaan',
            'converted_to_pekerjaan_id' => $pekerjaan->id,
            'converted_at' => now(),
            'updated_by' => $userId,
        ]);

        if ($program->rab && $program->rab->status_rab === 'Disetujui') {
            $program->rab->update(['pekerjaan_id' => $pekerjaan->id, 'updated_by' => $userId]);
            $pekerjaan->update(['is_rab' => true, 'updated_by' => $userId]);
        } else {
            $pekerjaan->update(['is_rab' => false, 'updated_by' => $userId]);
        }

    }

    private function syncProgramConversionAfterUpdate(?ProgramKerja $oldProgram, ?ProgramKerja $newProgram, Pekerjaan $pekerjaan, int $userId): void
    {
        if ($oldProgram && (! $newProgram || (int) $oldProgram->id !== (int) $newProgram->id)) {
            $this->releaseProgramConversion($oldProgram, $pekerjaan->id, $userId);
        }
        $this->markProgramAsConverted($newProgram, $pekerjaan, $userId);
    }

    private function releaseProgramConversion(?ProgramKerja $program, ?int $pekerjaanId = null, ?int $userId = null): void
    {
        if (! $program) {
            return;
        }

        if ($pekerjaanId && (int) $program->converted_to_pekerjaan_id !== (int) $pekerjaanId) {
            return;
        }

        $program->loadMissing(['rab']);
        $program->update([
            'status' => $program->status_before_conversion ?: ($program->needs_rab ? 'RAB Disetujui' : 'Siap Dijadikan Pekerjaan'),
            'converted_to_pekerjaan_id' => null,
            'converted_at' => null,
            'status_before_conversion' => null,
            'updated_by' => $userId,
        ]);
        if ($program->rab && $pekerjaanId) {
            $program->rab->update(['pekerjaan_id' => null, 'updated_by' => $userId]);
        }
    }

    private function normalizeLocationPayload(array $data): array
    {
        if (! empty($data['lokasi_id'])) {
            $ruang = \App\Models\Ruang::query()->with(['lantaiMaster.gedung'])->find($data['lokasi_id']);
            if ($ruang) {
                $data['nama_gedung'] = $data['nama_gedung'] ?: $ruang->lantaiMaster?->gedung?->nama_gedung;
                $data['nama_lantai'] = $data['nama_lantai'] ?: ($ruang->lantaiMaster?->nama_lantai ?: ($ruang->lantaiMaster ? 'Lantai '.$ruang->lantaiMaster->nomor_lantai : null));
                $data['lantai'] = $data['lantai'] ?? $ruang->lantaiMaster?->nomor_lantai;
                $data['nama_ruang'] = $data['nama_ruang'] ?: $ruang->nama_ruang;
                $data['no_ruang'] = $data['no_ruang'] ?: $ruang->kode_ruang;
            }
        }

        foreach (['nama_gedung', 'nama_lantai', 'nama_ruang', 'no_ruang', 'location_text'] as $field) {
            $data[$field] = trim((string) ($data[$field] ?? '')) ?: null;
        }

        return $data;
    }

    private function ensureRelatedDataVisible(array $data): void
    {
        if (! empty($data['program_kerja_id'])) {
            abort_unless(ProgramKerja::query()->forCurrentUser()->whereKey($data['program_kerja_id'])->exists(), 403);
        }
    }

    private function normalizeChecklist(array $items): array
    {
        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeAssignees(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                return [
                    'role_text' => trim((string) ($item['role_text'] ?? '')) ?: null,
                    'user_id' => filled($item['user_id'] ?? null) ? (int) $item['user_id'] : null,
                    'nama_petugas_manual' => trim((string) ($item['nama_petugas_manual'] ?? '')) ?: null,
                ];
            })
            ->filter(fn ($item) => $item['role_text'] || $item['user_id'] || $item['nama_petugas_manual'])
            ->unique(fn ($item) => ($item['user_id'] ?: 'manual-'.$item['nama_petugas_manual']).'-'.($item['role_text'] ?: ''))
            ->values()
            ->all();
    }

    private function syncAssignees(Pekerjaan $pekerjaan, array $assignees, int $userId): void
    {
        $pekerjaan->petugasTambahan()->delete();
        foreach ($assignees as $assignment) {
            $pekerjaan->petugasTambahan()->create($assignment + [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    private function syncChecklists(Pekerjaan $pekerjaan, array $items, int $userId): void
    {
        $existing = $pekerjaan->checklists()->get()->keyBy('deskripsi');
        $wanted = collect($items);

        foreach ($wanted as $deskripsi) {
            if ($existing->has($deskripsi)) {
                $existing[$deskripsi]->update(['updated_by' => $userId]);
            } else {
                $pekerjaan->checklists()->create([
                    'deskripsi' => $deskripsi,
                    'created_by' => $userId,
                ]);
            }
        }

        $pekerjaan->checklists()
            ->whereNotIn('deskripsi', $wanted->all())
            ->get()
            ->each(function (PekerjaanChecklist $checklist) use ($userId) {
                $checklist->update(['deleted_by' => $userId]);
                $checklist->delete();
            });
    }

    private function assertAssignmentUsersCanReceiveWork(mixed $primaryUserId, array $assignees): void
    {
        $ids = collect($assignees)
            ->pluck('user_id')
            ->push($primaryUserId)
            ->filter()
            ->unique()
            ->values();

        foreach ($ids as $id) {
            $user = User::query()
                ->with(['role.permission', 'roleCategory'])
                ->whereKey((int) $id)
                ->first();

            if (! $user || ! $user->canReceiveWorkAssignment()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'petugas_id' => 'Petugas pekerjaan hanya boleh user aktif dengan role staff atau role yang memiliki izin update progress.',
                ]);
            }
        }
    }
}