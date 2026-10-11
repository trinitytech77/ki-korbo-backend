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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'language')) {
                $table->string('language', 10)->default('en')->after('avatar');
            }
        });

        Schema::table('customer_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_profiles', 'gender')) {
                $table->string('gender', 20)->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('customer_profiles', 'emergency_contact')) {
                $table->string('emergency_contact')->nullable()->after('gender');
            }
        });

        Schema::table('agents', function (Blueprint $table) {
            if (!Schema::hasColumn('agents', 'nid_number')) {
                $table->string('nid_number')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('agents', 'service_area')) {
                $table->string('service_area')->nullable()->after('nid_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['nid_number', 'service_area']);
        });

        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn(['gender', 'emergency_contact']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'language']);
        });
    }
};
