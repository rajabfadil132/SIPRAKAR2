<?php

namespace Database\Seeders;

use App\Models\{Cabang, Gedung, JenisIdentitas, KategoriPekerjaan, Lantai, Role, RoleCategory, RolePermission, Ruang, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SiprakarSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = config('siprakar_permissions.keys', []);

        // Soft-delete cascade: hapus detail lokasi, lalu cabang (soft-delete).
        Ruang::query()->delete();
        Lantai::query()->delete();
        Gedung::query()->delete();

        $existingKode = ['VTR', 'PST', 'WTH', 'SRG'];
        foreach ($existingKode as $kode) {
            $c = \App\Models\Cabang::withTrashed()->where('kode', $kode)->first();
            if ($c) {
                $c->forceFill([
                    'nama_cabang' => match ($kode) {
                        'VTR' => 'Viktor',
                        'PST' => 'Pusat',
                        'WTH' => 'Witana Harja',
                        'SRG' => 'Serang',
                        default => $c->nama_cabang,
                    },
                    'alamat' => match ($kode) {
                        'VTR' => 'Jl. Viktor No.1',
                        'PST' => 'Jl. Pusat Administrasi',
                        'WTH' => 'Jl. Witana Harja No.1, Tangerang',
                        'SRG' => 'Jl. Serang Raya No.1, Banten',
                        default => $c->alamat,
                    },
                    'status' => 'active',
                    'deleted_at' => null,
                ])->save();
            }
        }

        $viktor = cabang::updateOrCreate(
            ['kode' => 'VTR'],
            ['nama_cabang' => 'Viktor', 'alamat' => 'Jl. Viktor No.1', 'status' => 'active']
        );
        $pusat = cabang::updateOrCreate(
            ['kode' => 'PST'],
            ['nama_cabang' => 'Pusat', 'alamat' => 'Jl. Pusat Administrasi', 'status' => 'active']
        );
        $witanaHarja = cabang::updateOrCreate(
            ['kode' => 'WTH'],
            ['nama_cabang' => 'Witana Harja', 'alamat' => 'Jl. Witana Harja No.1, Tangerang', 'status' => 'active']
        );
        $serang = cabang::updateOrCreate(
            ['kode' => 'SRG'],
            ['nama_cabang' => 'Serang', 'alamat' => 'Jl. Serang Raya No.1, Banten', 'status' => 'active']
        );

        $roleDefinitions = [
            'superadmin' => [
                'nama_role' => 'Superadmin',
                'keterangan' => 'Akun sistem dengan akses penuh seluruh modul.',
                'permissions' => array_fill_keys($permissions, true),
            ],
            'admin' => [
                'nama_role' => 'Admin Cabang',
                'keterangan' => 'Admin operasional cabang dengan akses penuh pada data cabangnya.',
                'permissions' => array_fill_keys($permissions, true),
            ],
            'staff' => [
                'nama_role' => 'Staff Teknis',
                'keterangan' => 'Petugas pelaksana pekerjaan dan update checklist/progress.',
                'permissions' => array_fill_keys($permissions, false),
                'allow' => [
                    'dashboard.view',
                    'program_kerja.view', 'program_kerja.show',
                    'pekerjaan.view', 'pekerjaan.show', 'pekerjaan.progress',
                    'rab.view',
                    'notifications.view',
                ],
            ],
            'lembaga' => [
                'nama_role' => 'Lembaga',
                'keterangan' => 'Pengusul/pemantau program kerja dengan akses baca terbatas.',
                'permissions' => array_fill_keys($permissions, false),
                'allow' => [
                    'dashboard.view',
                    'program_kerja.view', 'program_kerja.show', 'program_kerja.create',
                    'pekerjaan.view', 'pekerjaan.show',
                    'rab.view',
                    'notifications.view',
                ],
            ],
        ];

        $roles = [];
        foreach ($roleDefinitions as $slug => $definition) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'nama_role' => $definition['nama_role'],
                    'keterangan' => $definition['keterangan'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $map = $definition['permissions'];
            foreach ($definition['allow'] ?? [] as $allowed) {
                $map[$allowed] = true;
            }

            RolePermission::updateOrCreate(
                ['role_id' => $role->id],
                ['permissions' => $map]
            );

            $roles[$slug] = $role;
        }

        // Subkategori Role Staff
        $teknisiPrasarana = RoleCategory::updateOrCreate(
            ['role_id' => $roles['staff']->id, 'slug' => 'teknisi-prasarana'],
            ['name' => 'Teknisi Prasarana', 'description' => 'Staff teknis pelaksana pekerjaan prasarana.', 'is_active' => true]
        );
        $teknisiElektrikal = RoleCategory::updateOrCreate(
            ['role_id' => $roles['staff']->id, 'slug' => 'teknisi-elektrikal'],
            ['name' => 'Teknisi Elektrikal', 'description' => 'Staff teknis listrik dan peralatan elektrik.', 'is_active' => true]
        );
        $security = RoleCategory::updateOrCreate(
            ['role_id' => $roles['staff']->id, 'slug' => 'security'],
            ['name' => 'Security', 'description' => 'Petugas keamanan dan keselamatan.', 'is_active' => true]
        );
        $kebersihan = RoleCategory::updateOrCreate(
            ['role_id' => $roles['staff']->id, 'slug' => 'kebersihan-ob'],
            ['name' => 'Kebersihan / OB', 'description' => 'Petugas kebersihan dan office boy.', 'is_active' => true]
        );

        // Subkategori Role Lembaga
        $lpud = RoleCategory::updateOrCreate(
            ['role_id' => $roles['lembaga']->id, 'slug' => 'lpud'],
            ['name' => 'LPAUD', 'description' => 'Lembaga PAUD.', 'is_active' => true]
        );
        $saranaPrasarana = RoleCategory::updateOrCreate(
            ['role_id' => $roles['lembaga']->id, 'slug' => 'sarana-prasarana'],
            ['name' => 'Sarana Prasarana', 'description' => 'Unit sarana prasarana.', 'is_active' => true]
        );
        $unitPengusul = RoleCategory::updateOrCreate(
            ['role_id' => $roles['lembaga']->id, 'slug' => 'unit-pengusul'],
            ['name' => 'Unit Pengusul', 'description' => 'Unit/lembaga yang mengajukan program kerja.', 'is_active' => true]
        );

        // ===== JENIS IDENTITAS =====
        $jenisIdentitasData = [
            ['nama_jenis' => 'NIK Karyawan', 'kode' => 'NIK', 'keterangan' => 'Nomor Induk Kependudukan untuk staff dan admin'],
            ['nama_jenis' => 'No Pegawai', 'kode' => 'NOPEG', 'keterangan' => 'Nomor pegawai internal'],
            ['nama_jenis' => 'Kode Lembaga', 'kode' => 'LEMB', 'keterangan' => 'Kode unik lembaga/perguruan tinggi'],
            ['nama_jenis' => 'NIP', 'kode' => 'NIP', 'keterangan' => 'Nomor Induk Pegawai Negeri Sipil'],
            ['nama_jenis' => 'NIDN', 'kode' => 'NIDN', 'keterangan' => 'Nomor Induk Dosen Nasional'],
            ['nama_jenis' => 'NIM', 'kode' => 'NIM', 'keterangan' => 'Nomor Induk Mahasiswa'],
        ];
        foreach ($jenisIdentitasData as $ji) {
            JenisIdentitas::updateOrCreate(
                ['kode' => $ji['kode']],
                ['nama_jenis' => $ji['nama_jenis'], 'keterangan' => $ji['keterangan'], 'status' => 'active']
            );
        }

        // ===== KATEGORI PEKERJAAN =====
        $kategoris = [];
        $kategoriDefinitions = [
            'Pemeliharaan' => ['keterangan' => 'Pekerjaan pemeliharaan rutin berkala untuk menjaga kondisi aset.', 'role_categories' => [$teknisiPrasarana]],
            'Perbaikan AC' => ['keterangan' => 'Perbaikan dan servis AC (Air Conditioner).', 'role_categories' => [$teknisiPrasarana]],
            'Listrik' => ['keterangan' => 'Pekerjaan terkait instalasi dan perbaikan listrik.', 'role_categories' => [$teknisiElektrikal]],
            'Air' => ['keterangan' => 'Pekerjaan terkait plumbing, air bersih, dan sanitasi.', 'role_categories' => [$teknisiPrasarana]],
            'Bangunan' => ['keterangan' => 'Perbaikan struktur dan bangunan.', 'role_categories' => [$teknisiPrasarana]],
            'Furnitur' => ['keterangan' => 'Perbaikan dan perawatan furnitur dan meja kursi.', 'role_categories' => [$teknisiPrasarana]],
            'Kebersihan' => ['keterangan' => 'Pekerjaan kebersihan umum dan fasilitas sanitasi.', 'role_categories' => [$kebersihan]],
            'Keamanan' => ['keterangan' => 'Pekerjaan terkait keamanan, CCTV, dan pintu darurat.', 'role_categories' => [$security]],
            'Pengadaan' => ['keterangan' => 'Pengadaan barang dan peralatan baru.', 'role_categories' => [$saranaPrasarana]],
            'Renovasi' => ['keterangan' => 'Pekerjaan renovasi dan perbaikan besar.', 'role_categories' => [$teknisiPrasarana]],
            'Fasilitas' => ['keterangan' => 'Perawatan fasilitas umum dan taman.', 'role_categories' => [$kebersihan, $teknisiPrasarana]],
        ];

        foreach ($kategoriDefinitions as $nama => $def) {
            $kat = KategoriPekerjaan::updateOrCreate(
                ['nama_kategori' => $nama],
                ['keterangan' => $def['keterangan'], 'status' => 'active']
            );
            $kat->roleCategories()->sync(collect($def['role_categories'])->pluck('id')->all());
            $kategoris[$nama] = $kat;
        }

        // ===== GEDUNG, LANTAI, RUANG PER CABANG =====

        // Helper untuk membuat gedung-lantai-ruang
        $createTree = function (Cabang $cabang, array $gedungs) {
            foreach ($gedungs as $gedungDef) {
                $gedung = Gedung::withTrashed()
                    ->where('cabang_id', $cabang->id)
                    ->whereRaw('LOWER(nama_gedung) = ?', [mb_strtolower($gedungDef['nama'])])
                    ->first();

                if (! $gedung) {
                    $gedung = Gedung::create([
                        'cabang_id' => $cabang->id,
                        'nama_gedung' => $gedungDef['nama'],
                        'status' => 'active',
                    ]);
                } else {
                    if ($gedung->trashed()) {
                        $gedung->restore();
                    }
                    $gedung->update(['nama_gedung' => $gedungDef['nama'], 'status' => 'active']);
                }
                foreach ($gedungDef['lantais'] as $lantaiDef) {
                    $lantai = Lantai::withTrashed()
                        ->where('gedung_id', $gedung->id)
                        ->where('nomor_lantai', $lantaiDef['nomor'])
                        ->first();

                    if (! $lantai) {
                        $lantai = Lantai::create([
                            'gedung_id' => $gedung->id,
                            'nomor_lantai' => $lantaiDef['nomor'],
                            'nama_lantai' => $lantaiDef['nama'],
                            'status' => 'active',
                        ]);
                    } else {
                        if ($lantai->trashed()) {
                            $lantai->restore();
                        }
                        $lantai->update(['nama_lantai' => $lantaiDef['nama'], 'status' => 'active']);
                    }
                    foreach ($lantaiDef['ruangs'] as $ruangNama) {
                        $ruang = Ruang::withTrashed()
                            ->where('lantai_id', $lantai->id)
                            ->whereRaw('LOWER(nama_ruang) = ?', [mb_strtolower($ruangNama)])
                            ->first();

                        if (! $ruang) {
                            Ruang::create([
                                'lantai_id' => $lantai->id,
                                'nama_ruang' => $ruangNama,
                                'status' => 'active',
                            ]);
                        } else {
                            if ($ruang->trashed()) {
                                $ruang->restore();
                            }
                            $ruang->update(['nama_ruang' => $ruangNama, 'status' => 'active']);
                        }
                    }
                }
            }
        };

        // Viktor
        $createTree($viktor, [
            [
                'nama' => 'Gedung Utama Viktor',
                'lantais' => [
                    ['nomor' => 0, 'nama' => 'Basement', 'ruangs' => ['Ruang Genset', 'Ruang Panel Listrik', 'Ruang Pompa Air']],
                    ['nomor' => 1, 'nama' => 'Lantai 1', 'ruangs' => ['Ruang Lobby', 'Ruang TU', 'Ruang Rapat A', 'Ruang Meeting']],
                    ['nomor' => 2, 'nama' => 'Lantai 2', 'ruangs' => ['Ruang Staff A', 'Ruang Staff B', 'Ruang Arsip']],
                ],
            ],
            [
                'nama' => 'Gedung Serbaguna Viktor',
                'lantais' => [
                    ['nomor' => 1, 'nama' => 'Lantai 1', 'ruangs' => ['Aula Utama', 'Ruang Serbaguna 1', 'Ruang Serbaguna 2']],
                ],
            ],
        ]);

        // Pusat
        $createTree($pusat, [
            [
                'nama' => 'Gedung Administrasi Pusat',
                'lantais' => [
                    ['nomor' => 1, 'nama' => 'Lantai 1', 'ruangs' => ['Ruang reception', 'Ruang Direksi', 'Ruang Keuangan', 'Ruang TU']],
                    ['nomor' => 2, 'nama' => 'Lantai 2', 'ruangs' => ['Ruang Staff A', 'Ruang Staff B', 'Ruang IT', 'Ruang Arsip']],
                    ['nomor' => 3, 'nama' => 'Lantai 3', 'ruangs' => ['Ruang Rapat Utama', 'Ruang Seminar', 'Ruang Server']],
                ],
            ],
        ]);

        // Witana Harja
        $createTree($witanaHarja, [
            [
                'nama' => 'Gedung A Witana Harja',
                'lantais' => [
                    ['nomor' => 0, 'nama' => 'Basement', 'ruangs' => ['Ruang Genset', 'Ruang Pompa']],
                    ['nomor' => 1, 'nama' => 'Lantai 1', 'ruangs' => ['Ruang Lobby', 'Ruang TU', 'Ruang Arsip']],
                    ['nomor' => 2, 'nama' => 'Lantai 2', 'ruangs' => ['Ruang Kelas 1', 'Ruang Kelas 2', 'Ruang Staff']],
                ],
            ],
            [
                'nama' => 'Gedung B Witana Harja',
                'lantais' => [
                    ['nomor' => 1, 'nama' => 'Lantai 1', 'ruangs' => ['Ruang Serbaguna', 'Ruang UKS', 'Ruang Perpustakaan']],
                ],
            ],
        ]);

        // Serang
        $createTree($serang, [
            [
                'nama' => 'Gedung Utama Serang',
                'lantais' => [
                    ['nomor' => 0, 'nama' => 'Basement', 'ruangs' => ['Ruang Genset', 'Ruang Panel']],
                    ['nomor' => 1, 'nama' => 'Lantai 1', 'ruangs' => ['Ruang Lobby', 'Ruang TU', 'Ruang Keuangan', 'Ruang Admin']],
                    ['nomor' => 2, 'nama' => 'Lantai 2', 'ruangs' => ['Ruang Kelas A', 'Ruang Kelas B', 'Ruang Meeting', 'Ruang Staff']],
                    ['nomor' => 3, 'nama' => 'Lantai 3', 'ruangs' => ['Ruang Aula', 'Ruang Serbaguna', 'Ruang Perpustakaan']],
                ],
            ],
            [
                'nama' => 'Gedung Olahraga Serang',
                'lantais' => [
                    ['nomor' => 1, 'nama' => 'Lantai 1', 'ruangs' => ['Aula Olahraga', 'Ruang Pelatih', 'Ruang Ganti']],
                ],
            ],
        ]);

        // ===== USER AKUN =====

        User::updateOrCreate(
            ['email' => 'superadmin@siprakar.test'],
            [
                'name' => 'Superadmin SIPRAKAR',
                'identity_number' => 'SA-001',
                'identity_type' => 'No Pegawai',
                'user_type' => 'Superadmin',
                'password' => Hash::make('password'),
                'role_id' => $roles['superadmin']->id,
                'role_category_id' => null,
                'cabang_id' => null,
                'phone' => '08123456789',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Staff Viktor - Teknisi Prasarana
        User::updateOrCreate(
            ['email' => 'teknisi.vtr@siprakar.test'],
            [
                'name' => 'Budi Santoso',
                'identity_number' => 'VTR-STF-001',
                'identity_type' => 'NIK Karyawan',
                'user_type' => 'Staff',
                'password' => Hash::make('password'),
                'role_id' => $roles['staff']->id,
                'role_category_id' => $teknisiPrasarana->id,
                'cabang_id' => $viktor->id,
                'phone' => '08123456710',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Staff Viktor - Security
        User::updateOrCreate(
            ['email' => 'security.vtr@siprakar.test'],
            [
                'name' => 'Joko Pramono',
                'identity_number' => 'VTR-SEC-001',
                'identity_type' => 'NIK Karyawan',
                'user_type' => 'Staff',
                'password' => Hash::make('password'),
                'role_id' => $roles['staff']->id,
                'role_category_id' => $security->id,
                'cabang_id' => $viktor->id,
                'phone' => '08123456711',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Staff Witana Harja - Teknisi Elektrikal
        User::updateOrCreate(
            ['email' => 'teknisi.wth@siprakar.test'],
            [
                'name' => 'Ahmad Dahlan',
                'identity_number' => 'WTH-STF-001',
                'identity_type' => 'NIK Karyawan',
                'user_type' => 'Staff',
                'password' => Hash::make('password'),
                'role_id' => $roles['staff']->id,
                'role_category_id' => $teknisiElektrikal->id,
                'cabang_id' => $witanaHarja->id,
                'phone' => '08123456720',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Staff Witana Harja - Kebersihan
        User::updateOrCreate(
            ['email' => 'kebersihan.wth@siprakar.test'],
            [
                'name' => 'Siti Aminah',
                'identity_number' => 'WTH-KBR-001',
                'identity_type' => 'NIK Karyawan',
                'user_type' => 'Staff',
                'password' => Hash::make('password'),
                'role_id' => $roles['staff']->id,
                'role_category_id' => $kebersihan->id,
                'cabang_id' => $witanaHarja->id,
                'phone' => '08123456721',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Staff Serang - Teknisi Prasarana
        User::updateOrCreate(
            ['email' => 'teknisi.srg@siprakar.test'],
            [
                'name' => 'Rudi Hermawan',
                'identity_number' => 'SRG-STF-001',
                'identity_type' => 'NIK Karyawan',
                'user_type' => 'Staff',
                'password' => Hash::make('password'),
                'role_id' => $roles['staff']->id,
                'role_category_id' => $teknisiPrasarana->id,
                'cabang_id' => $serang->id,
                'phone' => '08123456730',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Staff Serang - Security
        User::updateOrCreate(
            ['email' => 'security.srg@siprakar.test'],
            [
                'name' => 'Dedi Kurniawan',
                'identity_number' => 'SRG-SEC-001',
                'identity_type' => 'NIK Karyawan',
                'user_type' => 'Staff',
                'password' => Hash::make('password'),
                'role_id' => $roles['staff']->id,
                'role_category_id' => $security->id,
                'cabang_id' => $serang->id,
                'phone' => '08123456731',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Lembaga Witana Harja - Sarana Prasarana
        User::updateOrCreate(
            ['email' => 'sarpras.wth@siprakar.test'],
            [
                'name' => 'Hendra Wijaya',
                'identity_number' => 'WTH-LMB-001',
                'identity_type' => 'NIK Karyawan',
                'user_type' => 'Lembaga',
                'password' => Hash::make('password'),
                'role_id' => $roles['lembaga']->id,
                'role_category_id' => $saranaPrasarana->id,
                'cabang_id' => $witanaHarja->id,
                'phone' => '08123456722',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Lembaga Serang - Unit Pengusul
        User::updateOrCreate(
            ['email' => 'pengusul.srg@siprakar.test'],
            [
                'name' => 'Nurul Hidayah',
                'identity_number' => 'SRG-LMB-001',
                'identity_type' => 'NIK Karyawan',
                'user_type' => 'Lembaga',
                'password' => Hash::make('password'),
                'role_id' => $roles['lembaga']->id,
                'role_category_id' => $unitPengusul->id,
                'cabang_id' => $serang->id,
                'phone' => '08123456732',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Admin Witana Harja
        User::updateOrCreate(
            ['email' => 'admin.wth@siprakar.test'],
            [
                'name' => 'Admin Witana Harja',
                'identity_number' => 'WTH-ADM-001',
                'identity_type' => 'NIK Karyawan',
                'user_type' => 'Admin',
                'password' => Hash::make('password'),
                'role_id' => $roles['admin']->id,
                'role_category_id' => null,
                'cabang_id' => $witanaHarja->id,
                'phone' => '08123456723',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Admin Serang
        User::updateOrCreate(
            ['email' => 'admin.srg@siprakar.test'],
            [
                'name' => 'Admin Serang',
                'identity_number' => 'SRG-ADM-001',
                'identity_type' => 'NIK Karyawan',
                'user_type' => 'Admin',
                'password' => Hash::make('password'),
                'role_id' => $roles['admin']->id,
                'role_category_id' => null,
                'cabang_id' => $serang->id,
                'phone' => '08123456733',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
