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
        Schema::table('purchase_setups', function (Blueprint $table) {
            $table->string('payment_nos', 50)->nullable()->after('vendor_nos');
            $table->foreign('payment_nos')->references('code')->on('number_series')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_setups', function (Blueprint $table) {
            $table->dropForeign(['payment_nos']);
            $table->dropColumn('payment_nos');
        });
    }
};
