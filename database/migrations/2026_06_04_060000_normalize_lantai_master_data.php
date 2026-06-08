<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lantais')) {
            return;
        }

        $now = now();

        DB::table('lantais')
            ->orderBy('id')
            ->get(['id', 'nomor_lantai', 'nama_lantai'])
            ->each(function ($lantai) use ($now) {
                $nomor = (int) $lantai->nomor_lantai;
                $nama = trim((string) ($lantai->nama_lantai ?? ''));
                $namaLower = mb_strtolower($nama);
                $namaIdeal = $nomor === 0 ? 'Basement' : 'Lantai '.$nomor;

                // Perbaiki data lama seperti nama_lantai = "0", "1", kosong,
                // atau "Lantai 0" agar dropdown hanya menampilkan Basement/0,
                // Lantai 1/1, dan seterusnya.
                if ($nama === '' || ctype_digit($nama) || $namaLower === 'lantai 0') {
                    DB::table('lantais')
                        ->where('id', $lantai->id)
                        ->update([
                            'nama_lantai' => $namaIdeal,
                            'updated_at' => $now,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Normalisasi nama lantai aman dibiarkan dan tidak perlu dibalik.
    }
};
