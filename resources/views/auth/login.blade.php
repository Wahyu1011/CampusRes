@extends('layouts.app')

@section('title', 'Login | Sistem Pendaftaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('style.css') }}">
@endpush

@section('content')
    <div class="login-container">
        <div class="login-header">
            <h1>Selamat Datang</h1>
            <p>Silakan login untuk melanjutkan</p>
        </div>
        <form method="POST" action="{{ route('login') }}" class="login-form">
            @csrf
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" placeholder="Masukkan alamat email" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Masukkan password Anda" required style="padding-right: 40px; width: 100%;">
                    <i class="fas fa-eye" id="togglePassword" style="position: absolute; right: 15px; cursor: pointer; color: #64748b;" onclick="toggleVisibility('password', 'togglePassword')"></i>
                </div>
            </div>
            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Ingat Saya</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot-password">Lupa Password?</a>
                @endif
            </div>
            <button type="submit" class="login-btn">
                <span class="btn-text">Masuk</span>
            </button>
            <div class="error-message @if($errors->any()) show @endif">
                @if($errors->any())
                    {{ $errors->first() }}
                @endif
            </div>
            @if(session('success'))
                <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 0.8rem; border-radius: 8px; margin-top: 1rem; font-size: 0.85rem; text-align: center;">
                    {{ session('success') }}
                </div>
            @endif
        </form>
        <div class="login-footer">
            <p>Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></p>
        </div>
    </div>
@endsection

@push('scripts')
<script>
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
