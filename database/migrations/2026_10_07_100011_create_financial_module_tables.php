<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('PENDING'); // PENDING, AUTHORIZED, PAID, FAILED, REFUNDED
            $table->string('gateway')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamps();
        });
        
        Schema::create('agent_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('PENDING'); // PENDING, PAID
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_payouts');
        Schema::dropIfExists('payments');
    }
};
