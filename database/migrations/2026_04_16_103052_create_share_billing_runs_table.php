<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_billing_runs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('billing_schedule_id')->constrained('share_billing_schedules')->noActionOnDelete();
            $table->date('billing_date');
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('draft')->index(); // draft, processing, completed, failed
            $table->boolean('notify_members')->default(true);
            $table->boolean('send_email')->default(true);
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
        Schema::dropIfExists('share_billing_runs');
    }
};
