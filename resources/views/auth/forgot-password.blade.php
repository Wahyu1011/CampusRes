<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - CampusRes</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <h2>Lupa Password</h2>
            <p>Masukkan email yang terdaftar untuk mengatur ulang sandi.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); color: #34d399; padding: 1rem; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.4); margin-bottom: 1.5rem; text-align: center;">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 1rem; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.4); margin-bottom: 1.5rem;">
                <ul style="margin-left: 1.5rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="Masukkan email Anda">
                </div>
            </div>

            <button type="submit" class="btn-login">Kirim Tautan Reset</button>
        </form>

        <div class="login-footer">
            <p>Ingat password Anda? <a href="{{ route('login') }}">Kembali ke Login</a></p>
        </div>
    </div>

</body>
</html>
