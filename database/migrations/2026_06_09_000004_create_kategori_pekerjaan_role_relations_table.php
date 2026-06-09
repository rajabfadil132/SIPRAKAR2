<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kategori_pekerjaan_role_relations')) {
            Schema::create('kategori_pekerjaan_role_relations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kategori_pekerjaan_id')->constrained('kategori_pekerjaans')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignId('role_category_id')->nullable()->constrained('role_categories')->nullOnDelete();
                $table->timestamps();

                $table->index(['kategori_pekerjaan_id', 'role_id'], 'kprr_kategori_role_idx');
                $table->index('role_category_id', 'kprr_role_category_idx');
            });
        }

        if (Schema::hasTable('kategori_role_categories')) {
            $legacyRows = DB::table('kategori_role_categories')->get();
            foreach ($legacyRows as $row) {
                $roleId = DB::table('role_categories')->where('id', $row->role_category_id)->value('role_id');
                if (! $roleId) {
                    continue;
                }

                $exists = DB::table('kategori_pekerjaan_role_relations')
                    ->where('kategori_pekerjaan_id', $row->kategori_pekerjaan_id)
                    ->where('role_id', $roleId)
                    ->where('role_category_id', $row->role_category_id)
                    ->exists();

                if (! $exists) {
                    DB::table('kategori_pekerjaan_role_relations')->insert([
                        'kategori_pekerjaan_id' => $row->kategori_pekerjaan_id,
                        'role_id' => $roleId,
                        'role_category_id' => $row->role_category_id,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]);
                }
            }

            Schema::dropIfExists('kategori_role_categories');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('kategori_role_categories')) {
            Schema::create('kategori_role_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kategori_pekerjaan_id')->constrained('kategori_pekerjaans')->cascadeOnDelete();
                $table->foreignId('role_category_id')->constrained('role_categories')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['kategori_pekerjaan_id', 'role_category_id']);
            });
        }

        if (Schema::hasTable('kategori_pekerjaan_role_relations')) {
            $rows = DB::table('kategori_pekerjaan_role_relations')->whereNotNull('role_category_id')->get();
            foreach ($rows as $row) {
                $exists = DB::table('kategori_role_categories')
                    ->where('kategori_pekerjaan_id', $row->kategori_pekerjaan_id)
                    ->where('role_category_id', $row->role_category_id)
                    ->exists();

                if (! $exists) {
                    DB::table('kategori_role_categories')->insert([
                        'kategori_pekerjaan_id' => $row->kategori_pekerjaan_id,
                        'role_category_id' => $row->role_category_id,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]);
                }
            }
        }

        Schema::dropIfExists('kategori_pekerjaan_role_relations');
    }
};
