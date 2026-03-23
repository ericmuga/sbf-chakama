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
        Schema::create('fund_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('gl_account_no', 50)->nullable();
            $table->decimal('balance', 18, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('number_series_code', 50)->nullable();
            $table->timestamps();

            $table->foreign('gl_account_no')->references('no')->on('gl_accounts')->nullOnDelete();
            $table->foreign('number_series_code')->references('code')->on('number_series')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_accounts');
    }
};
