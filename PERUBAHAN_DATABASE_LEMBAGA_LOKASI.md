# Perubahan Database: Lembaga dan Lokasi

Perubahan ini mengikuti keputusan desain terbaru:

- `role_category_id` tetap dipakai.
- `lembaga_id` dihapus dari `users` dan `program_kerjas`.
- Tabel `lembagas` dihapus.
- Model `app/Models/Lembaga.php` dihapus.
- Model `app/Models/Lokasi.php` dihapus.
- Master lokasi tetap memakai struktur `cabangs > gedungs > lantais > ruangs`.
- Field `lokasi_id` pada Program Kerja/Pekerjaan tetap mengarah ke `ruangs.id`.
- Konsep LPAUD/Sarana Prasarana dapat dimasukkan sebagai `role_categories` pada role `Lembaga`.

## Catatan teknis

Ditambahkan migration cleanup:

```text
2026_06_06_000001_remove_legacy_lembaga_and_lokasi_references.php
```

Migration ini berguna jika database lama masih memiliki `lembaga_id` atau tabel `lembagas`.

## Perintah setelah ekstrak ZIP

```powershell
composer install
npm install
php artisan optimize:clear
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Akun default:

```text
Email    : superadmin@siprakar.test
Password : password
Role     : Superadmin
```
