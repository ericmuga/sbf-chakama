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
        Schema::table('purchase_headers', function (Blueprint $table) {
            $table->unsignedBigInteger('claim_id')->nullable()->after('status')->index();
            $table->foreign('claim_id')->references('id')->on('claims')->nullOnDelete();
        });

        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('claim_id')->nullable()->after('status')->index();
            $table->foreign('claim_id')->references('id')->on('claims')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_headers', function (Blueprint $table) {
            $table->dropForeign(['claim_id']);
            $table->dropColumn('claim_id');
        });

        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->dropForeign(['claim_id']);
            $table->dropColumn('claim_id');
        });
    }
};
