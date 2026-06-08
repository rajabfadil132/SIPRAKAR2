<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'identity_number')) {
                $table->string('identity_number', 50)->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type', 30)->nullable()->after('identity_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'user_type')) {
                $table->dropColumn('user_type');
            }
            if (Schema::hasColumn('users', 'identity_number')) {
                $table->dropColumn('identity_number');
            }
        });
    }
};
