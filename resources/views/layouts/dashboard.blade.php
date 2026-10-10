@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('dashboard.css') }}?v={{ time() }}">
@endpush

@section('content')
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>CampusRes</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fas fa-home"></i> <span>Dashboard</span></a>
                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('reservations.index') }}" class="nav-item {{ request()->routeIs('reservations.index') ? 'active' : '' }}"><i class="fas fa-list-alt"></i> <span>Semua Reservasi</span></a>
                    <a href="{{ route('facilities.index') }}" class="nav-item {{ request()->routeIs('facilities.index') ? 'active' : '' }}"><i class="fas fa-building"></i> <span>Kelola Fasilitas</span></a>
                    <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}"><i class="fas fa-exclamation-triangle"></i> <span>Daftar Laporan</span></a>
                    <a href="{{ route('facility-reports.index') }}" class="nav-item {{ request()->routeIs('facility-reports.*') ? 'active' : '' }}"><i class="fas fa-chart-bar"></i> <span>Rekap Fasilitas</span></a>
                    <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a>
                @elseif(Auth::user()->role === 'petugas')
                    <a href="{{ route('reservations.index') }}" class="nav-item {{ request()->routeIs('reservations.index') ? 'active' : '' }}"><i class="fas fa-list-alt"></i> <span>Antrian Reservasi</span></a>
                    <a href="{{ route('facilities.index') }}" class="nav-item {{ request()->routeIs('facilities.index') ? 'active' : '' }}"><i class="fas fa-building"></i> <span>Status Fasilitas</span></a>
                    <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}"><i class="fas fa-exclamation-triangle"></i> <span>Daftar Laporan</span></a>
                @else
                    <a href="{{ route('reservations.index') }}" class="nav-item {{ request()->routeIs('reservations.index') ? 'active' : '' }}"><i class="fas fa-calendar-alt"></i> <span>Reservasi Saya</span></a>
                    <a href="{{ route('facilities.index') }}" class="nav-item {{ request()->routeIs('facilities.index') ? 'active' : '' }}"><i class="fas fa-building"></i> <span>Fasilitas</span></a>
                    <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}"><i class="fas fa-exclamation-triangle"></i> <span>Laporan Kerusakan</span></a>
                @endif
            </nav>
            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-item text-danger" style="background:none; border:none; width:100%; text-align:left; cursor:pointer;">
                        <i class="fas fa-sign-out-alt"></i> <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <form action="{{ route('facilities.index') }}" method="GET" class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari fasilitas..." value="{{ request('search') }}">
                </form>
                <div class="user-profile" style="display: flex; align-items: center; gap: 1rem;">
                    @if(!request()->routeIs('dashboard'))
                    <!-- Tombol Back Global -->
                    <a href="javascript:history.back()" title="Kembali ke halaman sebelumnya" style="color: var(--text-muted); text-decoration: none; display: flex; align-items: center; justify-content: center; width: 35px; height: 35px; border-radius: 50%; background: rgba(255,255,255,0.05); transition: background 0.3s; margin-right: 0.5rem;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    @endif
                    
                    <!-- Toggle Theme -->
                    <button id="themeToggleBtn" title="Ganti Tema" style="border: none; cursor: pointer; color: var(--text-muted); display: flex; align-items: center; justify-content: center; width: 35px; height: 35px; border-radius: 50%; background: rgba(255,255,255,0.05); transition: background 0.3s; margin-right: 0.5rem;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                        <i class="fas fa-sun" id="themeIcon"></i>
                    </button>
                    <div class="notification-bell">
                        <i class="fas fa-bell"></i>
                        @if($notifCount > 0)
                            <span class="badge">{{ $notifCount }}</span>
                        @endif
                    </div>
                    <div class="avatar">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=6366f1&color=fff" alt="User Avatar">
                    </div>
                    <span>{{ Auth::user()->name }} ({{ ucfirst(Auth::user()->role) }})</span>
                </div>
            </header>

            <div class="dashboard-content">
                @if(session('success'))
                    <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
                        {{ session('success') }}
                    </div>
                @endif
                @if($errors->any())
                    <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('dashboard_content')
            </div>
        </main>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('dashboard.js') }}?v={{ time() }}"></script>
@endpush
