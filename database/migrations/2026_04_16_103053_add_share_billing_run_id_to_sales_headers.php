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
            $table->foreignId('share_billing_run_id')
                ->nullable()
                ->after('share_subscription_id')
                ->constrained('share_billing_runs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_headers', function (Blueprint $table) {
            $table->dropForeign(['share_billing_run_id']);
            $table->dropColumn('share_billing_run_id');
        });
    }
};
