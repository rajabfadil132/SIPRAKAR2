<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_kerjas') && Schema::hasColumn('program_kerjas', 'lembaga_id')) {
            Schema::table('program_kerjas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('lembaga_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'lembaga_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('lembaga_id');
            });
        }

        Schema::dropIfExists('lembagas');
    }

    public function down(): void
    {
        // Struktur lembaga lama tidak dikembalikan.
        // Lembaga/unit sekarang direpresentasikan sebagai role_categories.
    }
};
