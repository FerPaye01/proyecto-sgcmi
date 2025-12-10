<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tramite;
use App\Models\User;

class TramitePolicy
{
    /**
     * Determine whether the user can view any tramites.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ADUANA_READ') 
            || $user->hasPermission('CUS_REPORT_READ');
    }

    /**
     * Determine whether the user can view the tramite.
     */
    public function view(User $user, Tramite $tramite): bool
    {
        return $user->hasPermission('ADUANA_READ') 
            || $user->hasPermission('CUS_REPORT_READ');
    }

    /**
     * Determine whether the user can create tramites.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('ADUANA_WRITE');
    }

    /**
     * Determine whether the user can update the tramite.
     */
    public function update(User $user, Tramite $tramite): bool
    {
        return $user->hasPermission('ADUANA_WRITE');
    }

    /**
     * Determine whether the user can delete the tramite.
     */
    public function delete(User $user, Tramite $tramite): bool
    {
        return $user->hasPermission('ADUANA_WRITE');
    }
}
