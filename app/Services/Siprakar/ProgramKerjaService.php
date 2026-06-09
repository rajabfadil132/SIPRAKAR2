<?php

namespace App\Services\Siprakar;

use App\Enums\ProgramKerjaStatus;
use App\Enums\RabStatus;
use App\Models\ProgramKerja;
use App\Models\User;
use App\Services\SequentialCodeGenerator;
use Illuminate\Support\Facades\DB;

class ProgramKerjaService
{
    public function __construct(
        private readonly SequentialCodeGenerator $codeGenerator,
        private readonly RabService $rabService,
    ) {}

    public function create(array $data, array $estimasiItems, User $user): ProgramKerja
    {
        return DB::transaction(function () use ($data, $estimasiItems, $user) {
            if ($user->roleKey() !== 'superadmin') {
                $data['cabang_id'] = $user->cabang_id;
            }

            $hasEstimasi = count($estimasiItems) > 0;
            $estimasiTotal = $this->sumEstimasiItems($estimasiItems);
            $data['kode_program'] = $this->codeGenerator->program('PROKER', $data['cabang_id']);
            $data['tahun'] = now()->year;
            $data['source_type'] = 'PROKER';
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;
            $data['needs_rab'] = $hasEstimasi;
            $data['status_key'] = $hasEstimasi ? ProgramKerjaStatus::RAB_SUBMITTED->value : ProgramKerjaStatus::READY_FOR_WORK->value;
            $data['estimasi_anggaran'] = $estimasiTotal;

            $program = ProgramKerja::create($data);
            $this->replaceEstimasiItems($program, $estimasiItems, $user->id);

            if ($hasEstimasi) {
                $this->rabService->createAutoFromProgramEstimasi($program->fresh('estimasiItems'), $user->id);
            }

            return $program->fresh(['estimasiItems', 'rab']);
        });
    }

    public function update(ProgramKerja $program, array $data, array $estimasiItems, User $user): ProgramKerja
    {
        return DB::transaction(function () use ($program, $data, $estimasiItems, $user) {
            $program->loadMissing('rab', 'estimasiItems');
            $hasRab = (bool) $program->rab;

            if (! $hasRab) {
                $hasEstimasi = count($estimasiItems) > 0;
                $estimasiTotal = $this->sumEstimasiItems($estimasiItems);

                $data['needs_rab'] = $hasEstimasi;
                $data['status_key'] = $hasEstimasi ? ProgramKerjaStatus::RAB_SUBMITTED->value : ProgramKerjaStatus::READY_FOR_WORK->value;
                $data['estimasi_anggaran'] = $estimasiTotal;
                $data['updated_by'] = $user->id;

                $program->update($data);
                $this->replaceEstimasiItems($program, $estimasiItems, $user->id);

                if ($hasEstimasi) {
                    $this->rabService->createAutoFromProgramEstimasi($program->fresh('estimasiItems'), $user->id);
                }
            } else {
                $rabStatus = $program->rab->statusEnum();
                $data['needs_rab'] = $rabStatus !== RabStatus::REJECTED;
                $data['status_key'] = ProgramKerjaStatus::fromRabStatus($rabStatus)->value;
                $data['estimasi_anggaran'] = $program->estimasiItems()->sum('subtotal');
                $data['updated_by'] = $user->id;
                $program->update($data);
            }

            return $program->fresh(['rab', 'estimasiItems']);
        });
    }

    public function normalizeEstimasiItems(array $items): array
    {
        return collect($items)
            ->map(fn ($item) => [
                'nama_item' => trim((string) ($item['nama_item'] ?? '')),
                'jumlah_item' => (float) ($item['jumlah_item'] ?? 0),
                'harga_satuan' => (float) ($item['harga_satuan'] ?? 0),
                'keterangan' => filled($item['keterangan'] ?? null) ? trim((string) $item['keterangan']) : null,
            ])
            ->filter(fn ($item) => $item['nama_item'] !== '' && $item['jumlah_item'] > 0)
            ->map(function ($item) {
                $item['subtotal'] = $item['jumlah_item'] * $item['harga_satuan'];
                return $item;
            })
            ->values()
            ->all();
    }

    public function sumEstimasiItems(array $items): float
    {
        return (float) collect($items)->sum('subtotal');
    }

    private function replaceEstimasiItems(ProgramKerja $program, array $items, int $userId): void
    {
        $program->estimasiItems()->update(['deleted_by' => $userId]);
        $program->estimasiItems()->delete();

        foreach ($items as $item) {
            $program->estimasiItems()->create($item + [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }
}
