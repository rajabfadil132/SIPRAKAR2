
# Project SIPRAKAR-SOLO-NOSAPA-NOHOMEPAGE

## 1. Gambaran umum project

Project ini adalah aplikasi web manajemen program kerja, RAB, pekerjaan prasarana, checklist progres, laporan, dan pengaturan sistem.

Stack utamanya:

| Bagian | Teknologi |
|---|---|
| Backend | Laravel |
| Frontend | React + Inertia.js |
| Styling | Tailwind CSS |
| Auth | Laravel Breeze-style auth |
| Database default | SQLite dari `.env.example`, tapi bisa diganti MySQL |
| Build frontend | Vite |
| Notifikasi | Database notification custom `app_notifications` |
| Opsional | Cloudflare Turnstile, WhatsApp Gateway/Fonnte |

Secara konsep, project ini adalah versi SIPRAKAR Solo, yaitu fokus ke modul SIPRAKAR saja.

## 2. Semua fitur yang ada

### A. Autentikasi dan profil pengguna

Fitur auth yang tersedia:

1. Login.
2. Logout.
3. Lupa password.
4. Reset password.
5. Verifikasi email.
6. Konfirmasi password.
7. Edit profil.
8. Update password.
9. Hapus akun sendiri dari profil.
10. Proteksi login dengan status user (`active`, `inactive`, `suspended`)
11. Opsional Cloudflare Turnstile untuk captcha login.

### B. Dashboard SIPRAKAR

Dashboard menampilkan ringkasan dan monitoring:

- Total Program Kerja.
- Program aktif.
- Program yang sedang proses RAB.
- Program siap dijadikan pekerjaan.
- Total pekerjaan.
- Pekerjaan belum diproses.
- Pekerjaan diproses.
- Pekerjaan selesai.
- Pekerjaan dibatalkan.
- RAB menunggu review.
- Deadline dekat.
- Pekerjaan terlambat.
- Pekerjaan tanpa petugas.
- Pekerjaan tanpa checklist.
- Rata-rata progres pekerjaan.
- Total nilai RAB.
- Grafik status pekerjaan.
- Grafik pekerjaan per cabang atau per kategori.
- Tren 6 bulan program dan pekerjaan selesai.
- Daftar pekerjaan terbaru.
- Daftar pekerjaan berjalan.
- Daftar RAB menunggu review.
- Daftar deadline dekat.
- Daftar pekerjaan belum lengkap.
- Daftar tugas saya.

### C. Program Kerja

Fitur:

- Lihat daftar Program Kerja aktif.
- Filter berdasarkan pencarian, status, kategori, cabang, urutan terbaru/terlama.
- Tambah, edit, detail, soft delete Program Kerja.
- Input kategori pekerjaan, prioritas, target mulai & selesai, lokasi/cabang, estimasi item biaya awal.
- Hitung otomatis subtotal & total estimasi anggaran.
- Buat RAB otomatis jika ada estimasi item.
- Ubah menjadi Data Pekerjaan jika memenuhi syarat.
- Status mengikuti RAB & konversi ke pekerjaan.

Kode Program Kerja: `PROKER/{KODE_CABANG}/{YY}/{URUTAN_3_DIGIT}`

Status Program Kerja:

| Status | Arti |
|---|---|
| Siap Dijadikan Pekerjaan | Program tidak perlu RAB atau RAB ditolak, bisa langsung menjadi pekerjaan |
| RAB Diajukan | RAB sudah dibuat dan menunggu review |
| RAB Direvisi | RAB dikembalikan untuk revisi |
| RAB Disetujui | Program boleh dijadikan pekerjaan |
| Dijadikan Pekerjaan | Program sudah dikonversi menjadi pekerjaan |
| Selesai | Pekerjaan sudah selesai |
| Dibatalkan | Program/pekerjaan dibatalkan |

### D. RAB Pekerjaan

Fitur:

- Lihat daftar RAB, filter, tambah, edit, hapus.
- Tambah/edit/hapus item RAB.
- Hitung subtotal & total RAB otomatis.
- Submit, approve, revise, reject RAB.
- Lock item RAB saat disetujui.
- Sinkronisasi status RAB ke Program Kerja.
- Prefill item RAB dari estimasi Program Kerja.

Status RAB dan dampak ke Program Kerja:

| Status RAB | Dampak ke Program Kerja |
|---|---|
| Diajukan | Program = RAB Diajukan |
| Direvisi | Program = RAB Direvisi |
| Disetujui | Program = RAB Disetujui, boleh jadi pekerjaan |
| Ditolak | Program = Siap Dijadikan Pekerjaan |

