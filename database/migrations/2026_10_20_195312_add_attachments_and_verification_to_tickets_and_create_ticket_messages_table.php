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
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('attachment_path', 500)->nullable()->after('description');
            $table->boolean('is_customer_verified')->default(false)->after('status');
            $table->timestamp('verified_at')->nullable()->after('is_customer_verified');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['attachment_path', 'is_customer_verified', 'verified_at', 'verified_by']);
        });
    }
};
