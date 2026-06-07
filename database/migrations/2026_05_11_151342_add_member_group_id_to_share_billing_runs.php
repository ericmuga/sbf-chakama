<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('share_billing_runs', function (Blueprint $table) {
            $table->foreignId('member_group_id')->nullable()->after('billing_schedule_id')->constrained('member_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('share_billing_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_group_id');
        });
    }
};
