<?php

namespace App\Models;

use App\Models\Concerns\TracksUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gedung extends Model
{
    use SoftDeletes, TracksUserActions;

    protected $fillable = [
        'cabang_id',
        'nama_gedung',
        'keterangan',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function lantais()
    {
        return $this->hasMany(Lantai::class)->orderBy('nomor_lantai');
    }

    public function ruangs()
    {
        return $this->hasManyThrough(Ruang::class, Lantai::class);
    }
}
