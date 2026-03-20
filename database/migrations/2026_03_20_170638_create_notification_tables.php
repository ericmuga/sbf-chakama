<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('member_no', 20);
            $table->string('title');
            $table->text('body');
            $table->enum('status', ['Draft', 'Scheduled', 'Sent', 'Failed']);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_log')->nullable();

            $table->foreign('member_no')->references('no')->on('bus_members')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_notifications');
    }
};
