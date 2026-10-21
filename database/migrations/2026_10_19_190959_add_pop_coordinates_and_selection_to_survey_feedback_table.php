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
        Schema::table('survey_feedback', function (Blueprint $table) {
            $table->decimal('pop_latitude', 10, 7)->nullable()->after('nearest_pop');
            $table->decimal('pop_longitude', 10, 7)->nullable()->after('pop_latitude');
            $table->boolean('is_selected')->default(false)->after('feedback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_feedback', function (Blueprint $table) {
            $table->dropColumn(['pop_latitude', 'pop_longitude', 'is_selected']);
        });
    }
};
