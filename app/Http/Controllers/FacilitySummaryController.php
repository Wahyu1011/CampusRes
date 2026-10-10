<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Report;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacilitySummaryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin();
        [$startDate, $endDate] = $this->validatedPeriod($request);

        return view('reports.facility-summary', [
            'summary' => $this->summaryForPeriod($startDate, $endDate),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorizeAdmin();
        [$startDate, $endDate] = $this->validatedPeriod($request);
        $summary = $this->summaryForPeriod($startDate, $endDate);

        return response()->streamDownload(function () use ($summary): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Fasilitas', 'Lokasi', 'Reservasi Disetujui', 'Jam Terpakai', 'Laporan Kerusakan']);

            foreach ($summary as $facility) {
                fputcsv($stream, [
                    $this->safeSpreadsheetValue($facility['name']),
                    $this->safeSpreadsheetValue($facility['location']),
                    $facility['reservation_count'],
                    $facility['reserved_hours'],
                    $facility['damage_report_count'],
                ]);
            }

            fclose($stream);
        }, "rekap_fasilitas_{$startDate}_{$endDate}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
    }

    /** @return array{0: string, 1: string} */
    private function validatedPeriod(Request $request): array
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $endDate = $filters['end_date'] ?? now()->toDateString();
        $startDate = $filters['start_date'] ?? CarbonImmutable::parse($endDate)->startOfMonth()->toDateString();

        if (! isset($filters['end_date']) && $startDate > $endDate) {
            $endDate = $startDate;
        }

        return [$startDate, $endDate];
    }

    private function summaryForPeriod(string $startDate, string $endDate): Collection
    {
        $facilities = Facility::query()->orderBy('location')->orderBy('name')->get(['id', 'name', 'location']);
        $reservationsByFacility = Reservation::query()
            ->where('status', 'approved')
            ->whereDate('start_time', '>=', $startDate)
            ->whereDate('start_time', '<=', $endDate)
            ->get(['facility_id', 'start_time', 'end_time'])
            ->groupBy('facility_id');
        $damageReportCounts = Report::query()
            ->selectRaw('facility_id, COUNT(*) as aggregate')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->groupBy('facility_id')
            ->pluck('aggregate', 'facility_id');

        return $facilities->map(function (Facility $facility) use ($reservationsByFacility, $damageReportCounts): array {
            $reservations = $reservationsByFacility->get($facility->id, collect());
            $reservedHours = $reservations->sum(fn (Reservation $reservation): float => CarbonImmutable::parse($reservation->start_time)->diffInMinutes($reservation->end_time) / 60
            );

            return [
                'facility_id' => $facility->id,
                'name' => $facility->name,
                'location' => $facility->location,
                'reservation_count' => $reservations->count(),
                'reserved_hours' => round($reservedHours, 2),
                'damage_report_count' => (int) ($damageReportCounts[$facility->id] ?? 0),
            ];
        });
    }

    private function safeSpreadsheetValue(string $value): string
    {
        return preg_match('/^\s*[=+@\-]/u', $value) === 1 ? "'".$value : $value;
    }
}
