<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\TracksUserActions;

class PekerjaanChecklist extends Model
{
    use SoftDeletes, TracksUserActions;

    protected $fillable = [
        'pekerjaan_id',
        'deskripsi',
        'is_done',
        'completed_by',
        'completed_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function pekerjaan()
    {
        return $this->belongsTo(Pekerjaan::class);
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
