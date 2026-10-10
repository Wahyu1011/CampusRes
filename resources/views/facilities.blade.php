@extends('layouts.dashboard')

@section('title', 'Kelola Fasilitas | Reservasi Fasilitas Kampus')

@section('dashboard_content')
    <div class="facilities-section glass-panel">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Daftar Fasilitas</h2>
            @if(Auth::user()->role === 'admin')
            <a href="{{ route('facilities.create') }}" class="btn-reserve" style="width: auto; padding: 0.5rem 1rem; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                <i class="fas fa-plus"></i> Tambah Fasilitas
            </a>
            @endif
        </div>
        
        @if(session('success'))
            <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" action="{{ route('facilities.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 0.75rem; align-items: end; margin-bottom: 1.5rem;">
            <label style="display: grid; gap: 0.4rem; color: var(--text-muted);">
                Tipe fasilitas
                <input type="search" name="type" value="{{ request('type') }}" placeholder="Contoh: Ruang" style="padding: 0.65rem; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-main);">
            </label>
            <label style="display: grid; gap: 0.4rem; color: var(--text-muted);">
                Lokasi
                <input type="search" name="location" value="{{ request('location') }}" placeholder="Contoh: Gedung A" style="padding: 0.65rem; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-main);">
            </label>
            <label style="display: grid; gap: 0.4rem; color: var(--text-muted);">
                Kapasitas minimal
                <input type="number" name="minimum_capacity" min="1" value="{{ request('minimum_capacity') }}" placeholder="Jumlah orang" style="padding: 0.65rem; border: 1px solid var(--border-color); border-radius: 6px; background: var(--input-bg); color: var(--text-main);">
            </label>
            <button class="btn-reserve" type="submit" style="width: auto;">Cari Fasilitas</button>
        </form>

        <div class="facility-cards">
            @forelse($facilities as $facility)
            <div class="facility-card">
                <div class="facility-img" style="background-image: url('{{ asset($facility->image_url) }}')"></div>
                <div class="facility-info">
                    <h4 style="display: flex; align-items: center; justify-content: space-between;">
                        {{ $facility->name }}
                        @if($facility->status !== 'active')
                            <span class="status status-danger" style="font-size: 0.6rem; padding: 0.2rem 0.5rem; border-radius: 4px;">{{ $facility->status === 'maintenance' ? 'Perbaikan' : 'Nonaktif' }}</span>
                        @endif
                    </h4>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.2rem;"><i class="fas fa-map-marker-alt"></i> {{ $facility->location }}</p>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;"><i class="fas fa-tag"></i> {{ $facility->type ?? 'Umum' }} &nbsp;&bull;&nbsp; <i class="fas fa-users"></i> {{ $facility->capacity ?? '-' }} Orang</p>
                    <p style="font-size: 0.8rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 1rem;">{{ $facility->description }}</p>
                    @if(Auth::user()->role === 'user')
                    <div style="margin-top: auto;">
                        @if($facility->status !== 'active')
                            <button class="btn-reserve" style="display: block; width: 100%; text-align: center; background: var(--border-color); color: var(--text-muted); cursor: not-allowed; border: none;" disabled>{{ $facility->status === 'maintenance' ? 'Sedang Diperbaiki' : 'Tidak Aktif' }}</button>
                        @else
                            <a href="{{ route('reservations.create', $facility) }}" class="btn-reserve" style="display: block; text-align: center; text-decoration: none;">Cek Jadwal & Reservasi</a>
                        @endif
                    </div>
                    @elseif(Auth::user()->role === 'admin')
                    <div style="display: flex; gap: 0.5rem; margin-top: auto; padding-top: 1rem;">
                        <a href="{{ route('facilities.edit', $facility) }}" class="btn-action btn-primary" style="flex: 1; padding: 0.5rem; font-size: 0.85rem;"><i class="fas fa-edit"></i> Edit</a>
                        <form method="POST" action="{{ route('facilities.destroy', $facility) }}" style="flex: 1; display: flex;" onsubmit="return confirm('Yakin ingin menghapus fasilitas ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-action btn-danger" style="flex: 1; padding: 0.5rem; font-size: 0.85rem;"><i class="fas fa-trash"></i> Hapus</button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <p style="color: var(--text-muted); text-align: center; width: 100%;">Tidak ada fasilitas yang cocok.</p>
            @endforelse
        </div>
    </div>
@endsection
