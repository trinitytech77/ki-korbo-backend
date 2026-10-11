<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * Super Admin has access to everything.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any tasks.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the specific task.
     * - Customer: own tasks only
     * - Agent: assigned tasks only
     * - Admin/Operations: operational access
     */
    public function view(User $user, Task $task): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if ($user->isCustomer()) {
            return (int) $task->user_id === (int) $user->id;
        }

        if ($user->isAgent() || $user->isProfessional()) {
            return $task->isAssignedTo($user->id);
        }

        return false;
    }

    /**
     * Determine whether the user can create tasks.
     * Customers and operational staff can create tasks.
     */
    public function create(User $user): bool
    {
        return $user->isCustomer() || $user->isStaff();
    }

    /**
     * Determine whether the user can update the task.
     * - Admin/Operations: operational access
     * - Customer: own tasks
     * - Agent: assigned tasks only (e.g. status updates, submitting proof)
     */
    public function update(User $user, Task $task): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if ($user->isCustomer()) {
            return (int) $task->user_id === (int) $user->id;
        }

        if ($user->isAgent() || $user->isProfessional()) {
            return $task->isAssignedTo($user->id);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the task.
     * - Admin/Staff: operational access
     * - Customer: own tasks only while still in DRAFT or PENDING status
     */
    public function delete(User $user, Task $task): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if ($user->isCustomer() && (int) $task->user_id === (int) $user->id) {
            $status = strtoupper($task->status ?? '');
            return in_array($status, ['DRAFT', 'PENDING', 'PENDING_PAYMENT']);
        }

        return false;
    }

    /**
     * Determine whether the user can assign an agent to the task.
     * Admin/Operations staff only.
     */
    public function assignAgent(User $user, Task $task): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can cancel the task.
     */
    public function cancel(User $user, Task $task): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if ($user->isCustomer() && (int) $task->user_id === (int) $user->id) {
            $status = strtoupper($task->status ?? '');
            return !in_array($status, ['COMPLETED', 'CANCELLED']);
        }

        return false;
    }
}
