<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('message');
            $table->string('entity_type', 80)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type', 80);
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 100);
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('notifications');
    }
};
