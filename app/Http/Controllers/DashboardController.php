<?php

namespace App\Http\Controllers;

use App\Models\{Pekerjaan, ProgramKerja, Rab};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $canViewRab = $user->hasPermission('rab.view');

        $pekerjaan = Pekerjaan::query()->forCurrentUser();
        $program = ProgramKerja::query()->forCurrentUser();
        $activeProgram = ProgramKerja::query()->forCurrentUser()->active();

        $progressValues = (clone $pekerjaan)->withChecklistProgress()->get(['id'])->pluck('progress');

        $statusCounts = DB::table('pekerjaans')
            ->join('cabangs', 'pekerjaans.cabang_id', '=', 'cabangs.id')
            ->where('pekerjaans.status', 'Selesai')
            ->selectRaw("cabangs.nama_cabang as label, COUNT(*) as total")
            ->groupBy('cabangs.nama_cabang')
            ->orderByDesc('total')
            ->get();

        $barCounts = DB::table('program_kerjas')
            ->join('cabangs', 'program_kerjas.cabang_id', '=', 'cabangs.id')
            ->selectRaw("cabangs.nama_cabang as label, COUNT(*) as total")
            ->groupBy('cabangs.nama_cabang')
            ->orderByDesc('total')
            ->get();

        $nearDeadlineQuery = Pekerjaan::query()
            ->forCurrentUser()
            ->whereNotIn('status', ['Selesai', 'Dibatalkan'])
            ->whereDate('target_selesai', '>=', now()->toDateString())
            ->whereDate('target_selesai', '<=', now()->copy()->addDays(7)->toDateString());

        $overdueQuery = Pekerjaan::query()
            ->forCurrentUser()
            ->whereNotIn('status', ['Selesai', 'Dibatalkan'])
            ->whereDate('target_selesai', '<', now()->toDateString());

        $withoutAssigneeQuery = Pekerjaan::query()
            ->forCurrentUser()
            ->whereNotIn('status', ['Selesai', 'Dibatalkan'])
            ->whereNull('petugas_id')
            ->whereDoesntHave('petugasTambahan');

        $withoutChecklistQuery = Pekerjaan::query()
            ->forCurrentUser()
            ->whereNotIn('status', ['Selesai', 'Dibatalkan'])
            ->whereDoesntHave('checklists');

        $withoutSetupQuery = Pekerjaan::query()
            ->forCurrentUser()
            ->whereNotIn('status', ['Selesai', 'Dibatalkan'])
            ->where(function ($query) {
                $query->where(function ($assignee) {
                    $assignee->whereNull('petugas_id')->whereDoesntHave('petugasTambahan');
                })->orWhereDoesntHave('checklists');
            });

        $rabWaitingQuery = Rab::query()
            ->whereIn('status_rab', ['Diajukan', 'Direvisi'])
            ->where(function ($query) {
                $query->whereHas('programKerja', fn ($program) => $program->forCurrentUser())
                    ->orWhereHas('pekerjaan', fn ($pekerjaan) => $pekerjaan->forCurrentUser());
            });

        $trend = collect(range(5, 0))->map(function ($i) {
            $date = now()->copy()->subMonths($i);

            return [
                'label' => $this->shortMonthName((int) $date->month).' '.$date->year,
                'month' => $this->monthName((int) $date->month).' '.$date->year,
                'total_program' => ProgramKerja::query()
                    ->forCurrentUser()
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'done' => Pekerjaan::query()
                    ->forCurrentUser()
                    ->where('status', 'Selesai')
                    ->whereYear('updated_at', $date->year)
                    ->whereMonth('updated_at', $date->month)
                    ->count(),
            ];
        })->values();

        $rabWaiting = $canViewRab
            ? (clone $rabWaitingQuery)
                ->with([
                    'programKerja:id,kode_program,nama_program,cabang_id,kategori_id,status',
                    'programKerja.cabang:id,nama_cabang',
                    'programKerja.kategori:id,nama_kategori',
                    'pekerjaan:id,kode_pekerjaan,nama_pekerjaan,cabang_id,kategori_id,status',
                    'pekerjaan.cabang:id,nama_cabang',
                    'pekerjaan.kategori:id,nama_kategori',
                ])
                ->latest('submitted_at')
                ->latest()
                ->limit(6)
                ->get()
            : collect();

        $deadlineItems = Pekerjaan::query()
            ->forCurrentUser()
            ->withChecklistProgress()
            ->with(['cabang:id,nama_cabang', 'kategori:id,nama_kategori', 'petugas:id,name'])
            ->whereNotIn('status', ['Selesai', 'Dibatalkan'])
            ->whereNotNull('target_selesai')
            ->whereDate('target_selesai', '<=', now()->copy()->addDays(7)->toDateString())
            ->orderBy('target_selesai')
            ->limit(8)
            ->get();

        $incompleteItems = (clone $withoutSetupQuery)
            ->withChecklistProgress()
            ->with(['cabang:id,nama_cabang', 'kategori:id,nama_kategori', 'petugas:id,name'])
            ->latest()
            ->limit(8)
            ->get();

        $myTasks = Pekerjaan::query()
            ->forCurrentUser()
            ->assignedToUser($user)
            ->withChecklistProgress()
            ->with(['cabang:id,nama_cabang', 'kategori:id,nama_kategori'])
            ->whereNotIn('status', ['Selesai', 'Dibatalkan'])
            ->orderByRaw("CASE prioritas WHEN 'Mendesak' THEN 1 WHEN 'Tinggi' THEN 2 WHEN 'Sedang' THEN 3 ELSE 4 END")
            ->latest()
            ->limit(6)
            ->get();

        return Inertia::render('Siprakar/Dashboard/Index', [
            'summary' => [
                'total_program' => (clone $program)->count(),
                'active_program' => (clone $activeProgram)->count(),
                'direncanakan' => (clone $activeProgram)->whereIn('status', ['RAB Diajukan', 'RAB Direvisi'])->count(),
                'siap_pekerjaan' => (clone $activeProgram)->where('status', 'Siap Dijadikan Pekerjaan')->count(),
                'total_pekerjaan' => (clone $pekerjaan)->count(),
                'belum' => (clone $pekerjaan)->where('status', 'Belum Diproses')->count(),
                'berjalan' => (clone $pekerjaan)->where('status', 'Diproses')->count(),
                'selesai' => (clone $pekerjaan)->where('status', 'Selesai')->count(),
                'dibatalkan' => (clone $pekerjaan)->where('status', 'Dibatalkan')->count(),
                'rab_waiting' => $canViewRab ? (clone $rabWaitingQuery)->count() : 0,
                'near_deadline' => (clone $nearDeadlineQuery)->count(),
                'overdue' => (clone $overdueQuery)->count(),
                'deadline_alerts' => (clone $nearDeadlineQuery)->count() + (clone $overdueQuery)->count(),
                'without_assignee' => (clone $withoutAssigneeQuery)->count(),
                'without_checklist' => (clone $withoutChecklistQuery)->count(),
                'without_setup' => (clone $withoutSetupQuery)->count(),
                'avg_progress' => round($progressValues->avg() ?? 0),
                'total_rab' => $canViewRab
                    ? Rab::query()
                        ->where(function ($query) {
                            $query->whereHas('programKerja', fn ($program) => $program->forCurrentUser())
                                ->orWhereHas('pekerjaan', fn ($pekerjaan) => $pekerjaan->forCurrentUser());
                        })
                        ->sum('total_rab')
                    : null,
            ],
            'monitoring' => [
                'program_waiting_decision' => (clone $activeProgram)->whereIn('status', ['RAB Diajukan', 'RAB Direvisi'])->count(),
                'program_ready' => (clone $activeProgram)->where('status', 'Siap Dijadikan Pekerjaan')->count(),
                'without_assignee' => (clone $withoutAssigneeQuery)->count(),
                'without_checklist' => (clone $withoutChecklistQuery)->count(),
                'near_deadline' => (clone $nearDeadlineQuery)->count(),
                'overdue' => (clone $overdueQuery)->count(),
                'rab_waiting' => $canViewRab ? (clone $rabWaitingQuery)->count() : 0,
            ],
            'statusCounts' => $statusCounts,
            'barCounts' => $barCounts,
            'trend' => $trend,
            'recentPekerjaan' => Pekerjaan::query()
                ->forCurrentUser()
                ->withChecklistProgress()
                ->with(['cabang:id,nama_cabang', 'kategori:id,nama_kategori', 'petugas:id,name'])
                ->latest()
                ->limit(8)
                ->get(),
            'runningItems' => Pekerjaan::query()
                ->forCurrentUser()
                ->withChecklistProgress()
                ->with(['cabang:id,nama_cabang', 'kategori:id,nama_kategori', 'petugas:id,name'])
                ->where('status', 'Diproses')
                ->orderByDesc('updated_at')
                ->limit(12)
                ->get(),
            'rabWaiting' => $rabWaiting,
            'deadlineItems' => $deadlineItems,
            'incompleteItems' => $incompleteItems,
            'myTasks' => $myTasks,
            'canViewRab' => $canViewRab,
        ]);
    }

    private function monthName(int $month): string
    {
        return [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'][$month] ?? (string) $month;
    }

    private function shortMonthName(int $month): string
    {
        return [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'][$month] ?? (string) $month;
    }
}
