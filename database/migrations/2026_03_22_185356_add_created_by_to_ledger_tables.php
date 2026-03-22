<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['gl_entries', 'customer_ledger_entries', 'vendor_ledger_entries'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('created_at');
            });
        }
    }

    public function down(): void
    {
        foreach (['gl_entries', 'customer_ledger_entries', 'vendor_ledger_entries'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['created_by']);
                $blueprint->dropColumn('created_by');
            });
        }
    }
};
