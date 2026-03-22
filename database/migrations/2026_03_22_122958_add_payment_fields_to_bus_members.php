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
        Schema::table('bus_members', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('vendor_no');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_no', 50)->nullable()->after('bank_account_name');
            $table->string('bank_branch')->nullable()->after('bank_account_no');
            $table->string('mpesa_phone', 20)->nullable()->after('bank_branch');
            $table->string('preferred_payment_method', 20)->nullable()->after('mpesa_phone');
        });
    }

    public function down(): void
    {
        Schema::table('bus_members', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_account_name',
                'bank_account_no',
                'bank_branch',
                'mpesa_phone',
                'preferred_payment_method',
            ]);
        });
    }
};
