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
            $table->string('nttn_provider', 120)->nullable()->after('description');
            $table->string('nttn_link_id', 120)->nullable()->after('nttn_provider');
            $table->string('nttn_provider_email', 255)->nullable()->after('nttn_link_id');
            $table->timestamp('nttn_email_sent_at')->nullable()->after('nttn_provider_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['nttn_provider', 'nttn_link_id', 'nttn_provider_email', 'nttn_email_sent_at']);
        });
    }
};
