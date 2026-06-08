<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\TracksUserActions;

class Cabang extends Model
{
    use SoftDeletes, TracksUserActions;

    protected $fillable = [
        'nama_cabang',
        'kode',
        'alamat',
        'keterangan',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }


    public function gedungs()
    {
        return $this->hasMany(Gedung::class);
    }


    public function programKerjas()
    {
        return $this->hasMany(ProgramKerja::class);
    }

    public function pekerjaans()
    {
        return $this->hasMany(Pekerjaan::class);
    }


    public function jadwalPemeliharaans()
    {
        return $this->hasMany(JadwalPemeliharaan::class);
    }
}