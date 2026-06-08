<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\TracksUserActions;
class Rab extends Model
{
    use SoftDeletes, TracksUserActions;

    protected $fillable = [
        'program_kerja_id', 'pekerjaan_id', 'nomor_rab', 'tanggal_rab', 'total_rab',
        'status_rab', 'submitted_at', 'reviewed_at', 'reviewed_by', 'catatan',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'tanggal_rab' => 'date',
        'total_rab' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function pekerjaan() { return $this->belongsTo(Pekerjaan::class); }
    public function programKerja() { return $this->belongsTo(ProgramKerja::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function details() { return $this->hasMany(RabDetail::class); }

    public function getEstimasiTotalAttribute(): float
    {
        return (float) optional($this->programKerja)->estimasi_total ?? 0;
    }

    public function getEstimasiItemsAttribute()
    {
        return optional($this->programKerja)->estimasiItems ?? collect();
    }
}
