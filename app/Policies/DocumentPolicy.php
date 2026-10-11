<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
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
     * Determine whether the user can view any documents.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the document.
     * - Admin/Operations: operational access to review any document
     * - Customer: own uploaded documents
     * - Agent/Professional: assigned task documents or explicitly granted access
     */
    public function view(User $user, Document $document): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        // Owner can always view their own document
        if ((int) $document->user_id === (int) $user->id) {
            return true;
        }

        // Agent can view if document is linked to a task assigned to them
        if (($user->isAgent() || $user->isProfessional()) && $document->task) {
            if ($document->task->isAssignedTo($user->id)) {
                return true;
            }
        }

        // Agent can view if explicit non-expired grant exists
        if ($user->isAgent() || $user->isProfessional()) {
            return $document->hasActiveGrantFor($user->id);
        }

        return false;
    }

    /**
     * Determine whether the user can upload/create documents.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return (int) $document->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return (int) $document->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can verify a document.
     * Admin/Operations staff only.
     */
    public function verify(User $user, Document $document): bool
    {
        return $user->isStaff();
    }
}
