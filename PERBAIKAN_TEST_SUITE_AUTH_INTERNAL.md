# Perbaikan Test Suite Auth Internal SIPRAKAR

Dokumen ini menjelaskan perbaikan setelah `npm run build` berhasil, tetapi `php artisan test` masih gagal pada beberapa test bawaan Laravel Breeze.

## Ringkasan masalah

Build frontend sudah berhasil. Kegagalan terjadi pada test PHPUnit berikut:

1. `AuthenticationTest::users_can_logout`
2. `RegistrationTest::registration_screen_can_be_rendered`
3. `RegistrationTest::new_users_can_register`
4. `ExampleTest::the_application_returns_a_successful_response`
5. `ProfileTest::user_can_delete_their_account`

Penyebab utamanya bukan error runtime aplikasi, melainkan test bawaan masih mengikuti perilaku default Breeze, sementara SIPRAKAR sudah memakai desain aplikasi internal.

## Keputusan desain aplikasi internal

SIPRAKAR adalah sistem internal. Karena itu perilaku yang benar adalah:

| Area | Perilaku yang benar di SIPRAKAR |
|---|---|
| Registrasi publik | Dinonaktifkan |
| User baru | Dibuat melalui User Management oleh admin/superadmin |
| Akses `/` oleh guest | Redirect ke `/login` |
| Logout | Redirect ke `/login` |
| Hapus akun profil | Soft delete untuk audit trail |

## Perubahan yang dilakukan

### 1. `tests/Feature/Auth/AuthenticationTest.php`

Assertion logout disesuaikan dari:

```php
$response->assertRedirect('/');
```

menjadi:

```php
$response->assertRedirect(route('login', absolute: false));
```

Alasannya: controller logout memang mengarahkan user ke halaman login setelah session dihapus.

### 2. `tests/Feature/Auth/RegistrationTest.php`

Test registrasi publik diubah agar sesuai dengan desain sistem internal.

Sebelumnya test mengharapkan:

```text
GET /register = 200
POST /register = user berhasil register dan login
```

Sekarang test mengharapkan:

```text
GET /register = 404
POST /register = 404
user tidak dibuat
user tetap guest
```

Alasannya: SIPRAKAR memakai manajemen user internal, bukan self-registration publik.

### 3. `tests/Feature/ExampleTest.php`

Test root `/` diubah dari mengharapkan HTTP 200 menjadi:

```text
Guest membuka / → redirect ke /login
User login membuka / → redirect ke /dashboard
```

Alasannya: route `/` berada di dalam middleware `auth` dan `verified`, sehingga guest memang tidak boleh langsung masuk aplikasi.

### 4. `tests/Feature/ProfileTest.php`

Assertion hapus akun diubah dari hard delete:

```php
$this->assertNull($user->fresh());
```

menjadi soft delete:

```php
$this->assertSoftDeleted($user);
```

Alasannya: model `User` memakai soft delete untuk menjaga audit trail dan riwayat data.

## Kenapa tidak mengaktifkan registrasi publik?

Mengaktifkan `/register` memang bisa membuat test Breeze default lolos, tetapi itu tidak cocok untuk SIPRAKAR karena:

1. sistem ini berbasis role, cabang, dan permission;
2. user perlu identitas, role, subkategori, dan cabang yang valid;
3. registrasi publik membuka risiko akun liar masuk ke sistem internal;
4. alur yang lebih aman adalah admin membuat user melalui menu User Management.

## Cara validasi ulang

Setelah patch ini diterapkan, jalankan:

```bash
php artisan test
```

Untuk build frontend:

```bash
npm run build
```

Jika dependency belum terpasang:

```bash
composer install
npm install
```

## Catatan

Perubahan ini tidak menurunkan keamanan aplikasi. Sebaliknya, test suite sekarang mengikuti perilaku aplikasi internal yang lebih aman dan lebih sesuai dengan desain SIPRAKAR.
