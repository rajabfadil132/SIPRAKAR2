# SIPRAKAR Final Fundamental Refactor

Dokumen ini menjelaskan perubahan final yang dilakukan agar struktur SIPRAKAR lebih matang, konsisten, aman, dan mudah dikembangkan.

## 1. Ringkasan Tujuan

Refactor ini memperkuat fundamental aplikasi pada bagian berikut:

- Relasi database yang lebih jelas dan kuat.
- Master data yang benar-benar menjadi sumber data utama.
- Kategori pekerjaan yang bisa dikaitkan dengan role dan subkategori role secara fleksibel.
- Seeder yang aman dijalankan ulang tanpa menghapus massal master data.
- Pembersihan legacy model yang tidak lagi digunakan.
- Test tambahan untuk memastikan relasi fundamental tetap berjalan.

## 2. Perubahan Database

### 2.1 Jenis Identitas menjadi relasi master data

Sebelumnya, user hanya menyimpan jenis identitas sebagai teks pada:

```text
users.identity_type
```

Sekarang ditambahkan foreign key baru:

```text
users.jenis_identitas_id → jenis_identitas.id
```

Kolom `identity_type` tetap dipertahankan sebagai label/snapshot agar kompatibel dengan tampilan lama, tetapi sumber data utamanya sekarang adalah `jenis_identitas_id`.

Migration baru:

```text
database/migrations/2026_06_09_000005_add_jenis_identitas_id_to_users_table.php
```

Migration ini juga melakukan backfill otomatis berdasarkan data lama:

- Staff/Admin → NIK Karyawan
- Lembaga → Kode Lembaga
- Default lainnya → No Pegawai

### 2.2 Relasi kategori pekerjaan ke role/subkategori role dibuat fleksibel

Sebelumnya kategori pekerjaan hanya dikaitkan ke subkategori role melalui:

```text
kategori_role_categories
```

Struktur ini kurang fleksibel karena kategori harus selalu punya subkategori role.

Sekarang dibuat pivot baru:

```text
kategori_pekerjaan_role_relations
```

Kolom:

```text
id
kategori_pekerjaan_id
role_id
role_category_id nullable
created_at
updated_at
```

Relasi ini mendukung dua mode:

```text
Kategori → Role utama saja
Kategori → Role + Subkategori Role
```

Contoh:

```text
Administrasi → Admin Cabang → Semua subkategori
Listrik → Staff Teknis → Teknisi Elektrikal
Kebersihan → Staff Teknis → Kebersihan / OB
```

Migration baru:

```text
database/migrations/2026_06_09_000004_create_kategori_pekerjaan_role_relations_table.php
```

Migration ini juga memigrasikan data lama dari `kategori_role_categories` lalu menghapus tabel legacy tersebut pada migrasi maju.

## 3. Perubahan Model

### 3.1 Model baru

Ditambahkan model:

```text
app/Models/KategoriPekerjaanRoleRelation.php
```

Model ini menjadi representasi resmi relasi kategori pekerjaan dengan role/subkategori role.

### 3.2 Model User

Ditambahkan relasi:

```php
public function jenisIdentitas()
{
    return $this->belongsTo(JenisIdentitas::class, 'jenis_identitas_id');
}
```

Dan field baru pada `$fillable`:

```text
jenis_identitas_id
```

### 3.3 Model KategoriPekerjaan

Ditambahkan relasi:

```php
roleRelations()
roles()
roleCategories()
```

Ditambahkan method sinkronisasi:

```php
syncRoleRelations(array $relations)
syncRoleCategories(array $roleCategoryIds)
```

`syncRoleCategories()` tetap disediakan sebagai backward compatibility untuk kode lama yang masih mengirim `role_category_ids`.

### 3.4 Model legacy dibersihkan

File berikut dihapus karena sudah tidak menjadi bagian dari domain final:

```text
app/Models/Lembaga.php
app/Models/Lokasi.php
```

Lembaga kini direpresentasikan sebagai role/subkategori role, sedangkan lokasi direpresentasikan dengan struktur:

```text
Cabang → Gedung → Lantai → Ruang
```

## 4. Perubahan Backend

### 4.1 MasterDataController

Perubahan:

- Mengirim data `roles` ke frontend Master Data.
- Mengirim kategori dengan relasi `roleRelations.role` dan `roleRelations.roleCategory`.
- Form kategori sekarang menerima `role_relations`.
- Validasi memastikan `role_category_id` harus sesuai dengan `role_id`.
- Payload lama `role_category_ids` tetap diterima untuk kompatibilitas.

### 4.2 UserManagementController

Perubahan:

