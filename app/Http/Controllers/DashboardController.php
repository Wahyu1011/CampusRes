<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Report;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $facilities = Facility::where('status', 'active')->get();

        if (in_array($user->role, ['admin', 'petugas'], true)) {
            $reservationsQuery = Reservation::with(['user', 'facility']);
            if ($user->role === 'petugas') {
                $reservationsQuery->where('status', 'pending');
            }
            $reservations = $reservationsQuery->latest()->get();
            $activeCount = Reservation::where('status', 'approved')->count();
            $pendingCount = Reservation::where('status', 'pending')->count();
            $pendingReportCount = Report::whereIn('status', ['new', 'processing'])->count();
        } else {
            $reservations = Reservation::with('facility')->where('user_id', $user->id)->latest()->get();
            $activeCount = Reservation::where('user_id', $user->id)->where('status', 'approved')->count();
            $pendingCount = Reservation::where('user_id', $user->id)->where('status', 'pending')->count();
            $pendingReportCount = Report::where('user_id', $user->id)
                ->whereIn('status', ['new', 'processing'])
                ->count();
        }

        $facilityCount = $facilities->count();

        return view('dashboard', compact('facilities', 'reservations', 'activeCount', 'pendingCount', 'pendingReportCount', 'facilityCount'));
    }
}
