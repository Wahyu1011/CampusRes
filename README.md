# CampusRes

CampusRes adalah aplikasi web pengelolaan dan reservasi fasilitas kampus serta sistem pelaporan kerusakan sarana dan prasarana berbasis kerangka kerja Laravel.

---

## 1. Kebutuhan Sistem (*Prerequisites*)

Sebelum menjalankan aplikasi, pengguna wajib menyiapkan perangkat lunak berikut pada sistem operasi:

- **PHP**: Versi 8.3 atau lebih baru
- **Composer**: Versi 2.x (manajer dependensi PHP)
- **Node.js & NPM**: Versi LTS (pembangun aset frontend)
- **Basis Data**: SQLite (konfigurasi bawaan) atau MySQL
- **Peramban Web (*Web Browser*)**: Chrome, Edge, Firefox, atau peramban modern lainnya

---

## 2. Pengaturan dan Petunjuk Menjalankan Program (*Environment Setup & Execution*)

Langkah-langkah berikut disusun secara berurutan untuk menyiapkan lingkungan pengembangan lokal (*local development*):

### A. Konfigurasi dan Instalasi Aplikasi

Pengguna (S) membuka (P) aplikasi terminal (O) pada direktori proyek c:\CampusRes (K).

#### 1. Memasang Dependensi Backend dan Frontend
Pengguna (S) memasang (P) dependensi PHP dan pustaka Node.js (O) melalui terminal (K).
```bash
composer install
npm install
```

#### 2. Menyiapkan Berkas Lingkungan (*Environment File*)
Pengguna (S) menyalin (P) berkas templat *.env.example* (O) menjadi berkas konfigurasi *.env* (K).
```powershell
if (!(Test-Path .env)) { Copy-Item .env.example .env }
```

