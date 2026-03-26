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
        Schema::create('fund_withdrawal_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_withdrawal_id')->constrained('fund_withdrawals')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->foreignId('approver_user_id')->constrained('users');
            $table->string('action', 20)->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamp('due_by')->nullable();
            $table->timestamps();

            $table->unique(['fund_withdrawal_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_withdrawal_approvals');
    }
};
