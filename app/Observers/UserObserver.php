<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AvatarStorageService;

class UserObserver
{
    public function __construct(
        private readonly AvatarStorageService $avatarStorage
    ) {
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): void
    {
        // Clean up avatar when user is permanently deleted
        if ($user->isForceDeleting() && $user->avatar) {
            $this->avatarStorage->delete($user->avatar);
        }
    }
}
