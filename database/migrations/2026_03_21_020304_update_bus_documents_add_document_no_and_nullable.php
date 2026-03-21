<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bus_documents', function (Blueprint $table) {
            $table->string('document_no')->nullable()->after('document_type');
            $table->string('document_type')->nullable()->change();
            $table->string('file_path')->nullable()->change();
            $table->string('original_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bus_documents', function (Blueprint $table) {
            $table->dropColumn('document_no');
            $table->string('document_type')->nullable(false)->change();
            $table->string('file_path')->nullable(false)->change();
            $table->string('original_name')->nullable(false)->change();
        });
    }
};
