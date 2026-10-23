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
        Schema::create('billing_rate_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained('links')->cascadeOnDelete();
            $table->foreignId('billing_id')->constrained('billing')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('old_monthly_charge', 14, 2);
            $table->decimal('new_monthly_charge', 14, 2);
            $table->decimal('old_installation_charge', 14, 2)->default(0);
            $table->decimal('new_installation_charge', 14, 2)->default(0);
            $table->decimal('old_nttn_cost', 14, 2)->default(0);
            $table->decimal('new_nttn_cost', 14, 2)->default(0);
            $table->date('effective_date')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 40)->default('PENDING');
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['link_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_rate_revisions');
    }
};
