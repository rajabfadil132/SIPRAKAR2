<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\Cabang;
use App\Models\KategoriPekerjaan;
use App\Models\Pekerjaan;
use App\Models\PekerjaanChecklist;
use App\Models\ProgressPekerjaan;
use App\Models\ProgramKerja;
use App\Models\ProgramKerjaEstimasiItem;
use App\Models\Rab;
use App\Models\RabDetail;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::where('email', 'superadmin@siprakar.test')->first();
        if (! $superadmin) {
            return;
        }

        $pemeliharaan = KategoriPekerjaan::where('nama_kategori', 'Pemeliharaan')->first();
        $listrik = KategoriPekerjaan::where('nama_kategori', 'Listrik')->first();
        $ac = KategoriPekerjaan::where('nama_kategori', 'Perbaikan AC')->first();
        $cabang = Cabang::where('kode', 'VTR')->first() ?: Cabang::first();
        $year = (int) now()->year;
        $shortYear = now()->format('y');
        $branchCode = $cabang?->kode ?: 'CBG';

        $readyProgram = ProgramKerja::updateOrCreate(
            ['kode_program' => "PROKER/{$branchCode}/{$shortYear}/001"],
            [
                'tahun' => $year,
                'nama_program' => 'Pengecekan berkala meja dan kursi ruang kelas',
                'deskripsi' => 'Program kerja tanpa RAB untuk memastikan sarana kelas tetap layak digunakan.',
                'cabang_id' => $cabang?->id,
                'kategori_id' => $pemeliharaan?->id,
                'prioritas' => 'Sedang',
                'target_mulai' => '2026-06-10',
                'target_selesai' => '2026-06-15',
                'estimasi_anggaran' => 0,
                'status' => 'Siap Dijadikan Pekerjaan',
                'source_type' => 'PROKER',
                'needs_rab' => false,
                'nama_gedung' => 'Gedung Utama',
                'nama_lantai' => 'Lantai 1',
                'nama_ruang' => 'Ruang Kelas A',
                'no_ruang' => 'A-101',
                'lantai' => 1,
                'location_text' => 'Lokasi manual; master data lokasi sengaja dikosongkan.',
                'created_by' => $superadmin->id,
                'updated_by' => $superadmin->id,
            ]
        );

        $rabProgram = ProgramKerja::updateOrCreate(
            ['kode_program' => "PROKER/{$branchCode}/{$shortYear}/002"],
            [
                'tahun' => $year,
                'nama_program' => 'Instalasi stop kontak tambahan laboratorium',
                'deskripsi' => 'Penambahan titik listrik laboratorium agar praktikum lebih tertib dan aman.',
                'cabang_id' => $cabang?->id,
                'kategori_id' => $listrik?->id,
                'prioritas' => 'Tinggi',
                'target_mulai' => '2026-06-12',
                'target_selesai' => '2026-06-25',
                'estimasi_anggaran' => 12500000,
                'status' => 'RAB Disetujui',
                'source_type' => 'PROKER',
                'needs_rab' => true,
                'nama_gedung' => 'Gedung Laboratorium',
                'nama_lantai' => 'Lantai 2',
                'nama_ruang' => 'Laboratorium Komputer',
                'no_ruang' => 'LAB-201',
                'lantai' => 2,
                'location_text' => 'Lokasi manual; tidak mengambil data master lokasi.',
                'created_by' => $superadmin->id,
                'updated_by' => $superadmin->id,
            ]
        );

        ProgramKerjaEstimasiItem::updateOrCreate(
            ['program_kerja_id' => $rabProgram->id, 'nama_item' => 'Stop kontak dan material instalasi'],
            ['jumlah_item' => 10, 'harga_satuan' => 750000, 'keterangan' => 'Material utama', 'created_by' => $superadmin->id, 'updated_by' => $superadmin->id]
        );
        ProgramKerjaEstimasiItem::updateOrCreate(
            ['program_kerja_id' => $rabProgram->id, 'nama_item' => 'Jasa instalasi listrik'],
            ['jumlah_item' => 1, 'harga_satuan' => 5000000, 'keterangan' => 'Pemasangan dan pengujian', 'created_by' => $superadmin->id, 'updated_by' => $superadmin->id]
        );

        $rab = Rab::updateOrCreate(
            ['nomor_rab' => "RAB/{$branchCode}/{$shortYear}/0001"],
            [
                'program_kerja_id' => $rabProgram->id,
                'tanggal_rab' => '2026-06-06',
                'total_rab' => 12500000,
                'status_rab' => 'Disetujui',
                'submitted_at' => now()->subDays(2),
                'reviewed_at' => now()->subDay(),
                'reviewed_by' => $superadmin->id,
                'catatan' => 'RAB disetujui untuk dilanjutkan menjadi pekerjaan.',
                'created_by' => $superadmin->id,
                'updated_by' => $superadmin->id,
            ]
        );

        foreach ([
            ['Stop kontak dan material instalasi', 'Titik stop kontak dan kabel pelindung', 10, 750000],
            ['Jasa instalasi listrik', 'Pemasangan, pengujian, dan rapikan jalur', 1, 5000000],
        ] as [$nama, $ket, $jumlah, $harga]) {
            RabDetail::updateOrCreate(
                ['rab_id' => $rab->id, 'nama_item' => $nama],
                ['keterangan' => $ket, 'jumlah_item' => $jumlah, 'harga_satuan' => $harga, 'subtotal' => $jumlah * $harga, 'created_by' => $superadmin->id, 'updated_by' => $superadmin->id]
            );
        }

        $activeProgram = ProgramKerja::updateOrCreate(
            ['kode_program' => "PROKER/{$branchCode}/{$shortYear}/003"],
            [
                'tahun' => $year,
                'nama_program' => 'Servis AC ruang kelas',
                'deskripsi' => 'Pembersihan filter, pengecekan freon, dan pemeriksaan unit AC ruang kelas.',
                'cabang_id' => $cabang?->id,
                'kategori_id' => $ac?->id,
                'prioritas' => 'Tinggi',
                'target_mulai' => '2026-06-03',
                'target_selesai' => '2026-06-12',
                'estimasi_anggaran' => 0,
                'status' => 'Dijadikan Pekerjaan',
                'source_type' => 'PROKER',
                'needs_rab' => false,
                'nama_gedung' => 'Gedung Utama',
                'nama_lantai' => 'Lantai 1',
                'nama_ruang' => 'Ruang Kelas B',
                'no_ruang' => 'B-102',
                'lantai' => 1,
                'location_text' => 'Lokasi manual.',
                'created_by' => $superadmin->id,
                'updated_by' => $superadmin->id,
            ]
        );

        $activeJob = $this->upsertPekerjaan($activeProgram, $superadmin, [
            'status' => 'Diproses',
            'progress' => 50,
            'tanggal_mulai' => '2026-06-03',
            'target_selesai' => '2026-06-12',
            'checklists_done' => 2,
        ]);

        $completedProgram = ProgramKerja::updateOrCreate(
            ['kode_program' => "PROKER/{$branchCode}/{$shortYear}/004"],
            [
                'tahun' => $year,
                'nama_program' => 'Perapihan kabel jaringan ruang administrasi',
                'deskripsi' => 'Perapihan kabel dan label jalur jaringan agar ruang kerja lebih aman.',
                'cabang_id' => $cabang?->id,
                'kategori_id' => $listrik?->id,
                'prioritas' => 'Sedang',
                'target_mulai' => '2026-05-27',
                'target_selesai' => '2026-06-02',
                'estimasi_anggaran' => 0,
                'status' => 'Selesai',
                'source_type' => 'PROKER',
                'needs_rab' => false,
                'nama_gedung' => 'Gedung Administrasi',
                'nama_lantai' => 'Lantai 1',
                'nama_ruang' => 'Ruang Administrasi',
                'no_ruang' => 'ADM-101',
                'lantai' => 1,
                'location_text' => 'Lokasi manual.',
                'created_by' => $superadmin->id,
                'updated_by' => $superadmin->id,
            ]
        );

        $completedJob = $this->upsertPekerjaan($completedProgram, $superadmin, [
            'status' => 'Selesai',
            'progress' => 100,
            'tanggal_mulai' => '2026-05-27',
            'target_selesai' => '2026-06-02',
            'tanggal_selesai' => '2026-06-02',
            'checklists_done' => 4,
        ]);

        foreach ([[$activeProgram, $activeJob], [$completedProgram, $completedJob]] as [$program, $job]) {
            $program->update([
                'converted_to_pekerjaan_id' => $job->id,
                'converted_at' => $job->created_at ?? now(),
                'status_before_conversion' => $program->status_before_conversion ?: 'Siap Dijadikan Pekerjaan',
                'updated_by' => $superadmin->id,
            ]);
        }

        AppNotification::updateOrCreate(
            ['user_id' => $superadmin->id, 'source_type' => Pekerjaan::class, 'source_id' => $activeJob->id, 'type' => 'pekerjaan.created'],
            ['title' => 'Tugas pekerjaan baru', 'message' => 'Anda ditugaskan pada pekerjaan Servis AC ruang kelas.', 'code' => $activeJob->kode_pekerjaan, 'status' => $activeJob->status, 'href' => route('pekerjaan.show', $activeJob->id, false), 'cabang' => $cabang?->nama_cabang ?? 'Belum ditentukan', 'notified_at' => now()]
        );
    }

    private function upsertPekerjaan(ProgramKerja $program, ?User $petugas, array $state): Pekerjaan
    {
        $job = Pekerjaan::updateOrCreate(
            ['kode_pekerjaan' => $program->kode_program],
            [
                'program_kerja_id' => $program->id,
                'nama_pekerjaan' => $program->nama_program,
                'deskripsi' => $program->deskripsi,
                'cabang_id' => $program->cabang_id,
                'lokasi_id' => null,
                'nama_gedung' => $program->nama_gedung,
                'nama_lantai' => $program->nama_lantai,
                'nama_ruang' => $program->nama_ruang,
                'lantai' => $program->lantai,
                'no_ruang' => $program->no_ruang,
                'location_text' => $program->location_text,
                'kategori_id' => $program->kategori_id,
                'prioritas' => $program->prioritas,
                'penanggung_jawab_id' => $petugas?->id,
                'petugas_id' => $petugas?->id,
                'tanggal_mulai' => $state['tanggal_mulai'] ?? null,
                'target_selesai' => $state['target_selesai'] ?? null,
                'tanggal_selesai' => $state['tanggal_selesai'] ?? null,
                'status' => $state['status'],
                'progress' => $state['progress'],
                'estimasi_rab_awal' => $program->estimasi_anggaran,
                'is_rab' => false,
                'catatan' => 'Data demo alur solo SIPRAKAR dari Program Kerja ke Pekerjaan.',
                'created_by' => $program->created_by,
                'updated_by' => $program->updated_by,
            ]
        );

        $items = ['Survei lokasi', 'Siapkan alat dan material', 'Kerjakan perbaikan', 'Dokumentasi dan pemeriksaan akhir'];
        foreach ($items as $index => $desc) {
            $done = $index < ($state['checklists_done'] ?? 0);
            PekerjaanChecklist::updateOrCreate(
                ['pekerjaan_id' => $job->id, 'deskripsi' => $desc],
                ['is_done' => $done, 'completed_by' => $done ? $petugas?->id : null, 'completed_at' => $done ? now()->subDays(max(0, 3 - $index)) : null, 'created_by' => $program->created_by, 'updated_by' => $program->updated_by]
            );
        }

        ProgressPekerjaan::updateOrCreate(
            ['pekerjaan_id' => $job->id, 'tanggal_update' => now()->toDateString(), 'progress' => $state['progress']],
            ['status' => $state['status'], 'catatan' => 'Progress demo dihitung dari checklist pekerjaan.', 'updated_by' => $petugas?->id]
        );

        return $job;
    }
}
