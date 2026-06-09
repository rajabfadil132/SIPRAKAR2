<?php

namespace App\Models;

use App\Enums\ProgramKerjaStatus;
use App\Enums\RabStatus;
use App\Models\Concerns\TracksUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramKerja extends Model
{
    use SoftDeletes, TracksUserActions;

    public const ACTIVE_STATUSES = [
        'RAB Diajukan',
        'RAB Direvisi',
        'RAB Disetujui',
        'Siap Dijadikan Pekerjaan',
    ];

    public const FINAL_STATUSES = [
        'Dijadikan Pekerjaan',
        'Selesai',
        'Dibatalkan',
    ];

    public const STATUSES = [
        'RAB Diajukan',
        'RAB Direvisi',
        'RAB Disetujui',
        'Siap Dijadikan Pekerjaan',
        'Dijadikan Pekerjaan',
        'Selesai',
        'Dibatalkan',
    ];

    protected $fillable = [
        'kode_program', 'tahun', 'nama_program', 'deskripsi', 'cabang_id', 'kategori_id',
        'prioritas', 'target_mulai', 'target_selesai', 'estimasi_anggaran', 'status', 'status_key', 'source_type', 'needs_rab',
        'converted_to_pekerjaan_id', 'converted_at', 'status_before_conversion', 'keterangan',
        'lokasi_id', 'nama_gedung', 'nama_lantai', 'nama_ruang', 'no_ruang', 'lantai', 'location_text',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'target_mulai' => 'date',
        'target_selesai' => 'date',
        'estimasi_anggaran' => 'decimal:2',
        'converted_at' => 'datetime',
        'needs_rab' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProgramKerja $program) {
            $status = ProgramKerjaStatus::fromLabelOrKey($program->status_key ?: $program->status);
            $program->status_key = $status->value;
            $program->status = $status->label();
        });
    }

    public function lokasi(){return $this->belongsTo(\App\Models\Ruang::class, 'lokasi_id');}
    public function cabang(){return $this->belongsTo(Cabang::class);}
    public function kategori(){return $this->belongsTo(KategoriPekerjaan::class,'kategori_id');}
    public function pekerjaans(){return $this->hasMany(Pekerjaan::class);}
    public function convertedPekerjaan(){return $this->belongsTo(Pekerjaan::class,'converted_to_pekerjaan_id');}
    public function rab(){return $this->hasOne(Rab::class);}
    public function estimasiItems(){return $this->hasMany(ProgramKerjaEstimasiItem::class);}

    public function getEstimasiTotalAttribute(): float
    {
        return (float) ($this->estimasiItems->sum('subtotal') ?? 0);
    }

    public function statusEnum(): ProgramKerjaStatus
    {
        return ProgramKerjaStatus::fromLabelOrKey($this->status_key ?: $this->status);
    }

    public function scopeForCurrentUser($query)
    {
        $u = auth()->user();
        if ($u && $u->roleKey() !== 'superadmin') {
            $query->where('cabang_id', $u->cabang_id);
        }
        return $query;
    }

    public function scopeActive($query)
    {
        return $query
            ->whereNull('converted_to_pekerjaan_id')
            ->where(function ($statusQuery) {
                $statusQuery->whereIn('status_key', ProgramKerjaStatus::activeKeys())
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('status_key')->whereIn('status', self::ACTIVE_STATUSES);
                    });
            });
    }

    public function scopeAvailableForPekerjaan($query, $currentProgramId = null)
    {
        return $query
            ->where(function ($q) use ($currentProgramId) {
                $q->active();
                if ($currentProgramId) {
                    $q->orWhere($q->getModel()->getQualifiedKeyName(), $currentProgramId);
                }
            });
    }

    public function isConverted(): bool
    {
        return filled($this->converted_to_pekerjaan_id)
            || in_array($this->statusEnum()->value, ProgramKerjaStatus::finalKeys(), true);
    }

    public function hasApprovedRab(): bool
    {
        return (bool) $this->rab && $this->rab->statusEnum() === RabStatus::APPROVED;
    }

    public function canBecomePekerjaan(): bool
    {
        if ($this->isConverted()) {
            return false;
        }

        if ($this->needs_rab) {
            return $this->statusEnum() === ProgramKerjaStatus::RAB_APPROVED && $this->hasApprovedRab();
        }

        return $this->statusEnum() === ProgramKerjaStatus::READY_FOR_WORK;
    }

    public function sourceLabel(): string
    {
        return 'PROKER';
    }
}
