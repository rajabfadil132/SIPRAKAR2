# SIPRAKAR Solo

Versi ini sudah difokuskan menjadi **solo SIPRAKAR** tanpa SAPA dan tanpa home page/portal gabungan.

## Perubahan utama

- Modul SAPA dihapus dari backend, route, menu, model, service, migration, seeder, dan halaman React.
- Home page dihapus. Route `/` langsung mengarah ke Dashboard SIPRAKAR.
- Semua pembatas role/permission dinonaktifkan. Akun aktif dapat membuka fitur SIPRAKAR tanpa blokir role.
- Seeder hanya membuat **1 akun Superadmin**.
- Data lokasi master dikosongkan: Cabang, Gedung, Lantai, Ruang, dan Lembaga tidak lagi memiliki data bawaan.
- Sidebar dibungkus menjadi grup seperti contoh: **Perencanaan** dan **Monitoring**.
- **Pengaturan Sistem** tetap menjadi portal terpisah, bukan dicampur sebagai menu anak SIPRAKAR.
- Alur utama tetap berjalan: Program Kerja -> RAB bila perlu -> Data Pekerjaan -> Checklist Progress -> Selesai.
- Seeder transaksi demo tetap ada, tetapi memakai lokasi manual pada transaksi, bukan master data lokasi.

## Akun demo

Password: `password`

| Role | Email |
|---|---|
| Superadmin | `superadmin@siprakar.test` |

## Jalankan ulang setelah extract

```powershell
cd "D:\laragon\www\siprakar"
composer install
npm install
php artisan key:generate
php artisan optimize:clear
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Jika memakai Laragon virtual host, buka `http://siprakar.test`.
Jika memakai artisan serve, buka alamat yang muncul di terminal, biasanya `http://127.0.0.1:8000`.
