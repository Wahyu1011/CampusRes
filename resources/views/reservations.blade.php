@extends('layouts.dashboard')

@section('title', 'Semua Reservasi | Reservasi Fasilitas Kampus')

@section('dashboard_content')
    <div class="recent-reservations glass-panel">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h2>{{ Auth::user()->role === 'admin' ? 'Semua Reservasi' : (Auth::user()->role === 'petugas' ? 'Antrian Reservasi' : 'Reservasi Saya') }}</h2>
            
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <form method="GET" action="{{ route('reservations.index') }}" style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="date" name="date" value="{{ request('date') }}" style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.5rem; border-radius: 8px; color: var(--text-main); outline: none;">
                    <select name="status" aria-label="Filter status reservasi" style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.5rem; border-radius: 8px; color: var(--text-main);">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua status</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                    <button type="submit" style="background: var(--primary-color); color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer;">Cari Tanggal</button>
                    @if(request('date'))
                        <a href="{{ route('reservations.index') }}" style="color: #fca5a5; text-decoration: none; font-size: 0.85rem; margin-left: 0.5rem;">Reset</a>
                    @endif
                </form>

                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('reservations.export') }}" class="btn-reserve" style="width: auto; padding: 0.5rem 1rem; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4);">
                        <i class="fas fa-file-csv"></i> Ekspor CSV
                    </a>
                @endif
            </div>
        </div>
        <ul class="reservation-list">
            @forelse($reservations as $res)
            <li class="reservation-item" style="flex-direction: column; align-items: stretch;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <div class="res-details" style="width: 100%;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h4>{{ $res->facility->name }}</h4>
                                <p>{{ \Carbon\Carbon::parse($res->start_time)->format('d M Y, H:i') }} - {{ \Carbon\Carbon::parse($res->end_time)->format('H:i') }}</p>
                                @if(in_array(Auth::user()->role, ['admin', 'petugas'], true))
                                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.3rem;"><i class="fas fa-user"></i> {{ $res->user->name }}</p>
                                @endif
                            </div>
                            @php
                                $statusClass = match($res->status) {
                                    'approved' => 'status-approved',
                                    'pending' => 'status-pending',
                                    'rejected', 'cancelled' => 'status-danger',
                                    default => ''
                                };
                            @endphp
                            <span class="status {{ $statusClass }}">
                                {{ ucfirst($res->status) }}
                            </span>
                        </div>

                        @if($res->cancellation_reason)
                            <div class="note-danger" style="margin-top: 0.5rem;">
                                <strong>Alasan Batal:</strong> {{ $res->cancellation_reason }}
                            </div>
                        @endif

                        @if($res->activity_name)
                        <div style="background: var(--input-bg); padding: 0.8rem; border-radius: 8px; border: 1px solid var(--border-color); margin-top: 0.8rem; display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.8rem;">
                            <div>
                                <strong style="color: var(--text-muted);">Kegiatan:</strong> {{ $res->activity_name }}<br>
                                <strong style="color: var(--text-muted);">Peserta:</strong> {{ $res->participant_count }} orang
                            </div>
                            <div>
                                <strong style="color: var(--text-muted);">Identitas:</strong> {{ $res->nim }}<br>
                                <strong style="color: var(--text-muted);">Prodi/UKM:</strong> {{ $res->department }} {{ $res->organization ? ' - ' . $res->organization : '' }}<br>
                                <strong style="color: var(--text-muted);">No. WA:</strong> {{ $res->phone }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @if(in_array(Auth::user()->role, ['admin', 'petugas'], true) && $res->status === 'pending')
                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 0.5rem; border-top: 1px solid var(--border-color); padding-top: 0.5rem;">
                    <form method="POST" action="{{ route('reservations.updateStatus', $res) }}" onsubmit="return confirm('Yakin ingin menyetujui reservasi ini?');">
                        @csrf
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="btn-action btn-success">Setujui</button>
                    </form>
                    <form method="POST" action="{{ route('reservations.updateStatus', $res) }}" onsubmit="return confirm('Yakin mau menolak reservasi ini?');">
                        @csrf
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="btn-action btn-danger">Tolak</button>
                    </form>
                </div>
                @endif

                @if(in_array(Auth::user()->role, ['admin', 'petugas'], true) && $res->status === 'approved' && \Carbon\Carbon::parse($res->start_time)->isFuture())
                <div style="margin-top: 0.5rem; border-top: 1px solid var(--border-color); padding-top: 0.5rem;">
                    <form method="POST" action="{{ route('reservations.adminCancel', $res) }}" style="display: flex; gap: 0.5rem; align-items: center;" onsubmit="return confirm('Yakin membatalkan reservasi ini secara paksa?');">
                        @csrf
                        <input type="text" name="cancellation_reason" placeholder="Alasan pembatalan mendesak..." required style="flex: 1; padding: 0.3rem 0.5rem; border-radius: 5px; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main); font-size: 0.8rem;">
                        <button type="submit" class="btn-action btn-danger" style="white-space: nowrap;">Batalkan (Darurat)</button>
                    </form>
                </div>
                @endif

                @if(Auth::user()->role === 'user' && !in_array($res->status, ['cancelled', 'rejected']) && \Carbon\Carbon::parse($res->start_time)->isFuture())
                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 0.5rem; border-top: 1px solid var(--border-color); padding-top: 0.5rem;">
                    <form method="POST" action="{{ route('reservations.cancel', $res) }}" onsubmit="return confirm('Yakin membatalkan reservasi ini?');">
                        @csrf
                        <button type="submit" class="btn-action btn-warning">Batalkan Reservasi</button>
                    </form>
                </div>
                @endif
            </li>
            @empty
            <li class="reservation-item" style="justify-content: center;">
                <p style="color: var(--text-muted); text-align: center; width: 100%;">Belum ada reservasi.</p>
            </li>
            @endforelse
        </ul>
    </div>
@endsection
