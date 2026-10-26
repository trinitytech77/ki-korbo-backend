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
        Schema::table('task_status_histories', function (Blueprint $table) {
            $table->foreignId('changed_by')->nullable()->after('notes')
                  ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_status_histories', function (Blueprint $table) {
            $table->dropForeign(['changed_by']);
            $table->dropColumn('changed_by');
        });
    }
};
