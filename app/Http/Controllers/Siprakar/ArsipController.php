<?php

namespace App\Http\Controllers\Siprakar;

use App\Http\Controllers\Controller;
use App\Models\{KategoriPekerjaan, Pekerjaan, ProgramKerja, Rab};
use App\Services\Pekerjaan\PekerjaanService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArsipController extends Controller
{
    public function __construct(
        private readonly PekerjaanService $pekerjaanService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->input('tab', 'program-kerja');

        $filterData = [
            'kategoris' => KategoriPekerjaan::where('status', 'active')->orderBy('nama_kategori')->get(['id', 'nama_kategori', 'keterangan']),
            'cabangs' => $user->roleKey() === 'superadmin'
                ? \App\Models\Cabang::where('status', 'active')->orderBy('nama_cabang')->get(['id', 'nama_cabang'])
                : collect(),
        ];

        $initialData = $this->getInitialTabData($tab, $request);

        return Inertia::render('Siprakar/Arsip/Index', array_merge($filterData, $initialData, [
            'permissions' => $user->permissionMap(),
            'activeTab' => $tab,
        ]));
    }

    private function getInitialTabData(string $tab, Request $request)
    {
        $user = $request->user();

        if ($tab === 'program-kerja') {
            $query = ProgramKerja::onlyTrashed()
                ->forCurrentUser()
                ->with(['cabang:id,nama_cabang', 'kategori:id,nama_kategori', 'creator:id,name', 'deleter:id,name']);

            $this->applyProgramKerjaFilters($query, $request);
            $query->latest('deleted_at');

            return ['items' => $query->paginate(10)->withQueryString()];
        }

        if ($tab === 'pekerjaan') {
            $query = Pekerjaan::onlyTrashed()
                ->forCurrentUser()
                ->with([
                    'programKerja:id,kode_program,nama_program',
                    'cabang:id,nama_cabang',
                    'kategori:id,nama_kategori',
                    'creator:id,name',
                    'deleter:id,name',
                ]);

            $this->applyPekerjaanFilters($query, $request);
            $query->latest('deleted_at');

            return ['items' => $query->paginate(10)->withQueryString()];
        }

        $query = Rab::onlyTrashed()
            ->where(function ($q) use ($user) {
                $q->whereHas('programKerja', fn ($p) => $p->forCurrentUser())
                    ->orWhereHas('pekerjaan', fn ($p) => $p->forCurrentUser());
            })
            ->with([
                'programKerja:id,kode_program,nama_program,cabang_id',
                'programKerja.cabang:id,nama_cabang',
                'pekerjaan:id,kode_pekerjaan,nama_pekerjaan,cabang_id',
                'pekerjaan.cabang:id,nama_cabang',
                'creator:id,name',
                'deleter:id,name',
            ]);

        $this->applyRabFilters($query, $request);
        $query->latest('deleted_at');

        return ['items' => $query->paginate(10)->withQueryString()];
    }

    public function programKerja(Request $request)
    {
        $query = ProgramKerja::onlyTrashed()
            ->forCurrentUser()
            ->with(['cabang:id,nama_cabang', 'kategori:id,nama_kategori', 'creator:id,name', 'deleter:id,name']);

        $this->applyProgramKerjaFilters($query, $request);
        $query->latest('deleted_at');

        return response()->json([
            'items' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only('search', 'cabang_id', 'kategori_id'),
        ]);
    }

    public function pekerjaan(Request $request)
    {
        $query = Pekerjaan::onlyTrashed()
            ->forCurrentUser()
            ->with([
                'programKerja:id,kode_program,nama_program',
                'cabang:id,nama_cabang',
                'kategori:id,nama_kategori',
                'creator:id,name',
                'deleter:id,name',
            ]);

        $this->applyPekerjaanFilters($query, $request);
        $query->latest('deleted_at');

        return response()->json([
            'items' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only('search', 'cabang_id', 'kategori_id'),
        ]);
    }

    public function rab(Request $request)
    {
        $user = $request->user();

        $query = Rab::onlyTrashed()
            ->where(function ($q) use ($user) {
                $q->whereHas('programKerja', fn ($p) => $p->forCurrentUser())
                    ->orWhereHas('pekerjaan', fn ($p) => $p->forCurrentUser());
            })
            ->with([
                'programKerja:id,kode_program,nama_program,cabang_id',
                'programKerja.cabang:id,nama_cabang',
                'pekerjaan:id,kode_pekerjaan,nama_pekerjaan,cabang_id',
                'pekerjaan.cabang:id,nama_cabang',
                'creator:id,name',
                'deleter:id,name',
            ]);

        $this->applyRabFilters($query, $request);
        $query->latest('deleted_at');

        return response()->json([
            'items' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only('search', 'cabang_id'),
        ]);
    }

    public function restoreProgramKerja(Request $request, int $id)
    {
        $program = ProgramKerja::onlyTrashed()->whereKey($id)->firstOrFail();
        abort_unless(ProgramKerja::withTrashed()->forCurrentUser()->whereKey($id)->exists(), 403);

        $program->restore();
        $program->update(['deleted_by' => null]);

        return back()->with('success', 'Program kerja dipulihkan dari arsip.');
    }

    public function forceDestroyProgramKerja(Request $request, int $id)
    {
        $program = ProgramKerja::withTrashed()->whereKey($id)->firstOrFail();
        abort_unless(ProgramKerja::withTrashed()->forCurrentUser()->whereKey($id)->exists(), 403);

        $program->forceDelete();

        return back()->with('success', 'Program kerja dihapus permanen.');
    }

    public function restorePekerjaan(Request $request, int $id)
    {
        $pekerjaan = Pekerjaan::onlyTrashed()->whereKey($id)->firstOrFail();
        abort_unless(Pekerjaan::withTrashed()->forCurrentUser()->whereKey($id)->exists(), 403);

        $this->pekerjaanService->restore($pekerjaan, $request->user()->id);

        return back()->with('success', 'Pekerjaan dipulihkan dari arsip.');
    }

    public function forceDestroyPekerjaan(Request $request, int $id)
    {
        $pekerjaan = Pekerjaan::withTrashed()->whereKey($id)->firstOrFail();
        abort_unless(Pekerjaan::withTrashed()->forCurrentUser()->whereKey($id)->exists(), 403);

        $this->pekerjaanService->forceDelete($pekerjaan);

        return back()->with('success', 'Pekerjaan dihapus permanen.');
    }

    public function restoreRab(Request $request, int $id)
    {
        $user = $request->user();

        $rab = Rab::onlyTrashed()->whereKey($id)->firstOrFail();
        abort_unless(Rab::withTrashed()->whereKey($id)->where(function ($q) use ($user) {
            $q->whereHas('programKerja', fn ($p) => $p->forCurrentUser())
                ->orWhereHas('pekerjaan', fn ($p) => $p->forCurrentUser());
        })->exists(), 403);

        $rab->restore();
        $rab->details()->restore();
        $rab->update(['deleted_by' => null]);

        return back()->with('success', 'RAB dipulihkan dari arsip.');
    }

    public function forceDestroyRab(Request $request, int $id)
    {
        $user = $request->user();

        $rab = Rab::withTrashed()->whereKey($id)->firstOrFail();
        abort_unless(Rab::withTrashed()->whereKey($id)->where(function ($q) use ($user) {
            $q->whereHas('programKerja', fn ($p) => $p->forCurrentUser())
                ->orWhereHas('pekerjaan', fn ($p) => $p->forCurrentUser());
        })->exists(), 403);

        $rab->details()->forceDelete();
        $rab->forceDelete();

        return back()->with('success', 'RAB dihapus permanen.');
    }

    private function applyProgramKerjaFilters($query, Request $request): void
    {
        $query
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim((string) $request->search);
                $q->where(function ($sub) use ($s) {
                    $sub->where('nama_program', 'like', "%{$s}%")
                        ->orWhere('kode_program', 'like', "%{$s}%")
                        ->orWhereHas('cabang', fn ($c) => $c->where('nama_cabang', 'like', "%{$s}%"))
                        ->orWhereHas('kategori', fn ($c) => $c->where('nama_kategori', 'like', "%{$s}%"));
                });
            })
            ->when($request->filled('cabang_id'), fn ($q) => $q->where('cabang_id', $request->cabang_id))
            ->when($request->filled('kategori_id'), fn ($q) => $q->where('kategori_id', $request->kategori_id));
    }

    private function applyPekerjaanFilters($query, Request $request): void
    {
        $query
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim((string) $request->search);
                $q->where(function ($sub) use ($s) {
                    $sub->where('nama_pekerjaan', 'like', "%{$s}%")
                        ->orWhere('kode_pekerjaan', 'like', "%{$s}%")
                        ->orWhereHas('programKerja', fn ($c) => $c->where('nama_program', 'like', "%{$s}%"))
                        ->orWhereHas('cabang', fn ($c) => $c->where('nama_cabang', 'like', "%{$s}%"))
                        ->orWhereHas('kategori', fn ($c) => $c->where('nama_kategori', 'like', "%{$s}%"));
                });
            })
            ->when($request->filled('cabang_id'), fn ($q) => $q->where('cabang_id', $request->cabang_id))
            ->when($request->filled('kategori_id'), fn ($q) => $q->where('kategori_id', $request->kategori_id));
    }

    private function applyRabFilters($query, Request $request): void
    {
        $query
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim((string) $request->search);
                $q->where(function ($sub) use ($s) {
                    $sub->where('nomor_rab', 'like', "%{$s}%")
                        ->orWhereHas('programKerja', fn ($p) => $p->where('nama_program', 'like', "%{$s}%"))
                        ->orWhereHas('pekerjaan', fn ($p) => $p->where('nama_pekerjaan', 'like', "%{$s}%"));
                });
            })
            ->when($request->filled('cabang_id'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->whereHas('programKerja', fn ($p) => $p->where('cabang_id', $request->cabang_id))
                        ->orWhereHas('pekerjaan', fn ($p) => $p->where('cabang_id', $request->cabang_id));
                });
            });
    }
}