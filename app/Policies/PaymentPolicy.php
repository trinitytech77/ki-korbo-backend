<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
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
     * Determine whether the user can view any payments.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the payment.
     * - Admin/Operations: operational access
     * - Customer: own payments only
     * - Agent: payments for tasks assigned to them
     */
    public function view(User $user, Payment $payment): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if ((int) $payment->user_id === (int) $user->id) {
            return true;
        }

        if (($user->isAgent() || $user->isProfessional()) && $payment->task) {
            return $payment->task->isAssignedTo($user->id);
        }

        return false;
    }

    /**
     * Determine whether the user can create payments.
     */
    public function create(User $user): bool
    {
        return $user->isCustomer() || $user->isStaff();
    }

    /**
     * Determine whether the user can update payment records.
     * Administrative/Operational staff only.
     */
    public function update(User $user, Payment $payment): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can process a refund.
     * Platform Administrators only.
     */
    public function refund(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }
}
