<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_setups', function (Blueprint $table) {
            $table->string('customer_nos', 50)->nullable()->after('posted_invoice_nos');
            $table->string('member_nos', 50)->nullable()->after('customer_nos');
            $table->foreign('customer_nos')->references('code')->on('number_series')->nullOnDelete();
            $table->foreign('member_nos')->references('code')->on('number_series')->nullOnDelete();
        });

        Schema::table('purchase_setups', function (Blueprint $table) {
            $table->string('vendor_nos', 50)->nullable()->after('posted_invoice_nos');
            $table->foreign('vendor_nos')->references('code')->on('number_series')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_setups', function (Blueprint $table) {
            $table->dropForeign(['customer_nos']);
            $table->dropForeign(['member_nos']);
            $table->dropColumn(['customer_nos', 'member_nos']);
        });

        Schema::table('purchase_setups', function (Blueprint $table) {
            $table->dropForeign(['vendor_nos']);
            $table->dropColumn('vendor_nos');
        });
    }
};
