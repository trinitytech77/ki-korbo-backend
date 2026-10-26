<?php

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Models\Service;
use App\Models\Task;
use App\Models\User;

class CreateTaskAction
{
    /**
     * Create a new task for a customer, auto-populating fees from the service.
     *
     * @param  User   $customer  The authenticated customer creating the task.
     * @param  array  $data      Validated request data (service_id, location, etc.)
     * @return Task
     */
    public function execute(User $customer, array $data): Task
    {
        /** @var Service $service */
        $service = Service::with('fees')->findOrFail($data['service_id']);

        // Auto-calculate fees from the service engine
        $governmentFee = (float) $service->government_fee;
        $serviceFee    = (float) $service->korbo_fee;     // Platform / KORBO fee
        $agentFee      = (float) $service->agent_fee;
        $totalAmount   = $governmentFee + $serviceFee + $agentFee;

        // Create the task
        $task = Task::create([
            'user_id'          => $customer->id,
            'service_id'       => $service->id,
            'status'           => TaskStatus::NEW->value,
            'government_fee'   => $governmentFee,
            'service_fee'      => $serviceFee,
            'agent_fee'        => $agentFee,
            'total_amount'     => $totalAmount,
            'location'         => $data['location'],
            'appointment_date' => $data['appointment_date'] ?? null,
            'notes'            => $data['notes'] ?? null,
        ]);

        // Record first status history entry
        $task->statusHistories()->create([
            'status'     => TaskStatus::NEW->value,
            'notes'      => 'Task created.',
            'changed_by' => $customer->id,
        ]);

        return $task->load(['service', 'customer', 'statusHistories']);
    }
}
