<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('code')->nullable();
            $table->string('status')->nullable();
            $table->string('href')->nullable();
            $table->string('cabang')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'notified_at']);
            $table->unique(['user_id', 'source_type', 'source_id', 'type'], 'app_notifications_unique_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
