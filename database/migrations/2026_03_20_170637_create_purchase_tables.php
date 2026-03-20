<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_purchase_headers', function (Blueprint $table) {
            $table->id();
            $table->string('no', 20)->unique();
            $table->string('vendor_no', 20);
            $table->enum('doc_type', ['Claim', 'Invoice']);
            $table->foreignId('project_id')->nullable()->constrained('bus_projects')->nullOnDelete();
            $table->enum('status', ['Draft', 'Pending Approval', 'Approved', 'Rejected']);

            $table->foreign('vendor_no')->references('no')->on('bus_vendors')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('doc_purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('header_id')->constrained('doc_purchase_headers')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 18, 2);
            $table->foreignId('project_id')->nullable()->constrained('bus_projects')->nullOnDelete();
        });

        Schema::create('pst_purchase_headers', function (Blueprint $table) {
            $table->id();
            $table->string('no', 20)->unique();
            $table->string('vendor_no', 20);
            $table->date('posting_date');
            $table->foreignId('project_id')->nullable()->constrained('bus_projects')->nullOnDelete();
            $table->decimal('total_amount', 18, 2);

            $table->foreign('vendor_no')->references('no')->on('bus_vendors')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pst_purchase_headers');
        Schema::dropIfExists('doc_purchase_lines');
        Schema::dropIfExists('doc_purchase_headers');
    }
};
