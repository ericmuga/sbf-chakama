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
        // Only rename if the old column name still exists (existing databases).
        // Fresh installs already use identity_no from the amended create migration.
        if (Schema::hasColumn('bus_members', 'national_id')) {
            Schema::table('bus_members', function (Blueprint $table) {
                $table->renameColumn('national_id', 'identity_no');
            });
        }

        if (! Schema::hasColumn('bus_members', 'identity_type')) {
            Schema::table('bus_members', function (Blueprint $table) {
                $table->enum('identity_type', ['national_id', 'passport_no', 'birth_cert_no', 'driving_licence_no', 'pin_no'])
                    ->default('national_id')
                    ->after('identity_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bus_members', 'identity_type')) {
            Schema::table('bus_members', function (Blueprint $table) {
                $table->dropColumn('identity_type');
            });
        }

        if (Schema::hasColumn('bus_members', 'identity_no') && ! Schema::hasColumn('bus_members', 'national_id')) {
            Schema::table('bus_members', function (Blueprint $table) {
                $table->renameColumn('identity_no', 'national_id');
            });
        }
    }
};
