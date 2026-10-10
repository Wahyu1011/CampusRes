@extends('layouts.dashboard')

@section('title', 'Rekap Fasilitas | CampusRes')

@section('dashboard_content')
    <div class="welcome-section mb-4">
        <h1>Rekap Fasilitas</h1>
        <p>Okupansi dan laporan kerusakan per fasilitas.</p>
    </div>

    <div class="glass-panel">
        <div class="section-header" style="flex-wrap: wrap; gap: 1rem;">
            <h2>Ringkasan {{ $startDate }} sampai {{ $endDate }}</h2>
            <a class="btn-reserve" style="width: auto; text-decoration: none;" href="{{ route('facility-reports.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}">
                <i class="fas fa-file-csv"></i> Ekspor CSV
            </a>
        </div>

        <form method="GET" action="{{ route('facility-reports.index') }}" style="display: flex; align-items: end; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
            <label style="display: grid; gap: 0.4rem; color: var(--text-muted);">
                Dari tanggal
                <input type="date" name="start_date" value="{{ $startDate }}" required style="padding: 0.65rem; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-main);">
            </label>
            <label style="display: grid; gap: 0.4rem; color: var(--text-muted);">
                Sampai tanggal
                <input type="date" name="end_date" value="{{ $endDate }}" required style="padding: 0.65rem; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-main);">
            </label>
            <button class="btn-reserve" type="submit" style="width: auto;">Terapkan</button>
        </form>

        <div class="table-responsive" style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 1rem; color: var(--text-muted);">Fasilitas</th>
                        <th style="padding: 1rem; color: var(--text-muted);">Lokasi</th>
                        <th style="padding: 1rem; color: var(--text-muted);">Reservasi disetujui</th>
                        <th style="padding: 1rem; color: var(--text-muted);">Jam terpakai</th>
                        <th style="padding: 1rem; color: var(--text-muted);">Laporan kerusakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $facility)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem;">{{ $facility['name'] }}</td>
                            <td style="padding: 1rem;">{{ $facility['location'] }}</td>
                            <td style="padding: 1rem;">{{ $facility['reservation_count'] }}</td>
                            <td style="padding: 1rem;">{{ number_format($facility['reserved_hours'], 2) }}</td>
                            <td style="padding: 1rem;">{{ $facility['damage_report_count'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">Belum ada fasilitas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection