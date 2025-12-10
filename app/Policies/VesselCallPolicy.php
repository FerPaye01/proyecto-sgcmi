<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\VesselCall;

class VesselCallPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('SCHEDULE_READ') 
            || $user->hasPermission('PORT_REPORT_READ');
    }

    public function view(User $user, VesselCall $vesselCall): bool
    {
        return $user->hasPermission('SCHEDULE_READ') 
            || $user->hasPermission('PORT_REPORT_READ');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('SCHEDULE_WRITE');
    }

    public function update(User $user, VesselCall $vesselCall): bool
    {
        return $user->hasPermission('SCHEDULE_WRITE');
    }

    public function delete(User $user, VesselCall $vesselCall): bool
    {
        return $user->hasPermission('SCHEDULE_WRITE');
    }
}
