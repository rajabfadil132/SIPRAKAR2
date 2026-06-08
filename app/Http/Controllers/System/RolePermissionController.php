<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\{Role, RoleCategory, RolePermission};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RolePermissionController extends Controller
{
    public function index()
    {
        $keys = config('siprakar_permissions.keys', []);

        $roles = Role::query()
            ->with(['permission', 'categories'])
            ->withCount('users')
            ->orderByRaw("CASE WHEN slug = 'superadmin' THEN 0 WHEN slug = 'admin' THEN 1 ELSE 2 END")
            ->orderBy('nama_role')
            ->get()
            ->map(function (Role $role) use ($keys) {
                $roleKey = $role->slug;
                $stored = $role->permission?->permissions ?? [];
                $permissions = $roleKey === 'superadmin'
                    ? array_fill_keys($keys, true)
                    : array_replace(array_fill_keys($keys, false), $stored);

                return [
                    'id' => $role->id,
                    'nama_role' => $role->nama_role,
                    'slug' => $roleKey,
                    'keterangan' => $role->keterangan,
                    'is_system' => (bool) $role->is_system,
                    'is_active' => (bool) $role->is_active,
                    'users_count' => $role->users_count,
                    'locked' => $roleKey === 'superadmin',
                    'system_locked' => $role->isSystemRole(),
                    'permissions' => $permissions,
                    'categories' => $role->categories->map(fn (RoleCategory $category) => [
                        'id' => $category->id,
                        'role_id' => $category->role_id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'description' => $category->description,
                        'is_active' => (bool) $category->is_active,
                    ])->values(),
                ];
            });

        return Inertia::render('Sistem/RolePermissions/Index', [
            'roles' => $roles,
            'groups' => config('siprakar_permissions.groups', []),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $this->ensureSuperadmin($request);

        if (($role->slug) === 'superadmin') {
            return back()->with('error', 'Hak akses superadmin selalu aktif dan tidak dapat diubah.');
        }

        $keys = config('siprakar_permissions.keys', []);
        $payload = $request->validate([
            'permissions' => ['required', 'array'],
        ]);

        $permissions = [];
        foreach ($keys as $key) {
            $permissions[$key] = (bool) ($payload['permissions'][$key] ?? false);
        }

        RolePermission::updateOrCreate(
            ['role_id' => $role->id],
            ['permissions' => $permissions]
        );

        return back()->with('success', 'Hak akses role berhasil diperbarui.');
    }

    public function storeRole(Request $request)
    {
        $this->ensureSuperadmin($request);

        $data = $request->validate([
            'nama_role' => ['required', 'string', 'max:80'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = Str::lower(trim($data['nama_role']));
        $slug = Str::slug($name);

        if (! $slug) {
            throw ValidationException::withMessages(['nama_role' => 'Nama role tidak valid.']);
        }

        if (Role::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['nama_role' => 'Role dengan nama tersebut sudah ada.']);
        }

        $role = Role::create([
            'nama_role' => $name,
            'slug' => $slug,
            'keterangan' => $data['keterangan'] ?? null,
            'is_system' => false,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        RolePermission::create([
            'role_id' => $role->id,
            'permissions' => array_fill_keys(config('siprakar_permissions.keys', []), false),
        ]);

        return back()->with('success', 'Role baru berhasil ditambahkan.');
    }

    public function updateRole(Request $request, Role $role)
    {
        $this->ensureSuperadmin($request);

        $roleKey = $role->slug;
        $isSuperadmin = $roleKey === 'superadmin';
        $isSystemRole = $role->isSystemRole();

        $data = $request->validate([
            'nama_role' => [$isSystemRole ? 'nullable' : 'required', 'string', 'max:80'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($isSystemRole) {
            $role->update([
                'keterangan' => $data['keterangan'] ?? $role->keterangan,
                'is_active' => $isSuperadmin ? true : (bool) $data['is_active'],
                'is_system' => true,
            ]);

            return back()->with('success', $isSuperadmin
                ? 'Keterangan superadmin berhasil diperbarui.'
                : 'Role sistem berhasil diperbarui tanpa mengubah slug/nama role.');
        }

        $name = Str::lower(trim($data['nama_role']));
        $slug = Str::slug($name);

        if (! $slug) {
            throw ValidationException::withMessages(['nama_role' => 'Nama role tidak valid.']);
        }

        if (Role::where('slug', $slug)->whereKeyNot($role->id)->exists()) {
            throw ValidationException::withMessages(['nama_role' => 'Role dengan nama tersebut sudah ada.']);
        }

        $role->update([
            'nama_role' => $name,
            'slug' => $slug,
            'keterangan' => $data['keterangan'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return back()->with('success', 'Role custom berhasil diperbarui.');
    }

    public function destroyRole(Request $request, Role $role)
    {
        $this->ensureSuperadmin($request);

        $roleKey = $role->slug;
        if ($roleKey === 'superadmin') {
            return back()->with('error', 'Role superadmin tidak boleh dihapus atau dinonaktifkan.');
        }

        if ($role->isSystemRole()) {
            $role->update(['is_active' => false, 'is_system' => true]);
            return back()->with('success', 'Role sistem tidak dihapus permanen, tetapi sudah dinonaktifkan agar data lama tetap aman.');
        }

        if ($role->users()->exists()) {
            $role->update(['is_active' => false]);
            return back()->with('success', 'Role masih digunakan user, sehingga tidak dihapus permanen dan hanya dinonaktifkan.');
        }

        $role->permission()->delete();
        $role->categories()->delete();
        $role->delete();

        return back()->with('success', 'Role custom yang belum digunakan berhasil dihapus permanen.');
    }

    public function storeCategory(Request $request, Role $role)
    {
        $this->ensureSuperadmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug(Str::lower($data['name']));
        if (! $slug) {
            throw ValidationException::withMessages(['name' => 'Nama subkategori tidak valid.']);
        }

        if ($role->categories()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'Subkategori tersebut sudah ada pada role ini.']);
        }

        $role->categories()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Subkategori role berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, RoleCategory $category)
    {
        $this->ensureSuperadmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ]);

        $slug = Str::slug(Str::lower($data['name']));
        if (! $slug) {
            throw ValidationException::withMessages(['name' => 'Nama subkategori tidak valid.']);
        }

        if (RoleCategory::where('role_id', $category->role_id)->where('slug', $slug)->whereKeyNot($category->id)->exists()) {
            throw ValidationException::withMessages(['name' => 'Subkategori tersebut sudah ada pada role ini.']);
        }

        $category->update([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return back()->with('success', 'Subkategori role berhasil diperbarui.');
    }

    public function destroyCategory(Request $request, RoleCategory $category)
    {
        $this->ensureSuperadmin($request);

        if ($category->users()->exists()) {
            return back()->with('error', 'Subkategori masih digunakan oleh user. Ubah subkategori user terlebih dahulu.');
        }

        $category->delete();

        return back()->with('success', 'Subkategori role berhasil dihapus.');
    }

    private function ensureSuperadmin(Request $request): void
    {
        // Role restriction dinonaktifkan pada versi solo SIPRAKAR.
    }
}
