<?php

namespace App\Models;

use App\Models\Concerns\TracksUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lantai extends Model
{
    use SoftDeletes, TracksUserActions;

    protected $fillable = [
        'gedung_id',
        'nomor_lantai',
        'nama_lantai',
        'keterangan',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'nomor_lantai' => 'integer',
    ];

    public function gedung()
    {
        return $this->belongsTo(Gedung::class);
    }

    public function ruangs()
    {
        return $this->hasMany(Ruang::class);
    }
}
