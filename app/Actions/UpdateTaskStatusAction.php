<?php

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateTaskStatusAction
{
    /**
     * Update a task's status and record the change in history.
     *
     * @param  Task        $task      The task to update.
     * @param  string      $newStatus The new TaskStatus value.
     * @param  User        $actor     The user performing the update.
     * @param  string|null $notes     Optional notes about the status change.
     * @return Task
     */
    public function execute(Task $task, string $newStatus, User $actor, ?string $notes = null): Task
    {
        // Prevent changes to terminal tasks
        if ($task->isTerminal()) {
            throw ValidationException::withMessages([
                'status' => ["এই টাস্কটি '{$task->status}' অবস্থায় আছে, আর পরিবর্তন করা যাবে না।"],
            ]);
        }

        $oldStatus = $task->status;

        // Update the task status
        $task->status = $newStatus;

        // Set completed_at timestamp when task is completed
        if ($newStatus === TaskStatus::COMPLETED->value) {
            $task->completed_at = now();
        }

        $task->save();

        // Log the status change
        $task->statusHistories()->create([
            'status'     => $newStatus,
            'notes'      => $notes ?? "স্ট্যাটাস পরিবর্তন: {$oldStatus} → {$newStatus}",
            'changed_by' => $actor->id,
        ]);

        return $task->load(['service', 'customer', 'agent', 'statusHistories.changedBy']);
    }
}
