<?php

namespace App\Http\Controllers\Siprakar\ProgramKerja;

use App\Http\Controllers\Controller;
use App\Models\ProgramKerja;
use App\Services\Pekerjaan\PekerjaanService;
use Illuminate\Http\Request;

class ConvertToPekerjaanController extends Controller
{
    public function __construct(
        private readonly PekerjaanService $pekerjaanService,
    ) {}

    public function __invoke(Request $request, ProgramKerja $programKerja)
    {
        abort_unless(
            ProgramKerja::query()->forCurrentUser()->whereKey($programKerja->id)->exists(),
            403
        );

        abort_unless($request->user()->hasPermission('pekerjaan.create'), 403);

        if ($programKerja->converted_to_pekerjaan_id) {
            return redirect()->route('pekerjaan.show', $programKerja->converted_to_pekerjaan_id)
                ->with('warning', 'Program kerja ini sudah dipindahkan ke Data Pekerjaan.');
        }

        if (! $programKerja->canBecomePekerjaan()) {
            if ($programKerja->needs_rab && $programKerja->status !== 'RAB Disetujui') {
                return redirect()->route('program-kerja.show', $programKerja->id)
                    ->with('warning', 'Program kerja perlu RAB dengan status Disetujui sebelum bisa dijadikan Data Pekerjaan. Silakan buat dan setujui RAB terlebih dahulu.');
            }

            return redirect()->route('program-kerja.show', $programKerja->id)
                ->with('warning', 'Program kerja tidak memenuhi syarat untuk dijadikan Data Pekerjaan.');
        }

        $data = $request->validate([
            'petugas_id' => ['nullable', 'exists:users,id'],
            'penanggung_jawab_id' => ['nullable', 'exists:users,id'],
            'tanggal_mulai' => ['nullable', 'date'],
            'target_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'keterangan' => ['nullable', 'string'],
            'checklists' => ['nullable', 'array'],
            'checklists.*' => ['nullable', 'string', 'max:255'],
        ]);

        $data['checklists'] = array_values(array_filter($data['checklists'] ?? [
            'Survei lokasi dan validasi kebutuhan',
            'Pelaksanaan pekerjaan',
            'Pemeriksaan akhir dan dokumentasi',
        ]));

        $pekerjaan = $this->pekerjaanService->createFromProgramKerja($programKerja, $data, $request->user());

        return redirect()->route('pekerjaan.show', $pekerjaan)
            ->with('success', 'Program kerja berhasil dijadikan Data Pekerjaan. Assign petugas dan mulai kerjakan.');
    }
}
