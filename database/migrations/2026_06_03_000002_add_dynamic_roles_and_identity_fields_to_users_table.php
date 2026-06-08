<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('nama_role');
            }
            if (! Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('keterangan');
            }
            if (! Schema::hasColumn('roles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_system');
            }
        });

        $roles = DB::table('roles')->get(['id', 'nama_role']);
        foreach ($roles as $role) {
            $slug = Str::slug(Str::lower((string) $role->nama_role));
            DB::table('roles')->where('id', $role->id)->update([
                'slug' => $slug ?: 'role-'.$role->id,
                'is_system' => in_array($slug, ['superadmin', 'admin', 'staff', 'lembaga'], true),
                'is_active' => true,
            ]);
        }

        Schema::create('role_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['role_id', 'slug']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role_category_id')) {
                $table->foreignId('role_category_id')->nullable()->after('role_id')->constrained('role_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'identity_type')) {
                $table->string('identity_type', 80)->nullable()->after('identity_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role_category_id')) {
                $table->dropConstrainedForeignId('role_category_id');
            }
            if (Schema::hasColumn('users', 'identity_type')) {
                $table->dropColumn('identity_type');
            }
        });

        Schema::dropIfExists('role_categories');

        Schema::table('roles', function (Blueprint $table) {
            foreach (['is_active', 'is_system', 'slug'] as $column) {
                if (Schema::hasColumn('roles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
