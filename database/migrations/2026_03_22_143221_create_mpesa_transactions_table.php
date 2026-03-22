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
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('FirstName')->nullable();
            $table->string('MiddleName')->nullable();
            $table->string('LastName')->nullable();
            $table->string('TransactionType')->nullable();
            $table->string('TransID');
            $table->string('TransTime')->nullable();
            $table->string('BusinessShortCode')->nullable();
            $table->string('BillRefNumber')->nullable();
            $table->string('InvoiceNumber')->nullable();
            $table->string('ThirdPartyTransID')->nullable();
            $table->string('MSISDN')->nullable();
            $table->decimal('TransAmount', 18, 2)->nullable();
            $table->decimal('OrgAccountBalance', 18, 2)->nullable();
            $table->integer('PaymentType')->default(1);
            $table->string('checkout_request_id')->nullable()->index();
            $table->string('merchant_request_id')->nullable();
            $table->integer('result_code')->nullable();
            $table->string('result_desc')->nullable();
            $table->boolean('is_claimed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};
