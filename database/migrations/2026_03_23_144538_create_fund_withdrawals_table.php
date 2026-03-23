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
        Schema::create('fund_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->foreignId('fund_account_id')->constrained('fund_accounts');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('description', 500);
            $table->decimal('amount', 18, 4);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('approval_template_id')->nullable();
            $table->unsignedInteger('current_step')->default(0);
            $table->string('payee_name', 255);
            $table->string('payment_method', 20)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account_name', 255)->nullable();
            $table->string('bank_account_no', 50)->nullable();
            $table->string('bank_branch', 255)->nullable();
            $table->string('mpesa_phone', 20)->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('purchase_header_id')->nullable();
            $table->unsignedBigInteger('vendor_payment_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->string('number_series_code', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('approval_template_id')->references('id')->on('claim_approval_templates')->nullOnDelete();
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            $table->foreign('purchase_header_id')->references('id')->on('purchase_headers')->nullOnDelete();
            $table->foreign('vendor_payment_id')->references('id')->on('vendor_payments')->nullOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('number_series_code')->references('code')->on('number_series')->nullOnDelete();

            $table->index('fund_account_id');
            $table->index('status');
            $table->index('project_id');
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_withdrawals');
    }
};
