<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Owner of doc
            $table->foreignId('task_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('file_name');
            $table->string('storage_path');
            $table->string('mime_type')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        Schema::create('document_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_grants');
        Schema::dropIfExists('documents');
    }
};
