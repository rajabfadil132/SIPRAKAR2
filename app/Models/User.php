<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Models\Concerns\TracksUserActions;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, TracksUserActions;

    protected $fillable = [
        'name',
        'identity_number',
        'identity_type',
        'user_type',
        'email',
        'password',
        'role_id',
        'role_category_id',
        'cabang_id',
        'phone',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function roleCategory()
    {
        return $this->belongsTo(RoleCategory::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }


    public function pekerjaanDitugaskan()
    {
        return $this->hasMany(Pekerjaan::class, 'petugas_id');
    }

    public function pekerjaanPenugasan()
    {
        return $this->belongsToMany(Pekerjaan::class, 'pekerjaan_petugas')->withPivot(['role_text', 'nama_petugas_manual'])->withTimestamps();
    }


    public function roleKey(): string
    {
        $this->loadMissing('role');

        return $this->role?->slug
            ? Str::slug(Str::lower($this->role->slug))
            : '';
    }

    public function roleLabel(): string
    {
        return $this->role?->nama_role ?? 'User';
    }

    public function roleCategoryLabel(): ?string
    {
        return $this->roleCategory?->name;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->loadMissing(['role.permission', 'roleCategory']);

        if (! $this->role || ! $this->role->is_active) {
            return false;
        }

        if ($this->roleCategory && ! $this->roleCategory->is_active) {
            return false;
        }

        if ($this->roleKey() === 'superadmin') {
            return true;
        }

        return (bool) data_get($this->role?->permission?->permissions ?? [], $permission, false);
    }

    public function permissionMap(): array
    {
        return collect(config('siprakar_permissions.keys', []))
            ->mapWithKeys(fn (string $permission) => [$permission => $this->hasPermission($permission)])
            ->all();
    }

    public function canReceiveWorkAssignment(): bool
    {
        return $this->hasPermission('pekerjaan.progress') || $this->roleKey() === 'staff';
    }

    public function accessibleRouteName(): string
    {
        $this->loadMissing('role.permission');

        $routes = [
            'dashboard.view' => 'dashboard',
            'program_kerja.view' => 'program-kerja.index',
            'pekerjaan.view' => 'pekerjaan.index',
            'rab.view' => 'rab.index',
            'reports.view' => 'reports.index',
            'master_data.view' => 'master-data.index',
            'users.view' => 'users-management.index',
        ];

        foreach ($routes as $permission => $route) {
            if ($this->hasPermission($permission)) {
                return $route;
            }
        }

        return 'profile.edit';
    }
}
