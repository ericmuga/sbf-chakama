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
        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->string('number_series_code', 50)->nullable()->after('no');
            $table->foreign('number_series_code')->references('code')->on('number_series')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->after('bank_account_id')->constrained('payment_methods')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->dropForeign(['number_series_code']);
            $table->dropColumn('number_series_code');
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });
    }
};
