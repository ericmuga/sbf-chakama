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
        foreach (['gl_entries', 'customer_ledger_entries', 'vendor_ledger_entries'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->string('dimension', 20)->nullable()->after('id')
                    ->comment('chakama | sbf — entity dimension for independent account tracking');
            });
        }
    }

    public function down(): void
    {
        foreach (['gl_entries', 'customer_ledger_entries', 'vendor_ledger_entries'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->dropColumn('dimension');
            });
        }
    }
};
