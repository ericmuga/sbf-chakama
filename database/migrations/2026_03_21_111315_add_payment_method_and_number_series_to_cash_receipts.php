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
            $table->foreignId('payment_method_id')->nullable()->after('bank_account_id')->constrained('payment_methods')->nullOnDelete();
            $table->string('number_series_code', 50)->nullable()->after('no');
            $table->foreign('number_series_code')->references('code')->on('number_series')->nullOnDelete();
            // make no nullable so it can be auto-assigned after insert
            $table->string('no', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_receipts', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropForeign(['number_series_code']);
            $table->dropColumn(['payment_method_id', 'number_series_code']);
            $table->string('no', 50)->nullable(false)->change();
        });
    }
};
