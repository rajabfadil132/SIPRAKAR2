<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jenis_identitas')) {
            Schema::create('jenis_identitas', function (Blueprint $table) {
                $table->id();
                $table->string('nama_jenis', 100);
                $table->string('kode', 30)->unique();
                $table->string('keterangan', 500)->nullable();
                $table->string('status', 20)->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $this->ensureDefaultIdentityTypes();

        if (! Schema::hasColumn('users', 'jenis_identitas_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('jenis_identitas_id')
                    ->nullable()
                    ->after('identity_type')
                    ->constrained('jenis_identitas')
                    ->nullOnDelete();
            });
        }

        $this->backfillUsersIdentityRelation();
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'jenis_identitas_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('jenis_identitas_id');
            });
        }
    }

    private function ensureDefaultIdentityTypes(): void
    {
        $defaults = [
            ['nama_jenis' => 'NIK Karyawan', 'kode' => 'NIK', 'keterangan' => 'Nomor identitas karyawan/staff internal.'],
            ['nama_jenis' => 'No Pegawai', 'kode' => 'NOPEG', 'keterangan' => 'Nomor pegawai internal.'],
            ['nama_jenis' => 'Kode Lembaga', 'kode' => 'LEMB', 'keterangan' => 'Kode unik lembaga/unit pengusul.'],
            ['nama_jenis' => 'NIP', 'kode' => 'NIP', 'keterangan' => 'Nomor Induk Pegawai.'],
            ['nama_jenis' => 'NID', 'kode' => 'NID', 'keterangan' => 'Nomor identitas dosen/pendidik internal.'],
            ['nama_jenis' => 'NIM', 'kode' => 'NIM', 'keterangan' => 'Nomor Induk Mahasiswa.'],
        ];

        foreach ($defaults as $default) {
            $existing = DB::table('jenis_identitas')->where('kode', $default['kode'])->first();
            if ($existing) {
                DB::table('jenis_identitas')->where('id', $existing->id)->update([
                    'nama_jenis' => $default['nama_jenis'],
                    'keterangan' => $existing->keterangan ?: $default['keterangan'],
                    'status' => 'active',
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('jenis_identitas')->insert([
                    ...$default,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function backfillUsersIdentityRelation(): void
    {
        if (! Schema::hasColumn('users', 'jenis_identitas_id')) {
            return;
        }

        $types = DB::table('jenis_identitas')->select('id', 'nama_jenis', 'kode')->get();
        $byName = $types->keyBy(fn ($type) => Str::lower($type->nama_jenis));
        $byCode = $types->keyBy(fn ($type) => Str::upper($type->kode));
        $defaultNopeg = $byCode->get('NOPEG')?->id ?? $types->first()?->id;
        $nik = $byCode->get('NIK')?->id ?? $defaultNopeg;
        $lemb = $byCode->get('LEMB')?->id ?? $defaultNopeg;

        $users = DB::table('users')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->select('users.id', 'users.identity_type', 'roles.slug as role_slug')
            ->whereNull('users.jenis_identitas_id')
            ->get();

        foreach ($users as $user) {
            $normalized = Str::lower(trim((string) $user->identity_type));
            $jenisId = $byName->get($normalized)?->id;

            if (! $jenisId) {
                $jenisId = match ($user->role_slug) {
                    'staff', 'admin' => $nik,
                    'lembaga' => $lemb,
                    default => $defaultNopeg,
                };
            }

            $label = $types->firstWhere('id', $jenisId)?->nama_jenis;

            DB::table('users')->where('id', $user->id)->update([
                'jenis_identitas_id' => $jenisId,
                'identity_type' => $label ?: $user->identity_type,
                'updated_at' => now(),
            ]);
        }
    }
};
