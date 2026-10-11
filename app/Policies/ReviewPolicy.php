<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewPolicy
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
     * Determine whether the user can view any reviews.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the review.
     */
    public function view(User $user, Review $review): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create a review.
     * Customers and staff can create reviews.
     */
    public function create(User $user): bool
    {
        return $user->isCustomer() || $user->isStaff();
    }

    /**
     * Determine whether the user can update the review.
     * - Admin/Operations: moderation
     * - Customer: their own review
     */
    public function update(User $user, Review $review): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return (int) $review->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can delete the review.
     * - Admin: moderation
     * - Customer: their own review
     */
    public function delete(User $user, Review $review): bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return (int) $review->user_id === (int) $user->id;
    }
}
