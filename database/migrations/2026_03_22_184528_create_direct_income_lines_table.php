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
        Schema::create('direct_income_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('direct_income_id')->constrained('direct_incomes')->cascadeOnDelete();
            $table->integer('line_no');
            $table->foreignId('service_id')->nullable()->nullOnDelete()->constrained('services');
            $table->string('description');
            $table->decimal('amount', 18, 4);
            $table->timestamps();

            $table->unique(['direct_income_id', 'line_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_income_lines');
    }
};
