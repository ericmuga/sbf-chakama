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
        Schema::table('cash_receipts', function (Blueprint $table) {
            $table->string('description')->nullable()->after('amount');
            $table->string('mpesa_receipt_no')->nullable()->after('description');
            $table->string('mpesa_phone')->nullable()->after('mpesa_receipt_no');
        });
    }

    public function down(): void
    {
        Schema::table('cash_receipts', function (Blueprint $table) {
            $table->dropColumn(['description', 'mpesa_receipt_no', 'mpesa_phone']);
        });
    }
};
