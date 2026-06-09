# Perbaikan Root View Inertia

## Masalah

Saat menjalankan `php artisan test`, beberapa test halaman Inertia gagal dengan error:

```text
View [app] not found.
```

Error ini terjadi karena Inertia Laravel membutuhkan file root view bernama:

```text
resources/views/app.blade.php
```

File ini adalah template HTML utama yang menjadi tempat React/Inertia dirender.

## Perbaikan

Ditambahkan file:

```text
resources/views/app.blade.php
```

Isi file sudah memuat komponen penting Laravel + Inertia:

```blade
@routes
@viteReactRefresh
@vite('resources/js/app.jsx')
@inertiaHead
@inertia
```

Selain itu, ditambahkan juga:

```text
bootstrap/cache/.gitignore
```

Tujuannya agar folder `bootstrap/cache` tetap ikut terbawa saat project di-zip atau dikirim lewat Git, karena Laravel membutuhkan folder tersebut untuk cache manifest/package/config.

## Dampak

Setelah perbaikan ini, halaman berikut seharusnya tidak lagi error 500 karena missing view:

- `/login`
- `/forgot-password`
- `/reset-password/{token}`
- `/verify-email`
- `/confirm-password`
- `/profile`
- halaman Inertia lainnya

## Perintah setelah update

Jalankan ulang:

```bash
composer dump-autoload
php artisan optimize:clear
npm run build
php artisan test
```

Jika ingin cek satu test saja terlebih dahulu:

```bash
php artisan test --filter=AuthenticationTest
```
