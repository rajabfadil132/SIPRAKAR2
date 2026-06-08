<?php

namespace Database\Seeders;

use App\Models\{Cabang, Gedung, KategoriPekerjaan, Lantai, Role, RoleCategory, RolePermission, Ruang, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SiprakarSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = config('siprakar_permissions.keys', []);

        // Bersihkan master lokasi detail, lalu siapkan cabang dasar agar kode Program Kerja selalu punya kode cabang.
        Ruang::query()->delete();
        Lantai::query()->delete();
        Gedung::query()->delete();
        Cabang::query()->delete();

        Cabang::updateOrCreate(
            ['kode' => 'VTR'],
            ['nama_cabang' => 'Viktor', 'alamat' => 'Cabang Viktor', 'status' => 'active']
        );
        Cabang::updateOrCreate(
            ['kode' => 'PST'],
            ['nama_cabang' => 'Pusat', 'alamat' => 'Cabang Pusat', 'status' => 'active']
        );

        $superadminRole = Role::updateOrCreate(
            ['slug' => 'superadmin'],
            [
                'nama_role' => 'Superadmin',
                'keterangan' => 'Akun tunggal dengan akses penuh tanpa pembatas role.',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        RolePermission::updateOrCreate(
            ['role_id' => $superadminRole->id],
            ['permissions' => array_fill_keys($permissions, true)]
        );

        // Hilangkan role/subkategori lain agar tidak ada pembatas role bawaan.
        RoleCategory::query()->delete();
        RolePermission::query()->where('role_id', '!=', $superadminRole->id)->delete();
        Role::query()->where('id', '!=', $superadminRole->id)->delete();

        foreach (['Pemeliharaan', 'Perbaikan AC', 'Listrik', 'Air', 'Bangunan', 'Furnitur', 'Kebersihan', 'Keamanan', 'Pengadaan', 'Renovasi'] as $nama) {
            KategoriPekerjaan::updateOrCreate(
                ['nama_kategori' => $nama],
                ['keterangan' => 'Kategori pekerjaan '.$nama, 'status' => 'active']
            );
        }

        User::query()->where('email', '!=', 'superadmin@siprakar.test')->delete();

        User::updateOrCreate(
            ['email' => 'superadmin@siprakar.test'],
            [
                'name' => 'Superadmin SIPRAKAR',
                'identity_number' => 'SA-001',
                'identity_type' => 'No Pegawai',
                'user_type' => 'Superadmin',
                'password' => Hash::make('password'),
                'role_id' => $superadminRole->id,
                'role_category_id' => null,
                'cabang_id' => null,
                'phone' => '08123456789',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
