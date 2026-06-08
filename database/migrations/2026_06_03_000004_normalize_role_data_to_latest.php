<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $roleDefinitions = [
            'superadmin' => ['name' => 'superadmin', 'description' => 'Pemilik sistem dengan akses penuh.'],
            'admin' => ['name' => 'admin', 'description' => 'Pengelola operasional sistem.'],
            'staff' => ['name' => 'staff', 'description' => 'Petugas pelaksana pekerjaan lapangan.'],
            'lembaga' => ['name' => 'lembaga', 'description' => 'Akun unit/lembaga kampus.'],
        ];

        foreach ($roleDefinitions as $slug => $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $slug],
                [
                    'nama_role' => $role['name'],
                    'keterangan' => $role['description'],
                    'is_system' => true,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $roles = DB::table('roles')->pluck('id', 'slug');

        $ensureCategory = function (string $roleSlug, string $categorySlug, string $name, string $description = '') use ($roles): ?int {
            $roleId = $roles[$roleSlug] ?? null;
            if (! $roleId) {
                return null;
            }

            DB::table('role_categories')->updateOrInsert(
                ['role_id' => $roleId, 'slug' => $categorySlug],
                [
                    'name' => $name,
                    'description' => $description,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            return DB::table('role_categories')
                ->where('role_id', $roleId)
                ->where('slug', $categorySlug)
                ->value('id');
        };

        $categories = [
            'staff.teknisi' => $ensureCategory('staff', 'teknisi', 'Teknisi', 'Menangani pekerjaan teknis seperti listrik, AC, air, dan fasilitas.'),
            'staff.security' => $ensureCategory('staff', 'security', 'Security', 'Menangani kebutuhan keamanan dan ketertiban.'),
            'staff.kebersihan-ob' => $ensureCategory('staff', 'kebersihan-ob', 'Kebersihan / OB', 'Menangani kebersihan, toilet, ruang kelas, dan area umum.'),
            'lembaga.lpaud' => $ensureCategory('lembaga', 'lpaud', 'LPAUD', 'Lembaga Pengembangan Aset dan Unit Dukungan.'),
            'lembaga.sarana-prasarana' => $ensureCategory('lembaga', 'sarana-prasarana', 'Sarana Prasarana', 'Unit sarana prasarana kampus.'),
        ];

        $normalizeRole = function (array $legacySlugs, string $targetSlug, ?string $categoryKey, string $identityType, string $userType) use ($roles, $categories): void {
            $targetRoleId = $roles[$targetSlug] ?? null;
            if (! $targetRoleId) {
                return;
            }

            $targetCategoryId = $categoryKey ? ($categories[$categoryKey] ?? null) : null;

            DB::table('roles')
                ->whereIn('slug', $legacySlugs)
                ->where('slug', '!=', $targetSlug)
                ->orderBy('id')
                ->get(['id'])
                ->each(function ($legacyRole) use ($targetRoleId, $targetCategoryId, $identityType, $userType): void {
                    DB::table('users')->where('role_id', $legacyRole->id)->update([
                        'role_id' => $targetRoleId,
                        'role_category_id' => $targetCategoryId,
                        'identity_type' => $identityType,
                        'user_type' => $userType,
                        'updated_at' => now(),
                    ]);

                    DB::table('role_permissions')->where('role_id', $legacyRole->id)->delete();
                    DB::table('role_categories')->where('role_id', $legacyRole->id)->delete();
                    DB::table('roles')->where('id', $legacyRole->id)->delete();
                });
        };

        $normalizeRole(['super-admin', 'super-administrator', 'superadministrator'], 'superadmin', null, 'No Pegawai', 'Superadmin');
        $normalizeRole(['administrator'], 'admin', null, 'NIK Karyawan', 'Admin');
        $normalizeRole(['petugas'], 'staff', 'staff.teknisi', 'NIK Karyawan', 'Staff');
        $normalizeRole(['teknisi'], 'staff', 'staff.teknisi', 'NIK Karyawan', 'Staff');
        $normalizeRole(['security'], 'staff', 'staff.security', 'NIK Karyawan', 'Staff');
        $normalizeRole(['kebersihan', 'ob', 'cleaning-service'], 'staff', 'staff.kebersihan-ob', 'NIK Karyawan', 'Staff');
        $normalizeRole(['unit-lembaga', 'unitlembaga'], 'lembaga', 'lembaga.sarana-prasarana', 'Kode Lembaga', 'Lembaga');
        DB::table('roles')
            ->whereIn('slug', array_keys($roleDefinitions))
            ->update(['is_system' => true, 'is_active' => true, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Data role lama sengaja tidak dikembalikan agar sistem tetap memakai struktur role terbaru.
    }
};
