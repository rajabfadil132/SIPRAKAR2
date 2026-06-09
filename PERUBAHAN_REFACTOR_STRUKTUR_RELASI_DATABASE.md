# Perubahan Refactor Struktur, Relasi, Permission, dan Database SIPRAKAR

Dokumen ini menjelaskan perubahan besar yang dilakukan pada project **SIPRAKAR2-main** agar sistem lebih terorganisir, relasi antar-entitas lebih matang, dan database lebih aman untuk dikembangkan ke depan.

## Tujuan Refactor

Refactor ini mengikuti rekomendasi arsitektur sebelumnya, yaitu:

1. Mengaktifkan kembali enforcement role/permission di backend.
2. Memperkuat relasi database agar tidak hanya dijaga oleh controller/service.
3. Menambahkan `status_key` berbasis enum agar status tidak lagi bergantung pada string label bebas.
4. Memisahkan business logic dari controller ke service domain.
5. Mencegah pekerjaan tanpa checklist dianggap selesai otomatis.
6. Membersihkan legacy model yang tidak lagi dipakai.
7. Menyiapkan migration transisi untuk database lama.

---

## 1. Penambahan Enum Status

Ditambahkan folder baru:

```text
app/Enums
```

File yang ditambahkan:

```text
app/Enums/ProgramKerjaStatus.php
app/Enums/RabStatus.php
app/Enums/PekerjaanStatus.php
```

### Fungsi enum

Enum dipakai untuk menyimpan status secara stabil di database melalui key, tetapi tetap menampilkan label Indonesia di UI.

Contoh:

```text
status_key = rab_approved
status     = RAB Disetujui
```

Dengan pola ini, sistem lebih aman dari typo seperti:

```text
RAB Disetujui
Rab disetujui
RAB disetujui
Disetujui RAB
```

### Status Program Kerja

```text
rab_submitted   => RAB Diajukan
rab_revision    => RAB Direvisi
rab_approved    => RAB Disetujui
ready_for_work  => Siap Dijadikan Pekerjaan
converted       => Dijadikan Pekerjaan
completed       => Selesai
cancelled       => Dibatalkan
```

### Status RAB

```text
submitted => Diajukan
revision  => Direvisi
approved  => Disetujui
rejected  => Ditolak
```

### Status Pekerjaan

```text
not_started => Belum Diproses
in_progress => Diproses
completed   => Selesai
cancelled   => Dibatalkan
```

---

## 2. Perubahan Model Utama

Model yang diperbarui:

```text
app/Models/ProgramKerja.php
app/Models/Rab.php
app/Models/Pekerjaan.php
app/Models/User.php
```

### ProgramKerja

Perubahan penting:

- Menambahkan field `status_key` ke `$fillable`.
- Menambahkan method `statusEnum()`.
- Menyinkronkan otomatis `status_key` dan `status` saat model disimpan.
- Memperkuat logic `canBecomePekerjaan()` dengan enum.
- Memastikan Program Kerja hanya bisa menjadi pekerjaan jika:
  - tidak perlu RAB dan status `ready_for_work`, atau
  - perlu RAB dan RAB sudah `approved`.

### Rab

Perubahan penting:

- Menambahkan field `status_rab_key` ke `$fillable`.
- Menambahkan method `statusEnum()`.
- Menyinkronkan otomatis `status_rab_key` dan `status_rab` saat model disimpan.

### Pekerjaan

Perubahan penting:

- Menambahkan field `status_key` ke `$fillable`.
- Menambahkan method `statusEnum()`.
- Progress pekerjaan tetap dihitung dari checklist.
- Pekerjaan tanpa checklist sekarang dihitung `0%`, bukan `100%`.

### User

Perubahan penting:

- `hasPermission()` sekarang benar-benar membaca permission dari role.
- User inactive/suspended tidak mendapat permission.
- Role nonaktif tidak mendapat permission.
- Subkategori role nonaktif tidak mendapat permission.
- `superadmin` tetap mendapat semua akses.
- Login diarahkan ke menu pertama yang sesuai dengan hak akses user.

---

## 3. Permission Backend Diaktifkan

File yang diperbarui:

```text
app/Http/Middleware/PermissionMiddleware.php
app/Http/Middleware/RoleMiddleware.php
routes/web.php
routes/siprakar.php
routes/system.php
```

