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
        Schema::table('claim_approval_template_steps', function (Blueprint $table): void {
            $table->string('label', 255)->nullable()->after('step_order');
        });
    }

    public function down(): void
    {
        Schema::table('claim_approval_template_steps', function (Blueprint $table): void {
            $table->dropColumn('label');
        });
    }
};
