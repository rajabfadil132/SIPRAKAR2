<?php

namespace App\Services;

use App\Models\Cabang;
use App\Models\Pekerjaan;
use App\Models\ProgramKerja;
use App\Models\Rab;
use Illuminate\Database\Eloquent\Model;

class SequentialCodeGenerator
{
    public function pekerjaan(string $prefix, mixed $cabangId, ?int $year = null): string
    {
        $year ??= (int) now()->year;
        return $this->nextScopedCode(Pekerjaan::class, 'kode_pekerjaan', $prefix, $cabangId, $year);
    }

    public function program(string $prefix, mixed $cabangId, ?int $year = null): string
    {
        $year ??= (int) now()->year;
        return $this->nextScopedCode(ProgramKerja::class, 'kode_program', $prefix, $cabangId, $year, 3);
    }

    public function rab(mixed $cabangId, ?int $year = null): string
    {
        $year ??= (int) now()->year;
        return $this->nextScopedCode(Rab::class, 'nomor_rab', 'RAB', $cabangId, $year);
    }

    public function branchCode(?Cabang $cabang): string
    {
        $source = $cabang?->kode ?: $cabang?->nama_cabang ?: 'CBG';
        $code = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($source)), 0, 3);

        return $code ?: 'CBG';
    }

    /**
     * Generate kode berurutan berdasarkan prefix + kode cabang + tahun.
     * Contoh Program Kerja: PROKER/VIK/26/002.
     * Contoh RAB/Pekerjaan tetap bisa memakai 4 digit: RAB/SRG/26/0003.
     *
     * @param class-string<Model> $modelClass
     */
    private function nextScopedCode(string $modelClass, string $column, string $prefix, mixed $cabangId, int $year, int $digits = 4): string
    {
        $cabang = Cabang::find($cabangId);
        $branchCode = $this->branchCode($cabang);
        $shortYear = substr((string) $year, -2);
        $base = sprintf('%s/%s/%s', strtoupper($prefix), $branchCode, $shortYear);
        $like = $base.'/%';

        $query = $modelClass::query();
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        $lastNumber = $query
            ->where($column, 'like', $like)
            ->lockForUpdate()
            ->pluck($column)
            ->map(function ($code) {
                if (preg_match('/\/(\d+)$/', (string) $code, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        $number = $lastNumber + 1;
        do {
            $code = sprintf('%s/%0'.$digits.'d', $base, $number++);
            $existsQuery = $modelClass::query();
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass), true)) {
                $existsQuery->withTrashed();
            }
        } while ($existsQuery->where($column, $code)->lockForUpdate()->exists());

        return $code;
    }
}