#### 3. Menyiapkan Berkas Basis Data SQLite
Pengguna (S) membuat (P) berkas basis data SQLite kosong (O) di dalam direktori *database/* (K).
```powershell
if (!(Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite | Out-Null }
```

#### 4. Menghasilkan Kunci Keamanan Aplikasi (*Application Key*)
Sistem (S) menghasilkan (P) kunci enkripsi unik (O) ke dalam berkas *.env* secara otomatis (K).
```bash
php artisan key:generate
```

#### 5. Menghubungkan Direktori Berkas Publik (*Storage Link*)
Sistem (S) membuat (P) tautan simbolik direktori penyimpanan berkas publik (O) agar foto fasilitas dan foto laporan dapat diakses (K).
```bash
php artisan storage:link
```

#### 6. Menjalankan Migrasi dan Pengisian Data Awal (*Database Migration & Seeding*)
Pengembang (S) mengeksekusi (P) skema tabel beserta data awal bawaan (*seeder*) (O) ke dalam basis data (K).
```bash
php artisan migrate --seed
```

> **Catatan:** Untuk memperbarui data akun demo tanpa menghapus data fasilitas yang sudah ada, pengembang dapat menjalankan perintah:
> ```bash
> php artisan db:seed --class=DemoUserSeeder
> ```

---

### B. Menjalankan Peladen Lokal (*Running the Application*)

Aplikasi memerlukan dua proses peladen yang dijalankan secara bersamaan pada jendela terminal terpisah:

#### Terminal 1: Peladen Backend Laravel
Pengguna (S) mengaktifkan (P) peladen lokal PHP (O) melalui perintah Artisan (K).
```bash
php artisan serve
```
*Peladen lokal (S) melayani (P) permintaan antarmuka (O) pada alamat `http://127.0.0.1:8000` (K).*

#### Terminal 2: Peladen Kompilasi Frontend Vite
Pengembang (S) menjalankan (P) peladen pengembangan Vite (O) untuk kompilasi gaya dan skrip frontend (K).
```bash
npm run dev
```

*(Atau jalankan `npm run build` jika ingin menghasilkan bundel aset produksi).*

---

## 3. Informasi Aktor dan Kredensial Masuk (*Actor Access & Credentials*)

Sistem menerapkan kontrol akses berbasis peran (*Role-Based Access Control* / RBAC) yang terbagi menjadi empat tingkatan aktor:

### A. Tabel Akun Bawaan (*Demo Accounts*)

| No | Aktor (*Role*) | Alamat Surel (*Email*) | Kata Sandi (*Password*) | Keterangan Autentikasi |
| :-: | :--- | :--- | :--- | :--- |
| **1** | **Pengunjung (*Guest*)** | *Tanpa surel* | *Tanpa sandi* | Dapat mengakses katalog fasilitas dan ketersediaan jadwal tanpa login. |
| **2** | **Pengguna (*User*)** | `user@user.com` | `user123` | Mahasiswa/Dosen/Staf terdaftar dan telah disetujui akunnya oleh Admin. |
| **3** | **Petugas (*Staff*)** | `petugas@campusres.test` | `petugas123` | Petugas operasional yang menangani antrean reservasi dan laporan kerusakan. |
| **4** | **Administrator (*Admin*)** | `admin@campusres.test` | `admin123` | Pengelola master data fasilitas, verifikasi akun, dan rekapitulasi data. |

### B. Ketentuan dan Batasan Hak Akses Aktor

1. **Pengunjung (*Guest*)**
   - Pengunjung (S) dapat melihat (P) daftar fasilitas kampus beserta ketersediaan jadwalnya (O) tanpa melakukan proses autentikasi (K).
   - Pengunjung (S) tidak dapat melihat (P) rincian peminjam maupun mengajukan reservasi fasilitas (O) sebelum mendaftar dan masuk (K).

2. **Pengguna (*User: Mahasiswa / Dosen / Staf*)**
   - Pengguna (S) dapat mengisi (P) formulir permohonan reservasi fasilitas (O) berdasarkan slot waktu yang masih tersedia (K).
   - Pengguna (S) dapat mengirimkan (P) laporan kerusakan fasilitas kampus (O) dengan melampirkan bukti foto fisik (K).
   - Pengguna baru (S) mendaftarkan (P) akun secara mandiri (O) melalui halaman `/register` (K).
   - Akun baru hasil registrasi (S) berstatus menunggu persetujuan (*pending*) (O) dan belum dapat masuk sebelum disetujui Admin (K).

3. **Petugas (*Staff / Operator*)**
   - Petugas (S) memeriksa (P) antrean permohonan reservasi fasilitas (O) yang berstatus *pending* (K).
   - Petugas (S) dapat menyetujui, menolak, atau membatalkan (P) reservasi fasilitas (O) sesuai ketentuan kampus (K).
   - Petugas (S) memperbarui (P) progres tindak lanjut laporan kerusakan (O) dari status *new*, *processing*, hingga *resolved* (K).
   - Petugas (S) dapat mengubah (P) status fasilitas menjadi dalam perbaikan (*maintenance*) (O) saat menangani laporan kerusakan (K).

4. **Administrator (*Admin*)**
   - Administrator (S) mengelola (P) data master fasilitas kampus (O) meliputi tambah, ubah, dan hapus fasilitas (K).
   - Administrator (S) memverifikasi (P) akun pengguna baru hasil pendaftaran mandiri (O) melalui menu Kelola Pengguna (K).
   - Administrator (S) dapat mendaftarkan (P) akun petugas baru (O) secara langsung ke dalam sistem (K).
   - Administrator (S) memantau (P) ringkasan statistik dan mengunduh rekapitulasi data okupansi (O) ke dalam format CSV (K).

---

## 4. Penjelasan Fitur Sistem (*System Features*)

Proyek CampusRes dikembangkan melalui tujuh modul fungsional utama:

### 1. Autentikasi dan Pemulihan Akun (`auth-account-recovery`)
- Pengguna (S) mendaftarkan (P) akun baru (O) melalui formulir registrasi mandiri pada sistem (K).
- Sistem (S) mengamankan (P) kata sandi pengguna (O) menggunakan enkripsi acakan terstandar *Bcrypt* (K).
- Pengguna yang lupa kata sandi (S) dapat mengajukan (P) permohonan pemulihan akun (O) melalui fitur *forgot password* (K).
- Sistem (S) menerbitkan (P) token keamanan khusus (O) untuk memverifikasi proses penetapan kata sandi baru (K).

### 2. Manajemen dan Persetujuan Akun Pengguna (`user-management-approval`)
- Administrator (S) mengelola (P) seluruh data akun pengguna (O) melalui panel tata kelola pengguna terpusat (K).
- Sistem (S) menetapkan (P) status *pending* secara otomatis (O) bagi setiap akun mahasiswa/pengguna yang baru mendaftar (K).
- Administrator (S) memvalidasi (P) kelayakan akun pengguna baru (O) dengan memberikan status disetujui (*approved*) atau ditolak (*rejected*) (K).
- Administrator (S) dapat mendaftarkan (P) akun petugas operasional baru (O) secara langsung tanpa alur persetujuan lanjutan (K).

### 3. Manajemen Data Fasilitas Kampus (`facility-management`)
- Administrator (S) mengelola (P) master data sarana dan prasarana (O) termasuk nama, kategori, lokasi, kapasitas, dan deskripsi fasilitas (K).
- Sistem (S) menyimpan (P) berkas foto fasilitas (O) ke dalam direktori penyimpanan publik (*storage*) (K).
- Pengunjung dan pengguna (S) memfilter (P) katalog fasilitas (O) berdasarkan tanggal, tipe, lokasi, dan kapasitas ruangan (K).
- Sistem (S) menampilkan (P) matriks ketersediaan slot waktu harian berdurasi 30 menit (O) pada halaman katalog publik (K).
- Pengelola (S) dapat memperbarui (P) status fasilitas menjadi aktif atau dalam perawatan (*maintenance*) (O) sewaktu-waktu (K).

### 4. Reservasi dan Peminjaman Fasilitas (`facility-reservations`)
- Pengguna terdaftar (S) mengajukan (P) permohonan reservasi fasilitas (O) dengan memilih slot waktu mulai dan selesai (K).
- Algoritma validasi (S) memeriksa (P) bentrok jadwal penggunaan ruangan (*conflict detection*) (O) secara otomatis (K).
- Sistem (S) mencegah (P) permohonan peminjaman ganda (*double booking*) (O) pada ruangan dan waktu yang bersamaan (K).
- Petugas dan admin (S) memproses (P) antrean reservasi (O) dengan opsi menyetujui (*approve*), menolak (*reject*), atau membatalkan (*cancel*) (K).
- Pengguna (S) dapat membatalkan (P) reservasi miliknya secara mandiri (O) selama status permohonan masih menunggu persetujuan (K).

### 5. Pelaporan Kerusakan Fasilitas (`facility-damage-reports`)
- Pengguna (S) melaporkan (P) kerusakan fasilitas sarana dan prasarana (O) melalui formulir pelaporan terintegrasi (K).
- Pengguna (S) melampirkan (P) berkas foto bukti kerusakan fisik (O) ke dalam formulir laporan (K).
- Petugas (S) memeriksa (P) antrean laporan kerusakan baru (O) melalui halaman daftar laporan (K).
- Petugas (S) memperbarui (P) status penanganan laporan (O) dari tahap *new*, *processing*, hingga terselesaikan (*resolved*) (K).
- Petugas (S) dapat menyematkan (P) catatan investigasi dan resolusi perbaikan (O) pada berkas laporan (K).

### 6. Dasbor dan Ringkasan Aktivitas (*dashboard-overview*)
- Sistem (S) menyajikan (P) ringkasan statistik dan kartu metrik operasional (O) secara dinamis sesuai peran pengguna (K).
- Administrator dan petugas (S) memantau (P) jumlah fasilitas aktif, antrean reservasi, dan laporan kerusakan yang perlu ditangani (O) pada dasbor (K).
- Pengguna (S) melihat (P) riwayat reservasi aktif dan status laporan kerusakan pribadi (O) secara ringkas (K).
- Dasbor (S) menampilkan (P) daftar reservasi terbaru (O) untuk memudahkan pemantauan harian (K).

### 7. Rekapitulasi dan Ekspor Data Reservasi (`reservation-export`)
- Administrator (S) memfilter (P) periode rentang tanggal awal dan akhir (O) pada modul rekapitulasi fasilitas (K).
- Sistem (S) mengagregasi (P) total reservasi disetujui, akumulasi jam terpakai, dan frekuensi insiden kerusakan (O) per fasilitas (K).
- Administrator (S) mengunduh (P) data rekapitulasi penggunaan fasilitas (O) dalam format berkas CSV (K).
- Sistem (S) menyematkan (P) penanda *UTF-8 BOM* dan pengamanan formula spreadsheet (O) agar berkas ekspor terbaca aman pada Microsoft Excel (K).

---

## 5. Pengujian Sistem (*Testing*)

Untuk memastikan seluruh fungsi validasi dan logika bisnis berjalan dengan benar, jalankan pengujian otomatis menggunakan perintah:

```powershell
php artisan test --compact
```
