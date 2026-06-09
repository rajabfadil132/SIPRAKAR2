<?php

namespace App\Models;

use App\Models\Concerns\TracksUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriPekerjaan extends Model
{
    use SoftDeletes, TracksUserActions;

    protected $fillable = [
        'nama_kategori',
        'keterangan',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function roleRelations()
    {
        return $this->hasMany(KategoriPekerjaanRoleRelation::class, 'kategori_pekerjaan_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'kategori_pekerjaan_role_relations')
            ->withPivot('role_category_id')
            ->withTimestamps();
    }

    public function roleCategories()
    {
        return $this->belongsToMany(RoleCategory::class, 'kategori_pekerjaan_role_relations', 'kategori_pekerjaan_id', 'role_category_id')
            ->wherePivotNotNull('role_category_id')
            ->withTimestamps();
    }

    /**
     * Backward-compatible helper for old UI/seeder calls that only pass subkategori role IDs.
     */
    public function syncRoleCategories(array $roleCategoryIds): void
    {
        $relations = RoleCategory::query()
            ->whereIn('id', collect($roleCategoryIds)->filter()->values())
            ->get()
            ->map(fn (RoleCategory $category) => [
                'role_id' => $category->role_id,
                'role_category_id' => $category->id,
            ])
            ->all();

        $this->syncRoleRelations($relations);
    }

    /**
     * Sync role/subkategori recommendations. role_category_id may be null.
     *
     * @param array<int, array{role_id:int|string, role_category_id?:int|string|null}> $relations
     */
    public function syncRoleRelations(array $relations): void
    {
        $normalized = collect($relations)
            ->map(function (array $relation) {
                $roleId = (int) ($relation['role_id'] ?? 0);
                $roleCategoryId = filled($relation['role_category_id'] ?? null)
                    ? (int) $relation['role_category_id']
                    : null;

                if (! $roleId && $roleCategoryId) {
                    $roleId = (int) RoleCategory::query()->whereKey($roleCategoryId)->value('role_id');
                }

                return $roleId ? [
                    'role_id' => $roleId,
                    'role_category_id' => $roleCategoryId,
                ] : null;
            })
            ->filter()
            ->unique(fn (array $relation) => $relation['role_id'].'|'.($relation['role_category_id'] ?? 'all'))
            ->values();

        $this->roleRelations()->delete();

        $normalized->each(fn (array $relation) => $this->roleRelations()->create($relation));
    }
}
