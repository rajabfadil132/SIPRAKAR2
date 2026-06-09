<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriPekerjaanRoleRelation extends Model
{
    protected $table = 'kategori_pekerjaan_role_relations';

    protected $fillable = [
        'kategori_pekerjaan_id',
        'role_id',
        'role_category_id',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriPekerjaan::class, 'kategori_pekerjaan_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function roleCategory()
    {
        return $this->belongsTo(RoleCategory::class);
    }
}
