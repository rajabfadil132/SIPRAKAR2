<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pekerjaans', function (Blueprint $table) {
            if (! Schema::hasColumn('pekerjaans', 'prioritas')) {
                $table->string('prioritas')->default('Sedang')->after('kategori_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pekerjaans', function (Blueprint $table) {
            if (Schema::hasColumn('pekerjaans', 'prioritas')) {
                $table->dropColumn('prioritas');
            }
        });
    }
};
