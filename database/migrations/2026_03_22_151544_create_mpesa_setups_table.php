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
        Schema::create('mpesa_setups', function (Blueprint $table) {
            $table->id();
            // 'local' = test mode (no real Safaricom calls), 'sandbox', 'production'
            $table->string('mpesa_env')->default('local');
            $table->string('consumer_key')->nullable();
            $table->string('consumer_secret')->nullable();
            $table->string('shortcode')->nullable();
            $table->string('passkey')->nullable();
            $table->string('callback_url')->nullable();
            $table->string('transaction_type')->default('CustomerPayBillOnline');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_setups');
    }
};
