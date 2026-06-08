<?php

namespace App\Http\Controllers\Siprakar;

use App\Http\Controllers\Controller;
use App\Models\{Cabang, Gedung, KategoriPekerjaan, Lantai, Pekerjaan, PekerjaanChecklist, ProgramKerja, Role, Ruang, User};
use App\Services\AppNotificationService;
use App\Services\Pekerjaan\PekerjaanService;
use App\Services\SequentialCodeGenerator;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PekerjaanController extends Controller
{
    private array $statuses = ['Belum Diproses', 'Diproses', 'Selesai', 'Dibatalkan'];
    private array $editableStatuses = ['Belum Diproses', 'Diproses', 'Dibatalkan'];
    private array $legacyStatuses = ['Pending', 'Belum dilaksanakan', 'Sedang berjalan', 'Berjalan', 'Tertunda'];

    public function __construct(
        private readonly PekerjaanService $pekerjaanService,
    ) {}

    public function index(Request $request)
    {
        $query = Pekerjaan::query()
            ->forCurrentUser()
            ->withChecklistProgress()
            ->with($this->listRelations($request));

        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        return Inertia::render('Siprakar/Pekerjaan/Index', array_merge($this->listFilterData($request), [
            'items' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only('search', 'status', 'kategori_id', 'petugas_id', 'cabang_id', 'sort_by', 'sort_dir'),
            'permissions' => $request->user()->permissionMap(),
        ]));
    }

    public function tasks(Request $request)
    {
        $query = Pekerjaan::query()
            ->assignedToUser($request->user())
            ->withChecklistProgress()
            ->with($this->listRelations($request));

        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        return Inertia::render('Siprakar/Pekerjaan/Index', array_merge($this->listFilterData($request), [
            'items' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only('search', 'status', 'kategori_id', 'petugas_id', 'cabang_id', 'sort_by', 'sort_dir'),
            'permissions' => array_replace($request->user()->permissionMap(), [
                'pekerjaan.show' => true,
                'pekerjaan.progress' => true,
            ]),
            'title' => 'Tugas Saya',
            'description' => 'Daftar pekerjaan SIPRAKAR yang ditugaskan ke akun Anda. Anda dapat membuka detail dan memperbarui checklist penugasan.',
            'basePath' => '/tugas-saya',
            'isMyTasks' => true,
        ]));
    }

    public function archive(Request $request)
    {
        $query = Pekerjaan::onlyTrashed()
            ->forCurrentUser()
            ->withChecklistProgress()
            ->with($this->listRelations($request));

        $this->applyFilters($query, $request);
        $query->latest('deleted_at');

        return Inertia::render('Siprakar/Pekerjaan/Index', array_merge($this->listFilterData($request), [
            'items' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only('search', 'status', 'kategori_id', 'petugas_id', 'cabang_id', 'sort_by', 'sort_dir'),
            'permissions' => $request->user()->permissionMap(),
            'title' => 'Arsip Pekerjaan',
            'description' => 'Data pekerjaan yang sudah dihapus secara soft delete. Data masih tersimpan untuk audit dan pelacakan.',
            'basePath' => '/pekerjaan/archive',
            'isArchive' => true,
        ]));
    }

    public function create(Request $request)
    {
        return Inertia::render('Siprakar/Pekerjaan/Form', array_merge($this->formData(), [
            'selectedProgramId' => $request->input('program_kerja_id'),
        ]));
    }

    public function store(Request $request)
    {
        $data = $this->validatedPekerjaan($request);
        $data['status'] = $this->normalizeStatus($data['status'] ?? 'Belum Diproses');
        $this->guardManualFinishedStatus($data['status'] ?? null);
        $user = $request->user();

        $pekerjaan = $this->pekerjaanService->create($data, $user);
        app(AppNotificationService::class)->pekerjaanCreated($pekerjaan);

        return redirect()->route('pekerjaan.index')
            ->with('success', 'Pekerjaan berhasil dibuat. Tambahkan anggaran dari tombol aksi jika pekerjaan memerlukan rincian biaya.');
    }

    public function show(Request $request, Pekerjaan $pekerjaan)
    {
        $this->ensureVisible($pekerjaan);

        $relations = [
            'programKerja', 'cabang', 'lokasi.lantaiMaster.gedung.cabang', 'kategori', 'petugas', 'penanggungJawab',
            'petugasTambahan.user.role', 'petugasTambahan.user.roleCategory', 'assignedUsers.role', 'assignedUsers.roleCategory',
            'progressLogs.updater:id,name',
            'checklists.creator:id,name', 'checklists.updater:id,name', 'checklists.deleter:id,name', 'checklists.completer:id,name',
            'creator:id,name', 'updater:id,name', 'deleter:id,name',
        ];
        if ($this->canUseRab($request)) {
            $relations = array_merge($relations, [
                'rab.details.creator:id,name', 'rab.details.updater:id,name', 'rab.details.deleter:id,name',
                'rab.creator:id,name', 'rab.updater:id,name', 'rab.deleter:id,name',
            ]);
        }

        $pekerjaan->load($relations);

        return Inertia::render('Siprakar/Pekerjaan/Show', [
            'item' => $pekerjaan,
            'permissions' => $request->user()->permissionMap(),
            'canUpdateAssignment' => $this->canUpdateAssignment($pekerjaan, $request->user()),
            'canUpdateChecklist' => $this->canUpdateAssignment($pekerjaan, $request->user()) && $this->canChecklistBeUpdated($pekerjaan),
            'rabBlockMessage' => $this->rabBlockMessage($pekerjaan),
        ]);
    }

    public function edit(Pekerjaan $pekerjaan)
    {
        $this->ensureVisible($pekerjaan);

        $pekerjaan->load(['creator:id,name', 'updater:id,name', 'checklists', 'rab', 'petugasTambahan.user.role', 'petugasTambahan.user.roleCategory', 'assignedUsers.role', 'assignedUsers.roleCategory']);
        return Inertia::render('Siprakar/Pekerjaan/Form', array_merge($this->formData($pekerjaan), ['item' => $pekerjaan]));
    }

    public function update(Request $request, Pekerjaan $pekerjaan)
    {
        $this->ensureVisible($pekerjaan);

        $previousStatus = $pekerjaan->status;
        $data = $this->validatedPekerjaan($request, true);
        $data['status'] = $this->normalizeStatus($data['status'] ?? $pekerjaan->status);
        $this->guardManualFinishedStatus($data['status'] ?? null, $pekerjaan);

        $this->pekerjaanService->update($pekerjaan, $data, $request->user());

        $pekerjaan->refresh();
        $message = $previousStatus !== $pekerjaan->status
            ? "Status pekerjaan berhasil diubah menjadi {$pekerjaan->status}."
            : 'Pekerjaan diperbarui.';

        return redirect()->route('pekerjaan.show', $pekerjaan)->with('success', $message);
    }

    public function destroy(Request $request, Pekerjaan $pekerjaan)
    {
        $this->ensureVisible($pekerjaan);

        $data = $request->validate([
            'delete_reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->pekerjaanService->softDelete($pekerjaan, $data['delete_reason'], $request->user()->id);

        return redirect()->route('pekerjaan.index')->with('success', 'Pekerjaan dihapus ke arsip beserta alasan penghapusan.');
    }

    public function restore(Request $request, int $id)
    {
        $pekerjaan = Pekerjaan::onlyTrashed()->whereKey($id)->firstOrFail();
        abort_unless(Pekerjaan::withTrashed()->forCurrentUser()->whereKey($id)->exists(), 403);

        $this->pekerjaanService->restore($pekerjaan, $request->user()->id);

        return redirect()->route('pekerjaan.show', $pekerjaan)->with('success', 'Pekerjaan berhasil dipulihkan dari arsip.');
    }

    public function forceDestroy(Request $request, int $id)
    {
        $pekerjaan = Pekerjaan::withTrashed()->whereKey($id)->firstOrFail();
        abort_unless(Pekerjaan::withTrashed()->forCurrentUser()->whereKey($id)->exists(), 403);

        $this->pekerjaanService->forceDelete($pekerjaan);

        return redirect()->route('pekerjaan.archive')->with('success', 'Pekerjaan dihapus permanen.');
    }

    public function storeProgress(Request $request, Pekerjaan $pekerjaan)
    {
        $this->ensureVisible($pekerjaan);

        return back()->with('warning', 'Progress manual dinonaktifkan. Gunakan checklist detail pekerjaan untuk memperbarui progress.');
    }

    public function toggleChecklist(Request $request, Pekerjaan $pekerjaan, PekerjaanChecklist $checklist)
    {
        $this->ensureVisible($pekerjaan);
        abort_unless($this->canUpdateAssignment($pekerjaan, $request->user()), 403);
        abort_unless($checklist->pekerjaan_id === $pekerjaan->id, 404);
        if (! $this->canChecklistBeUpdated($pekerjaan)) {
            throw ValidationException::withMessages([
                'rab' => $this->rabBlockMessage($pekerjaan) ?: 'Checklist belum dapat diperbarui.',
            ]);
        }

        $data = $request->validate(['is_done' => ['required', 'boolean']]);

        $this->pekerjaanService->toggleChecklist($checklist, $data['is_done'], $request->user()->id);

        $pekerjaan->refresh();
        $state = $data['is_done'] ? 'selesai' : 'dibuka kembali';

        return back()->with('success', "Checklist progress {$state}. Progress pekerjaan sekarang {$pekerjaan->progress}%.");
    }

    private function listRelations(Request $request): array
    {
        $relations = ['programKerja', 'cabang', 'lokasi.lantaiMaster.gedung.cabang', 'kategori', 'petugas', 'petugasTambahan.user.role', 'petugasTambahan.user.roleCategory', 'assignedUsers.role', 'assignedUsers.roleCategory', 'creator:id,name', 'updater:id,name', 'deleter:id,name'];
        if ($this->canUseRab($request)) {
            $relations[] = 'rab';
        }
        return $relations;
    }

    private function listFilterData(Request $request): array
    {
        return [
            'kategoris' => KategoriPekerjaan::where('status', 'active')->orderBy('nama_kategori')->get(['id', 'nama_kategori']),
            'cabangs' => Cabang::where('status', 'active')->orderBy('nama_cabang')->get(['id', 'nama_cabang']),
            'users' => $this->assignableUsersFor($request->user()),
            'statuses' => $this->statuses,
        ];
    }

    private function applyFilters($query, Request $request): void
    {
        $query
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim((string) $request->search);
                $q->where(function ($sub) use ($s) {
                    $sub->where('nama_pekerjaan', 'like', "%{$s}%")
                        ->orWhere('kode_pekerjaan', 'like', "%{$s}%")
                        ->orWhereHas('programKerja', fn ($c) => $c->where('nama_program', 'like', "%{$s}%"))
                        ->orWhere('nama_gedung', 'like', "%{$s}%")
                        ->orWhere('nama_lantai', 'like', "%{$s}%")
                        ->orWhere('nama_ruang', 'like', "%{$s}%")
                        ->orWhere('no_ruang', 'like', "%{$s}%")
                        ->orWhere('location_text', 'like', "%{$s}%")
                        ->orWhereHas('cabang', fn ($c) => $c->where('nama_cabang', 'like', "%{$s}%"))
                        ->orWhereHas('kategori', fn ($c) => $c->where('nama_kategori', 'like', "%{$s}%"))
                        ->orWhereHas('petugas', fn ($c) => $c->where('name', 'like', "%{$s}%"))
                        ->orWhereHas('petugasTambahan.user', fn ($c) => $c->where('name', 'like', "%{$s}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('kategori_id'), fn ($q) => $q->where('kategori_id', $request->kategori_id))
            ->when($request->filled('petugas_id'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('petugas_id', $request->petugas_id)
                        ->orWhere('penanggung_jawab_id', $request->petugas_id)
                        ->orWhereHas('petugasTambahan', fn ($assignment) => $assignment->where('user_id', $request->petugas_id));
                });
            })
            ->when($request->filled('cabang_id'), fn ($q) => $q->where('cabang_id', $request->cabang_id));
    }

    private function applySort($query, Request $request): void
    {
        $sortBy = in_array($request->input('sort_by'), ['created_at', 'target_selesai', 'tanggal_mulai', 'nama_pekerjaan', 'progress'], true)
            ? $request->input('sort_by')
            : 'created_at';
        $direction = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $direction)->orderBy('id', $direction);
    }

    private function validatedPekerjaan(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'program_kerja_id' => [$updating ? 'nullable' : 'required', 'exists:program_kerjas,id'],
            'nama_pekerjaan' => [$updating ? 'required' : 'nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'cabang_id' => ['nullable', 'exists:cabangs,id'],
            'lokasi_id' => ['nullable', 'exists:ruangs,id'],
            'nama_gedung' => ['nullable', 'string', 'max:150'],
            'nama_lantai' => ['nullable', 'string', 'max:100'],
            'nama_ruang' => ['nullable', 'string', 'max:100'],
            'lantai' => ['nullable', 'integer', 'min:0', 'max:200'],
            'no_ruang' => ['nullable', 'string', 'max:30'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'kategori_id' => ['nullable', 'exists:kategori_pekerjaans,id'],
            'prioritas' => ['nullable', 'string'],
            'penanggung_jawab_id' => ['nullable', 'exists:users,id'],
            'petugas_id' => ['nullable', 'exists:users,id'],
            'tanggal_mulai' => ['nullable', 'date'],
            'target_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['required', 'string', 'in:'.implode(',', array_merge($this->statuses, $this->legacyStatuses))],
            'estimasi_rab_awal' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
            'checklists' => ['nullable', 'array'],
            'checklists.*' => ['nullable', 'string', 'max:255'],
            'assignees' => ['nullable', 'array'],
            'assignees.*.role_text' => ['nullable', 'string', 'max:100'],
            'assignees.*.user_id' => ['nullable', 'exists:users,id'],
            'assignees.*.nama_petugas_manual' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function formData(?Pekerjaan $current = null): array
    {
        return [
            'programs' => ProgramKerja::forCurrentUser()
                ->availableForPekerjaan($current?->program_kerja_id)
                ->where(function ($q) use ($current) {
                    $q->where(function ($ready) {
                        $ready->where('needs_rab', false)->where('status', 'Siap Dijadikan Pekerjaan');
                    })->orWhere(function ($ready) {
                        $ready->where('needs_rab', true)
                            ->where('status', 'RAB Disetujui')
                            ->whereHas('rab', fn ($rab) => $rab->where('status_rab', 'Disetujui'));
                    });
                    if ($current?->program_kerja_id) {
                        $q->orWhere($q->getModel()->getQualifiedKeyName(), $current->program_kerja_id);
                    }
                })
                ->with(['cabang:id,nama_cabang,kode', 'kategori:id,nama_kategori', 'rab:id,program_kerja_id,status_rab,total_rab'])
                ->orderByDesc('created_at')
                ->get(),
            'cabangs' => Cabang::where('status', 'active')->get(),
            'gedungs' => Gedung::where('status', 'active')->with(['cabang:id,nama_cabang'])->orderBy('nama_gedung')->get(),
            'lantais' => Lantai::where('status', 'active')->with(['gedung.cabang:id,nama_cabang'])->orderBy('nomor_lantai')->get(),
            'ruangs' => Ruang::where('status', 'active')->with(['lantaiMaster.gedung.cabang'])->get(),
            'kategoris' => KategoriPekerjaan::where('status', 'active')->get(),
            'roles' => Role::active()->with('activeCategories')->orderBy('nama_role')->get(['id', 'nama_role', 'slug']),
            'users' => $this->assignableUsersFor(auth()->user()),
            'statuses' => $this->statuses,
            'editableStatuses' => $this->editableStatuses,
        ];
    }

    private function assignableUsersFor(?User $viewer)
    {
        return User::query()
            ->where('status', 'active')
            ->when($viewer?->roleKey() !== 'superadmin', fn ($q) => $q->where('cabang_id', $viewer?->cabang_id))
            ->with(['role.permission', 'roleCategory:id,name,role_id,is_active', 'cabang:id,nama_cabang'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role_id', 'role_category_id', 'cabang_id', 'status'])
            ->filter(fn (User $user) => $user->canReceiveWorkAssignment())
            ->values();
    }

    private function canUseRab(Request $request): bool
    {
        $user = $request->user();
        return $user->hasPermission('rab.view') || $user->hasPermission('rab.create') || $user->hasPermission('rab.edit');
    }

    private function generateKode(string $prefix, mixed $cabangId): string
    {
        return app(SequentialCodeGenerator::class)->pekerjaan($prefix, $cabangId);
    }

    private function ensureVisible(Pekerjaan $pekerjaan): void
    {
        $user = auth()->user();
        if ($pekerjaan->isAssignedTo($user)) {
            return;
        }
        abort_unless(Pekerjaan::query()->forCurrentUser()->whereKey($pekerjaan->id)->exists(), 403);
    }

    private function canUpdateAssignment(Pekerjaan $pekerjaan, ?User $user): bool
    {
        return (bool) $user && ($user->hasPermission('pekerjaan.progress') || $pekerjaan->isAssignedTo($user));
    }

    private function canChecklistBeUpdated(Pekerjaan $pekerjaan): bool
    {
        $pekerjaan->loadMissing('rab');
        if (! $pekerjaan->rab) {
            return true;
        }
        return $pekerjaan->rab->status_rab === 'Disetujui';
    }

    private function rabBlockMessage(Pekerjaan $pekerjaan): ?string
    {
        $pekerjaan->loadMissing('rab');
        if (! $pekerjaan->rab || $pekerjaan->rab->status_rab === 'Disetujui') {
            return null;
        }
        return "Pekerjaan ini memiliki RAB dengan status {$pekerjaan->rab->status_rab}. Checklist baru bisa diperbarui setelah RAB disetujui.";
    }

    private function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'Belum Diproses', 'Pending', 'Belum dilaksanakan' => 'Belum Diproses',
            'Diproses', 'Sedang berjalan', 'Berjalan', 'Tertunda' => 'Diproses',
            'Selesai' => 'Selesai',
            'Dibatalkan' => 'Dibatalkan',
            default => 'Belum Diproses',
        };
    }

    private function guardManualFinishedStatus(?string $requestedStatus, ?Pekerjaan $pekerjaan = null): void
    {
        if ($requestedStatus !== 'Selesai') {
            return;
        }
        if ($pekerjaan && $pekerjaan->status === 'Selesai') {
            return;
        }
        throw ValidationException::withMessages([
            'status' => 'Status Selesai tidak boleh dipilih manual. Selesaikan semua checklist pekerjaan agar status berubah otomatis menjadi Selesai.',
        ]);
    }
}
