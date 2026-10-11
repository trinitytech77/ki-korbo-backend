<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    /**
     * Super Admin bypasses all checks.
     */
    public function before(?User $user, string $ability): ?bool
    {
        if ($user && $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Anyone can view list of services.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Anyone can view active services; staff can view all.
     */
    public function view(?User $user, Service $service): bool
    {
        if ($service->is_active) {
            return true;
        }

        return $user && $user->isStaff();
    }

    /**
     * Platform administrators and operational staff can create services.
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Platform administrators and operational staff can update services.
     */
    public function update(User $user, Service $service): bool
    {
        return $user->isStaff();
    }

    /**
     * Administrators can delete services.
     */
    public function delete(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }
}
