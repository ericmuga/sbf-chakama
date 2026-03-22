<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('entry_no');
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('document_type', 50)->nullable();
            $table->string('document_no', 50);
            $table->date('posting_date');
            $table->string('description')->nullable();
            $table->decimal('amount', 18, 4);
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['bank_account_id', 'posting_date']);
            $table->index('entry_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_ledger_entries');
    }
};
