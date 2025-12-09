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
}
