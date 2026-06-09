<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\TracksUserActions;
class KategoriPekerjaan extends Model {
    use SoftDeletes, TracksUserActions;

    protected $fillable = [
        'nama_kategori',
        'keterangan',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function roleCategories()
    {
        return $this->belongsToMany(RoleCategory::class, 'kategori_role_categories')
            ->withTimestamps();
    }

    public function syncRoleCategories(array $roleCategoryIds): void
    {
        $this->roleCategories()->sync(
            collect($roleCategoryIds)->filter(fn ($id) => $id !== '' && $id !== null)->values()->all()
        );
    }
}
