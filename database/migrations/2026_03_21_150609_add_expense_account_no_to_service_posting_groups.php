<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_posting_groups', function (Blueprint $table) {
            $table->string('expense_account_no', 20)->nullable()->after('revenue_account_no');
        });
    }

    public function down(): void
    {
        Schema::table('service_posting_groups', function (Blueprint $table) {
            $table->dropColumn('expense_account_no');
        });
    }
};
