<?php

namespace App\Models;

use App\Models\Concerns\TracksUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pekerjaan extends Model
{
    use SoftDeletes, TracksUserActions;

    protected $fillable = [
        'program_kerja_id', 'kode_pekerjaan', 'nama_pekerjaan', 'deskripsi', 'cabang_id', 'lokasi_id',
        'nama_gedung', 'nama_lantai', 'nama_ruang', 'lantai', 'no_ruang', 'location_text', 'kategori_id', 'prioritas',
        'penanggung_jawab_id', 'petugas_id', 'tanggal_mulai', 'target_selesai', 'tanggal_selesai', 'status', 'progress',
        'estimasi_rab_awal', 'is_rab', 'catatan', 'created_by', 'updated_by', 'deleted_by', 'delete_reason',
    ];

    protected $appends = ['label_lokasi', 'durasi_label', 'sisa_label', 'status_label', 'is_late'];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'target_selesai' => 'date',
        'tanggal_selesai' => 'date',
        'is_rab' => 'boolean',
        'progress' => 'integer',
    ];

    public function getLabelLokasiAttribute(): string
    {
        $lantai = $this->nama_lantai ?: ($this->lantai !== null ? 'Lantai '.$this->lantai : null);
        $no = $this->no_ruang ? 'No. '.$this->no_ruang : null;
        $parts = collect([
            $this->cabang?->nama_cabang,
            $this->nama_gedung ?: $this->lokasi?->nama_gedung,
            $lantai ?: ($this->lokasi?->lantai !== null ? 'Lantai '.$this->lokasi?->lantai : null),
            $this->nama_ruang ?: $this->lokasi?->nama_ruang,
            $no ?: ($this->lokasi?->kode_ruang ? 'No. '.$this->lokasi?->kode_ruang : null),
            $this->location_text,
        ])->filter(fn ($value) => filled($value));

        return $parts->isNotEmpty() ? $parts->join(' · ') : ($this->lokasi?->label_lokasi ?: '-');
    }

    public function getProgressAttribute($value): int
    {
        return $this->calculatedChecklistProgress($value);
    }

    public function getDurasiLabelAttribute(): string
    {
        if (! $this->tanggal_mulai || ! $this->target_selesai) {
            return '-';
        }

        $start = $this->tanggal_mulai->copy()->startOfDay();
        $target = $this->target_selesai->copy()->startOfDay();
        if ($target->lt($start)) {
            return '-';
        }

        return $start->diffInDays($target).' hari';
    }

    public function getSisaLabelAttribute(): string
    {
        if (! $this->target_selesai) {
            return '-';
        }

        $target = $this->target_selesai->copy()->startOfDay();
        $isFinished = $this->isFinishedForDuration();
        $referenceDate = $this->durationReferenceDate();

        if (! $isFinished && $this->tanggal_mulai && $referenceDate->lt($this->tanggal_mulai->copy()->startOfDay())) {
            return $this->durasi_label;
        }

        if ($isFinished) {
            if ($referenceDate->lte($target)) {
                $days = $referenceDate->diffInDays($target);

                return $days > 0
                    ? 'selesai '.$days.' hari lebih cepat'
                    : 'selesai tepat waktu';
            }

            return 'selesai terlambat '.$target->diffInDays($referenceDate).' hari';
        }

        if ($referenceDate->lte($target)) {
            return 'sisa '.$referenceDate->diffInDays($target).' hari';
        }

        return 'terlambat '.$target->diffInDays($referenceDate).' hari';
    }

    public function getIsLateAttribute(): bool
    {
        return $this->target_selesai
            && ! $this->isFinishedForDuration()
            && now()->startOfDay()->gt($this->target_selesai->copy()->startOfDay());
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->target_selesai && ! $this->isFinishedForDuration() && now()->startOfDay()->gt($this->target_selesai->copy()->addDays(2)->startOfDay())) {
            return 'Terlambat';
        }

        return $this->status ?: '-';
    }

    private function isFinishedForDuration(): bool
    {
        return $this->status === 'Selesai' || (int) $this->progress >= 100;
    }

    private function durationReferenceDate()
    {
        if ($this->isFinishedForDuration()) {
            return ($this->tanggal_selesai ?: $this->updated_at ?: now())->copy()->startOfDay();
        }

        return now()->startOfDay();
    }

    public function programKerja(){return $this->belongsTo(ProgramKerja::class);}
    public function cabang(){return $this->belongsTo(Cabang::class);}
    public function lokasi(){return $this->belongsTo(Ruang::class, 'lokasi_id');}
    public function ruang(){return $this->belongsTo(Ruang::class, 'lokasi_id');}
    public function kategori(){return $this->belongsTo(KategoriPekerjaan::class,'kategori_id');}
    public function petugas(){return $this->belongsTo(User::class,'petugas_id');}
    public function penanggungJawab(){return $this->belongsTo(User::class,'penanggung_jawab_id');}
    public function rab(){return $this->hasOne(Rab::class);}
    public function checklists(){return $this->hasMany(PekerjaanChecklist::class)->orderBy('id');}
    public function progressLogs(){return $this->hasMany(ProgressPekerjaan::class)->latest('tanggal_update')->latest();}
    public function petugasTambahan(){return $this->hasMany(PekerjaanPetugas::class);}
    public function assignedUsers(){return $this->belongsToMany(User::class, 'pekerjaan_petugas')->withPivot(['role_text', 'nama_petugas_manual'])->withTimestamps();}

    public function scopeWithChecklistProgress($query)
    {
        return $query->withCount(['checklists','checklists as completed_checklists_count'=>fn($q)=>$q->where('is_done',true)]);
    }

    public function scopeAssignedToUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('petugas_id', $user->id)
              ->orWhere('penanggung_jawab_id', $user->id)
              ->orWhereHas('petugasTambahan', fn ($assignment) => $assignment->where('user_id', $user->id));
        });
    }

    public function isAssignedTo(?User $user): bool
    {
        if (! $user || ! $this->exists) {
            return false;
        }

        if ((int) $this->petugas_id === (int) $user->id || (int) $this->penanggung_jawab_id === (int) $user->id) {
            return true;
        }

        if ($this->relationLoaded('petugasTambahan')) {
            return $this->petugasTambahan->contains(fn ($assignment) => (int) $assignment->user_id === (int) $user->id);
        }

        return $this->petugasTambahan()->where('user_id', $user->id)->exists();
    }

    public function scopeForCurrentUser($query)
    {
        $u = auth()->user();
        if (! $u) return $query->whereRaw('1 = 0');
        $role = $u->roleKey();
        if ($role === 'superadmin') return $query;
        $query->where('cabang_id', $u->cabang_id);
        if ($role === 'staff') {
            $query->where(function ($q) use ($u) {
                $q->where('petugas_id', $u->id)
                  ->orWhere('penanggung_jawab_id', $u->id)
                  ->orWhereHas('petugasTambahan', fn ($assignment) => $assignment->where('user_id', $u->id));
            });
        }
        return $query;
    }

    public function calculatedChecklistProgress(mixed $fallback = 0): int
    {
        $counts = $this->checklistProgressCounts();
        if ($counts['total'] < 1) {
            return max(0, min(100, (int) ($fallback ?? 0)));
        }
        return (int) round(($counts['done'] / $counts['total']) * 100);
    }

    private function checklistProgressCounts(): array
    {
        if (array_key_exists('checklists_count', $this->attributes)) {
            $done = array_key_exists('completed_checklists_count', $this->attributes) ? (int) $this->attributes['completed_checklists_count'] : $this->checklists()->where('is_done', true)->count();
            return ['total' => (int) $this->attributes['checklists_count'], 'done' => $done];
        }
        if ($this->relationLoaded('checklists')) {
            $items = $this->getRelation('checklists');
            return ['total' => $items->count(), 'done' => $items->where('is_done', true)->count()];
        }
        return ['total' => $this->checklists()->count(), 'done' => $this->checklists()->where('is_done', true)->count()];
    }
}
