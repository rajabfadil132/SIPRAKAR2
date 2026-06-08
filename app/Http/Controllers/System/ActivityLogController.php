<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\{Pekerjaan, ProgramKerja, Rab};
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request)
    {
        $activities = collect()
            ->merge($this->programActivities())
            ->merge($this->pekerjaanActivities())
            ->merge($this->rabActivities())
            ->sortByDesc('time')
            ->values()
            ->take(100);

        return Inertia::render('Sistem/ActivityLogs/Index', [
            'items' => $activities,
        ]);
    }

    private function programActivities()
    {
        return ProgramKerja::withTrashed()
            ->forCurrentUser()
            ->with(['creator:id,name', 'updater:id,name', 'deleter:id,name'])
            ->latest('updated_at')
            ->limit(40)
            ->get()
            ->flatMap(function (ProgramKerja $item) {
                return collect([
                    $this->entry('Program Kerja', 'Dibuat', $item->kode_program, $item->nama_program, $item->created_at, $item->creator?->name, route('program-kerja.show', $item->id, false), 'Program kerja baru dibuat.'),
                    $item->updated_by ? $this->entry('Program Kerja', 'Diperbarui', $item->kode_program, $item->nama_program, $item->updated_at, $item->updater?->name, route('program-kerja.show', $item->id, false), 'Status, RAB, atau konversi program diperbarui.') : null,
                    $item->deleted_at ? $this->entry('Program Kerja', 'Dihapus', $item->kode_program, $item->nama_program, $item->deleted_at, $item->deleter?->name, null, 'Data masuk ke arsip soft delete.') : null,
                ])->filter();
            });
    }

    private function pekerjaanActivities()
    {
        return Pekerjaan::withTrashed()
            ->forCurrentUser()
            ->with(['creator:id,name', 'updater:id,name', 'deleter:id,name'])
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->flatMap(function (Pekerjaan $item) {
                return collect([
                    $this->entry('Pekerjaan', 'Dibuat', $item->kode_pekerjaan, $item->nama_pekerjaan, $item->created_at, $item->creator?->name, route('pekerjaan.show', $item->id, false), 'Data pekerjaan dibuat dari Program Kerja.'),
                    $item->updated_by ? $this->entry('Pekerjaan', 'Diperbarui', $item->kode_pekerjaan, $item->nama_pekerjaan, $item->updated_at, $item->updater?->name, route('pekerjaan.show', $item->id, false), 'Data pekerjaan, penugasan, atau progress checklist diperbarui.') : null,
                    $item->deleted_at ? $this->entry('Pekerjaan', 'Dihapus', $item->kode_pekerjaan, $item->nama_pekerjaan, $item->deleted_at, $item->deleter?->name, null, 'Data masuk ke arsip soft delete.') : null,
                ])->filter();
            });
    }

    private function rabActivities()
    {
        return Rab::withTrashed()
            ->where(function ($query) {
                $query->whereHas('programKerja', fn ($q) => $q->forCurrentUser())
                    ->orWhereHas('pekerjaan', fn ($q) => $q->forCurrentUser());
            })
            ->with(['creator:id,name', 'updater:id,name', 'deleter:id,name', 'programKerja:id,nama_program,kode_program', 'pekerjaan:id,nama_pekerjaan,kode_pekerjaan'])
            ->latest('updated_at')
            ->limit(40)
            ->get()
            ->flatMap(function (Rab $item) {
                return collect([
                    $this->entry('RAB', 'Dibuat', $item->nomor_rab, $item->programKerja?->nama_program ?? $item->pekerjaan?->nama_pekerjaan, $item->created_at, $item->creator?->name, route('rab.show', $item->id, false), 'RAB program/pekerjaan dibuat.'),
                    $item->updated_by ? $this->entry('RAB', 'Diperbarui', $item->nomor_rab, $item->programKerja?->nama_program ?? $item->pekerjaan?->nama_pekerjaan, $item->updated_at, $item->updater?->name, route('rab.show', $item->id, false), 'Status atau item RAB diperbarui.') : null,
                    $item->deleted_at ? $this->entry('RAB', 'Dihapus', $item->nomor_rab, $item->programKerja?->nama_program ?? $item->pekerjaan?->nama_pekerjaan, $item->deleted_at, $item->deleter?->name, null, 'Data RAB masuk ke arsip soft delete.') : null,
                ])->filter();
            });
    }

    private function entry(string $module, string $action, ?string $code, ?string $title, mixed $time, ?string $actor, ?string $href, string $description): array
    {
        return [
            'module' => $module,
            'action' => $action,
            'code' => $code,
            'title' => $title,
            'time' => optional($time)->toISOString(),
            'actor' => $actor ?: '-',
            'href' => $href,
            'description' => $description,
        ];
    }
}
