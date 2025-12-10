<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GateEvent;
use App\Models\User;

class GateEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('GATE_EVENT_READ');
    }

    public function view(User $user, GateEvent $gateEvent): bool
    {
        return $user->hasPermission('GATE_EVENT_READ');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('GATE_EVENT_WRITE');
    }

    public function update(User $user, GateEvent $gateEvent): bool
    {
        return $user->hasPermission('GATE_EVENT_WRITE');
    }

    public function delete(User $user, GateEvent $gateEvent): bool
    {
        return $user->hasPermission('GATE_EVENT_WRITE');
    }
}
