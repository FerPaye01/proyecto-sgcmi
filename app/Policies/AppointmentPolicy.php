<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('APPOINTMENT_READ');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasPermission('APPOINTMENT_READ')) {
            // TRANSPORTISTA solo ve sus propias citas
            if ($user->roles()->where('code', 'TRANSPORTISTA')->exists()) {
                return $user->companies()->pluck('id')->contains($appointment->company_id);
            }
            return true;
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('APPOINTMENT_WRITE');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission('APPOINTMENT_WRITE');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission('APPOINTMENT_WRITE');
    }
}
