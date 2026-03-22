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
            $table->string('claim_nos', 50)->nullable()->after('receipt_nos');
            $table->foreign('claim_nos')->references('code')->on('number_series')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_setups', function (Blueprint $table) {
            $table->dropForeign(['claim_nos']);
            $table->dropColumn('claim_nos');
        });
    }
};
