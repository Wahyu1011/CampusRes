@extends('layouts.dashboard')

@section('title', 'Lapor Kerusakan | CampusRes')

@section('dashboard_content')
    <div class="glass-panel" style="max-width: 600px; margin: 0 auto;">
        <div class="section-header">
            <h2>Lapor Kerusakan Fasilitas</h2>
            <a href="{{ route('reports.index') }}" class="view-all"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>
        
        <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1.5rem;">
            @csrf
            
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label for="facility_id" style="font-weight: 500;">Pilih Fasilitas <span style="color:red;">*</span></label>
                <select name="facility_id" id="facility_id" required style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.8rem; border-radius: 8px; color: var(--text-main); outline: none;">
                    <option value="">-- Pilih Fasilitas --</option>
                    @foreach($facilities as $fac)
                        <option value="{{ $fac->id }}" {{ $selectedFacility == $fac->id ? 'selected' : '' }}>{{ $fac->name }} ({{ $fac->location }})</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label for="category" style="font-weight: 500;">Kategori Kerusakan <span style="color:red;">*</span></label>
                <select name="category" id="category" required style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.8rem; border-radius: 8px; color: var(--text-main); outline: none;">
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Listrik / Lampu" {{ old('category') == 'Listrik / Lampu' ? 'selected' : '' }}>Listrik / Lampu</option>
                    <option value="AC / Pendingin" {{ old('category') == 'AC / Pendingin' ? 'selected' : '' }}>AC / Pendingin Ruangan</option>
                    <option value="Kebersihan" {{ old('category') == 'Kebersihan' ? 'selected' : '' }}>Kebersihan</option>
                    <option value="Peralatan Patah / Rusak" {{ old('category') == 'Peralatan Patah / Rusak' ? 'selected' : '' }}>Peralatan Patah / Rusak (Meja, Kursi, dsb)</option>
                    <option value="Lainnya" {{ old('category') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label for="description" style="font-weight: 500;">Deskripsi Kerusakan <span style="color:red;">*</span></label>
                <textarea id="description" name="description" rows="4" required placeholder="Jelaskan detail kerusakannya..." style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.8rem; border-radius: 8px; color: var(--text-main); outline: none; resize: vertical;">{{ old('description') }}</textarea>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label for="image" style="font-weight: 500;">Foto Bukti Kerusakan (Opsional)</label>
                <input type="file" id="image" name="image" accept="image/*" style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.8rem; border-radius: 8px; color: var(--text-main); outline: none;">
                <small style="color: var(--text-muted);">Maksimal ukuran foto 2MB (JPG/PNG).</small>
            </div>

            <button type="submit" class="btn-reserve" style="margin-top: 1rem; padding: 1rem;">
                <i class="fas fa-paper-plane"></i> Kirim Laporan
            </button>
        </form>
    </div>
@endsection
