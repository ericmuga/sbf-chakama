<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bus_members', function (Blueprint $table) {
            $table->enum('type', ['member', 'dependant', 'next_of_kin'])->default('member')->after('id');
            $table->unsignedBigInteger('member_id')->nullable()->after('type');
            $table->foreign('member_id')->references('id')->on('bus_members')->cascadeOnDelete();
            $table->string('name')->nullable()->after('member_id');
            $table->string('email')->nullable()->after('name');
            $table->date('date_of_birth')->nullable()->after('email');
            $table->string('relationship')->nullable()->after('date_of_birth');
            $table->enum('contact_preference', ['phone', 'email', 'both'])->nullable()->after('relationship');

            $table->string('no', 20)->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('identity_no', 50)->nullable()->change();
            $table->string('phone', 20)->nullable()->change();
            $table->enum('member_status', ['active', 'lapsed', 'suspended'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bus_members', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropColumn(['type', 'member_id', 'name', 'email', 'date_of_birth', 'relationship', 'contact_preference']);

            $table->string('no', 20)->nullable(false)->change();
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->string('identity_no', 50)->nullable(false)->change();
            $table->string('phone', 20)->nullable(false)->change();
            $table->enum('member_status', ['active', 'lapsed', 'suspended'])->nullable(false)->change();
        });
    }
};
