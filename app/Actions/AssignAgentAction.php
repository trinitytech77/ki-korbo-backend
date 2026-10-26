<?php

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AssignAgentAction
{
    /**
     * Assign an agent to a task and record the assignment history.
     *
     * @param  Task  $task    The task to assign.
     * @param  User  $agent   The agent user to assign.
     * @param  User  $actor   The staff member performing the assignment.
     * @return Task
     */
    public function execute(Task $task, User $agent, User $actor): Task
    {
        // Cannot assign agents to terminal tasks
        if ($task->isTerminal()) {
            throw ValidationException::withMessages([
                'agent_id' => ["এই টাস্কটি '{$task->status}' অবস্থায় আছে, এজেন্ট assign করা যাবে না।"],
            ]);
        }

        $previousAgentId = $task->agent_id;

        // Update task with new agent and set status to ASSIGNED
        $task->agent_id = $agent->id;
        $task->status   = TaskStatus::ASSIGNED->value;
        $task->save();

        // Create assignment record in task_assignments table
        $task->assignments()->create([
            'agent_id'    => $agent->id,
            'status'      => 'ASSIGNED',
            'assigned_at' => now(),
        ]);

        // Log the status change
        $note = $previousAgentId
            ? "এজেন্ট পরিবর্তন করা হয়েছে। নতুন এজেন্ট: {$agent->name}"
            : "এজেন্ট assign করা হয়েছে: {$agent->name}";

        $task->statusHistories()->create([
            'status'     => TaskStatus::ASSIGNED->value,
            'notes'      => $note,
            'changed_by' => $actor->id,
        ]);

        return $task->load(['service', 'customer', 'agent', 'statusHistories.changedBy', 'assignments']);
    }
}
