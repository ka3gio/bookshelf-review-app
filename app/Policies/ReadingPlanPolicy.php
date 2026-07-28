<?php

namespace App\Policies;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReadingPlan $readingPlan): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id
            && $readingPlan->status !== ReadingPlanStatus::Completed;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReadingPlan $readingPlan): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReadingPlan $readingPlan): bool
    {
        //
    }

    public function inprogress(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id
            && $readingPlan->status === ReadingPlanStatus::NotStarted;
    }

    public function complete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }
}
