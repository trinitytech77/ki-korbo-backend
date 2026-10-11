<?php

namespace App\Policies;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceCategoryPolicy
{
    use HandlesAuthorization;

    public function before(?User $user, string $ability): ?bool
    {
        if ($user && $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ServiceCategory $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, ServiceCategory $category): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, ServiceCategory $category): bool
    {
        return $user->isAdmin();
    }
}