### E. Data Pekerjaan

Fitur:

- Lihat, tambah, edit, hapus (soft delete & restore) pekerjaan.
- Assign petugas utama, penanggung jawab, tambahan.
- Checklist pekerjaan, update otomatis progress & status.
- Tugas Saya untuk user ditugaskan.
- Label durasi, sisa waktu, deteksi terlambat.
- Lokasi pekerjaan manual atau master ruang.
- Sinkronisasi status ke Program Kerja.

Status pekerjaan otomatis dihitung dari checklist.

### F. Tugas Saya

- Menampilkan pekerjaan yang ditugaskan ke user login.
- User dianggap punya tugas jika jadi petugas, penanggung jawab, atau ada di tabel pekerjaan_petugas.

### G. Arsip Pekerjaan

- Soft delete pekerjaan & checklist.
- Restore pekerjaan & checklist.
- Hapus permanen & lepaskan relasi RAB & Program Kerja.

### H. Laporan dan Statistik

- Filter & ringkasan laporan pekerjaan.
- Export ke CSV.

### I. Master Data

- Cabang, Gedung, Lantai, Ruang, Kategori Pekerjaan.
- Tambah, edit, hapus soft delete.
- Status aktif/nonaktif.
- Audit `created_by`, `updated_by`, `deleted_by`.

### J. User Management

- Daftar user, filter, tambah, edit, detail, hapus soft delete.
- Role, subkategori role, cabang user.
- Validasi identitas berdasarkan role.
- Status user: active/inactive/suspended.

### K. Role dan Hak Akses

- Daftar role, tambah, edit, nonaktifkan, hapus role custom.
- Edit permission per role.
- Subkategori role.

### L. Riwayat Aktivitas

- Log aktivitas dibuat/diperbarui/dihapus via `created_by`, `updated_by`, `deleted_by`.

### M. Notifikasi

- Notifikasi pekerjaan & status.
- Lihat daftar, hitung belum dibaca, tandai dibaca, link ke detail pekerjaan.

### N. WhatsApp Gateway

- Service siap integrasi via Fonnte.
- Konfigurasi di `.env`.
- Belum dipakai penuh di controller utama.

## 3. Alur program utama

```text
User login
  ↓
Dashboard
  ↓
Buat Program Kerja
  ↓
Isi data program, kategori, cabang, lokasi, target, prioritas
  ↓
Apakah ada estimasi item biaya?
  ├─ Tidak → Program Kerja = Siap Dijadikan Pekerjaan → bisa dijadikan Data Pekerjaan
  └─ Ya → Simpan item estimasi → Auto generate RAB → Review RAB → Disetujui → Bisa dijadikan Data Pekerjaan
```

## 4. Database utama

Tabel inti:

```
users
roles
role_permissions
role_categories

cabangs
gedungs
lantais
ruangs
kategori_pekerjaans

program_kerjas
program_kerja_estimasi_items

rabs
rab_details

pekerjaans
pekerjaan_checklists
pekerjaan_petugas
progress_pekerjaans

app_notifications
```

## 5. Alur data utama

### Program Kerja tanpa RAB

```
users → program_kerjas → pekerjaans → pekerjaan_checklists → progress_pekerjaans
```

### Program Kerja dengan RAB

```
users → program_kerjas → program_kerja_estimasi_items → rabs → rab_details → RAB Disetujui → pekerjaans → rabs.pekerjaan_id
```

### Checklist & Progress

```
pekerjaans → pekerjaan_checklists (centang) → hitung progress → pekerjaans.progress & status → progress_pekerjaans → app_notifications
```

### Notifikasi

```
pekerjaan dibuat/status berubah → AppNotificationService → simpan app_notifications → ditampilkan di layout
```

## 6. Catatan penting

- Permission ada, enforcement dinonaktifkan.
- Pekerjaan wajib berasal dari Program Kerja.
- Kode pekerjaan = kode program.
- Progress manual dinonaktifkan.
- RAB otomatis dibuat dari estimasi Program Kerja.
- Beberapa model/tabel legacy ada tapi tidak dipakai.
- Beberapa tabel (`vendors`, `jadwal_pemeliharaans`, `dokumen_administrasis`) siap fitur tapi UI belum aktif.

## 7. Ringkasan alur inti

```
Program Kerja → Estimasi → RAB → Review RAB → Data Pekerjaan → Petugas → Checklist → Progress → Selesai → Laporan
```
