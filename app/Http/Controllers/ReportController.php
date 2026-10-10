<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $reports = Report::query()
            ->with($user->role === 'user' ? ['facility'] : ['user', 'facility'])
            ->when($user->role === 'petugas', fn ($query) => $query->whereIn('status', ['new', 'processing']))
            ->when($user->role === 'user', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->get();

        return view('reports', compact('reports'));
    }

    public function create(Request $request)
    {
        if (Auth::user()->role !== 'user') {
            abort(403);
        }

        $facilities = Facility::all();
        $selectedFacility = $request->query('facility_id');

        return view('reports_create', compact('facilities', 'selectedFacility'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'user') {
            abort(403);
        }

        $validated = $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'category' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['status'] = 'new';

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('reports', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        unset($validated['image']);

        Report::create($validated);

        return redirect()->route('reports.index')->with('success', 'Laporan kerusakan berhasil dikirim.');
    }

    public function updateStatus(Request $request, Report $report)
    {
        if (! in_array(Auth::user()->role, ['admin', 'petugas'], true)) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:processing,completed,rejected',
            'resolution_note' => 'required_if:status,completed,rejected|nullable|string|max:2000',
            'facility_status' => 'nullable|in:active,maintenance',
        ]);

        DB::transaction(function () use ($report, $validated): void {
            $report->update([
                'status' => $validated['status'],
                'resolution_note' => $validated['resolution_note'] ?? null,
            ]);

            if (! empty($validated['facility_status'])) {
                $report->facility()->update(['status' => $validated['facility_status']]);
            }
        });

        return back()->with('success', 'Status laporan berhasil diperbarui.');
    }
}
