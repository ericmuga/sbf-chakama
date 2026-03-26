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
        Schema::create('share_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('no', 50)->unique();
            $table->foreignId('member_id')->constrained('bus_members');
            $table->foreignId('billing_schedule_id')->constrained('share_billing_schedules');
            $table->unsignedInteger('number_of_shares')->default(1);
            $table->decimal('price_per_share', 18, 4);
            $table->decimal('total_amount', 18, 4);
            $table->decimal('amount_paid', 18, 4)->default(0);
            $table->string('status', 20)->default('pending_payment');
            $table->boolean('is_first_share')->default(false);
            $table->boolean('is_nominee')->default(false);
            $table->unsignedBigInteger('nominee_id')->nullable();
            $table->date('subscribed_at');
            $table->date('next_billing_date')->nullable();
            $table->string('number_series_code', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('number_series_code')->references('code')->on('number_series')->nullOnDelete();
            $table->foreign('nominee_id')->references('id')->on('share_nominees')->nullOnDelete();

            $table->index('status');
            $table->index('subscribed_at');
            $table->index('nominee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_subscriptions');
    }
};
