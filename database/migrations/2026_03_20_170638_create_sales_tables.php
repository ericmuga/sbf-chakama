<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_sales_headers', function (Blueprint $table) {
            $table->id();
            $table->string('no', 20)->unique();
            $table->string('member_no', 20);
            $table->date('posting_date');
            $table->date('due_date');
            $table->decimal('total_amount', 18, 2);

            $table->foreign('member_no')->references('no')->on('bus_members')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('doc_sales_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('header_id')->constrained('doc_sales_headers')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 18, 2);
            $table->string('gl_account_no', 20);
        });

        Schema::create('pst_sales_headers', function (Blueprint $table) {
            $table->id();
            $table->string('no', 20)->unique();
            $table->string('member_no', 20);
            $table->date('posting_date');
            $table->string('external_doc_no', 100)->nullable();
            $table->decimal('total_amount', 18, 2);

            $table->foreign('member_no')->references('no')->on('bus_members')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('pst_sales_lines', function (Blueprint $table) {
            $table->id();
            $table->string('header_no', 20);
            $table->string('description');
            $table->decimal('amount', 18, 2);

            $table->foreign('header_no')->references('no')->on('pst_sales_headers')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pst_sales_lines');
        Schema::dropIfExists('pst_sales_headers');
        Schema::dropIfExists('doc_sales_lines');
        Schema::dropIfExists('doc_sales_headers');
    }
};
