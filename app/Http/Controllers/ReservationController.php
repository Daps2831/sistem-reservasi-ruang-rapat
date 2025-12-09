<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;

class ReservationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of reservations for the authenticated user.
     * Ordered by latest first.
     */
    public function index()
    {
        $reservations = Reservation::with(['room', 'user'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('reservations.index', compact('reservations'));
    }

    /**
     * Store a newly created reservation in storage.
     * Validates working hours (08:00 - 17:00) and checks for schedule conflicts.
     * Uses Universal Overlap formula and lockForUpdate to prevent race conditions.
     */
    public function store(Request $request)
    {
        // Validasi Input Dasar
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'notes' => 'nullable|string|max:500',
        ], [
            'start_time.after' => 'Waktu mulai tidak boleh waktu lampau.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
        ]);

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = Carbon::parse($validated['end_time']);

        // Validasi Jam Kerja (08:00 - 17:00)
        $workStart = Carbon::parse($startTime->format('Y-m-d') . ' 08:00:00');
        $workEnd = Carbon::parse($startTime->format('Y-m-d') . ' 17:00:00');

        if ($startTime->lt($workStart) || $startTime->gte($workEnd)) {
            return back()->withErrors([
                'start_time' => 'Waktu mulai harus antara jam 08:00 - 17:00.'
            ])->withInput();
        }

        if ($endTime->gt($workEnd) || $endTime->lte($workStart)) {
            return back()->withErrors([
                'end_time' => 'Waktu selesai harus antara jam 08:00 - 17:00.'
            ])->withInput();
        }

        // Gunakan Database Transaction dan Lock untuk mencegah Race Condition
        return \DB::transaction(function () use ($validated, $startTime, $endTime) {
            // Validasi Tidak Tumpang Tindih menggunakan Universal Overlap Formula
            // (StartA < EndB) && (EndA > StartB)
            // Dengan strict inequality untuk menghindari false positive
            $conflict = Reservation::where('room_id', $validated['room_id'])
                ->where('start_time', '<', $endTime)      // StartA < EndB
                ->where('end_time', '>', $startTime)       // EndA > StartB
                ->lockForUpdate()                          // Lock row untuk mencegah race condition
                ->exists();

            if ($conflict) {
                return back()->withErrors([
                    'start_time' => 'Ruangan sudah dibooking pada waktu tersebut. Silakan pilih waktu lain.'
                ])->withInput();
            }

            // Simpan jika aman
            Reservation::create([
                'user_id' => Auth::id(),
                'room_id' => $validated['room_id'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'notes' => $validated['notes'] ?? null,
            ]);

            return redirect()->route('reservations.index')
                ->with('success', 'Reservasi berhasil dibuat!');
        });
    }

    /**
     * Remove the specified reservation from storage.
     * Uses Policy/Gate Authorization for validation.
     */
    public function destroy(Reservation $reservation)
    {
        // Validasi Kepemilikan User menggunakan Policy
        $this->authorize('delete', $reservation);

        $reservation->delete();

        return redirect()->route('reservations.index')
            ->with('success', 'Reservasi berhasil dibatalkan!');
    }
}
