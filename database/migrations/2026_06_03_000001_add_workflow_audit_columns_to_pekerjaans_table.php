<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pekerjaans', function (Blueprint $table) {
            if (! Schema::hasColumn('pekerjaans', 'delete_reason')) {
                $table->text('delete_reason')->nullable()->after('deleted_by');
            }
        });

        Schema::table('rabs', function (Blueprint $table) {
            if (! Schema::hasColumn('rabs', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('status_rab');
            }
            if (! Schema::hasColumn('rabs', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('rabs', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rabs', function (Blueprint $table) {
            foreach (['reviewed_by', 'reviewed_at', 'submitted_at'] as $column) {
                if (Schema::hasColumn('rabs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('pekerjaans', function (Blueprint $table) {
            if (Schema::hasColumn('pekerjaans', 'delete_reason')) {
                $table->dropColumn('delete_reason');
            }
        });
    }
};
