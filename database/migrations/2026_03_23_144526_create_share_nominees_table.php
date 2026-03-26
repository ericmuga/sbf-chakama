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
        Schema::create('share_nominees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('bus_members')->cascadeOnDelete();
            $table->string('full_name', 255);
            $table->string('national_id', 50);
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('relationship', 100)->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'national_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_nominees');
    }
};
