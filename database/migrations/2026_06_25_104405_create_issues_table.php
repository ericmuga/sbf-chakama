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
        Schema::create('app_issues', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('portal_type')->default('sbf');
            $table->text('details')->nullable();
            $table->string('issue_owner')->nullable();
            $table->string('category')->default('development');
            $table->string('resource')->nullable();
            $table->date('date_assigned')->nullable();
            $table->string('status')->default('open');
            $table->date('closure_date')->nullable();
            $table->text('comments')->nullable();
            $table->date('reviewed_date')->nullable();
            $table->string('qa_test_result')->nullable();
            $table->foreignId('release_id')->nullable()->constrained('app_releases')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_issues');
    }
};
