<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_no_series', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('description');
            $table->string('prefix', 10);
            $table->unsignedInteger('last_no_used')->default(0);
            $table->unsignedInteger('increment_by')->default(1);
        });

        Schema::create('bus_members', function (Blueprint $table) {
            $table->id();
            $table->string('no', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('national_id', 50)->unique();
            $table->string('phone', 20);
            $table->enum('member_status', ['active', 'lapsed', 'suspended']);
            $table->string('customer_no', 20)->nullable();
            $table->string('vendor_no', 20)->nullable();
        });

        Schema::create('bus_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('no', 20)->unique();
            $table->string('name');
            $table->enum('vendor_type', ['External', 'Member']);
            $table->foreignId('member_id')->nullable()->constrained('bus_members')->nullOnDelete();
            $table->string('payment_terms', 20);
        });

        Schema::create('bus_projects', function (Blueprint $table) {
            $table->id();
            $table->string('no', 20)->unique();
            $table->string('title');
            $table->decimal('budget_lcy', 18, 2);
            $table->decimal('total_actual_cost', 18, 2)->default(0);
            $table->enum('status', ['Planning', 'Active', 'Completed', 'Closed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_projects');
        Schema::dropIfExists('bus_vendors');
        Schema::dropIfExists('bus_members');
        Schema::dropIfExists('bus_no_series');
    }
};