Sebelumnya permission hanya bersifat tampilan/UI dan belum benar-benar memblokir akses backend.

Sekarang setiap route penting sudah diberi middleware permission, misalnya:

```text
program_kerja.view
program_kerja.create
program_kerja.edit
program_kerja.delete
pekerjaan.view
pekerjaan.create
pekerjaan.edit
pekerjaan.delete
pekerjaan.progress
rab.view
rab.create
rab.edit
rab.delete
reports.view
master_data.view
users.view
notifications.view
```

Jika user tidak punya permission, backend akan mengembalikan HTTP `403 Forbidden`.

---

## 4. Service Layer Baru

Ditambahkan folder:

```text
app/Services/Siprakar
```

File baru:

```text
app/Services/Siprakar/ProgramKerjaService.php
app/Services/Siprakar/RabService.php
```

### ProgramKerjaService

Bertanggung jawab untuk:

- Membuat Program Kerja.
- Mengupdate Program Kerja.
- Menormalisasi estimasi item.
- Menghitung total estimasi.
- Membuat RAB otomatis jika Program Kerja memiliki estimasi item.

### RabService

Bertanggung jawab untuk:

- Membuat RAB dari Program Kerja.
- Membuat RAB otomatis dari estimasi Program Kerja.
- Mengubah status RAB.
- Menyinkronkan status RAB ke Program Kerja.
- Mengunci item RAB jika status sudah tidak editable.
- Menghitung ulang total RAB.

### PekerjaanService

File yang diperbarui:

```text
app/Services/Pekerjaan/PekerjaanService.php
```

Perubahan penting:

- Pekerjaan wajib memiliki minimal satu checklist.
- Progress tanpa checklist tidak lagi otomatis 100%.
- Status pekerjaan dihitung menggunakan enum.
- Status Program Kerja ikut sinkron saat pekerjaan selesai/dibatalkan.
- RAB approved dicek menggunakan enum, bukan string bebas.

---

## 5. Perubahan Controller

Controller yang diperbarui:

```text
app/Http/Controllers/Siprakar/ProgramKerjaController.php
app/Http/Controllers/Siprakar/RabController.php
app/Http/Controllers/Siprakar/PekerjaanController.php
app/Http/Controllers/Siprakar/ProgramKerja/ConvertToPekerjaanController.php
app/Http/Controllers/Auth/AuthenticatedSessionController.php
app/Http/Controllers/System/UserManagementController.php
app/Http/Controllers/System/RolePermissionController.php
```

### ProgramKerjaController

Perubahan:

- Logic create/update dipindahkan ke `ProgramKerjaService`.
- Logic lama yang menduplikasi pembuatan RAB dibersihkan.
- Controller sekarang lebih fokus ke validasi request, response, dan tampilan.

### RabController

Perubahan:

- Logic create/update status/item dipindahkan ke `RabService`.
- Status RAB memakai enum.
- Item RAB terkunci saat status sudah tidak editable.
- Logic lama yang menduplikasi perubahan status dibersihkan.

### PekerjaanController

Perubahan:

- Validasi checklist sekarang wajib minimal satu item.
- Status manual `Selesai` tetap tidak boleh dipilih langsung.
- RAB approved dicek dengan enum.

### ConvertToPekerjaanController

Perubahan:

- Saat Program Kerja dikonversi ke pekerjaan, sistem memastikan pekerjaan memiliki checklist.
- Jika checklist tidak dikirim dari form, sistem memberi checklist default:
  - Survei lokasi dan validasi kebutuhan.
  - Pelaksanaan pekerjaan.
  - Pemeriksaan akhir dan dokumentasi.

### AuthenticatedSessionController

Perubahan:

- Setelah login, user diarahkan ke route pertama yang sesuai dengan permission-nya.
- User tanpa akses dashboard tidak langsung dipaksa ke dashboard.

### UserManagementController

Perubahan:

- Check superadmin tidak lagi selalu `true`.
- Hanya role `superadmin` yang diperlakukan sebagai superadmin.

### RolePermissionController

Perubahan:

- Aksi sensitif di role/permission sekarang benar-benar dibatasi untuk superadmin.

---

