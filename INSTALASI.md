# Panduan Instalasi Lentera Desa

Panduan ini menjelaskan cara memasang Lentera Desa di komputer server desa (Windows dengan XAMPP). Setelah selesai, aplikasi dapat dibuka dari komputer server maupun komputer lain di jaringan lokal kantor desa.

## Daftar Isi

1. [Kebutuhan Sistem](#1-kebutuhan-sistem)
2. [Langkah Instalasi](#2-langkah-instalasi)
3. [Konfigurasi `.env`](#3-konfigurasi-env)
4. [Akun Awal](#4-akun-awal)
5. [Akses dari Komputer Lain](#5-akses-dari-komputer-lain-jaringan-lokal)
6. [Persiapan Sebelum Dipakai Sungguhan](#6-persiapan-sebelum-dipakai-sungguhan)
7. [Pencadangan](#7-pencadangan)
8. [Pemecahan Masalah](#8-pemecahan-masalah)

## 1. Kebutuhan Sistem

| Kebutuhan | Keterangan |
| --- | --- |
| XAMPP | Menyediakan PHP 8.2 atau lebih baru dan MySQL/MariaDB. Laragon juga dapat dipakai. |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `xml`, `zip`, `fileinfo` (sudah aktif pada XAMPP bawaan). |
| Composer 2 | Memasang dependensi PHP. |
| Node.js 20 atau lebih baru dan npm | Membangun aset tampilan (Tailwind CSS dan Vite). |
| LibreOffice | Mengonversi templat Word (`.docx`) menjadi PDF. |
| Git | Opsional, untuk mengunduh proyek dari repositori. |

Versi yang dipakai saat pengembangan: Laravel 12, PHP 8.2.12, MariaDB 10.4.32, Apache 2.4.58.

## 2. Langkah Instalasi

### 2.1 Nyalakan MySQL

Buka **XAMPP Control Panel**, lalu klik **Start** pada baris **MySQL**. Apache hanya perlu dinyalakan bila Anda ingin memakai phpMyAdmin.

### 2.2 Buat database

Buka `http://localhost/phpmyadmin`, lalu buat database baru:

- Nama: `lentera_desa`
- Collation: `utf8mb4_unicode_ci`

Atau lewat terminal:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE lentera_desa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 2.3 Unduh proyek

```bash
git clone <alamat-repositori> lentera-desa
cd lentera-desa
```

Tanpa Git, unduh proyek sebagai ZIP dari GitHub, ekstrak, lalu buka terminal di folder hasil ekstrak.

### 2.4 Pasang dependensi

```bash
composer install
npm install
```

### 2.5 Siapkan konfigurasi

```bash
copy .env.example .env
php artisan key:generate
```

(Pada Git Bash atau Linux/macOS, ganti `copy` dengan `cp`.)

Lalu buka berkas `.env` dan sesuaikan sesuai [bagian 3](#3-konfigurasi-env).

### 2.6 Buat tabel dan data awal

```bash
php artisan migrate --seed
```

Perintah ini membuat seluruh tabel, dua akun bawaan, empat jenis surat beserta templat Word contoh, dan profil desa awal.

### 2.7 Bangun aset tampilan

```bash
npm run build
```

### 2.8 Jalankan aplikasi

```bash
php artisan serve
```

Buka `http://127.0.0.1:8000` di browser, lalu masuk dengan akun pada [bagian 4](#4-akun-awal).

## 3. Konfigurasi `.env`

| Variabel | Isi |
| --- | --- |
| `DB_HOST`, `DB_PORT` | `127.0.0.1` dan `3306` (bawaan XAMPP). |
| `DB_DATABASE` | `lentera_desa`. |
| `DB_USERNAME`, `DB_PASSWORD` | `root` dan kosong pada XAMPP bawaan. Gunakan akun khusus dengan kata sandi bila server dipakai sungguhan. |
| `LIBREOFFICE_PATH` | Jalur lengkap `soffice.exe`, misalnya `C:\Program Files\LibreOffice\program\soffice.exe`. |
| `LIBREOFFICE_TIMEOUT` | Batas waktu satu konversi PDF dalam detik (bawaan 60). Naikkan bila komputer lambat. |
| `DESA_NAMA`, `DESA_KEPALA`, `DESA_KODE` | Nilai awal profil desa. Setelah aplikasi berjalan, ubah lewat menu **Profil Desa**, bukan lewat `.env`. |
| `SEED_PASSWORD` | Kata sandi awal akun bawaan (opsional). |
| `APP_ENV`, `APP_DEBUG` | `local` dan `true` untuk pengembangan. Lihat [bagian 6](#6-persiapan-sebelum-dipakai-sungguhan) untuk pemakaian sungguhan. |

Zona waktu aplikasi sudah diatur ke `Asia/Jakarta` pada `config/app.php`, sehingga tanggal surat dan bulan pada nomor surat mengikuti WIB.

## 4. Akun Awal

Seeder membuat dua akun:

| Username | Peran | Akses |
| --- | --- | --- |
| `admin` | Administrator | Kelola jenis surat, kelola pengguna, dan profil desa. |
| `operator` | Operator | Dasbor, data warga, buat surat, arsip, dan rekap layanan. |

Kata sandi awal ditentukan oleh `SEED_PASSWORD` di `.env`. Bila kosong, nilai bawaan pada `database/seeders/UserSeeder.php` yang dipakai.

> **Penting:** ganti kata sandi kedua akun lewat menu **Kelola Pengguna** sebelum aplikasi dipakai sungguhan.

## 5. Akses dari Komputer Lain (Jaringan Lokal)

1. Jalankan server pada semua antarmuka jaringan:

   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

2. Cari alamat IP komputer server:

   ```bash
   ipconfig
   ```

   Catat nilai **IPv4 Address**, misalnya `192.168.1.10`.

3. Izinkan port `8000` pada **Windows Defender Firewall** (Inbound Rules, New Rule, Port, TCP 8000).
4. Dari komputer lain di jaringan yang sama, buka `http://<alamat-ip-server>:8000`.

Komputer server harus menyala selama jam layanan. Aplikasi ini dirancang untuk jaringan lokal dan tidak dipublikasikan ke internet.

## 6. Persiapan Sebelum Dipakai Sungguhan

- Ubah `.env` menjadi `APP_ENV=production` dan `APP_DEBUG=false`, supaya pesan galat internal tidak tampil kepada pengguna.
- Ganti kata sandi akun `admin` dan `operator`.
- Isi **Profil Desa** (nama desa, kecamatan, kabupaten, alamat kantor, kode pos, telepon, nama kepala desa, dan kode desa).
- Periksa templat surat pada menu **Kelola Jenis Surat**. Unggah templat resmi desa bila perlu. Format templat adalah `.docx` dengan ukuran maksimal 2 MB, dan penandanya ditulis seperti `${nama}` atau `{{ nama }}`.
- Cetak satu surat percobaan untuk memastikan konversi PDF berjalan.
- Untuk pemakaian harian, disarankan memakai Apache (XAMPP) dengan `DocumentRoot` mengarah ke folder `public`, sehingga server tidak bergantung pada jendela terminal yang terbuka.

## 7. Pencadangan

Data yang perlu dicadangkan: basis data dan folder `storage/app` (templat dan PDF surat). Skrip bawaan melakukan keduanya:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\cadangan.ps1
```

Hasilnya disimpan di folder `cadangan` pada akar proyek:

- `lentera_desa_<waktu>.sql`: ekspor basis data
- `berkas_<waktu>.zip`: salinan templat dan PDF surat

Skrip menyimpan 14 pasang cadangan terbaru. Untuk menyimpan ke flashdisk atau komputer lain, gunakan parameter `-Tujuan`:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\cadangan.ps1 -Tujuan "E:\cadangan"
```

Folder `cadangan` berisi data warga dan sudah masuk `.gitignore`. Jangan mengunggahnya ke GitHub.

## 8. Pemecahan Masalah

| Gejala | Penyebab dan solusi |
| --- | --- |
| `SQLSTATE[HY000] [2002] ... actively refused it` saat `migrate` | MySQL belum berjalan. Klik **Start** pada MySQL di XAMPP Control Panel. |
| `Unknown database 'lentera_desa'` | Database belum dibuat. Ikuti [langkah 2.2](#22-buat-database). |
| `No application encryption key has been generated` | Jalankan `php artisan key:generate`. |
| Tampilan tanpa gaya (CSS kosong) | Jalankan `npm run build`. |
| `LibreOffice tidak ditemukan` | Periksa `LIBREOFFICE_PATH` di `.env`, lalu pastikan berkasnya ada. |
| PDF gagal dibuat atau lama | Naikkan `LIBREOFFICE_TIMEOUT`. Peluncuran LibreOffice pertama bisa lambat. Pada halaman detail surat, tekan **Buat Ulang PDF**. |
| `Bad Request: Size of a request header field exceeds server limit` pada `localhost` | Cookie `localhost` terlalu besar. Hapus cookie `localhost` di browser, atau gunakan jendela Incognito. |
| Komputer lain tidak dapat membuka aplikasi | Pastikan server dijalankan dengan `--host=0.0.0.0`, port 8000 diizinkan di firewall, dan kedua komputer berada di jaringan yang sama. |
| `could not find driver` | Ekstensi `pdo_mysql` belum aktif. Aktifkan di `php.ini` (hapus tanda `;` pada `extension=pdo_mysql`), lalu restart. |

Setelah mengubah `.env`, bila pengaturan tidak terbaca, jalankan:

```bash
php artisan config:clear
```