- Form tambah/edit user sekarang wajib memilih `jenis_identitas_id`.
- Backend menyimpan `jenis_identitas_id` sebagai relasi utama.
- Backend otomatis mengisi `identity_type` dari master Jenis Identitas yang dipilih.
- Query user memuat relasi `jenisIdentitas`.
- Pencarian user dapat mencari berdasarkan nama/kode jenis identitas.

### 4.3 PekerjaanController dan ProgramKerjaController

Perubahan:

- Data kategori yang dikirim ke frontend memuat `roleRelations`.
- Form pekerjaan dapat memfilter user berdasarkan role atau subkategori role yang cocok dengan kategori.

## 5. Perubahan Frontend

### 5.1 Master Data Kategori

Form tambah/edit kategori sekarang mendukung:

- Nama kategori
- Keterangan
- Status
- Petugas yang sesuai berupa role utama atau subkategori role

Tampilan tabel kategori menampilkan:

```text
Nama Kategori
Keterangan
Petugas yang Sesuai
Status
Aksi
```

### 5.2 User Management

Form tambah/edit user sekarang menggunakan dropdown:

```text
Jenis Identitas
```

Dropdown mengambil data dari master Jenis Identitas aktif dan mengirim `jenis_identitas_id`, bukan teks bebas.

Tampilan user tetap menampilkan label yang ramah dibaca melalui relasi:

```text
user.jenis_identitas.nama_jenis
```

### 5.3 Tambah/Edit Pekerjaan

Filter user pada bagian petugas sekarang membaca relasi kategori yang lebih fleksibel:

- Jika relasi kategori hanya role utama, semua user dengan role tersebut boleh direkomendasikan.
- Jika relasi kategori role + subkategori, hanya user dengan subkategori tersebut yang direkomendasikan.

## 6. Seeder

Seeder diperbaiki agar lebih aman dan idempotent.

Perubahan penting:

- Tidak lagi melakukan delete massal pada `ruangs`, `lantais`, dan `gedungs`.
- Data master dibuat/diupdate dengan pola `withTrashed()` lalu restore jika perlu.
- Jenis Identitas dibuat sebagai master data aktif.
- User demo memiliki `jenis_identitas_id` yang sesuai.
- Lembaga memakai Jenis Identitas `Kode Lembaga`, bukan lagi `NIK Karyawan`.
- Kategori `Administrasi` ditambahkan sebagai contoh relasi kategori ke role utama tanpa subkategori.

Cabang demo yang tersedia:

```text
Viktor
Pusat
Witana Harja
Serang
```

## 7. Test Tambahan

Ditambahkan test baru:

```text
tests/Feature/System/FundamentalRelationsTest.php
```

Test ini memeriksa:

1. User memiliki relasi master `jenisIdentitas`.
2. Kategori pekerjaan memiliki relasi role/subkategori yang fleksibel.
3. Legacy model `Lembaga.php` dan `Lokasi.php` sudah tidak ada.

## 8. File yang Ditambahkan

```text
app/Models/KategoriPekerjaanRoleRelation.php
database/migrations/2026_06_09_000004_create_kategori_pekerjaan_role_relations_table.php
database/migrations/2026_06_09_000005_add_jenis_identitas_id_to_users_table.php
tests/Feature/System/FundamentalRelationsTest.php
SIPRAKAR_FINAL_FUNDAMENTAL_REFACTOR.md
```

## 9. File Legacy yang Dihapus

```text
app/Models/Lembaga.php
app/Models/Lokasi.php
```

## 10. Validasi yang Dilakukan

Validasi syntax PHP sudah dilakukan pada folder:

```text
app
database
routes
config
tests
```

Hasil:

```text
Tidak ada syntax error PHP.
```

Catatan: di environment ini tidak tersedia perintah `composer`, sehingga `php artisan test` dan `npm run build` belum bisa dijalankan dari sisi saya. Jalankan validasi lengkap di lokal project.

## 11. Perintah Validasi Lokal

Setelah extract ZIP, jalankan:

```bash
composer install
npm install
php artisan migrate:fresh --seed
npm run build
php artisan test
```

Untuk database existing tanpa reset:

```bash
php artisan migrate
php artisan db:seed --class=SiprakarSeeder
npm run build
php artisan test
```

## 12. Kesimpulan

Setelah refactor ini, fundamental SIPRAKAR menjadi lebih kuat karena:

```text
Jenis Identitas bukan teks bebas lagi, tetapi relasi master data.
Kategori pekerjaan tidak lagi bergantung hanya pada subkategori, tetapi bisa dikaitkan ke role utama atau subkategori role.
Seeder tidak lagi menghapus massal master data.
Legacy model yang membingungkan sudah dihapus.
Struktur kategori-petugas sudah siap untuk rekomendasi petugas otomatis di masa depan.
```
