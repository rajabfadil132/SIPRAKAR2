<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pekerjaans', function (Blueprint $table) {
            if (! Schema::hasColumn('pekerjaans', 'estimasi_rab_awal')) {
                $table->decimal('estimasi_rab_awal', 16, 2)->default(0)->after('progress');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pekerjaans', function (Blueprint $table) {
            if (Schema::hasColumn('pekerjaans', 'estimasi_rab_awal')) {
                $table->dropColumn('estimasi_rab_awal');
            }
        });
    }
};
