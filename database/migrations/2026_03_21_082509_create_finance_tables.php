<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_series', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description');
            $table->string('prefix', 50)->nullable();
            $table->bigInteger('last_no')->default(0);
            $table->date('last_date_used')->nullable();
            $table->integer('length')->default(6);
            $table->boolean('is_manual_allowed')->default(false);
            $table->boolean('prevent_repeats')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_posting_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description');
            $table->string('receivables_account_no', 50);
            $table->string('service_charge_account_no', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_posting_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description');
            $table->string('payables_account_no', 50);
            $table->timestamps();
        });

        Schema::create('service_posting_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description');
            $table->string('revenue_account_no', 50);
            $table->timestamps();
        });

        Schema::create('bank_posting_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description');
            $table->string('bank_account_gl_no', 50);
            $table->timestamps();
        });

        Schema::create('general_posting_setups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_posting_group_id')->constrained('customer_posting_groups');
            $table->foreignId('service_posting_group_id')->constrained('service_posting_groups');
            $table->string('sales_account_no', 50);
            $table->timestamps();
            $table->unique(['customer_posting_group_id', 'service_posting_group_id']);
        });

        Schema::create('payment_terms', function (Blueprint $table) {
            $table->string('code', 50)->primary();
            $table->string('description');
            $table->integer('due_days');
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->string('name');
            $table->foreignId('customer_posting_group_id')->constrained('customer_posting_groups');
            $table->string('payment_terms_code', 50)->nullable();
            $table->foreign('payment_terms_code')->references('code')->on('payment_terms');
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->string('name');
            $table->foreignId('vendor_posting_group_id')->constrained('vendor_posting_groups');
            $table->string('payment_terms_code', 50)->nullable();
            $table->foreign('payment_terms_code')->references('code')->on('payment_terms');
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description');
            $table->decimal('unit_price', 18, 4);
            $table->foreignId('service_posting_group_id')->constrained('service_posting_groups');
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('bank_account_no', 50);
            $table->foreignId('bank_posting_group_id')->constrained('bank_posting_groups');
            $table->string('currency_code', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('sales_setups', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_nos', 50);
            $table->foreign('invoice_nos')->references('code')->on('number_series');
            $table->string('posted_invoice_nos', 50);
            $table->foreign('posted_invoice_nos')->references('code')->on('number_series');
            $table->timestamps();
        });

        Schema::create('purchase_setups', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_nos', 50);
            $table->foreign('invoice_nos')->references('code')->on('number_series');
            $table->string('posted_invoice_nos', 50);
            $table->foreign('posted_invoice_nos')->references('code')->on('number_series');
            $table->timestamps();
        });

        Schema::create('sales_headers', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('document_type', 50);
            $table->date('posting_date');
            $table->date('due_date')->nullable();
            $table->foreignId('customer_posting_group_id')->constrained('customer_posting_groups');
            $table->string('number_series_code', 50);
            $table->foreign('number_series_code')->references('code')->on('number_series');
            $table->string('status', 50)->default('Open');
            $table->timestamps();
        });

        Schema::create('sales_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_header_id')->constrained('sales_headers')->cascadeOnDelete();
            $table->integer('line_no');
            $table->foreignId('service_id')->constrained('services');
            $table->string('description');
            $table->decimal('quantity', 10, 4);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('line_amount', 18, 4);
            $table->foreignId('customer_posting_group_id')->constrained('customer_posting_groups');
            $table->foreignId('service_posting_group_id')->constrained('service_posting_groups');
            $table->foreignId('general_posting_setup_id')->constrained('general_posting_setups');
            $table->timestamps();
            $table->unique(['sales_header_id', 'line_no']);
        });

        Schema::create('purchase_headers', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->date('posting_date');
            $table->date('due_date')->nullable();
            $table->foreignId('vendor_posting_group_id')->constrained('vendor_posting_groups');
            $table->string('number_series_code', 50);
            $table->foreign('number_series_code')->references('code')->on('number_series');
            $table->string('status', 50)->default('Open');
            $table->timestamps();
        });

        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_header_id')->constrained('purchase_headers')->cascadeOnDelete();
            $table->integer('line_no');
            $table->foreignId('service_id')->constrained('services');
            $table->string('description');
            $table->decimal('quantity', 10, 4);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('line_amount', 18, 4);
            $table->timestamps();
            $table->unique(['purchase_header_id', 'line_no']);
        });

        Schema::create('gl_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->string('name');
            $table->string('account_type', 50);
            $table->timestamps();
        });

        Schema::create('gl_entries', function (Blueprint $table) {
            $table->id();
            $table->date('posting_date');
            $table->string('document_no', 50)->index();
            $table->string('account_no', 50)->index();
            $table->decimal('debit_amount', 18, 4)->default(0);
            $table->decimal('credit_amount', 18, 4)->default(0);
            $table->string('source_type', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entry_no')->unique()->index();
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('document_type', 50);
            $table->string('document_no', 50)->index();
            $table->date('posting_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 18, 4);
            $table->decimal('remaining_amount', 18, 4);
            $table->boolean('is_open')->default(true);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('vendor_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entry_no')->unique()->index();
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->string('document_type', 50);
            $table->string('document_no', 50)->index();
            $table->date('posting_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 18, 4);
            $table->decimal('remaining_amount', 18, 4);
            $table->boolean('is_open')->default(true);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('detailed_customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_ledger_entry_id')->constrained('customer_ledger_entries')->cascadeOnDelete();
            $table->unsignedBigInteger('applied_entry_id')->nullable()->index();
            $table->string('document_no', 50)->index();
            $table->date('posting_date');
            $table->decimal('amount', 18, 4);
            $table->string('entry_type', 50);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('detailed_vendor_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_ledger_entry_id')->constrained('vendor_ledger_entries')->cascadeOnDelete();
            $table->unsignedBigInteger('applied_entry_id')->nullable()->index();
            $table->string('document_no', 50)->index();
            $table->date('posting_date');
            $table->decimal('amount', 18, 4);
            $table->string('entry_type', 50);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('cash_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('bank_account_id')->constrained('bank_accounts');
            $table->date('posting_date');
            $table->decimal('amount', 18, 4);
            $table->string('status', 50)->default('Open');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->foreignId('bank_account_id')->constrained('bank_accounts');
            $table->date('posting_date');
            $table->decimal('amount', 18, 4);
            $table->string('status', 50)->default('Open');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('customer_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_entry_id')->constrained('customer_ledger_entries');
            $table->foreignId('invoice_entry_id')->constrained('customer_ledger_entries');
            $table->decimal('amount_applied', 18, 4);
        });

        Schema::create('vendor_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_entry_id')->constrained('vendor_ledger_entries');
            $table->foreignId('invoice_entry_id')->constrained('vendor_ledger_entries');
            $table->decimal('amount_applied', 18, 4);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_applications');
        Schema::dropIfExists('customer_applications');
        Schema::dropIfExists('vendor_payments');
        Schema::dropIfExists('cash_receipts');
        Schema::dropIfExists('detailed_vendor_ledger_entries');
        Schema::dropIfExists('detailed_customer_ledger_entries');
        Schema::dropIfExists('vendor_ledger_entries');
        Schema::dropIfExists('customer_ledger_entries');
        Schema::dropIfExists('gl_entries');
        Schema::dropIfExists('gl_accounts');
        Schema::dropIfExists('purchase_lines');
        Schema::dropIfExists('purchase_headers');
        Schema::dropIfExists('sales_lines');
        Schema::dropIfExists('sales_headers');
        Schema::dropIfExists('purchase_setups');
        Schema::dropIfExists('sales_setups');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('services');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('payment_terms');
        Schema::dropIfExists('general_posting_setups');
        Schema::dropIfExists('bank_posting_groups');
        Schema::dropIfExists('service_posting_groups');
        Schema::dropIfExists('vendor_posting_groups');
        Schema::dropIfExists('customer_posting_groups');
        Schema::dropIfExists('number_series');
    }
};
