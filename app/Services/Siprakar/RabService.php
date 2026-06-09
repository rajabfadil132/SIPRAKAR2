<?php

namespace App\Services\Siprakar;

use App\Enums\ProgramKerjaStatus;
use App\Enums\RabStatus;
use App\Models\ProgramKerja;
use App\Models\Rab;
use App\Models\RabDetail;
use App\Models\User;
use App\Services\SequentialCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RabService
{
    public function __construct(
        private readonly SequentialCodeGenerator $codeGenerator,
    ) {}

    public function createForProgram(ProgramKerja $program, array $data, User $user): Rab
    {
        abort_if($program->rab()->exists(), 422, 'Program Kerja ini sudah memiliki RAB.');
        abort_if($program->converted_to_pekerjaan_id, 422, 'Program Kerja yang sudah menjadi Data Pekerjaan tidak dapat dibuatkan RAB baru.');
        abort_unless(in_array($program->statusEnum()->value, ProgramKerjaStatus::activeKeys(), true), 422, 'RAB hanya bisa dibuat dari Program Kerja aktif.');

        return DB::transaction(function () use ($program, $data, $user) {
            $rab = Rab::create([
                'program_kerja_id' => $program->id,
                'pekerjaan_id' => $program->converted_to_pekerjaan_id,
                'tanggal_rab' => $data['tanggal_rab'] ?? now()->toDateString(),
                'nomor_rab' => $this->codeGenerator->rab($program->cabang_id),
                'status_rab_key' => RabStatus::SUBMITTED->value,
                'submitted_at' => now(),
                'catatan' => $data['catatan'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->copyEstimasiItemsToRab($program->loadMissing('estimasiItems'), $rab, $user->id);
            $this->syncProgramFromRab($rab->fresh('programKerja'), $user->id);

            return $rab->fresh(['details', 'programKerja']);
        });
    }

    public function createAutoFromProgramEstimasi(ProgramKerja $program, int $userId): Rab
    {
        $program->loadMissing('estimasiItems', 'rab');

        if ($program->rab) {
            return $program->rab;
        }

        return DB::transaction(function () use ($program, $userId) {
            $rab = Rab::create([
                'program_kerja_id' => $program->id,
                'pekerjaan_id' => $program->converted_to_pekerjaan_id,
                'tanggal_rab' => now()->toDateString(),
                'nomor_rab' => $this->codeGenerator->rab($program->cabang_id),
                'status_rab_key' => RabStatus::SUBMITTED->value,
                'submitted_at' => now(),
                'catatan' => 'RAB otomatis dibuat dari estimasi item Program Kerja.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->copyEstimasiItemsToRab($program, $rab, $userId);
            $this->syncProgramFromRab($rab->fresh('programKerja'), $userId);

            return $rab->fresh(['details', 'programKerja']);
        });
    }

    public function changeStatus(Rab $rab, RabStatus|string $status, int $userId, array $extra = []): void
    {
        $targetStatus = $status instanceof RabStatus ? $status : RabStatus::fromLabelOrKey($status);

        DB::transaction(function () use ($rab, $targetStatus, $userId, $extra) {
            $rab->loadMissing(['programKerja', 'pekerjaan']);
            $rab->update($extra + [
                'status_rab_key' => $targetStatus->value,
                'updated_by' => $userId,
            ]);

            $this->syncProgramFromRab($rab->fresh(['programKerja', 'pekerjaan']), $userId);
        });
    }

    public function ensureItemsEditable(Rab $rab): void
    {
        if (! $this->itemsEditable($rab)) {
            throw ValidationException::withMessages([
                'status_rab' => 'Item RAB hanya bisa diedit saat status Diajukan atau Direvisi. RAB yang sudah disetujui/ditolak akan terkunci.',
            ]);
        }
    }

    public function itemsEditable(Rab $rab): bool
    {
        return in_array($rab->statusEnum()->value, RabStatus::editableKeys(), true);
    }

    public function createItem(Rab $rab, array $data, int $userId): RabDetail
    {
        return DB::transaction(function () use ($rab, $data, $userId) {
            $this->ensureItemsEditable($rab);
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;
            $detail = $rab->details()->create($data);
            $this->recalculateTotal($rab, $userId);
            $this->markAsRevisedAfterItemChange($rab, $userId);

            return $detail;
        });
    }

    public function updateItem(RabDetail $detail, array $data, int $userId): void
    {
        DB::transaction(function () use ($detail, $data, $userId) {
            $rab = $detail->rab;
            $this->ensureItemsEditable($rab);
            $data['updated_by'] = $userId;
            $detail->update($data);
            $this->recalculateTotal($rab, $userId);
            $this->markAsRevisedAfterItemChange($rab, $userId);
        });
    }

    public function deleteItem(RabDetail $detail, int $userId): void
    {
        DB::transaction(function () use ($detail, $userId) {
            $rab = $detail->rab;
            $this->ensureItemsEditable($rab);
            $detail->update(['deleted_by' => $userId]);
            $detail->delete();
            $this->recalculateTotal($rab, $userId);
            $this->markAsRevisedAfterItemChange($rab, $userId);
        });
    }

    public function recalculateTotal(Rab $rab, int $userId): void
    {
        $rab->update([
            'total_rab' => $rab->details()->sum('subtotal'),
            'updated_by' => $userId,
        ]);
    }

    private function copyEstimasiItemsToRab(ProgramKerja $program, Rab $rab, int $userId): void
    {
        $totalRab = 0;
        foreach ($program->estimasiItems as $item) {
            $subtotal = (float) $item->jumlah_item * (float) $item->harga_satuan;
            $totalRab += $subtotal;
            $rab->details()->create([
                'nama_item' => $item->nama_item,
                'jumlah_item' => $item->jumlah_item,
                'harga_satuan' => $item->harga_satuan,
                'subtotal' => $subtotal,
                'keterangan' => $item->keterangan,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        $rab->update(['total_rab' => $totalRab, 'updated_by' => $userId]);
    }

    private function markAsRevisedAfterItemChange(Rab $rab, int $userId): void
    {
        $rab->refresh();
        if ($rab->statusEnum() === RabStatus::SUBMITTED) {
            $this->changeStatus($rab, RabStatus::REVISION, $userId, [
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]);
        }
    }

    private function syncProgramFromRab(Rab $rab, int $userId): void
    {
        $rab->loadMissing(['programKerja', 'pekerjaan']);
        $status = $rab->statusEnum();

        if ($rab->programKerja && ! $rab->programKerja->converted_to_pekerjaan_id) {
            $rab->programKerja->update([
                'needs_rab' => $status !== RabStatus::REJECTED,
                'status_key' => ProgramKerjaStatus::fromRabStatus($status)->value,
                'estimasi_anggaran' => $rab->total_rab ?: $rab->programKerja->estimasi_anggaran,
                'updated_by' => $userId,
            ]);
        }

        $rab->pekerjaan?->update([
            'is_rab' => $status === RabStatus::APPROVED,
            'updated_by' => $userId,
        ]);
    }
}
