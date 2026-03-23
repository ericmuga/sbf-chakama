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
        Schema::create('fund_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_account_id')->constrained('fund_accounts');
            $table->string('transaction_type', 30);
            $table->string('description', 500);
            $table->decimal('amount', 18, 4);
            $table->decimal('running_balance', 18, 4);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('document_no', 50)->nullable();
            $table->date('posting_date');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index('transaction_type');
            $table->index('posting_date');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_transactions');
    }
};
