<?php

namespace App\Models;

use App\Models\Concerns\TracksUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ruang extends Model
{
    use SoftDeletes, TracksUserActions;

    protected $fillable = [
        'lantai_id',
        'nama_ruang',
        'kode_ruang',
        'keterangan',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = [
        'nama_gedung',
        'lantai',
        'ruangan',
        'label_lokasi',
        'cabang_id',
    ];


    public function lantaiMaster()
    {
        return $this->belongsTo(Lantai::class, 'lantai_id');
    }

    public function getNamaGedungAttribute(): ?string
    {
        return $this->lantaiMaster?->gedung?->nama_gedung;
    }

    public function getLantaiAttribute(): ?int
    {
        return $this->lantaiMaster?->nomor_lantai;
    }

    public function getRuanganAttribute(): ?string
    {
        return $this->nama_ruang;
    }

    public function getCabangIdAttribute(): ?int
    {
        return $this->lantaiMaster?->gedung?->cabang_id;
    }

    public function getLabelLokasiAttribute(): string
    {
        return collect([
            $this->lantaiMaster?->gedung?->cabang?->nama_cabang,
            $this->lantaiMaster?->gedung?->nama_gedung,
            $this->lantaiMaster ? ($this->lantaiMaster->nama_lantai ?: 'Lantai '.$this->lantaiMaster->nomor_lantai) : null,
            $this->nama_ruang,
        ])->filter()->join(' · ');
    }
}
