<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    /**
     * Determine whether the user can delete the reservation.
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id;
    }
}
