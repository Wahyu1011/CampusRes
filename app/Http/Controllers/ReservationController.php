<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Facility;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    public function create(Facility $facility)
    {
        $reservations = Reservation::where('facility_id', $facility->id)
            ->where('end_time', '>=', now())
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('start_time')
            ->get();

        return view('reservations_create', compact('facility', 'reservations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'start_time' => [
                'required', 'date', 'after:now',
                function ($attribute, $value, $fail) {
                    $time = \Carbon\Carbon::parse($value);
                    if ($time->minute !== 0 && $time->minute !== 30) {
                        $fail('Waktu Mulai hanya boleh tepat pada menit ke-00 atau 30.');
                    }
                    $hour = $time->format('H:i');
                    if ($hour < '07:00' || $hour >= '20:00') {
                        $fail('Jam operasional peminjaman adalah dari pukul 07:00 hingga 20:00.');
                    }
                },
            ],
            'end_time' => [
                'required', 'date', 'after:start_time',
                function ($attribute, $value, $fail) {
                    $time = \Carbon\Carbon::parse($value);
                    if ($time->minute !== 0 && $time->minute !== 30) {
                        $fail('Waktu Selesai hanya boleh tepat pada menit ke-00 atau 30.');
                    }
                    $hour = $time->format('H:i');
                    if ($hour <= '07:00' || $hour > '20:00') {
                        $fail('Batas maksimal waktu selesai peminjaman adalah pukul 20:00.');
                    }
                },
            ],
            'nim' => 'required|string',
            'department' => 'required|string',
            'organization' => 'nullable|string',
            'phone' => 'required|string',
            'activity_name' => 'required|string',
            'participant_count' => 'required|integer|min:1',
            'commitment_agreed' => 'required|accepted',
        ]);

        $overlap = Reservation::where('facility_id', $request->facility_id)
            ->whereIn('status', ['approved', 'pending'])
            ->where('start_time', '<', $request->end_time)
            ->where('end_time', '>', $request->start_time)
            ->exists();

        if ($overlap) {
            return back()->withErrors(['Jadwal bentrok! Fasilitas ini sudah direservasi dan disetujui untuk kegiatan lain pada waktu tersebut. Silakan pilih waktu atau fasilitas lain.'])->withInput();
        }

        Reservation::create([
            'user_id' => Auth::id(),
            'facility_id' => $request->facility_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'status' => 'pending',
            'nim' => $request->nim,
            'department' => $request->department,
            'organization' => $request->organization,
            'phone' => $request->phone,
            'activity_name' => $request->activity_name,
            'participant_count' => $request->participant_count,
            'commitment_agreed' => $request->has('commitment_agreed'),
        ]);

        return redirect()->route('dashboard')->with('success', 'Reservasi berhasil diajukan dan menunggu persetujuan.');
    }

    public function updateStatus(Request $request, Reservation $reservation)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        if ($request->status === 'approved') {
            $overlap = Reservation::where('facility_id', $reservation->facility_id)
                ->where('id', '!=', $reservation->id)
                ->where('status', 'approved')
                ->where('start_time', '<', $reservation->end_time)
                ->where('end_time', '>', $reservation->start_time)
                ->exists();

            if ($overlap) {
                return back()->withErrors(['Jadwal bentrok! Fasilitas ini sudah disetujui untuk kegiatan lain pada rentang waktu tersebut.']);
            }
        }

        $reservation->update(['status' => $request->status]);

        return back()->with('success', 'Status reservasi berhasil diperbarui.');
    }

    public function cancel(Reservation $reservation)
    {
        if (Auth::id() !== $reservation->user_id) abort(403);
        if (\Carbon\Carbon::parse($reservation->start_time)->isPast()) {
            return back()->withErrors(['Tidak bisa membatalkan reservasi yang sudah lewat.']);
        }
        $reservation->update(['status' => 'cancelled']);
        return back()->with('success', 'Reservasi berhasil dibatalkan.');
    }

    public function adminCancel(Request $request, Reservation $reservation)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        
        $request->validate([
            'cancellation_reason' => 'required|string',
        ]);

        $reservation->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason
        ]);

        return back()->with('success', 'Reservasi berhasil dibatalkan oleh admin.');
    }
    public function exportCsv()
    {
        if (Auth::user()->role !== 'admin') abort(403);
        
        $reservations = Reservation::with(['user', 'facility'])->get();
        
        $filename = "laporan_reservasi_" . date('Y-m-d_H-i-s') . ".csv";
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        fputcsv($handle, ['ID', 'Nama Pemesan', 'Fasilitas', 'Tipe Fasilitas', 'Waktu Mulai', 'Waktu Selesai', 'Status', 'Alasan Batal']);
        
        foreach ($reservations as $res) {
            fputcsv($handle, [
                $res->id,
                $res->user->name ?? 'Unknown',
                $res->facility->name ?? 'Unknown',
                $res->facility->type ?? '-',
                $res->start_time,
                $res->end_time,
                $res->status,
                $res->cancellation_reason ?? '-'
            ]);
        }
        
        fclose($handle);
        exit;
    }
}
