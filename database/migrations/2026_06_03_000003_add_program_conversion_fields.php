<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            if (! Schema::hasColumn('program_kerjas', 'converted_to_pekerjaan_id')) {
                $table->unsignedBigInteger('converted_to_pekerjaan_id')->nullable()->after('status');
                $table->index('converted_to_pekerjaan_id');
            }
            if (! Schema::hasColumn('program_kerjas', 'converted_at')) {
                $table->timestamp('converted_at')->nullable()->after('converted_to_pekerjaan_id');
            }
            if (! Schema::hasColumn('program_kerjas', 'status_before_conversion')) {
                $table->string('status_before_conversion')->nullable()->after('converted_at');
            }

            if (! Schema::hasColumn('program_kerjas', 'source_type')) {
                $table->string('source_type', 20)->default('PROKER')->after('status_before_conversion');
            }
            if (! Schema::hasColumn('program_kerjas', 'needs_rab')) {
                $table->boolean('needs_rab')->default(false)->after('source_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            if (Schema::hasColumn('program_kerjas', 'converted_to_pekerjaan_id')) {
                $table->dropIndex(['converted_to_pekerjaan_id']);
                $table->dropColumn('converted_to_pekerjaan_id');
            }
            if (Schema::hasColumn('program_kerjas', 'converted_at')) {
                $table->dropColumn('converted_at');
            }
            if (Schema::hasColumn('program_kerjas', 'needs_rab')) {
                $table->dropColumn('needs_rab');
            }
            if (Schema::hasColumn('program_kerjas', 'source_type')) {
                $table->dropColumn('source_type');
            }
            if (Schema::hasColumn('program_kerjas', 'status_before_conversion')) {
                $table->dropColumn('status_before_conversion');
            }
        });
    }
};