## 6. Perubahan Database dan Migration

Migration awal diperkuat agar fresh install langsung memiliki struktur yang lebih matang.

File migration yang diperbarui antara lain:

```text
database/migrations/2026_01_01_000001_create_siprakar_master_tables.php
database/migrations/2026_01_01_000002_create_siprakar_transaction_tables.php
database/migrations/2026_05_19_000001_add_audit_columns_to_rab_details.php
database/migrations/2026_05_21_000001_add_permissions_and_pekerjaan_checklists.php
database/migrations/2026_06_02_000004_create_pekerjaan_petugas_table_table.php
database/migrations/2026_06_03_000001_add_workflow_audit_columns_to_pekerjaans_table.php
database/migrations/2026_06_03_000003_add_program_conversion_fields.php
database/migrations/2026_06_03_000006_enforce_final_program_pekerjaan_flow_table.php
```

Migration baru ditambahkan:

```text
database/migrations/2026_06_09_000001_harden_siprakar_relations_and_status_keys.php
```

### Struktur baru yang diperkuat

#### Program Kerja

Ditambahkan:

```text
program_kerjas.status_key
```

Relasi konversi diperkuat:

```text
program_kerjas.converted_to_pekerjaan_id -> pekerjaans.id
```

#### RAB

Ditambahkan:

```text
rabs.status_rab_key
```

Relasi RAB diperkuat menjadi satu-satu:

```text
rabs.program_kerja_id unique
rabs.pekerjaan_id unique
```

Artinya:

```text
1 Program Kerja = maksimal 1 RAB
1 Pekerjaan    = maksimal 1 RAB
```

#### Pekerjaan

Ditambahkan:

```text
pekerjaans.status_key
```

Relasi pekerjaan ke Program Kerja diperkuat:

```text
pekerjaans.program_kerja_id wajib ada
```

Ini sesuai dengan aturan aplikasi bahwa Data Pekerjaan wajib berasal dari Program Kerja.

#### Progress Pekerjaan

Kolom ganda dihapus:

```text
progress_pekerjaans.program_kerja_id
```

Sekarang progress cukup merujuk ke:

```text
progress_pekerjaans.pekerjaan_id
```

Program Kerja dapat diambil lewat relasi:

```text
progress_pekerjaans -> pekerjaans -> program_kerjas
```

Ini mencegah data progress menyimpan `program_kerja_id` yang tidak sinkron dengan pekerjaan.

#### Audit Columns

Kolom audit dibuat lebih konsisten sebagai foreign key ke `users`:

```text
created_by -> users.id
updated_by -> users.id
deleted_by -> users.id
reviewed_by -> users.id
```

---

## 7. Seeder Role dan Permission

File yang diperbarui:

```text
database/seeders/SiprakarSeeder.php
```

Seeder sekarang membuat role default dengan permission yang jelas:

```text
superadmin
admin
staff
lembaga
```

### Superadmin

Mendapat semua permission.

### Admin

Mendapat semua permission, tetapi data tetap dapat dibatasi oleh scope cabang sesuai logic aplikasi.

### Staff

Akses utama:

```text
dashboard.view
program_kerja.view
program_kerja.show
pekerjaan.view
pekerjaan.show
pekerjaan.progress
rab.view
notifications.view
```

### Lembaga

Akses utama:

```text
dashboard.view
program_kerja.view
program_kerja.show
program_kerja.create
pekerjaan.view
pekerjaan.show
rab.view
notifications.view
```

Seeder tidak lagi menghapus semua role non-superadmin secara agresif.

---

## 8. Legacy Cleanup

File model lama yang dihapus:

```text
app/Models/Lembaga.php
app/Models/Lokasi.php
```

Alasan:

- Struktur terbaru sudah tidak memakai tabel `lembagas` dan `lokasis` sebagai alur utama.
- Lokasi saat ini memakai struktur master:

```text
Cabang -> Gedung -> Lantai -> Ruang
```

---

## 9. Perubahan Alur Data Setelah Refactor

### Program Kerja Tanpa RAB

```text
Program Kerja dibuat tanpa estimasi item
  -> status_key = ready_for_work
  -> dapat dijadikan Data Pekerjaan
  -> pekerjaan wajib punya checklist
  -> progress dihitung dari checklist
```

