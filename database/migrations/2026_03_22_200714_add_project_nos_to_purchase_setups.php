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
            $table->string('project_nos', 50)->nullable()->after('payment_nos');
            $table->string('direct_cost_nos', 50)->nullable()->after('project_nos');

            $table->foreign('project_nos')->references('code')->on('number_series')->nullOnDelete();
            $table->foreign('direct_cost_nos')->references('code')->on('number_series')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_setups', function (Blueprint $table) {
            $table->dropForeign(['project_nos']);
            $table->dropForeign(['direct_cost_nos']);
            $table->dropColumn(['project_nos', 'direct_cost_nos']);
        });
    }
};
