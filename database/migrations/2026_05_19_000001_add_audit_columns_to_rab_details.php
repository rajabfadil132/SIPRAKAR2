<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rab_details', function (Blueprint $table) {
            if (! Schema::hasColumn('rab_details', 'created_by')) $table->unsignedBigInteger('created_by')->nullable()->after('keterangan');
            if (! Schema::hasColumn('rab_details', 'updated_by')) $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            if (! Schema::hasColumn('rab_details', 'deleted_by')) $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            if (! Schema::hasColumn('rab_details', 'deleted_at')) $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('rab_details', function (Blueprint $table) {
            foreach (['created_by', 'updated_by', 'deleted_by', 'deleted_at'] as $column) {
                if (Schema::hasColumn('rab_details', $column)) $table->dropColumn($column);
            }
        });
    }
};
