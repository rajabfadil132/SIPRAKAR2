<?php

namespace App\Http\Controllers\Siprakar;

use App\Http\Controllers\Controller;
use App\Models\{Cabang, KategoriPekerjaan, ProgramKerja};
use App\Services\Siprakar\ProgramKerjaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramKerjaController extends Controller
{
    private array $statuses = ProgramKerja::STATUSES;
    private array $activeStatuses = ProgramKerja::ACTIVE_STATUSES;

    public function __construct(
        private readonly ProgramKerjaService $programKerjaService,
    ) {}

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
            'kategoris' => KategoriPekerjaan::where('status', 'active')->orderBy('nama_kategori')->get(['id', 'nama_kategori', 'keterangan']),
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

        $estimasiItems = $this->programKerjaService->normalizeEstimasiItems($request->input('estimasi_items', []));
        $hasEstimasi = count($estimasiItems) > 0;

        $this->programKerjaService->create($data, $estimasiItems, $request->user());

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

        $estimasiItems = $this->programKerjaService->normalizeEstimasiItems($request->input('estimasi_items', []));
        $program = $this->programKerjaService->update($programKerja, $data, $estimasiItems, $request->user());

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
            'kategoris' => KategoriPekerjaan::where('status', 'active')->orderBy('nama_kategori')->with(['roleRelations.role:id,nama_role,slug','roleRelations.roleCategory:id,role_id,name,slug','roleCategories:id,role_id,name'])->get(['id', 'nama_kategori', 'keterangan']),
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

    private function ensureVisible(ProgramKerja $programKerja): void
    {
        abort_unless(ProgramKerja::query()->forCurrentUser()->whereKey($programKerja->id)->exists(), 403);
    }
}
