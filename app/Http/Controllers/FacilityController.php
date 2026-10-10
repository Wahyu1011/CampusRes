<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FacilityController extends Controller
{
    public function publicIndex(Request $request): View
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date', 'after_or_equal:today'],
            'type' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'minimum_capacity' => ['nullable', 'integer', 'min:1'],
        ]);
        $date = $filters['date'] ?? now()->toDateString();

        $facilities = Facility::query()
            ->select(['id', 'name', 'status'])
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', 'like', '%'.$type.'%'))
            ->when($filters['location'] ?? null, fn ($query, $location) => $query->where('location', 'like', '%'.$location.'%'))
            ->when($filters['minimum_capacity'] ?? null, fn ($query, $capacity) => $query->where('capacity', '>=', $capacity))
            ->orderBy('name')
            ->get();

        $dayStart = CarbonImmutable::parse($date)->setTime(7, 0);
        $dayEnd = $dayStart->setTime(20, 0);
        $reservationsByFacility = Reservation::query()
            ->where('start_time', '<', $dayEnd)
            ->where('end_time', '>', $dayStart)
            ->whereIn('status', ['pending', 'approved'])
            ->get(['facility_id', 'start_time', 'end_time'])
            ->groupBy('facility_id');

        $slots = collect(range(0, 25))->map(fn (int $index): array => [
            'start' => $dayStart->addMinutes($index * 30),
            'end' => $dayStart->addMinutes(($index + 1) * 30),
        ]);

        $availability = $facilities->map(function (Facility $facility) use ($reservationsByFacility, $slots): array {
            $reservations = $reservationsByFacility->get($facility->id, collect());

            return [
                'name' => $facility->name,
                'status' => $facility->status,
                'slots' => $slots->map(function (array $slot) use ($facility, $reservations): array {
                    $isBooked = $reservations->contains(fn (Reservation $reservation): bool => $slot['start']->lt($reservation->end_time)
                        && $slot['end']->gt($reservation->start_time)
                    );

                    return [
                        'label' => $slot['start']->format('H:i').'–'.$slot['end']->format('H:i'),
                        'available' => $facility->status === 'active' && ! $isBooked,
                    ];
                }),
            ];
        });

        return view('public.facilities', compact('availability', 'date', 'filters'));
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'minimum_capacity' => ['nullable', 'integer', 'min:1'],
        ]);

        $facilities = Facility::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('location', 'like', '%'.$search.'%')
                        ->orWhere('type', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', 'like', '%'.$type.'%'))
            ->when($filters['location'] ?? null, fn ($query, $location) => $query->where('location', 'like', '%'.$location.'%'))
            ->when($filters['minimum_capacity'] ?? null, fn ($query, $capacity) => $query->where('capacity', '>=', $capacity))
            ->get();

        return view('facilities', compact('facilities'));
    }

    public function create()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        return view('facilities_form');
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:active,maintenance,inactive',
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $path = $request->file('image')->store('facilities', 'public');
        $validated['image_url'] = Storage::url($path);
        unset($validated['image']);

        Facility::create($validated);

        return redirect()->route('facilities.index')->with('success', 'Fasilitas berhasil ditambahkan!');
    }

    public function edit(Facility $facility)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        return view('facilities_form', compact('facility'));
    }

    public function update(Request $request, Facility $facility)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:active,maintenance,inactive',
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('facilities', 'public');
            $validated['image_url'] = Storage::url($path);

            // Delete old image if it's from local storage
            if (str_starts_with($facility->image_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $facility->image_url);
                Storage::disk('public')->delete($oldPath);
            }
        }
        unset($validated['image']);

        $facility->update($validated);

        return redirect()->route('facilities.index')->with('success', 'Fasilitas berhasil diperbarui!');
    }

    public function destroy(Facility $facility)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        if ($facility->reservations()->exists() || $facility->reports()->exists()) {
            return back()->withErrors(['Fasilitas yang memiliki riwayat reservasi atau laporan tidak dapat dihapus. Nonaktifkan fasilitas sebagai gantinya.']);
        }

        // Delete image if it's from local storage
        if (str_starts_with($facility->image_url, '/storage/')) {
            $oldPath = str_replace('/storage/', '', $facility->image_url);
            Storage::disk('public')->delete($oldPath);
        }

        $facility->delete();

        return redirect()->route('facilities.index')->with('success', 'Fasilitas berhasil dihapus!');
    }
}
