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
        Schema::create('project_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('gl_account_no', 50);
            $table->string('description', 255);
            $table->decimal('budgeted_amount', 18, 4);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('gl_account_no')->references('no')->on('gl_accounts');
            $table->unique(['project_id', 'gl_account_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_budget_lines');
    }
};
