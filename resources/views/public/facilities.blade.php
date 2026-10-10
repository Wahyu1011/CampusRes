@extends('layouts.app')

@section('title', 'Ketersediaan Fasilitas | CampusRes')

@push('styles')
    <link rel="stylesheet" href="{{ asset('dashboard.css') }}">
    <style>
        .public-page { width: min(1100px, calc(100% - 2rem)); margin: 0 auto; padding: 1.5rem 0 3rem; }
        .public-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem; }
        .public-header h1 { font-size: 1.5rem; }
        .public-header a { color: var(--text-main); text-decoration: none; }
        .public-header .public-login { color: #a5b4fc; border: 1px solid var(--border-color); border-radius: 6px; padding: 0.6rem 1rem; }
        .public-intro { margin-bottom: 1.5rem; }
        .public-intro h2 { font-size: 1.8rem; margin-bottom: 0.4rem; }
        .public-intro p { color: var(--text-muted); }
        .public-filters { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; align-items: end; margin-bottom: 1.5rem; }
        .public-date-filter label { display: grid; gap: 0.4rem; color: var(--text-muted); }
        .public-filters input { width: 100%; min-width: 0; padding: 0.65rem; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-main); }
        .public-filters button { width: auto; padding: 0.65rem 1rem; }
        .public-facility { margin-bottom: 1rem; }
        .public-facility-heading { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .public-facility-heading h3 { font-size: 1.05rem; }
        .public-slot-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(125px, 1fr)); gap: 0.5rem; }
        .public-slot { display: grid; gap: 0.3rem; padding: 0.65rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.8rem; }
        .public-slot-available span { color: #6ee7b7; }
        .public-slot-unavailable span { color: #fca5a5; }
        .public-empty { color: var(--text-muted); padding: 1.5rem; text-align: center; }
        @media (max-width: 600px) {
            .public-header { margin-bottom: 2rem; }
            .public-intro h2 { font-size: 1.4rem; }
            .public-filters { grid-template-columns: 1fr 1fr; }
            .public-filters button { width: 100%; }
        }
    </style>
@endpush

@section('content')
    <main class="public-page">
        <header class="public-header">
            <a href="{{ route('public.facilities') }}"><strong>CampusRes</strong></a>
            <a class="public-login" href="{{ route('login') }}">Masuk</a>
        </header>

        <section class="public-intro">
            <h2>Ketersediaan Fasilitas</h2>
            <p>Pilih tanggal untuk melihat slot yang tersedia.</p>
        </section>

        <form class="public-filters" method="GET" action="{{ route('public.facilities') }}">
            <label for="availability-date">
                Tanggal
                <input id="availability-date" type="date" name="date" value="{{ $date }}" min="{{ now()->toDateString() }}" required>
            </label>
            <label for="facility-type">
                Tipe fasilitas
                <input id="facility-type" type="search" name="type" value="{{ $filters['type'] ?? '' }}" placeholder="Contoh: Ruang">
            </label>
            <label for="facility-location">
                Lokasi
                <input id="facility-location" type="search" name="location" value="{{ $filters['location'] ?? '' }}" placeholder="Contoh: Gedung A">
            </label>
            <label for="minimum-capacity">
                Kapasitas minimal
                <input id="minimum-capacity" type="number" name="minimum_capacity" min="1" value="{{ $filters['minimum_capacity'] ?? '' }}" placeholder="Jumlah orang">
            </label>
            <button class="btn-reserve" type="submit">Lihat Jadwal</button>
        </form>

        @forelse($availability as $facility)
            <section class="glass-panel public-facility" aria-label="Jadwal {{ $facility['name'] }}">
                <div class="public-facility-heading">
                    <h3>{{ $facility['name'] }}</h3>
                    @if($facility['status'] !== 'active')
                        <span class="status status-danger">{{ $facility['status'] === 'maintenance' ? 'Dalam Perbaikan' : 'Nonaktif' }}</span>
                    @endif
                </div>
                <div class="public-slot-grid">
                    @foreach($facility['slots'] as $slot)
                        <div class="public-slot {{ $slot['available'] ? 'public-slot-available' : 'public-slot-unavailable' }}">
                            <strong>{{ $slot['label'] }}</strong>
                            <span>{{ $slot['available'] ? 'Tersedia' : 'Tidak tersedia' }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="glass-panel public-empty">Belum ada fasilitas.</p>
        @endforelse
    </main>
@endsection