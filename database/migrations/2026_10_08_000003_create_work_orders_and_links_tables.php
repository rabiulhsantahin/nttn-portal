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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('work_order_no', 40)->unique();
            $table->foreignId('survey_request_id')->constrained('survey_requests')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('ISSUED');
            $table->date('deadline')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('link_id', 60)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->integer('bandwidth_mbps');
            $table->string('nttn_provider', 120)->nullable();
            $table->string('circuit_id', 120)->nullable();
            $table->string('vlan', 100)->nullable();
            $table->string('port', 100)->nullable();
            $table->string('status', 40)->default('PENDING');
            $table->date('activation_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
        Schema::dropIfExists('work_orders');
    }
};
