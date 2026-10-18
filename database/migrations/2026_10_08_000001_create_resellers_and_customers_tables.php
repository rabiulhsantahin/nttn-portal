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
        Schema::create('resellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reseller_code', 50)->unique();
            $table->string('company_name', 200);
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('district', 100)->nullable();
            $table->string('thana', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('resellers');
    }
};
