# Perubahan Alur Auto RAB SIPRAKAR

Versi ini memakai alur Program Kerja yang lebih ringkas:

```text
Tambah Program Kerja
↓
Admin boleh isi estimasi item awal
↓
Jika estimasi kosong:
    Program Kerja = Siap Dijadikan Pekerjaan
    Program boleh langsung dijadikan Data Pekerjaan
↓
Jika estimasi ada:
    Data masuk ke program_kerja_estimasi_items
    Sistem otomatis membuat rabs
    Sistem otomatis menyalin estimasi ke rab_details
    RAB = Diajukan
    Program Kerja = RAB Diajukan
↓
Jika item RAB ditambah/diubah/dihapus:
    RAB = Direvisi
    Program Kerja = RAB Direvisi
↓
Jika RAB disetujui:
    RAB = Disetujui
    Program Kerja = RAB Disetujui
↓
Program Kerja boleh dijadikan Data Pekerjaan
```

## Struktur item biaya

`program_kerja_estimasi_items` dan `rab_details` sekarang memakai pola kolom yang sama:

```text
nama_item
jumlah_item
harga_satuan
subtotal
keterangan
```

Contoh:

```text
Item: Freon AC
Jumlah Item: 2
Harga Satuan: Rp150.000
Subtotal: Rp300.000

Item: Jasa teknisi
Jumlah Item: 1
Harga Satuan: Rp250.000
Subtotal: Rp250.000
```

Subtotal dihitung otomatis dari:

```text
jumlah_item × harga_satuan
```

## Catatan penggunaan

- Tidak ada lagi status `Menunggu RAB`.
- Tidak ada lagi status RAB `Belum diajukan` sebagai alur utama.
- RAB otomatis langsung `Diajukan` ketika Program Kerja memiliki estimasi item.
- RAB yang sudah `Disetujui` terkunci dari perubahan item.
- Jika item RAB diedit saat status `Diajukan`, sistem otomatis mengubahnya menjadi `Direvisi`.
