<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms for the dashboard.
     */
    public function index()
    {
        $rooms = Room::withCount('reservations')->get();
        
        return view('dashboard', compact('rooms'));
    }

    /**
     * Display the specified room with its reservations.
     */
    public function show(Room $room)
    {
        // Ambil semua reservasi untuk room ini yang akan datang, diurutkan dari yang terdekat
        $reservations = $room->reservations()
            ->with('user')
            ->where('end_time', '>=', now())
            ->orderBy('start_time', 'asc')
            ->get();

        return view('rooms.show', compact('room', 'reservations'));
    }
}
