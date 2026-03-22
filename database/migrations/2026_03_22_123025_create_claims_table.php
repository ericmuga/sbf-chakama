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
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->foreignId('member_id')->constrained('bus_members');
            $table->string('claim_type', 20);
            $table->string('subject');
            $table->text('description')->nullable();
            $table->decimal('claimed_amount', 18, 4);
            $table->decimal('approved_amount', 18, 4)->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('approval_template_id')->nullable()->constrained('claim_approval_templates')->nullOnDelete();
            $table->unsignedInteger('current_step')->default(0);

            // Payee details
            $table->string('payee_name');
            $table->string('payment_method', 20)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_no', 50)->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('mpesa_phone', 20)->nullable();

            // Finance links
            $table->foreignId('purchase_header_id')->nullable()->constrained('purchase_headers')->nullOnDelete();
            $table->foreignId('vendor_payment_id')->nullable()->constrained('vendor_payments')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            // Timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('number_series_code', 50);
            $table->timestamps();
            $table->softDeletes();

            $table->index('member_id');
            $table->index('status');
            $table->index('claim_type');
            $table->index('submitted_at');

            $table->foreign('number_series_code')->references('code')->on('number_series');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
