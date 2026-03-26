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
        Schema::create('share_billing_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->decimal('price_per_share', 18, 4);
            $table->unsignedInteger('acres_per_share')->default(10);
            $table->string('billing_frequency', 20)->default('once');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('fund_account_id')->constrained('fund_accounts');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->timestamps();

            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_billing_schedules');
    }
};
