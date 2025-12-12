<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AccessPermit;
use App\Models\User;

class AccessPermitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('GATE_EVENT_READ');
    }

    public function view(User $user, AccessPermit $accessPermit): bool
    {
        return $user->hasPermission('GATE_EVENT_READ');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('GATE_EVENT_WRITE');
    }

    public function update(User $user, AccessPermit $accessPermit): bool
    {
        return $user->hasPermission('GATE_EVENT_WRITE');
    }

    public function delete(User $user, AccessPermit $accessPermit): bool
    {
        return $user->hasPermission('GATE_EVENT_WRITE');
    }
}

