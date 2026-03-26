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
        Schema::table('sales_setups', function (Blueprint $table) {
            $table->string('share_subscription_nos', 50)->nullable();
            $table->foreign('share_subscription_nos')->references('code')->on('number_series')->nullOnDelete();
        });

        Schema::table('purchase_setups', function (Blueprint $table) {
            $table->string('fund_withdrawal_nos', 50)->nullable();
            $table->foreign('fund_withdrawal_nos')->references('code')->on('number_series')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_setups', function (Blueprint $table) {
            $table->dropForeign(['share_subscription_nos']);
            $table->dropColumn('share_subscription_nos');
        });

        Schema::table('purchase_setups', function (Blueprint $table) {
            $table->dropForeign(['fund_withdrawal_nos']);
            $table->dropColumn('fund_withdrawal_nos');
        });
    }
};
