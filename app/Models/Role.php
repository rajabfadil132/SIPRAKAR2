<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Role extends Model
{
    public const SYSTEM_SLUGS = ['superadmin', 'admin', 'staff', 'lembaga'];

    protected $fillable = [
        'nama_role',
        'slug',
        'keterangan',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Role $role) {
            if (! $role->slug) {
                $role->slug = Str::slug(Str::lower($role->nama_role));
            }
        });
    }

    public function permission()
    {
        return $this->hasOne(RolePermission::class);
    }

    public function categories()
    {
        return $this->hasMany(RoleCategory::class)->orderBy('name');
    }

    public function activeCategories()
    {
        return $this->hasMany(RoleCategory::class)->where('is_active', true)->orderBy('name');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isSystemRole(): bool
    {
        return $this->is_system || in_array($this->slug, self::SYSTEM_SLUGS, true);
    }
}
