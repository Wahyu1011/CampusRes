<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:pending,approved,rejected,cancelled,all'],
        ]);

        $user = Auth::user();
        $isOperator = in_array($user->role, ['admin', 'petugas'], true);
        $status = $filters['status'] ?? ($user->role === 'petugas' ? 'pending' : 'all');
        $reservations = Reservation::query()
            ->with($isOperator ? ['facility', 'user'] : ['facility'])
            ->when(! $isOperator, fn ($query) => $query->where('user_id', $user->id))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('start_time', $date))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('reservations', compact('reservations', 'status'));
    }

    public function create(Facility $facility)
    {
        if (Auth::user()->role !== 'user') {
            abort(403);
        }

        abort_unless($facility->status === 'active', 404);

        $reservations = Reservation::where('facility_id', $facility->id)
            ->where('end_time', '>=', now())
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('start_time')
            ->get();

        return view('reservations_create', compact('facility', 'reservations'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'user') {
            abort(403);
        }

        $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'start_time' => [
                'required', 'date', 'after:now',
                function ($attribute, $value, $fail) {
                    $time = Carbon::parse($value);
                    if (($time->minute !== 0 && $time->minute !== 30) || $time->format('s.u') !== '00.000000') {
                        $fail('Waktu Mulai harus tepat pada slot 30 menit.');
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
                    $time = Carbon::parse($value);
                    if (($time->minute !== 0 && $time->minute !== 30) || $time->format('s.u') !== '00.000000') {
                        $fail('Waktu Selesai harus tepat pada slot 30 menit.');
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

        if (Carbon::parse($request->input('start_time'))->toDateString() !== Carbon::parse($request->input('end_time'))->toDateString()) {
            return back()->withErrors(['end_time' => 'Reservasi harus dimulai dan berakhir pada tanggal yang sama.'])->withInput();
        }

        if (! Facility::whereKey($request->input('facility_id'))->where('status', 'active')->exists()) {
            return back()->withErrors(['facility_id' => 'Fasilitas sedang tidak tersedia untuk dipesan.'])->withInput();
        }

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
        if (! in_array(Auth::user()->role, ['admin', 'petugas'], true)) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        if ($request->status === 'approved') {
            if (! $reservation->facility()->where('status', 'active')->exists()) {
                return back()->withErrors(['Fasilitas sedang tidak tersedia untuk disetujui.']);
            }

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
        if (Auth::id() !== $reservation->user_id) {
            abort(403);
        }
        if (! in_array($reservation->status, ['pending', 'approved'], true)) {
            return back()->withErrors(['Reservasi ini sudah tidak dapat dibatalkan.']);
        }
        if (! Carbon::parse($reservation->start_time)->isFuture()) {
            return back()->withErrors(['Tidak bisa membatalkan reservasi yang sudah lewat.']);
        }
        $reservation->update(['status' => 'cancelled']);

        return back()->with('success', 'Reservasi berhasil dibatalkan.');
    }

    public function adminCancel(Request $request, Reservation $reservation)
    {
        if (! in_array(Auth::user()->role, ['admin', 'petugas'], true)) {
            abort(403);
        }

        if ($reservation->status !== 'approved' || ! Carbon::parse($reservation->start_time)->isFuture()) {
            return back()->withErrors(['Hanya reservasi yang disetujui dan belum dimulai yang dapat dibatalkan petugas.']);
        }

        $request->validate([
            'cancellation_reason' => 'required|string',
        ]);

        $reservation->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        return back()->with('success', 'Reservasi berhasil dibatalkan oleh admin.');
    }

    public function exportCsv()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $reservations = Reservation::with(['user', 'facility'])->get();

        $filename = 'laporan_reservasi_'.date('Y-m-d_H-i-s').'.csv';
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

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
                $res->cancellation_reason ?? '-',
            ]);
        }

        fclose($handle);
        exit;
    }
}
