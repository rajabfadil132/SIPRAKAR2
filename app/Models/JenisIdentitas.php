<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\TracksUserActions;

class JenisIdentitas extends Model
{
    use SoftDeletes, TracksUserActions;

    protected $fillable = [
        'nama_jenis',
        'kode',
        'keterangan',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
