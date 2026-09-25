@extends('layouts.app')

@section('title', 'Daftar | Sistem Pendaftaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('style.css') }}">
@endpush

@section('content')
    <div class="login-container">
        <div class="login-header">
            <h1>Buat Akun</h1>
            <p>Silakan isi data untuk mendaftar</p>
        </div>
        <form method="POST" action="{{ route('register') }}" class="login-form">
            @csrf
            <div class="input-group">
                <label for="regName">Nama Lengkap</label>
                <input type="text" id="regName" name="name" placeholder="Masukkan nama lengkap" required>
            </div>
            <div class="input-group">
                <label for="regEmail">Email</label>
                <input type="email" id="regEmail" name="email" placeholder="Masukkan alamat email" required>
            </div>
            <div class="input-group">
                <label for="regPassword">Password</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="regPassword" name="password" placeholder="Buat password" required onkeyup="checkPasswordStrength(this.value)" style="padding-right: 40px; width: 100%;">
                    <i class="fas fa-eye" id="togglePassword" style="position: absolute; right: 15px; cursor: pointer; color: #64748b;" onclick="toggleVisibility('regPassword', 'togglePassword')"></i>
                </div>
                
                <div id="password-strength-container" style="display: none; margin-top: 0.5rem; background: rgba(15,23,42,0.4); padding: 0.5rem 0.8rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); align-items: center; gap: 0.5rem;">
                    <i id="strength-icon" class="fas fa-key" style="color: #64748b;"></i>
                    <div style="flex: 1; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; display: flex;">
                        <div id="strength-bar" style="height: 100%; width: 0%; transition: all 0.3s ease;"></div>
                    </div>
                    <span id="strength-text" style="font-size: 0.75rem; font-weight: 600; min-width: 80px; text-align: right; color: #64748b;"></span>
                </div>
            </div>
            <div class="input-group">
                <label for="regConfirmPassword">Konfirmasi Password</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="regConfirmPassword" name="password_confirmation" placeholder="Ulangi password" required style="padding-right: 40px; width: 100%;">
                    <i class="fas fa-eye" id="toggleConfirmPassword" style="position: absolute; right: 15px; cursor: pointer; color: #64748b;" onclick="toggleVisibility('regConfirmPassword', 'toggleConfirmPassword')"></i>
                </div>
            </div>
            <button type="submit" class="login-btn">
                <span class="btn-text">Daftar</span>
            </button>
            <div class="error-message @if($errors->any()) show @endif">
                @if($errors->any())
                    {{ $errors->first() }}
                @endif
            </div>
        </form>
        <div class="login-footer">
            <p>Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function checkPasswordStrength(password) {
        const container = document.getElementById('password-strength-container');
        const bar = document.getElementById('strength-bar');
        const text = document.getElementById('strength-text');
        const icon = document.getElementById('strength-icon');
        
        if (password.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'flex';
        
        let strength = 0;
        
        // Aturan penilaian
        if (password.length >= 8) strength += 1;
        if (password.match(/[a-z]+/)) strength += 1;
        if (password.match(/[A-Z]+/)) strength += 1;
        if (password.match(/[0-9]+/)) strength += 1;
        if (password.match(/[\W]+/)) strength += 1;
        
        // Update UI
        if (strength <= 2) {
            bar.style.width = '33%';
            bar.style.background = '#ef4444'; // Merah
            text.innerText = 'Lemah';
            text.style.color = '#fca5a5';
            icon.style.color = '#ef4444';
        } else if (strength === 3 || strength === 4) {
            bar.style.width = '66%';
            bar.style.background = '#f59e0b'; // Kuning
            text.innerText = 'Kuat';
            text.style.color = '#fcd34d';
            icon.style.color = '#f59e0b';
        } else if (strength === 5) {
            bar.style.width = '100%';
            bar.style.background = '#10b981'; // Hijau
            text.innerText = 'Sangat Kuat';
            text.style.color = '#6ee7b7';
            icon.style.color = '#10b981';
        }
    }

    function toggleVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endpush
