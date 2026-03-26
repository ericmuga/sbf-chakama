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
        Schema::table('sales_headers', function (Blueprint $table) {
            $table->unsignedBigInteger('share_subscription_id')->nullable();
            $table->index('share_subscription_id');
            $table->foreign('share_subscription_id')->references('id')->on('share_subscriptions')->nullOnDelete();
        });

        Schema::table('cash_receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('share_subscription_id')->nullable();
            $table->index('share_subscription_id');
            $table->foreign('share_subscription_id')->references('id')->on('share_subscriptions')->nullOnDelete();
        });

        Schema::table('purchase_headers', function (Blueprint $table) {
            $table->unsignedBigInteger('fund_withdrawal_id')->nullable();
            $table->index('fund_withdrawal_id');
            $table->foreign('fund_withdrawal_id')->references('id')->on('fund_withdrawals')->nullOnDelete();
        });

        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('fund_withdrawal_id')->nullable();
            $table->index('fund_withdrawal_id');
            $table->foreign('fund_withdrawal_id')->references('id')->on('fund_withdrawals')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_headers', function (Blueprint $table) {
            $table->dropForeign(['share_subscription_id']);
            $table->dropIndex(['share_subscription_id']);
            $table->dropColumn('share_subscription_id');
        });

        Schema::table('cash_receipts', function (Blueprint $table) {
            $table->dropForeign(['share_subscription_id']);
            $table->dropIndex(['share_subscription_id']);
            $table->dropColumn('share_subscription_id');
        });

        Schema::table('purchase_headers', function (Blueprint $table) {
            $table->dropForeign(['fund_withdrawal_id']);
            $table->dropIndex(['fund_withdrawal_id']);
            $table->dropColumn('fund_withdrawal_id');
        });

        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->dropForeign(['fund_withdrawal_id']);
            $table->dropIndex(['fund_withdrawal_id']);
            $table->dropColumn('fund_withdrawal_id');
        });
    }
};
