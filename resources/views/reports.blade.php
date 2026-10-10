@extends('layouts.dashboard')

@section('title', 'Daftar Laporan | CampusRes')

@section('dashboard_content')
<div class="welcome-section mb-4">
    <h1>Laporan Kerusakan Fasilitas</h1>
    <p>Kelola dan pantau status laporan kerusakan fasilitas kampus.</p>
</div>

<div class="glass-panel">
    <div class="section-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Daftar Laporan</h2>
        @if(Auth::user()->role === 'user')
        <a href="{{ route('reports.create') }}" class="btn-reserve" style="width: auto; padding: 0.5rem 1rem; text-decoration: none;">
            <i class="fas fa-plus"></i> Buat Laporan Baru
        </a>
        @endif
    </div>

    @if($reports->isEmpty())
        <p style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada laporan kerusakan.</p>
    @else
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($reports as $rep)
            <div style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 12px; display: flex; gap: 1.5rem; flex-wrap: wrap;">
                
                @if($rep->image_url)
                <div style="width: 150px; height: 150px; flex-shrink: 0;">
                    <img src="{{ asset($rep->image_url) }}" alt="Foto Kerusakan" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                </div>
                @endif
                
                <div style="flex: 1; min-width: 300px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <h3 style="margin: 0; font-size: 1.2rem; color: var(--text-main);">{{ $rep->facility->name }} - {{ $rep->category }}</h3>
                        
                        @php
                            $badgeClass = match($rep->status) {
                                'new' => 'status-primary',
                                'processing' => 'status-pending',
                                'completed' => 'status-approved',
                                'rejected' => 'status-danger',
                                default => ''
                            };
                            $badgeLabel = match($rep->status) {
                                'new' => 'Baru',
                                'processing' => 'Diproses',
                                'completed' => 'Selesai',
                                'rejected' => 'Ditolak',
                                default => 'Unknown'
                            };
                        @endphp
                        <span class="status {{ $badgeClass }}">
                            {{ $badgeLabel }}
                        </span>
                    </div>
                    
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem;">Dilaporkan oleh <strong>{{ $rep->user->name }}</strong> pada {{ \Carbon\Carbon::parse($rep->created_at)->format('d M Y, H:i') }}</p>
                    
                    <div style="background: var(--card-bg); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--border-color);">
                        <p style="margin: 0; font-size: 0.95rem; line-height: 1.5; color: var(--text-main);">"{{ $rep->description }}"</p>
                    </div>

                    @if($rep->resolution_note)
                        <div class="note-success">
                            <strong>Catatan Penyelesaian:</strong>
                            <p style="margin: 0.3rem 0 0 0; color: var(--text-main);">{{ $rep->resolution_note }}</p>
                        </div>
                    @endif

                    @if(in_array(Auth::user()->role, ['admin', 'petugas'], true) && in_array($rep->status, ['new', 'processing']))
                        <div style="border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 1rem;">
                            <form method="POST" action="{{ route('reports.updateStatus', $rep) }}" class="report-status-form" style="display: flex; gap: 0.8rem; align-items: flex-start; flex-wrap: wrap;">
                                @csrf
                                <select name="status" class="report-status-select" required style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.5rem; border-radius: 5px; color: var(--text-main);">
                                    <option value="processing" {{ $rep->status == 'processing' ? 'selected' : '' }}>Tandai Diproses</option>
                                    <option value="completed">Tandai Selesai</option>
                                    <option value="rejected">Tolak Laporan</option>
                                </select>
                                <select name="facility_status" style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.5rem; border-radius: 5px; color: var(--text-main);">
                                    <option value="">Status fasilitas tidak diubah</option>
                                    <option value="active">Fasilitas aktif</option>
                                    <option value="maintenance">Tandai dalam perbaikan</option>
                                </select>
                                <input type="text" name="resolution_note" class="report-resolution-note" placeholder="Catatan wajib saat selesai/ditolak..." style="flex: 1; min-width: 200px; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.5rem; border-radius: 5px; color: var(--text-main);">
                                <button type="submit" style="background: var(--primary-color); color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">Update Status</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.querySelectorAll('.report-status-form').forEach(form => {
        const status = form.querySelector('.report-status-select');
        const note = form.querySelector('.report-resolution-note');
        const updateNoteRequirement = () => {
            note.required = ['completed', 'rejected'].includes(status.value);
        };

        status.addEventListener('change', updateNoteRequirement);
        updateNoteRequirement();
    });
</script>
@endpush
@endsection
