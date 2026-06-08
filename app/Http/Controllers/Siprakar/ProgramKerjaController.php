<?php

namespace App\Http\Controllers\Siprakar;

use App\Http\Controllers\Controller;
use App\Models\{Cabang, KategoriPekerjaan, ProgramKerja, ProgramKerjaEstimasiItem, Rab};
use App\Services\SequentialCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProgramKerjaController extends Controller
{
    private array $statuses = ProgramKerja::STATUSES;
    private array $activeStatuses = ProgramKerja::ACTIVE_STATUSES;

    public function index(Request $request)
    {
        $items = ProgramKerja::query()
            ->forCurrentUser()
            ->active()
            ->with(['cabang', 'kategori', 'rab:id,program_kerja_id,status_rab,total_rab', 'estimasiItems', 'creator:id,name', 'updater:id,name', 'deleter:id,name'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search');
                $q->where(function ($sub) use ($s) {
                    $sub->where('nama_program', 'like', "%$s%")
                        ->orWhere('kode_program', 'like', "%$s%")
                        ->orWhere('tahun', 'like', "%$s%")
                        ->orWhereHas('cabang', fn ($c) => $c->where('nama_cabang', 'like', "%$s%"))
                        ->orWhereHas('kategori', fn ($c) => $c->where('nama_kategori', 'like', "%$s%"));
                });
            })
            ->when($request->filled('status') && in_array($request->status, $this->activeStatuses, true), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('kategori_id'), fn ($q) => $q->where('kategori_id', $request->kategori_id))
            ->when($request->filled('cabang_id'), fn ($q) => $q->where('cabang_id', $request->cabang_id))
            ->orderBy('created_at', $request->input('sort_dir') === 'asc' ? 'asc' : 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Siprakar/ProgramKerja/Index', [
            'items' => $items,
            'filters' => $request->only('search', 'status', 'kategori_id', 'cabang_id', 'sort_dir'),
            'permissions' => $request->user()->permissionMap(),
            'kategoris' => KategoriPekerjaan::where('status', 'active')->orderBy('nama_kategori')->get(['id', 'nama_kategori']),
            'cabangs' => Cabang::where('status', 'active')->orderBy('nama_cabang')->get(['id', 'nama_cabang']),
            'statuses' => $this->activeStatuses,
        ]);
    }

    public function create()
    {
        return Inertia::render('Siprakar/ProgramKerja/Form', array_merge($this->formData(), ['item' => null]));
    }

    public function store(Request $request)
    {
        $data = $this->validatedProgramPayload($request, true);
        unset($data['estimasi_items']);
        $estimasiItems = $this->normalizeEstimasiItems($request->input('estimasi_items', []));
        $hasEstimasi = count($estimasiItems) > 0;
        $estimasiTotal = $this->sumEstimasiItems($estimasiItems);

        $user = $request->user();
        if ($user->roleKey() !== 'superadmin') {
            $data['cabang_id'] = $user->cabang_id;
        }
        $data['tahun'] = now()->year;

        $program = DB::transaction(function () use ($data, $estimasiItems, $hasEstimasi, $estimasiTotal, $user) {
            $data['kode_program'] = app(SequentialCodeGenerator::class)->program('PROKER', $data['cabang_id']);
            $data['source_type'] = 'PROKER';
            $data['created_by'] = $user->id;
            $data['needs_rab'] = $hasEstimasi;
            $data['status'] = $hasEstimasi ? 'RAB Diajukan' : 'Siap Dijadikan Pekerjaan';
            $data['estimasi_anggaran'] = $estimasiTotal;

            $program = ProgramKerja::create($data);
            $this->replaceEstimasiItems($program, $estimasiItems, $user->id);

            if ($hasEstimasi) {
                $this->createAutoRabFromEstimasi($program->fresh('estimasiItems'), $user->id);
            }

            return $program;
        });

        return redirect()->route('program-kerja.index')->with('success', $hasEstimasi
            ? 'Program kerja berhasil dibuat. Estimasi item otomatis menjadi RAB Diajukan.'
            : 'Program kerja berhasil dibuat. Karena estimasi kosong, status menjadi Siap Dijadikan Pekerjaan.');
    }

    public function show(ProgramKerja $programKerja)
    {
        $this->ensureVisible($programKerja);

        $programKerja->load([
            'cabang', 'kategori',
            'rab.details', 'rab.reviewer:id,name',
            'estimasiItems',
            'convertedPekerjaan:id,kode_pekerjaan,nama_pekerjaan,status,progress',
            'pekerjaans' => fn ($q) => $q->withChecklistProgress(),
            'pekerjaans.petugas', 'pekerjaans.kategori',
            'creator:id,name', 'updater:id,name', 'deleter:id,name',
        ]);

        return Inertia::render('Siprakar/ProgramKerja/Show', [
            'item' => $programKerja,
            'permissions' => request()->user()->permissionMap(),
            'canBecomePekerjaan' => $programKerja->canBecomePekerjaan(),
        ]);
    }

    public function edit(ProgramKerja $programKerja)
    {
        $this->ensureVisible($programKerja);

        if ($programKerja->isConverted()) {
            if ($programKerja->converted_to_pekerjaan_id) {
                return redirect()->route('pekerjaan.show', $programKerja->converted_to_pekerjaan_id)->with('warning', 'Program kerja ini sudah dipindahkan ke Data Pekerjaan. Perubahan lanjutan dilakukan dari halaman pekerjaan.');
            }

            return redirect()->route('program-kerja.index')->with('warning', 'Program kerja ini sudah masuk status akhir dan tidak tampil di daftar aktif.');
        }

        return Inertia::render('Siprakar/ProgramKerja/Form', array_merge($this->formData(), [
            'item' => $programKerja->load(['creator:id,name', 'updater:id,name', 'estimasiItems', 'rab:id,program_kerja_id,status_rab,total_rab']),
        ]));
    }

    public function update(Request $request, ProgramKerja $programKerja)
    {
        $this->ensureVisible($programKerja);

        if ($programKerja->isConverted()) {
            if ($programKerja->converted_to_pekerjaan_id) {
                return redirect()->route('pekerjaan.show', $programKerja->converted_to_pekerjaan_id)->with('warning', 'Program kerja ini sudah dipindahkan ke Data Pekerjaan dan tidak bisa diedit dari halaman Program Kerja.');
            }

            return redirect()->route('program-kerja.index')->with('warning', 'Program kerja ini sudah masuk status akhir dan tidak bisa diedit dari daftar aktif.');
        }

        $data = $this->validatedProgramPayload($request, false);
        unset($data['estimasi_items']);
        $user = $request->user();

        $program = DB::transaction(function () use ($programKerja, $data, $request, $user) {
            $programKerja->loadMissing('rab', 'estimasiItems');
            $hasRab = (bool) $programKerja->rab;

            if (! $hasRab) {
                $estimasiItems = $this->normalizeEstimasiItems($request->input('estimasi_items', []));
                $hasEstimasi = count($estimasiItems) > 0;
                $estimasiTotal = $this->sumEstimasiItems($estimasiItems);

                $data['needs_rab'] = $hasEstimasi;
                $data['status'] = $hasEstimasi ? 'RAB Diajukan' : 'Siap Dijadikan Pekerjaan';
                $data['estimasi_anggaran'] = $estimasiTotal;
                $data['updated_by'] = $user->id;

                $programKerja->update($data);
                $this->replaceEstimasiItems($programKerja, $estimasiItems, $user->id);

                if ($hasEstimasi) {
                    $this->createAutoRabFromEstimasi($programKerja->fresh('estimasiItems'), $user->id);
                }
            } else {
                $data['needs_rab'] = $programKerja->rab->status_rab !== 'Ditolak';
                $data['status'] = $this->programStatusFromRabStatus($programKerja->rab->status_rab);
                $data['estimasi_anggaran'] = $programKerja->estimasiItems()->sum('subtotal');
                $data['updated_by'] = $user->id;
                $programKerja->update($data);
            }

            return $programKerja->fresh(['rab', 'estimasiItems']);
        });

        return redirect()->route('program-kerja.index')->with('success', $program->rab
            ? 'Program kerja diperbarui. RAB tetap mengikuti item estimasi/RAB yang sudah ada.'
            : 'Program kerja diperbarui. Status otomatis mengikuti ada/tidaknya estimasi item.');
    }

    public function destroy(ProgramKerja $programKerja)
    {
        $this->ensureVisible($programKerja);

        if ($programKerja->isConverted()) {
            if ($programKerja->converted_to_pekerjaan_id) {
                return redirect()->route('pekerjaan.show', $programKerja->converted_to_pekerjaan_id)->with('warning', 'Program kerja ini sudah dipindahkan ke Data Pekerjaan dan tidak bisa dihapus dari halaman Program Kerja.');
            }

            return redirect()->route('program-kerja.index')->with('warning', 'Program kerja ini sudah masuk status akhir dan tidak bisa dihapus dari daftar aktif.');
        }

        $programKerja->update(['deleted_by' => auth()->id()]);
        $programKerja->delete();

        return back()->with('success', 'Program kerja dihapus secara soft delete.');
    }

    private function formData(): array
    {
        return [
            'cabangs' => Cabang::where('status', 'active')->orderBy('nama_cabang')->get(),
            'kategoris' => KategoriPekerjaan::where('status', 'active')->orderBy('nama_kategori')->get(),
        ];
    }

    private function validatedProgramPayload(Request $request, bool $isCreate): array
    {
        $isSuperadmin = $request->user()?->roleKey() === 'superadmin';
        $cabangRules = $isCreate
            ? [$isSuperadmin ? 'required' : 'nullable', 'exists:cabangs,id']
            : ['sometimes', 'nullable', 'exists:cabangs,id'];

        return $request->validate([
            'nama_program' => ['required', 'string', 'max:255'],
            'cabang_id' => $cabangRules,
            'kategori_id' => ['required', 'exists:kategori_pekerjaans,id'],
            'prioritas' => ['required', 'string'],
            'target_mulai' => ['nullable', 'date'],
            'target_selesai' => ['nullable', 'date', 'after_or_equal:target_mulai'],
            'deskripsi' => ['nullable', 'string'],
            'estimasi_items' => ['nullable', 'array'],
            'estimasi_items.*.nama_item' => ['nullable', 'string', 'max:255'],
            'estimasi_items.*.jumlah_item' => ['nullable', 'numeric', 'min:0'],
            'estimasi_items.*.harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'estimasi_items.*.keterangan' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function normalizeEstimasiItems(array $items): array
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

    private function sumEstimasiItems(array $items): float
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

    private function createAutoRabFromEstimasi(ProgramKerja $program, int $userId): Rab
    {
        $program->loadMissing('estimasiItems', 'rab');

        if ($program->rab) {
            return $program->rab;
        }

        $rab = Rab::create([
            'program_kerja_id' => $program->id,
            'pekerjaan_id' => $program->converted_to_pekerjaan_id,
            'tanggal_rab' => now()->toDateString(),
            'nomor_rab' => app(SequentialCodeGenerator::class)->rab($program->cabang_id),
            'status_rab' => 'Diajukan',
            'submitted_at' => now(),
            'catatan' => 'RAB otomatis dibuat dari estimasi item Program Kerja.',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

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
        $program->update([
            'needs_rab' => true,
            'status' => 'RAB Diajukan',
            'estimasi_anggaran' => $totalRab,
            'updated_by' => $userId,
        ]);

        return $rab;
    }

    private function programStatusFromRabStatus(?string $rabStatus): string
    {
        return match ($rabStatus) {
            'Diajukan' => 'RAB Diajukan',
            'Direvisi' => 'RAB Direvisi',
            'Disetujui' => 'RAB Disetujui',
            'Ditolak' => 'Siap Dijadikan Pekerjaan',
            default => 'RAB Diajukan',
        };
    }

    private function ensureVisible(ProgramKerja $programKerja): void
    {
        abort_unless(ProgramKerja::query()->forCurrentUser()->whereKey($programKerja->id)->exists(), 403);
    }
}
