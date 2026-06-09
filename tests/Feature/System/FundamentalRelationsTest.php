<?php

namespace Tests\Feature\System;

use App\Models\KategoriPekerjaan;
use App\Models\User;
use Database\Seeders\SiprakarSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundamentalRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_have_master_identity_relation(): void
    {
        $this->seed(SiprakarSeeder::class);

        $user = User::query()->with('jenisIdentitas')->where('email', 'teknisi.wth@siprakar.test')->firstOrFail();

        $this->assertNotNull($user->jenis_identitas_id);
        $this->assertNotNull($user->jenisIdentitas);
        $this->assertSame($user->identity_type, $user->jenisIdentitas->nama_jenis);
    }

    public function test_categories_have_flexible_role_relations(): void
    {
        $this->seed(SiprakarSeeder::class);

        $administrasi = KategoriPekerjaan::query()->with('roleRelations.role')->where('nama_kategori', 'Administrasi')->firstOrFail();
        $listrik = KategoriPekerjaan::query()->with('roleRelations.roleCategory')->where('nama_kategori', 'Listrik')->firstOrFail();

        $this->assertTrue($administrasi->roleRelations->contains(fn ($relation) => $relation->role?->slug === 'admin' && $relation->role_category_id === null));
        $this->assertTrue($listrik->roleRelations->contains(fn ($relation) => $relation->roleCategory?->slug === 'teknisi-elektrikal'));
    }

    public function test_legacy_location_and_lembaga_models_are_removed(): void
    {
        $this->assertFileDoesNotExist(app_path('Models/Lembaga.php'));
        $this->assertFileDoesNotExist(app_path('Models/Lokasi.php'));
    }
}
