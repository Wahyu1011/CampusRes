<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CampusRes</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <h2>Buat Password Baru</h2>
            <p>Silakan masukkan password baru Anda.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 1rem; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.4); margin-bottom: 1.5rem;">
                <ul style="margin-left: 1.5rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

            <div class="form-group">
                <label for="password">Password Baru</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required placeholder="Minimal 8 karakter">
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Konfirmasi Password Baru</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Ulangi password baru">
                </div>
            </div>

            <button type="submit" class="btn-login">Simpan Password Baru</button>
        </form>
    </div>

</body>
</html>
