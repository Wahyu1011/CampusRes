# CampusRes

CampusRes adalah aplikasi reservasi fasilitas kampus dan pelaporan kerusakan berbasis Laravel.

## Kebutuhan Sistem

- PHP 8.3 atau lebih baru
- Composer 2
- Node.js dan npm untuk membangun aset frontend
- SQLite (konfigurasi bawaan) atau database lain yang didukung Laravel

## Menyiapkan Aplikasi

Langkah ini ditujukan untuk instalasi development lokal yang baru.

Di PowerShell dari folder proyek:

```powershell
composer install

if (!(Test-Path .env)) {
    Copy-Item .env.example .env
}

if (!(Test-Path database/database.sqlite)) {
    New-Item -ItemType File -Path database/database.sqlite | Out-Null
}

php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

Konfigurasi bawaan menggunakan SQLite. Jika memakai database lain, ubah `DB_CONNECTION`, kredensial database, dan `APP_URL` pada `.env` sebelum menjalankan migrasi. Jangan commit `.env`.

Pada database yang sudah berisi data, jalankan `php artisan migrate` untuk menerapkan migrasi baru. Untuk membuat atau memperbarui akun demo tanpa menambahkan ulang fasilitas contoh, jalankan `php artisan db:seed --class=DemoUserSeeder`.

## Menjalankan Aplikasi

```powershell
php artisan serve
```

Buka `http://127.0.0.1:8000`. Katalog dan jadwal fasilitas publik tersedia pada `/`. Saat mengembangkan aset Vite, jalankan `npm run dev` pada terminal lain.

## Login Demo

Akun berikut dibuat oleh seeder dan berstatus disetujui agar dapat langsung digunakan:

| Aktor      | Email                    | Password     |
| ---------- | ------------------------ | ------------ |
| Admin      | `admin@campusres.test`   | `admin123`   |
| Petugas    | `petugas@campusres.test` | `petugas123` |
| Pengguna   | `user@user.com`          | `user123`    |
| Pengunjung | Tidak perlu login        | Tidak ada    |

Kredensial tersebut hanya untuk development lokal. Jangan gunakan pada production. Menjalankan `DemoUserSeeder` akan mengatur ulang password akun demo sesuai nilai di atas.

Pengguna juga dapat mendaftar melalui `/register`. Akun hasil registrasi berstatus menunggu persetujuan Admin dan belum dapat login sebelum disetujui. Petugas tidak dapat mendaftar mandiri; Admin membuat akun Petugas melalui menu **Kelola Pengguna**.

## Pengujian

```powershell
php artisan test --compact
```
