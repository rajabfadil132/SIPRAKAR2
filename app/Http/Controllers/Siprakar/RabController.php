<?php

namespace App\Http\Controllers\Siprakar;

use App\Enums\RabStatus;
use App\Http\Controllers\Controller;
use App\Models\{Cabang, KategoriPekerjaan, ProgramKerja, Rab, RabDetail};
use App\Services\Siprakar\RabService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RabController extends Controller
{
    public function __construct(
        private readonly RabService $rabService,
    ) {}

    public function index(Request $request)
    {
        $items = Rab::query()
            ->with(['programKerja.cabang', 'programKerja.kategori', 'pekerjaan.cabang', 'pekerjaan.kategori', 'creator:id,name', 'updater:id,name', 'deleter:id,name', 'reviewer:id,name'])
            ->where(function ($q) {
                $q->whereHas('programKerja', fn ($program) => $program->forCurrentUser())
                    ->orWhereHas('pekerjaan', fn ($pekerjaan) => $pekerjaan->forCurrentUser());
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search');
                $q->where(function ($sub) use ($s) {
                    $sub->where('nomor_rab', 'like', "%$s%")
                        ->orWhere('status_rab', 'like', "%$s%")
                        ->orWhereHas('programKerja', fn ($p) => $p->where('nama_program', 'like', "%$s%")
                            ->orWhere('kode_program', 'like', "%$s%")
                            ->orWhereHas('cabang', fn ($c) => $c->where('nama_cabang', 'like', "%$s%"))
                            ->orWhereHas('kategori', fn ($c) => $c->where('nama_kategori', 'like', "%$s%")))
                        ->orWhereHas('pekerjaan', fn ($p) => $p->where('nama_pekerjaan', 'like', "%$s%")
                            ->orWhere('kode_pekerjaan', 'like', "%$s%")
                            ->orWhereHas('cabang', fn ($c) => $c->where('nama_cabang', 'like', "%$s%"))
                            ->orWhereHas('kategori', fn ($c) => $c->where('nama_kategori', 'like', "%$s%")));
                });
            })
            ->when($request->filled('status_rab'), fn ($q) => $q->where('status_rab', $request->status_rab))
            ->when($request->filled('cabang_id'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->whereHas('programKerja', fn ($p) => $p->where('cabang_id', $request->cabang_id))
                        ->orWhereHas('pekerjaan', fn ($p) => $p->where('cabang_id', $request->cabang_id));
                });
            })
            ->when($request->filled('kategori_id'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->whereHas('programKerja', fn ($p) => $p->where('kategori_id', $request->kategori_id))
                        ->orWhereHas('pekerjaan', fn ($p) => $p->where('kategori_id', $request->kategori_id));
                });
            })
            ->orderBy('created_at', $request->input('sort_dir') === 'asc' ? 'asc' : 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Siprakar/Rab/Index', [
            'items' => $items,
            'filters' => $request->only('search', 'status_rab', 'cabang_id', 'kategori_id', 'sort_dir'),
            'permissions' => $request->user()->permissionMap(),
            'cabangs' => Cabang::where('status', 'active')->orderBy('nama_cabang')->get(['id', 'nama_cabang']),
            'kategoris' => KategoriPekerjaan::where('status', 'active')->orderBy('nama_kategori')->get(['id', 'nama_kategori']),
        ]);
    }

    public function create(Request $request)
    {
        $programs = $this->availableProgramsForRab();

        $selectedProgram = null;
        if ($request->program_kerja_id) {
            $selectedProgram = $programs->first(fn ($p) => (string) $p->id === (string) $request->program_kerja_id);
            if (! $selectedProgram) {
                $selectedProgram = ProgramKerja::query()
                    ->forCurrentUser()
                    ->whereKey($request->program_kerja_id)
                    ->with(['cabang:id,nama_cabang,kode', 'kategori:id,nama_kategori', 'estimasiItems'])
                    ->first();
            }
        }

        return Inertia::render('Siprakar/Rab/Form', [
            'programs' => $programs,
            'program_kerja_id' => $request->program_kerja_id,
            'selectedProgram' => $selectedProgram,
            'permissions' => $request->user()->permissionMap(),
            'itemsEditable' => true,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'program_kerja_id' => ['required', 'exists:program_kerjas,id'],
            'tanggal_rab' => ['nullable', 'date'],
            'catatan' => ['nullable'],
        ]);

        $program = ProgramKerja::query()
            ->forCurrentUser()
            ->with(['rab', 'estimasiItems'])
            ->whereKey($data['program_kerja_id'])
            ->firstOrFail();

        abort_if($program->rab()->exists(), 422, 'Program Kerja ini sudah memiliki RAB.');
        abort_if($program->converted_to_pekerjaan_id, 422, 'Program Kerja yang sudah menjadi Data Pekerjaan tidak dapat dibuatkan RAB baru.');
        abort_unless(in_array($program->status, ProgramKerja::ACTIVE_STATUSES, true), 422, 'RAB hanya bisa dibuat dari Program Kerja aktif.');

        $rab = $this->rabService->createForProgram($program, $data, $request->user());

        return redirect()->route('rab.edit', $rab)->with('success', $rab->details()->count() > 0
            ? 'RAB berhasil dibuat dan langsung berstatus Diajukan. Item sudah diisi dari estimasi program kerja.'
            : 'RAB berhasil dibuat dan langsung berstatus Diajukan. Tambahkan item RAB jika diperlukan.');
    }

    public function show(Rab $rab)
    {
        $this->ensureVisible($rab);

        $rab->load(['programKerja.cabang', 'programKerja.kategori', 'programKerja.convertedPekerjaan', 'programKerja.estimasiItems', 'pekerjaan.cabang', 'pekerjaan.kategori', 'pekerjaan.petugas', 'details.creator:id,name', 'details.updater:id,name', 'details.deleter:id,name', 'creator:id,name', 'updater:id,name', 'deleter:id,name', 'reviewer:id,name']);
        return Inertia::render('Siprakar/Rab/Show', ['item' => $rab, 'permissions' => request()->user()->permissionMap(), 'itemsEditable' => $this->rabItemsEditable($rab)]);
    }

    public function edit(Rab $rab)
    {
        $this->ensureVisible($rab);

        $rab->load(['programKerja.cabang', 'programKerja.kategori', 'programKerja.estimasiItems', 'pekerjaan', 'details.creator:id,name', 'details.updater:id,name', 'details.deleter:id,name', 'creator:id,name', 'updater:id,name', 'deleter:id,name', 'reviewer:id,name']);
        return Inertia::render('Siprakar/Rab/Form', [
            'item' => $rab,
            'programs' => collect([$rab->programKerja])->filter()->values(),
            'program_kerja_id' => $rab->program_kerja_id,
            'itemsEditable' => $this->rabItemsEditable($rab),
            'permissions' => request()->user()->permissionMap(),
        ]);
    }

    public function update(Request $request, Rab $rab)
    {
        $this->ensureVisible($rab);

        $data = $request->validate([
            'tanggal_rab' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
        ]);
        $data['tanggal_rab'] = $data['tanggal_rab'] ?? optional($rab->tanggal_rab)->toDateString() ?? now()->toDateString();
        $data['updated_by'] = $request->user()->id;

        DB::transaction(fn () => $rab->update($data));

        return back()->with('success', 'RAB diperbarui. Gunakan tombol review untuk menyetujui, meminta revisi, atau menolak RAB.');
    }

    public function submit(Request $request, Rab $rab)
    {
        $this->ensureVisible($rab);
        abort_unless($rab->statusEnum() === RabStatus::REVISION, 422, 'RAB hanya perlu diajukan ulang dari status Direvisi.');
        abort_if($rab->details()->count() < 1, 422, 'Tambahkan minimal satu item sebelum mengajukan RAB.');

        $this->rabService->changeStatus($rab, RabStatus::SUBMITTED, $request->user()->id, [
            'submitted_at' => now(),
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);

        return back()->with('success', 'RAB berhasil diajukan untuk persetujuan. Item RAB otomatis dikunci.');
    }

    public function approve(Request $request, Rab $rab)
    {
        $this->ensureVisible($rab);
        abort_unless($request->user()->hasPermission('rab.edit'), 403);
        abort_unless(in_array($rab->statusEnum()->value, RabStatus::editableKeys(), true), 422, 'RAB hanya bisa disetujui dari status Diajukan atau Direvisi.');
        abort_if($rab->details()->count() < 1, 422, 'RAB harus memiliki minimal satu item sebelum disetujui.');

        $this->rabService->changeStatus($rab, RabStatus::APPROVED, $request->user()->id, [
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'RAB disetujui. Program Kerja sekarang dapat dijadikan Data Pekerjaan.');
    }

    public function revise(Request $request, Rab $rab)
    {
        $this->ensureVisible($rab);
        abort_unless($request->user()->hasPermission('rab.edit'), 403);
        abort_unless(in_array($rab->statusEnum()->value, RabStatus::editableKeys(), true), 422, 'Revisi hanya bisa diminta dari status Diajukan atau Direvisi.');

        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:1000']]);
        $this->rabService->changeStatus($rab, RabStatus::REVISION, $request->user()->id, [
            'catatan' => $data['catatan'] ?? $rab->catatan,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'RAB dikembalikan untuk revisi. Item RAB dapat diedit kembali.');
    }

    public function reject(Request $request, Rab $rab)
    {
        $this->ensureVisible($rab);
        abort_unless($request->user()->hasPermission('rab.edit'), 403);
        abort_unless(in_array($rab->statusEnum()->value, RabStatus::editableKeys(), true), 422, 'RAB hanya bisa ditolak dari status Diajukan atau Direvisi.');

        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:1000']]);
        $this->rabService->changeStatus($rab, RabStatus::REJECTED, $request->user()->id, [
            'catatan' => $data['catatan'] ?? $rab->catatan,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'RAB ditolak. Program Kerja diubah menjadi Siap Dijadikan Pekerjaan dan RAB ditandai tidak perlu.');
    }

    public function destroy(Rab $rab)
    {
        $this->ensureVisible($rab);

        DB::transaction(function () use ($rab) {
            $rab->loadMissing(['programKerja', 'pekerjaan']);
            $program = $rab->programKerja;
            $pekerjaan = $rab->pekerjaan;

            $rab->details()->update(['deleted_by' => auth()->id()]);
            $rab->details()->delete();
            $rab->update(['deleted_by' => auth()->id()]);
            $rab->delete();

            $pekerjaan?->update(['is_rab' => false, 'updated_by' => auth()->id()]);
            if ($program && ! $program->converted_to_pekerjaan_id) {
                $program->update(['status_key' => \App\Enums\ProgramKerjaStatus::RAB_SUBMITTED->value, 'updated_by' => auth()->id()]);
            }
        });

        return back()->with('success', 'RAB dihapus. Program Kerja kembali ke status RAB Diajukan jika belum menjadi pekerjaan.');
    }

    public function storeItem(Request $request, Rab $rab)
    {
        $this->ensureVisible($rab);
        $this->ensureRabItemsEditable($rab);

        $data = $request->validate(['nama_item' => ['required', 'string', 'max:255'], 'jumlah_item' => ['required', 'numeric', 'min:0'], 'harga_satuan' => ['required', 'numeric', 'min:0'], 'keterangan' => ['nullable', 'string', 'max:500']]);

        $this->rabService->createItem($rab, $data, $request->user()->id);

        return back()->with('success', 'Item RAB ditambahkan.');
    }

    public function updateItem(Request $request, RabDetail $detail)
    {
        $this->ensureDetailVisible($detail);
        $this->ensureRabItemsEditable($detail->rab);

        $data = $request->validate(['nama_item' => ['required', 'string', 'max:255'], 'jumlah_item' => ['required', 'numeric', 'min:0'], 'harga_satuan' => ['required', 'numeric', 'min:0'], 'keterangan' => ['nullable', 'string', 'max:500']]);

        $this->rabService->updateItem($detail, $data, $request->user()->id);

        return back()->with('success', 'Item RAB diperbarui.');
    }

    public function destroyItem(Request $request, RabDetail $detail)
    {
        $this->ensureDetailVisible($detail);
        $this->ensureRabItemsEditable($detail->rab);

        $this->rabService->deleteItem($detail, $request->user()->id);

        return back()->with('success', 'Item RAB dihapus.');
    }

    private function availableProgramsForRab()
    {
        return ProgramKerja::query()
            ->forCurrentUser()
            ->active()
            ->where('needs_rab', true)
            ->doesntHave('rab')
            ->with(['cabang:id,nama_cabang,kode', 'kategori:id,nama_kategori', 'estimasiItems'])
            ->orderByDesc('created_at')
            ->get();
    }

    private function ensureVisible(Rab $rab): void
    {
        abort_unless(Rab::query()
            ->whereKey($rab->id)
            ->where(function ($q) {
                $q->whereHas('programKerja', fn ($program) => $program->forCurrentUser())
                    ->orWhereHas('pekerjaan', fn ($pekerjaan) => $pekerjaan->forCurrentUser());
            })
            ->exists(), 403);
    }

    private function ensureDetailVisible(RabDetail $detail): void
    {
        $detail->loadMissing('rab.programKerja', 'rab.pekerjaan');
        abort_unless($detail->rab && Rab::query()
            ->whereKey($detail->rab_id)
            ->where(function ($q) {
                $q->whereHas('programKerja', fn ($program) => $program->forCurrentUser())
                    ->orWhereHas('pekerjaan', fn ($pekerjaan) => $pekerjaan->forCurrentUser());
            })
            ->exists(), 403);
    }

    private function rabItemsEditable(Rab $rab): bool
    {
        return $this->rabService->itemsEditable($rab);
    }

    private function ensureRabItemsEditable(Rab $rab): void
    {
        $this->rabService->ensureItemsEditable($rab);
    }

}
