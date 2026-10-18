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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 50)->unique();
            $table->foreignId('link_id')->constrained('links')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 60);
            $table->string('priority', 30)->default('NORMAL');
            $table->text('description')->nullable();
            $table->string('status', 40)->default('OPEN');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 50)->unique();
            $table->foreignId('link_id')->constrained('links')->cascadeOnDelete();
            $table->string('type', 30); // UPGRADE / DOWNGRADE
            $table->integer('old_bandwidth_mbps');
            $table->integer('requested_bandwidth_mbps');
            $table->text('reason')->nullable();
            $table->string('status', 40)->default('PENDING');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained('links')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_room_id')->constrained('chat_rooms')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->string('attachment_path', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_rooms');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('tickets');
    }
};
