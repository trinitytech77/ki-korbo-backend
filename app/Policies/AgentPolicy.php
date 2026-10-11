<?php

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AgentPolicy
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
     * Determine whether the user can view any agents.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the agent profile.
     */
    public function view(User $user, Agent $agent): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create an agent profile.
     */
    public function create(User $user): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return ($user->isAgent() || $user->isProfessional()) && !$user->agent()->exists();
    }

    /**
     * Determine whether the user can update the agent profile.
     * - Admin/Operations: operational management
     * - Agent themselves: update bio, skills, service areas
     */
    public function update(User $user, Agent $agent): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return (int) $agent->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can delete an agent profile.
     * Admin only.
     */
    public function delete(User $user, Agent $agent): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can verify/approve an agent.
     * Admin or Operations staff only.
     */
    public function verify(User $user, Agent $agent): bool
    {
        return $user->isStaff();
    }
}
