<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OperationsMeeting;
use App\Models\User;

class OperationsMeetingPolicy
{
    /**
     * Determine whether the user can view any operations meetings.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('PORT_REPORT_READ') 
            || $user->hasPermission('ADMIN');
    }

    /**
     * Determine whether the user can view the operations meeting.
     */
    public function view(User $user, OperationsMeeting $operationsMeeting): bool
    {
        return $user->hasPermission('PORT_REPORT_READ') 
            || $user->hasPermission('ADMIN');
    }

    /**
     * Determine whether the user can create operations meetings.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('PORT_REPORT_WRITE') 
            || $user->hasPermission('ADMIN');
    }

    /**
     * Determine whether the user can update the operations meeting.
     */
    public function update(User $user, OperationsMeeting $operationsMeeting): bool
    {
        return $user->hasPermission('PORT_REPORT_WRITE') 
            || $user->hasPermission('ADMIN');
    }

    /**
     * Determine whether the user can delete the operations meeting.
     */
    public function delete(User $user, OperationsMeeting $operationsMeeting): bool
    {
        // PLANIFICADOR_PUERTO y ADMIN pueden borrar
        return $user->hasPermission('PORT_REPORT_WRITE') 
            || $user->hasPermission('ADMIN');
    }
}
