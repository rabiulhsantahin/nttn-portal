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
        Schema::table('links', function (Blueprint $table) {
            $table->boolean('is_legacy')->default(false)->after('status');
            $table->string('md_approval_ref', 120)->nullable()->after('is_legacy');
            $table->string('md_approval_doc', 255)->nullable()->after('md_approval_ref');
            $table->text('legacy_notes')->nullable()->after('md_approval_doc');
        });

        Schema::table('billing', function (Blueprint $table) {
            $table->boolean('is_legacy')->default(false)->after('status');
            $table->text('notes')->nullable()->after('is_legacy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing', function (Blueprint $table) {
            $table->dropColumn(['is_legacy', 'notes']);
        });

        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn(['is_legacy', 'md_approval_ref', 'md_approval_doc', 'legacy_notes']);
        });
    }
};
