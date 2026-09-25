@extends('layouts.dashboard')

@section('title', 'Dashboard | Reservasi Fasilitas Kampus')

@section('dashboard_content')
    <div class="welcome-section">
        <h1>Selamat Datang, {{ Auth::user()->name }}! 👋</h1>
        <p>{{ Auth::user()->role === 'admin' ? 'Kelola reservasi dan fasilitas kampus dari sini.' : 'Temukan dan reservasi fasilitas kampus dengan mudah.' }}</p>
    </div>

    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-icon icon-primary"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h3>{{ $activeCount }}</h3>
                <p>Reservasi Disetujui</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-success"><i class="fas fa-building"></i></div>
            <div class="stat-info">
                <h3>{{ $facilityCount }}</h3>
                <p>Fasilitas Tersedia</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-warning"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <h3>{{ $pendingCount }}</h3>
                <p>Menunggu Persetujuan</p>
            </div>
        </div>
    </div>

    <div class="content-grid">
        <div class="facilities-section glass-panel">
            <div class="section-header">
                <h2>Sorotan Fasilitas</h2>
                <a href="{{ route('facilities.index') }}" class="view-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="facility-cards" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                @foreach($facilities->take(2) as $facility)
                <div class="facility-card">
                    <div class="facility-img" style="background-image: url('{{ $facility->image_url }}')"></div>
                    <div class="facility-info">
                        <h4>{{ $facility->name }}</h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.3rem;"><i class="fas fa-map-marker-alt"></i> {{ $facility->location }}</p>
                        <p style="font-size: 0.75rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $facility->description }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="recent-reservations glass-panel">
            <div class="section-header">
                <h2>Aktivitas Terkini</h2>
                <a href="{{ route('reservations.index') }}" class="view-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
            </div>
            <ul class="reservation-list">
                @forelse($reservations->take(4) as $res)
                <li class="reservation-item" style="padding: 0.8rem;">
                    <div class="res-details">
                        <h4 style="font-size: 0.9rem; margin-bottom: 0.2rem;">{{ Str::limit($res->facility->name, 20) }}</h4>
                        <p style="font-size: 0.75rem;"><i class="fas fa-clock"></i> {{ \Carbon\Carbon::parse($res->start_time)->format('d M') }} | @if(Auth::user()->role === 'admin') <i class="fas fa-user" style="margin-left: 4px;"></i> {{ explode(' ', $res->user->name)[0] }} @endif</p>
                    </div>
                    @php
                        $statusClass = match($res->status) {
                            'approved' => 'status-approved',
                            'pending' => 'status-pending',
                            'rejected', 'cancelled' => 'status-danger',
                            default => ''
                        };
                    @endphp
                    <span class="status {{ $statusClass }}" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                        {{ ucfirst($res->status) }}
                    </span>
                </li>
                @empty
                <li class="reservation-item" style="justify-content: center; padding: 1.5rem;">
                    <p style="color: var(--text-muted); text-align: center; width: 100%; font-size:0.85rem;">Belum ada aktivitas baru.</p>
                </li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
