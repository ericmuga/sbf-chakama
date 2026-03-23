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
        Schema::create('project_direct_costs', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('cost_type', 20)->default('other');
            $table->string('description', 500);
            $table->decimal('amount', 18, 4);
            $table->string('gl_account_no', 50);
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('receipt_path', 500)->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->string('status', 20)->default('pending');
            $table->date('posting_date');
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('submitted_by')->constrained('users');
            $table->string('number_series_code', 50);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('gl_account_no')->references('no')->on('gl_accounts');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            $table->foreign('posted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('number_series_code')->references('code')->on('number_series');

            $table->index('project_id');
            $table->index('status');
            $table->index('cost_type');
            $table->index('posting_date');
            $table->index('submitted_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_direct_costs');
    }
};
