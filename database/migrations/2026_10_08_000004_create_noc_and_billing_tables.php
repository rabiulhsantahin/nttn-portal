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
        Schema::create('noc_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('link_id')->nullable()->constrained('links')->nullOnDelete();
            $table->string('status', 40)->default('RECEIVED');
            $table->text('remarks')->nullable();
            $table->timestamp('handed_over_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('noc_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('noc_handover_id')->constrained('noc_handovers')->cascadeOnDelete();
            $table->boolean('physical_link')->default(false);
            $table->boolean('optical_power')->default(false);
            $table->boolean('vlan_ok')->default(false);
            $table->boolean('bandwidth_ok')->default(false);
            $table->boolean('ping_ok')->default(false);
            $table->boolean('latency_ok')->default(false);
            $table->boolean('packet_loss_ok')->default(false);
            $table->boolean('monitoring_ok')->default(false);
            $table->string('result', 30)->default('PENDING');
            $table->text('remarks')->nullable();
            $table->foreignId('tested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('billing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained('links')->cascadeOnDelete();
            $table->decimal('installation_charge', 14, 2)->default(0);
            $table->decimal('monthly_charge', 14, 2)->default(0);
            $table->decimal('nttn_cost', 14, 2)->default(0);
            $table->date('billing_start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('invoice_no', 80)->nullable();
            $table->string('status', 40)->default('PENDING');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing');
        Schema::dropIfExists('noc_tests');
        Schema::dropIfExists('noc_handovers');
    }
};
