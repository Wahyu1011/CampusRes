@extends('layouts.dashboard')

@section('title', 'Kelola Pengguna | CampusRes')

@section('dashboard_content')
<div class="welcome-section mb-4">
    <h1>Manajemen Pengguna</h1>
    <p>Kelola akses, buat akun pengguna atau petugas, dan verifikasi pendaftaran mandiri.</p>
</div>

<div class="glass-panel mb-4">
    <div class="section-header">
        <h2><i class="fas fa-user-plus"></i> Tambah Pengguna Baru</h2>
    </div>
    
    <form action="{{ route('users.store') }}" method="POST" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        @csrf
        <div style="flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: 0.5rem;">
            <label style="font-size: 0.9rem; color: var(--text-muted);">Nama Lengkap</label>
            <input type="text" name="name" required placeholder="Nama Lengkap" style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.8rem; border-radius: 8px; color: var(--text-main);">
        </div>
        <div style="flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: 0.5rem;">
            <label style="font-size: 0.9rem; color: var(--text-muted);">Email</label>
            <input type="email" name="email" required placeholder="Alamat Email" style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.8rem; border-radius: 8px; color: var(--text-main);">
        </div>
        <div style="flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: 0.5rem;">
            <label style="font-size: 0.9rem; color: var(--text-muted);">Password</label>
            <input type="password" name="password" required placeholder="Minimal 8 karakter" style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.8rem; border-radius: 8px; color: var(--text-main);">
        </div>
        <div style="flex: 1; min-width: 150px; display: flex; flex-direction: column; gap: 0.5rem;">
            <label style="font-size: 0.9rem; color: var(--text-muted);">Role</label>
            <select name="role" required style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.8rem; border-radius: 8px; color: var(--text-main);">
                <option value="user">Pengguna Biasa</option>
                <option value="petugas">Petugas</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn-reserve" style="width: auto; padding: 0.8rem 1.5rem;"><i class="fas fa-plus"></i> Tambah</button>
    </form>
</div>

<div class="glass-panel">
    <div class="section-header">
        <h2><i class="fas fa-users"></i> Daftar Semua Pengguna</h2>
    </div>
    
    <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 1rem; color: var(--text-muted);">Nama</th>
                    <th style="padding: 1rem; color: var(--text-muted);">Email</th>
                    <th style="padding: 1rem; color: var(--text-muted);">Role</th>
                    <th style="padding: 1rem; color: var(--text-muted);">Status Akun</th>
                    <th style="padding: 1rem; color: var(--text-muted);">Aksi Verifikasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 1rem;">
                        <strong>{{ $u->name }}</strong>
                        @if($u->id === Auth::id())
                            <span style="font-size: 0.7rem; background: var(--primary-color); color: white; padding: 0.1rem 0.4rem; border-radius: 4px; margin-left: 0.5rem;">Anda</span>
                        @endif
                    </td>
                    <td style="padding: 1rem; color: var(--text-muted);">{{ $u->email }}</td>
                    <td style="padding: 1rem;">
                        <span style="border: 1px solid var(--border-color); padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">
                            {{ ucfirst($u->role) }}
                        </span>
                    </td>
                    <td style="padding: 1rem;">
                        @if($u->account_status == 'approved')
                            <span style="color: #34d399;"><i class="fas fa-check-circle"></i> Disetujui</span>
                        @elseif($u->account_status == 'pending')
                            <span style="color: #fbbf24;"><i class="fas fa-clock"></i> Menunggu</span>
                        @else
                            <span style="color: #fca5a5;"><i class="fas fa-times-circle"></i> Ditolak</span>
                        @endif
                    </td>
                    <td style="padding: 1rem;">
                        @if($u->account_status === 'pending' && $u->id !== Auth::id())
                        <div style="display: flex; gap: 0.5rem;">
                            <form method="POST" action="{{ route('users.updateStatus', $u) }}" onsubmit="return confirm('Yakin ingin menyetujui pengguna ini untuk bergabung?');">
                                @csrf
                                <input type="hidden" name="account_status" value="approved">
                                <button type="submit" class="btn-action btn-success">Setujui</button>
                            </form>
                            <form method="POST" action="{{ route('users.updateStatus', $u) }}" onsubmit="return confirm('Yakin ingin menolak pendaftaran pengguna ini?');">
                                @csrf
                                <input type="hidden" name="account_status" value="rejected">
                                <button type="submit" class="btn-action btn-danger">Tolak</button>
                            </form>
                        </div>
                        @else
                            <span style="color: var(--text-muted); font-size: 0.85rem;">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
