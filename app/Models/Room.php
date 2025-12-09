<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'description',
    ];

    /**
     * Get the reservations for the room.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
