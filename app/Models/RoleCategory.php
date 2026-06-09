<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleCategory extends Model
{
    protected $fillable = [
        'role_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role_category_id');
    }

    public function kategoriRelations()
    {
        return $this->hasMany(KategoriPekerjaanRoleRelation::class);
    }
}
