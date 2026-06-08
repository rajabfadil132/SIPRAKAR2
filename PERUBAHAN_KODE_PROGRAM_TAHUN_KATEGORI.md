# Perubahan Kode Program, Tahun, dan Kategori

## 1. Tahun Program Kerja

Input `tahun` sudah dihapus dari form Tambah/Edit Program Kerja.

Nilai `program_kerjas.tahun` sekarang diisi otomatis di backend memakai waktu server:

```php
$data['tahun'] = now()->year;
```

Tujuannya agar user tidak bisa memanipulasi tahun kode program dan tidak perlu mengisi tanggal/tahun tambahan selain `Target Mulai` dan `Target Selesai`.

## 2. Format `kode_program`

Format kode Program Kerja sekarang:

```text
PROKER/{KODE_CABANG}/{YY}/{URUTAN_3_DIGIT}
```

Contoh:

```text
PROKER/VTR/26/001
PROKER/PST/26/001
PROKER/PST/26/002
```

- `PROKER` = prefix tetap Program Kerja.
- `{KODE_CABANG}` = diambil dari `cabangs.kode`.
- `{YY}` = diambil otomatis dari tahun server, contoh 2026 menjadi `26`.
- `{URUTAN_3_DIGIT}` = nomor urut per cabang dan per tahun.

`kode_program` tetap `unique`, tetapi tidak dijadikan primary key. Primary key tetap memakai `id`.

## 3. Cabang untuk Superadmin

Superadmin wajib memilih cabang saat membuat Program Kerja agar kode tidak menjadi kode umum/tidak jelas.

Admin cabang tetap otomatis menggunakan `cabang_id` dari akun login.

## 4. Kategori Program Kerja

`kategori_id` dipertahankan dan wajib dipilih.

Pilihan kategori di form diambil dari master data:

```php
KategoriPekerjaan::where('status', 'active')->orderBy('nama_kategori')->get()
```

Artinya pilihan kategori berasal dari tabel `kategori_pekerjaans`, bukan hardcode di frontend.


## Perbaikan lanjutan 2026-06-06

- Menambahkan folder `storage/framework/views`, `storage/framework/cache`, `storage/framework/cache/data`, `storage/framework/sessions`, dan `storage/logs` agar `php artisan optimize:clear` tidak gagal dengan pesan `View path not found`.
- Memperbaiki `DemoTransactionSeeder` pada method `upsertPekerjaan()` agar memakai `$program->cabang_id`, bukan variabel `$cabang` yang tidak tersedia di scope method tersebut.
