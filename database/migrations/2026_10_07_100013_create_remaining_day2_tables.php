<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customer - missing family_members
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relation');
            $table->date('date_of_birth')->nullable();
            $table->timestamps();
        });

        // Agent - missing agent_verifications, agent_service_areas, agent_availability
        Schema::create('agent_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('document_type'); // NID, Utility Bill
            $table->string('file_path');
            $table->string('status')->default('PENDING');
            $table->timestamps();
        });

        Schema::create('agent_service_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('district');
            $table->string('area')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        // Tasks - missing task_assignments, task_notes
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('ASSIGNED');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('task_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // whoever wrote the note
            $table->text('note');
            $table->timestamps();
        });

        // Documents - missing document_access_logs
        Schema::create('document_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // who accessed it
            $table->string('action'); // VIEWED, DOWNLOADED
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        // Operations - appointments, submissions, collections
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->date('appointment_date');
            $table->time('appointment_time')->nullable();
            $table->string('location');
            $table->timestamps();
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('office_name');
            $table->date('submission_date');
            $table->timestamps();
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('office_name');
            $table->date('collection_date');
            $table->timestamps();
        });

        // Financial - payment_transactions, invoices, refunds
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_id');
            $table->string('gateway_response')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->string('status')->default('PENDING');
            $table->timestamps();
        });

        // Trust - disputes, audit_logs
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Raised by
            $table->string('reason');
            $table->text('evidence')->nullable();
            $table->string('status')->default('OPEN');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('document_access_logs');
        Schema::dropIfExists('task_notes');
        Schema::dropIfExists('task_assignments');
        Schema::dropIfExists('agent_availability');
        Schema::dropIfExists('agent_service_areas');
        Schema::dropIfExists('agent_verifications');
        Schema::dropIfExists('family_members');
    }
};
