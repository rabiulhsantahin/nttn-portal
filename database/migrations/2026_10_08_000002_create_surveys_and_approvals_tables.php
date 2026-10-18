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
        Schema::create('survey_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 40)->unique();
            $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->integer('bandwidth_mbps');
            $table->string('service_type', 80)->nullable();
            $table->string('preferred_nttn', 120)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 50)->default('SURVEY_REQUESTED');
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();
        });

        Schema::create('survey_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_request_id')->constrained('survey_requests')->cascadeOnDelete();
            $table->string('provider', 120)->nullable();
            $table->boolean('feasible')->nullable();
            $table->text('route')->nullable();
            $table->decimal('fiber_distance_km', 10, 3)->nullable();
            $table->string('nearest_pop', 200)->nullable();
            $table->string('port', 100)->nullable();
            $table->string('vlan', 100)->nullable();
            $table->decimal('installation_cost', 14, 2)->default(0);
            $table->decimal('monthly_cost', 14, 2)->default(0);
            $table->integer('expected_delivery_days')->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 80);
            $table->unsignedBigInteger('entity_id');
            $table->string('approval_stage', 50);
            $table->string('status', 30)->default('PENDING');
            $table->text('remarks')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('survey_feedback');
        Schema::dropIfExists('survey_requests');
    }
};
