<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\{Cabang, Role, RoleCategory, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('role');
        $isSuperadmin = $this->isSuperadmin($user);

        $items = User::with(['role', 'roleCategory', 'cabang', 'creator:id,name', 'updater:id,name', 'deleter:id,name'])
            ->when(! $isSuperadmin, fn ($query) => $query->where('cabang_id', $user->cabang_id))
            ->when($request->filled('search'), function ($query) use ($request) {
                $s = $request->string('search');
                $query->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%$s%")
                        ->orWhere('email', 'like', "%$s%")
                        ->orWhere('identity_number', 'like', "%$s%")
                        ->orWhere('identity_type', 'like', "%$s%")
                        ->orWhere('phone', 'like', "%$s%")
                        ->orWhereHas('role', fn ($role) => $role->where('nama_role', 'like', "%$s%"))
                        ->orWhereHas('roleCategory', fn ($category) => $category->where('name', 'like', "%$s%"))
                        ->orWhereHas('cabang', fn ($cabang) => $cabang->where('nama_cabang', 'like', "%$s%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('role_id'), fn ($query) => $query->where('role_id', $request->role_id))
            ->when($request->filled('role_category_id'), fn ($query) => $query->where('role_category_id', $request->role_category_id))
            ->when($request->filled('cabang_id') && $isSuperadmin, fn ($query) => $query->where('cabang_id', $request->cabang_id))
            ->orderBy('created_at', $request->input('sort_dir') === 'asc' ? 'asc' : 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Sistem/UserManagement/Index', [
            'items' => $items,
            'filters' => $request->only('search', 'status', 'role_id', 'role_category_id', 'cabang_id', 'sort_dir'),
            'permissions' => $request->user()->permissionMap(),
            'roles' => $this->availableRoles($user)->with('activeCategories')->get(['id', 'nama_role', 'slug', 'keterangan', 'is_active']),
            'roleCategories' => RoleCategory::query()->where('is_active', true)->with('role:id,nama_role,slug')->orderBy('name')->get(['id', 'role_id', 'name', 'slug']),
            'cabangs' => $this->availableCabangs($user)->get(['id', 'nama_cabang', 'kode']),
            'canManagePermissions' => $isSuperadmin,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user()->loadMissing('role');

        return Inertia::render('Sistem/UserManagement/Form', [
            'roles' => $this->availableRoles($user)->with('activeCategories')->get(),
            'cabangs' => $this->availableCabangs($user)->get(),
            'identityTypes' => $this->identityTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $actor = $request->user()->loadMissing('role');
        $data = $this->validatedUserData($request);
        $this->assertRoleIsAssignable($actor, (int) $data['role_id']);

        if (! $this->isSuperadmin($actor)) {
            $data['cabang_id'] = $actor->cabang_id;
        }

        $data['password'] = Hash::make($data['password']);
        $data['created_by'] = $actor->id;

        User::create($data);

        return redirect()->route('users-management.index')->with('success', 'User berhasil dibuat.');
    }

    public function show(Request $request, User $users_management)
    {
        $this->ensureUserVisible($request->user(), $users_management);
        $users_management->load([
            'role', 'roleCategory', 'cabang', 'creator:id,name', 'updater:id,name', 'deleter:id,name',
            'pekerjaanDitugaskan' => fn ($q) => $q->withChecklistProgress(),
            'pekerjaanDitugaskan.kategori', 'pekerjaanDitugaskan.cabang',
        ]);

        return Inertia::render('Sistem/UserManagement/Show', [
            'item' => $users_management,
            'permissions' => request()->user()->permissionMap(),
        ]);
    }

    public function edit(Request $request, User $users_management)
    {
        $actor = $request->user()->loadMissing('role');
        $this->ensureUserVisible($actor, $users_management);
        $this->assertCanManageTarget($actor, $users_management);

        return Inertia::render('Sistem/UserManagement/Form', [
            'item' => $users_management->load(['role', 'roleCategory', 'cabang', 'creator:id,name', 'updater:id,name']),
            'roles' => $this->availableRoles($actor)->with('activeCategories')->get(),
            'cabangs' => $this->availableCabangs($actor)->get(),
            'identityTypes' => $this->identityTypes(),
        ]);
    }

    public function update(Request $request, User $users_management)
    {
        $actor = $request->user()->loadMissing('role');
        $this->ensureUserVisible($actor, $users_management);
        $this->assertCanManageTarget($actor, $users_management);

        $data = $this->validatedUserData($request, $users_management->id, true);
        $this->assertRoleIsAssignable($actor, (int) $data['role_id']);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (! $this->isSuperadmin($actor)) {
            $data['cabang_id'] = $actor->cabang_id;
        }

        $data['updated_by'] = $actor->id;
        $users_management->update($data);

        return redirect()->route('users-management.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $users_management)
    {
        $actor = $request->user()->loadMissing('role');
        $this->ensureUserVisible($actor, $users_management);
        $this->assertCanManageTarget($actor, $users_management);

        if ($users_management->id === $actor->id) {
            return back()->with('error', 'User yang sedang login tidak bisa menghapus akunnya sendiri.');
        }

        $users_management->update(['deleted_by' => $actor->id]);
        $users_management->delete();

        return back()->with('success', 'User berhasil dihapus secara soft delete.');
    }

    private function validatedUserData(Request $request, ?int $id = null, bool $isUpdate = false): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'identity_type' => ['nullable', 'string', 'max:80'],
            'identity_number' => ['required', 'string', 'max:50', Rule::unique('users', 'identity_number')->ignore($id)],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'role_category_id' => ['nullable', 'exists:role_categories,id'],
            'cabang_id' => ['nullable', 'exists:cabangs,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $role = Role::query()->with('activeCategories')->findOrFail($data['role_id']);
        if (! $role->is_active) {
            throw ValidationException::withMessages(['role_id' => 'Role yang dipilih sedang nonaktif.']);
        }

        $categories = $role->activeCategories;
        if ($categories->isNotEmpty()) {
            if (empty($data['role_category_id'])) {
                throw ValidationException::withMessages(['role_category_id' => 'Subkategori role wajib dipilih untuk role ini.']);
            }
            if (! $categories->contains('id', (int) $data['role_category_id'])) {
                throw ValidationException::withMessages(['role_category_id' => 'Subkategori tidak sesuai dengan role yang dipilih.']);
            }
        } else {
            $data['role_category_id'] = null;
        }

        $data['identity_type'] = $this->identityTypeForRole($role);
        $this->validateIdentityNumberForRole($data['identity_number'], $role);
        $data['user_type'] = $this->userTypeForRole($role);

        return $data;
    }

    private function isSuperadmin(User $user): bool
    {
        return true;
    }

    private function availableRoles(User $actor)
    {
        return Role::query()
            ->active()
            ->when(! $this->isSuperadmin($actor), fn ($query) => $query->whereNotIn('slug', ['superadmin', 'admin']))
            ->orderByRaw("CASE WHEN slug = 'superadmin' THEN 0 WHEN slug = 'admin' THEN 1 ELSE 2 END")
            ->orderBy('nama_role');
    }

    private function availableCabangs(User $actor)
    {
        return Cabang::query()
            ->when(! $this->isSuperadmin($actor), fn ($query) => $query->whereKey($actor->cabang_id))
            ->orderBy('nama_cabang');
    }

    private function ensureUserVisible(User $actor, User $target): void
    {
        if ($this->isSuperadmin($actor)) {
            return;
        }

        abort_unless((int) $target->cabang_id === (int) $actor->cabang_id, 403);
    }

    private function assertCanManageTarget(User $actor, User $target): void
    {
        if ($this->isSuperadmin($actor)) {
            return;
        }

        $target->loadMissing('role');
        if (in_array($target->roleKey(), ['superadmin', 'admin'], true)) {
            throw ValidationException::withMessages([
                'role_id' => 'Admin cabang tidak boleh mengubah atau menghapus akun Admin dan Superadmin.',
            ]);
        }
    }

    private function assertRoleIsAssignable(User $actor, int $roleId): void
    {
        $role = Role::findOrFail($roleId);

        if (! $role->is_active) {
            throw ValidationException::withMessages([
                'role_id' => 'Role yang dipilih sedang nonaktif.',
            ]);
        }

        if ($this->isSuperadmin($actor)) {
            return;
        }

        $roleKey = $role->slug;
        if (in_array($roleKey, ['superadmin', 'admin'], true)) {
            throw ValidationException::withMessages([
                'role_id' => 'Admin cabang hanya boleh membuat/mengubah user non-admin di cabangnya sendiri.',
            ]);
        }
    }

    private function identityTypes(): array
    {
        return [
            'NIK Karyawan',
            'Kode Lembaga',
            'No Pegawai',
        ];
    }

    private function identityTypeForRole(Role $role): string
    {
        return match ($role->slug) {
            'staff', 'admin' => 'NIK Karyawan',
            'lembaga' => 'Kode Lembaga',
            default => 'No Pegawai',
        };
    }

    private function validateIdentityNumberForRole(string $identityNumber, Role $role): void
    {
        $roleKey = $role->slug;
        $value = trim($identityNumber);

        $valid = match ($roleKey) {
            'staff', 'admin' => (bool) preg_match('/^[0-9A-Za-z\-]{5,40}$/', $value),
            'lembaga' => (bool) preg_match('/^[0-9A-Za-z\-\/\.]{3,40}$/', $value),
            default => strlen($value) >= 3 && strlen($value) <= 50,
        };

        if (! $valid) {
            throw ValidationException::withMessages([
                'identity_number' => 'Format nomor identitas tidak sesuai dengan role yang dipilih.',
            ]);
        }
    }

    private function userTypeForRole(Role $role): string
    {
        return Str::headline($role->slug);
    }
}
