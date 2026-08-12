<?php

namespace App\Policies;

use App\Models\Trackable;
use App\Models\User;

class TrackablePolicy
{
    public function view(User $user, Trackable $trackable): bool
    {
        return $user->id === $trackable->user_id;
    }

    public function update(User $user, Trackable $trackable): bool
    {
        return $user->id === $trackable->user_id;
    }

    public function delete(User $user, Trackable $trackable): bool
    {
        return $user->id === $trackable->user_id;
    }
}
