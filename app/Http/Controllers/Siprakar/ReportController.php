<?php

namespace App\Http\Controllers\Siprakar;

use App\Http\Controllers\Controller;
use App\Models\Pekerjaan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $filtered = $this->filteredQuery($request);

        $items = (clone $filtered)
            ->withChecklistProgress()
            ->with(['cabang', 'kategori', 'petugas', 'rab', 'creator:id,name', 'updater:id,name'])
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $progressValues = (clone $filtered)->withChecklistProgress()->get(['id'])->pluck('progress');

        return Inertia::render('Siprakar/Laporan/Index', [
            'items' => $items,
            'summary' => [
                'total' => (clone $filtered)->count(),
                'avg_progress' => round($progressValues->avg() ?? 0),
                'done' => (clone $filtered)->where('status', 'Selesai')->count(),
                'with_rab' => (clone $filtered)->whereHas('rab')->count(),
            ],
            'filters' => $request->only('search', 'status', 'kategori_id'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->filteredQuery($request)
            ->withChecklistProgress()
            ->with(['cabang', 'kategori', 'petugas', 'rab'])
            ->latest()
            ->get();

        $filename = 'laporan_siprakar_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Kode', 'Pekerjaan', 'Cabang', 'Kategori', 'Petugas', 'Progress', 'Status', 'Total RAB', 'Target Selesai', 'Dibuat']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->kode_pekerjaan,
                    $row->nama_pekerjaan,
                    $row->cabang?->nama_cabang,
                    $row->kategori?->nama_kategori,
                    $row->petugas?->name,
                    $row->progress.'%',
                    $row->status,
                    $row->rab?->total_rab ?? 0,
                    optional($row->target_selesai)->format('Y-m-d'),
                    optional($row->created_at)->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredQuery(Request $request)
    {
        return Pekerjaan::query()
            ->forCurrentUser()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search');
                $q->where(function ($sub) use ($s) {
                    $sub->where('nama_pekerjaan', 'like', "%$s%")
                        ->orWhere('kode_pekerjaan', 'like', "%$s%")
                        ->orWhereHas('cabang', fn ($c) => $c->where('nama_cabang', 'like', "%$s%"))
                        ->orWhereHas('kategori', fn ($c) => $c->where('nama_kategori', 'like', "%$s%"))
                        ->orWhereHas('petugas', fn ($c) => $c->where('name', 'like', "%$s%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('kategori_id'), fn ($q) => $q->where('kategori_id', $request->kategori_id));
    }
}
