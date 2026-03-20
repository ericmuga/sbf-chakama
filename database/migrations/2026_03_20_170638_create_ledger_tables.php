<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ent_member_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('entry_no')->unique();
            $table->string('member_no', 20);
            $table->date('posting_date');
            $table->enum('document_type', ['Invoice', 'Payment', 'Refund']);
            $table->string('document_no', 20);
            $table->decimal('amount', 18, 2);
            $table->decimal('remaining_amount', 18, 2);
            $table->boolean('open')->default(true);

            $table->foreign('member_no')->references('no')->on('bus_members')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('ent_vendor_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('entry_no')->unique();
            $table->string('vendor_no', 20);
            $table->date('posting_date');
            $table->enum('document_type', ['Claim', 'Invoice', 'Payment']);
            $table->string('document_no', 20);
            $table->decimal('amount', 18, 2);
            $table->decimal('remaining_amount', 18, 2);
            $table->boolean('open')->default(true);

            $table->foreign('vendor_no')->references('no')->on('bus_vendors')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('ent_detailed_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ledger_entry_no');
            $table->enum('ledger_type', ['Member', 'Vendor']);
            $table->enum('entry_type', ['Initial', 'Application', 'Correction', 'Unrealized Loss/Gain']);
            $table->date('posting_date');
            $table->decimal('amount', 18, 2);
        });

        Schema::create('ent_project_ledger', function (Blueprint $table) {
            $table->id();
            $table->string('project_no', 20);
            $table->date('posting_date');
            $table->string('document_no', 20);
            $table->enum('entry_type', ['Budget', 'Usage']);
            $table->decimal('amount', 18, 2);

            $table->foreign('project_no')->references('no')->on('bus_projects')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ent_project_ledger');
        Schema::dropIfExists('ent_detailed_ledger');
        Schema::dropIfExists('ent_vendor_ledger');
        Schema::dropIfExists('ent_member_ledger');
    }
};
