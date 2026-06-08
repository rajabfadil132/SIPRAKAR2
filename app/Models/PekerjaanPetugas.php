<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PekerjaanPetugas extends Model
{
    protected $table = 'pekerjaan_petugas';

    protected $fillable = [
        'pekerjaan_id',
        'user_id',
        'role_text',
        'nama_petugas_manual',
        'created_by',
        'updated_by',
    ];

    public function pekerjaan()
    {
        return $this->belongsTo(Pekerjaan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
