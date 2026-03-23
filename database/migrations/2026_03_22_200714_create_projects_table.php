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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('module', 20);
            $table->decimal('budget', 18, 4)->default(0);
            $table->decimal('spent', 18, 4)->default(0);
            $table->string('status', 20)->default('draft');
            $table->string('priority', 20)->default('medium');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('number_series_code', 50);
            $table->foreignId('created_by')->constrained('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('number_series_code')->references('code')->on('number_series');

            $table->index('module');
            $table->index('status');
            $table->index('priority');
            $table->index('created_by');
            $table->index('start_date');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
