<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->nullable()->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('customer_posting_group_id')->constrained('customer_posting_groups');
            $table->decimal('amount', 18, 4);
            $table->date('scheduled_date');
            $table->date('due_date')->nullable();
            $table->boolean('notify_members')->default(true);
            $table->boolean('send_email')->default(false);
            $table->string('status', 20)->default('draft'); // draft, processing, completed, failed
            $table->timestamp('processed_at')->nullable();
            $table->decimal('total_invoiced', 18, 4)->default(0);
            $table->unsignedInteger('member_count')->default(0);
            $table->text('error_log')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_invoices');
    }
};
