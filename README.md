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

Pengguna membuka aplikasi terminal pada direktori proyek c:\CampusRes.

#### 1. Memasang Dependensi Backend dan Frontend
Pengguna memasang dependensi PHP dan pustaka Node.js melalui terminal.
```bash
composer install
npm install
```

#### 2. Menyiapkan Berkas Lingkungan (*Environment File*)
Pengguna menyalin berkas templat *.env.example* menjadi berkas konfigurasi *.env*.
```powershell
if (!(Test-Path .env)) { Copy-Item .env.example .env }
```

#### 3. Menyiapkan Berkas Basis Data SQLite
Pengguna membuat berkas basis data SQLite kosong di dalam direktori *database/*.
```powershell
if (!(Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite | Out-Null }
```

#### 4. Menghasilkan Kunci Keamanan Aplikasi (*Application Key*)
Sistem menghasilkan kunci enkripsi unik ke dalam berkas *.env* secara otomatis.
```bash
php artisan key:generate
```

#### 5. Menghubungkan Direktori Berkas Publik (*Storage Link*)
Sistem membuat tautan simbolik direktori penyimpanan berkas publik agar foto fasilitas dan foto laporan dapat diakses.
```bash
php artisan storage:link
```

#### 6. Menjalankan Migrasi dan Pengisian Data Awal (*Database Migration & Seeding*)
Pengembang mengeksekusi skema tabel beserta data awal bawaan (*seeder*) ke dalam basis data.
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
Pengguna mengaktifkan peladen lokal PHP melalui perintah Artisan.
```bash
php artisan serve
```
*Peladen lokal melayani permintaan antarmuka pada alamat `http://127.0.0.1:8000`.*

#### Terminal 2: Peladen Kompilasi Frontend Vite
Pengembang menjalankan peladen pengembangan Vite untuk kompilasi gaya dan skrip frontend.
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
   - Pengunjung dapat melihat daftar fasilitas kampus beserta ketersediaan jadwalnya tanpa melakukan proses autentikasi.
   - Pengunjung tidak dapat melihat rincian peminjam maupun mengajukan reservasi fasilitas sebelum mendaftar dan masuk.

2. **Pengguna (*User: Mahasiswa / Dosen / Staf*)**
   - Pengguna dapat mengisi formulir permohonan reservasi fasilitas berdasarkan slot waktu yang masih tersedia.
   - Pengguna dapat mengirimkan laporan kerusakan fasilitas kampus dengan melampirkan bukti foto fisik.
   - Pengguna baru mendaftarkan akun secara mandiri melalui halaman `/register`.
   - Akun baru hasil registrasi berstatus menunggu persetujuan (*pending*) dan belum dapat masuk sebelum disetujui Admin.

3. **Petugas (*Staff / Operator*)**
   - Petugas memeriksa antrean permohonan reservasi fasilitas yang berstatus *pending*.
   - Petugas dapat menyetujui, menolak, atau membatalkan reservasi fasilitas sesuai ketentuan kampus.
   - Petugas memperbarui progres tindak lanjut laporan kerusakan dari status *new*, *processing*, hingga *resolved*.
   - Petugas dapat mengubah status fasilitas menjadi dalam perbaikan (*maintenance*) saat menangani laporan kerusakan.

4. **Administrator (*Admin*)**
   - Administrator mengelola data master fasilitas kampus meliputi tambah, ubah, dan hapus fasilitas.
   - Administrator memverifikasi akun pengguna baru hasil pendaftaran mandiri melalui menu Kelola Pengguna.
   - Administrator dapat mendaftarkan akun petugas baru secara langsung ke dalam sistem.
   - Administrator memantau ringkasan statistik dan mengunduh rekapitulasi data okupansi ke dalam format CSV.

---

## 4. Penjelasan Fitur Sistem (*System Features*)

Proyek CampusRes dikembangkan melalui tujuh modul fungsional utama:

### 1. Autentikasi dan Pemulihan Akun (`auth-account-recovery`)
- Pengguna mendaftarkan akun baru melalui formulir registrasi mandiri pada sistem.
- Sistem mengamankan kata sandi pengguna menggunakan enkripsi acakan terstandar *Bcrypt*.
- Pengguna yang lupa kata sandi dapat mengajukan permohonan pemulihan akun melalui fitur *forgot password*.
- Sistem menerbitkan token keamanan khusus untuk memverifikasi proses penetapan kata sandi baru.

### 2. Manajemen dan Persetujuan Akun Pengguna (`user-management-approval`)
- Administrator mengelola seluruh data akun pengguna melalui panel tata kelola pengguna terpusat.
- Sistem menetapkan status *pending* secara otomatis bagi setiap akun mahasiswa/pengguna yang baru mendaftar.
- Administrator memvalidasi kelayakan akun pengguna baru dengan memberikan status disetujui (*approved*) atau ditolak (*rejected*).
- Administrator dapat mendaftarkan akun petugas operasional baru secara langsung tanpa alur persetujuan lanjutan.

### 3. Manajemen Data Fasilitas Kampus (`facility-management`)
- Administrator mengelola master data sarana dan prasarana termasuk nama, kategori, lokasi, kapasitas, dan deskripsi fasilitas.
- Sistem menyimpan berkas foto fasilitas ke dalam direktori penyimpanan publik (*storage*).
- Pengunjung dan pengguna memfilter katalog fasilitas berdasarkan tanggal, tipe, lokasi, dan kapasitas ruangan.
- Sistem menampilkan matriks ketersediaan slot waktu harian berdurasi 30 menit pada halaman katalog publik.
- Pengelola dapat memperbarui status fasilitas menjadi aktif atau dalam perawatan (*maintenance*) sewaktu-waktu.

### 4. Reservasi dan Peminjaman Fasilitas (`facility-reservations`)
- Pengguna terdaftar mengajukan permohonan reservasi fasilitas dengan memilih slot waktu mulai dan selesai.
- Algoritma validasi memeriksa bentrok jadwal penggunaan ruangan (*conflict detection*) secara otomatis.
- Sistem mencegah permohonan peminjaman ganda (*double booking*) pada ruangan dan waktu yang bersamaan.
- Petugas dan admin memproses antrean reservasi dengan opsi menyetujui (*approve*), menolak (*reject*), atau membatalkan (*cancel*).
- Pengguna dapat membatalkan reservasi miliknya secara mandiri selama status permohonan masih menunggu persetujuan.

### 5. Pelaporan Kerusakan Fasilitas (`facility-damage-reports`)
- Pengguna melaporkan kerusakan fasilitas sarana dan prasarana melalui formulir pelaporan terintegrasi.
- Pengguna melampirkan berkas foto bukti kerusakan fisik ke dalam formulir laporan.
- Petugas memeriksa antrean laporan kerusakan baru melalui halaman daftar laporan.
- Petugas memperbarui status penanganan laporan dari tahap *new*, *processing*, hingga terselesaikan (*resolved*).
- Petugas dapat menyematkan catatan investigasi dan resolusi perbaikan pada berkas laporan.

### 6. Dasbor dan Ringkasan Aktivitas (*dashboard-overview*)
- Sistem menyajikan ringkasan statistik dan kartu metrik operasional secara dinamis sesuai peran pengguna.
- Administrator dan petugas memantau jumlah fasilitas aktif, antrean reservasi, dan laporan kerusakan yang perlu ditangani pada dasbor.
- Pengguna melihat riwayat reservasi aktif dan status laporan kerusakan pribadi secara ringkas.
- Dasbor menampilkan daftar reservasi terbaru untuk memudahkan pemantauan harian.

### 7. Rekapitulasi dan Ekspor Data Reservasi (`reservation-export`)
- Administrator memfilter periode rentang tanggal awal dan akhir pada modul rekapitulasi fasilitas.
- Sistem mengagregasi total reservasi disetujui, akumulasi jam terpakai, dan frekuensi insiden kerusakan per fasilitas.
- Administrator mengunduh data rekapitulasi penggunaan fasilitas dalam format berkas CSV.
- Sistem menyematkan penanda *UTF-8 BOM* dan pengamanan formula spreadsheet agar berkas ekspor terbaca aman pada Microsoft Excel.

---

## 5. Pengujian Sistem (*Testing*)

Untuk memastikan seluruh fungsi validasi dan logika bisnis berjalan dengan benar, jalankan pengujian otomatis menggunakan perintah:

```powershell
php artisan test --compact
```
