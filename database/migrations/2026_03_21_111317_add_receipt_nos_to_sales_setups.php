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
            $table->string('receipt_nos', 50)->nullable()->after('member_nos');
            $table->foreign('receipt_nos')->references('code')->on('number_series')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_setups', function (Blueprint $table) {
            $table->dropForeign(['receipt_nos']);
            $table->dropColumn('receipt_nos');
        });
    }
};
