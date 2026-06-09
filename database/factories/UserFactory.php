<?php

namespace Database\Factories;

use App\Models\JenisIdentitas;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'identity_number' => fake()->unique()->numerify('USR-####'),
            'identity_type' => 'No Pegawai',
            'jenis_identitas_id' => $this->defaultJenisIdentitasId(),
            'user_type' => 'Superadmin',
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => $this->defaultRoleId(),
            'role_category_id' => null,
            'cabang_id' => null,
            'phone' => fake()->numerify('08##########'),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    private function defaultJenisIdentitasId(): ?int
    {
        if (! class_exists(JenisIdentitas::class)) {
            return null;
        }

        return JenisIdentitas::firstOrCreate(
            ['kode' => 'NOPEG'],
            [
                'nama_jenis' => 'No Pegawai',
                'keterangan' => 'Nomor pegawai internal untuk data test.',
                'status' => 'active',
            ]
        )->id;
    }

    private function defaultRoleId(): ?int
    {
        if (! class_exists(Role::class)) {
            return null;
        }

        $role = Role::firstOrCreate(
            ['slug' => 'superadmin'],
            [
                'nama_role' => 'Superadmin',
                'keterangan' => 'Akun test dengan akses penuh.',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        RolePermission::firstOrCreate(
            ['role_id' => $role->id],
            ['permissions' => array_fill_keys(config('siprakar_permissions.keys', []), true)]
        );

        return $role->id;
    }
}
