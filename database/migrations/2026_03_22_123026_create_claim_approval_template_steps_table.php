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
        Schema::create('claim_approval_template_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('claim_approval_templates')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->foreignId('approver_user_id')->nullable()->constrained('users');
            $table->string('role_name', 100)->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['template_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claim_approval_template_steps');
    }
};