### Program Kerja Dengan RAB

```text
Program Kerja dibuat dengan estimasi item
  -> status_key = rab_submitted
  -> sistem membuat RAB otomatis
  -> estimasi item disalin ke rab_details
  -> RAB direview
  -> jika approved, Program Kerja menjadi rab_approved
  -> baru bisa menjadi Data Pekerjaan
```

### RAB Ditolak

```text
RAB rejected
  -> Program Kerja menjadi ready_for_work
  -> needs_rab = false
  -> Program Kerja tetap bisa menjadi Data Pekerjaan tanpa RAB approved
```

### Checklist dan Progress

```text
Checklist dicentang
  -> sistem menghitung progress
  -> 0% = Belum Diproses
  -> 1-99% = Diproses
  -> 100% = Selesai
  -> Program Kerja ikut berubah menjadi completed jika pekerjaan selesai
```

---

## 10. Validasi Setelah Perubahan

Validasi yang sudah dilakukan pada package ini:

```text
php -l untuk seluruh file PHP di app, database, routes, dan config
```

Hasil:

```text
Tidak ditemukan syntax error PHP.
```

Yang belum dijalankan:

```text
php artisan test
npm run build
```

Alasan:

```text
Folder vendor dan node_modules tidak tersedia di ZIP awal, sehingga artisan dan build frontend tidak bisa dijalankan langsung tanpa composer install dan npm install.
```

---

## 11. Catatan Penting untuk Migrasi Database Lama

Untuk fresh install, struktur database sudah langsung lebih ketat.

Untuk database lama yang sudah berisi data:

1. Backup database terlebih dahulu.
2. Jalankan migration baru.
3. Cek data RAB duplikat, karena migration akan mempertahankan RAB terbaru dan melepas relasi duplikat.
4. Cek pekerjaan lama tanpa Program Kerja, karena migration akan membuat Program Kerja legacy agar relasi pekerjaan tetap valid.
5. Jika memakai SQLite, sebagian penguatan constraint in-place terbatas oleh driver database. Fresh install tetap aman karena migration awal sudah dikunci.

Urutan yang disarankan:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=SiprakarSeeder
npm install
npm run build
php artisan optimize:clear
```

Untuk production yang sudah punya data:

```bash
php artisan down
php artisan migrate --force
php artisan optimize:clear
php artisan up
```

---

## 12. Ringkasan Dampak Refactor

Setelah refactor ini, project menjadi lebih siap dikembangkan karena:

1. Workflow status lebih stabil dengan enum dan `status_key`.
2. Permission backend benar-benar aktif.
3. Relasi RAB lebih matang sebagai one-to-one.
4. Pekerjaan wajib berasal dari Program Kerja.
5. Progress pekerjaan tidak bisa selesai otomatis karena checklist kosong.
6. Business logic utama tidak lagi terlalu menumpuk di controller.
7. Database lebih konsisten dengan foreign key dan audit trail.
8. Legacy model yang membingungkan sudah dibersihkan.

---

## 13. File Penting yang Berubah

```text
app/Enums/*
app/Models/ProgramKerja.php
app/Models/Rab.php
app/Models/Pekerjaan.php
app/Models/User.php
app/Services/Siprakar/ProgramKerjaService.php
app/Services/Siprakar/RabService.php
app/Services/Pekerjaan/PekerjaanService.php
app/Http/Middleware/PermissionMiddleware.php
app/Http/Middleware/RoleMiddleware.php
routes/web.php
routes/siprakar.php
routes/system.php
database/migrations/*
database/seeders/SiprakarSeeder.php
database/factories/UserFactory.php
```

---

## 14. Status Akhir

Refactor ini tidak mengubah tujuan utama SIPRAKAR, tetapi memperkuat fondasinya.

Alur akhir yang dipertahankan:

```text
Program Kerja
  -> Estimasi Item
  -> RAB jika perlu
  -> Review RAB
  -> Data Pekerjaan
  -> Checklist
  -> Progress otomatis
  -> Laporan
```

Fondasi baru yang ditambahkan:

```text
Enum status
Permission enforcement
Service layer
Relasi database lebih ketat
Validasi checklist wajib
Audit foreign key
Legacy cleanup
```
