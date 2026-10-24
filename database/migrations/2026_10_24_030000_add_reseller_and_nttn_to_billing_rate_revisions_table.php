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
        Schema::table('billing_rate_revisions', function (Blueprint $table) {
            $table->foreignId('old_reseller_id')->nullable()->after('approved_by')->constrained('resellers')->nullOnDelete();
            $table->foreignId('new_reseller_id')->nullable()->after('old_reseller_id')->constrained('resellers')->nullOnDelete();
            $table->string('old_nttn_provider', 120)->nullable()->after('new_reseller_id');
            $table->string('new_nttn_provider', 120)->nullable()->after('old_nttn_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_rate_revisions', function (Blueprint $table) {
            $table->dropForeign(['old_reseller_id']);
            $table->dropForeign(['new_reseller_id']);
            $table->dropColumn(['old_reseller_id', 'new_reseller_id', 'old_nttn_provider', 'new_nttn_provider']);
        });
    }
};
