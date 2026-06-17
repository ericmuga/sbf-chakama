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
            $table->string('applies_to_doc_no', 50)->nullable()->after('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_headers', function (Blueprint $table) {
            $table->dropColumn('applies_to_doc_no');
        });
    }
};
