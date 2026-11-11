<div align="center">

<img src="docs/img/logo.svg" alt="Logo Lentera Desa" width="96" height="96">

# Lentera Desa

**Layanan terpadu administrasi desa: surat bernomor otomatis, PDF siap cetak, dan arsip yang mudah dicari.**

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)
![Pengujian](https://img.shields.io/badge/pengujian-lulus-2EA44F)
![Status](https://img.shields.io/badge/status-finalisasi-2563C9)

</div>

## Tentang

Lentera Desa adalah aplikasi web lokal untuk perangkat desa. Aplikasi ini menggantikan pembuatan surat keterangan yang diketik ulang satu per satu dan diarsipkan di kertas: operator memilih warga dan jenis surat, sistem mengisi data, memberi nomor, dan menghasilkan PDF dari templat Word resmi desa. Seluruh surat tersimpan sebagai arsip digital yang dapat disaring dan direkap per bulan.

Pengguna aplikasi adalah **operator pelayanan** (sekretaris desa atau staf administrasi) dan **administrator** (pengelola sistem). Warga dilayani di kantor desa; datanya dimasukkan operator. Aplikasi berjalan di satu komputer server pada jaringan lokal desa, tanpa membutuhkan internet.

<!--
Tangkapan layar: simpan berkas PNG berukuran seragam di docs/img/, lalu hapus tanda komentar ini.

## Tangkapan Layar

| Login | Dasbor |
|---|---|
| ![Login](docs/img/login.png) | ![Dasbor](docs/img/dasbor.png) |

| Buat Surat | Detail Surat |
|---|---|
| ![Buat Surat](docs/img/buat-surat.png) | ![Detail Surat](docs/img/detail-surat.png) |

| Arsip Surat | Kelola Jenis Surat |
|---|---|
| ![Arsip Surat](docs/img/arsip-surat.png) | ![Kelola Jenis Surat](docs/img/kelola-jenis-surat.png) |
-->

## Fitur

### Operator

| Fitur                  | Keterangan                                                                                                                                          |
| ---------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| Data warga             | Tambah, ubah, hapus, dan cari berdasarkan NIK atau nama. Warga yang sudah memiliki surat tidak dapat dihapus.                                       |
| Impor warga dari Excel | Unggah berkas `.xlsx` berisi banyak warga. Setiap baris divalidasi seperti formulir; baris yang gagal dilaporkan beserta nomor baris dan alasannya. |
| Buat surat             | Cari warga, pilih jenis surat, isi keperluan. Nomor surat otomatis, berurutan per jenis dan tahun, contoh `012/SKD/DS/XI/2026`.                     |
| Detail dan PDF         | Pratinjau PDF, unduh kapan saja, buat ulang PDF bila diperlukan.                                                                                    |
| Ubah dan hapus surat   | Nomor tetap saat diubah dan PDF dibuat ulang. Menghapus surat ikut menghapus berkas PDF-nya.                                                        |
| Arsip                  | Saring menurut jenis surat, rentang tanggal, serta nama, NIK, atau nomor surat.                                                                     |
| Rekap layanan          | Jumlah surat per jenis untuk bulan dan tahun pilihan, dengan grafik batang dan tombol cetak.                                                        |
| Dasbor                 | Jumlah warga, surat bulan berjalan, jumlah jenis surat, dan lima surat terbaru.                                                                     |

### Administrator

| Fitur              | Keterangan                                                                                                                                                                    |
| ------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Kelola jenis surat | Tambah, ubah, dan hapus jenis surat beserta templat Word (`.docx`, maksimal 2 MB). Jenis yang sudah dipakai tidak dapat dihapus.                                              |
| Pratinjau templat  | Lihat hasil templat dengan data contoh sebelum disimpan, termasuk daftar penanda (`${nama}`, `${nik}`, dan seterusnya).                                                       |
| Kelola pengguna    | Tambah, ubah, dan hapus akun operator atau administrator. Administrator tidak dapat menghapus atau menurunkan peran akunnya sendiri.                                          |
| Profil desa        | Atur nama desa, kecamatan, kabupaten, alamat kantor, kode pos, telepon, nama kepala desa, dan kode desa untuk nomor surat. Datanya mengisi kop dan isi surat secara otomatis. |

## Cara Kerja

1. Operator membuka **Buat Surat** dan mencari warga berdasarkan NIK atau nama; data warga tampil otomatis.
2. Operator memilih jenis surat, mengisi keperluan, dan menyimpan.
3. Sistem memberi nomor surat dalam satu transaksi basis data sehingga tidak pernah ganda.
4. Sistem mengisi templat Word dengan data warga dan surat, lalu mengubahnya menjadi PDF.
5. Operator mengunduh PDF, mencetaknya, dan kepala desa menandatangani secara manual.

## Teknologi

| Komponen                    | Kegunaan                                                 |
| --------------------------- | -------------------------------------------------------- |
| Laravel 12 (PHP 8.2+)       | Kerangka aplikasi: MVC, Blade, Eloquent, migrasi, seeder |
| MySQL atau MariaDB          | Basis data `lentera_desa`                                |
| Tailwind CSS 4 dan Vite     | Tampilan dan pembangunan aset                            |
| PhpWord                     | Mengisi penanda pada templat Word                        |
| LibreOffice (mode headless) | Mengubah dokumen Word menjadi PDF                        |
| OpenSpout                   | Membaca dan membuat berkas Excel untuk impor warga       |
| PHPUnit                     | Pengujian otomatis                                       |

## Persyaratan

- PHP 8.2 atau lebih baru dengan ekstensi `pdo_mysql`, `mbstring`, `xml`, `zip`, dan `fileinfo`
- Composer 2
- Node.js 20 atau lebih baru dan npm
- MySQL atau MariaDB
- LibreOffice (untuk pembuatan PDF)

XAMPP dan Laragon sudah memuat PHP dan MySQL yang memadai.

## Instalasi

```bash
# 1. Unduh proyek dan pasang dependensi
git clone <alamat-repositori> lentera-desa
cd lentera-desa
composer install
npm install

# 2. Siapkan konfigurasi
cp .env.example .env
php artisan key:generate
```

Buka `.env` dan sesuaikan bagian berikut:

| Variabel                                    | Keterangan                                                                                          |
| ------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Basis data (buat basis data `lentera_desa` dengan _collation_ `utf8mb4_unicode_ci` terlebih dahulu) |
| `LIBREOFFICE_PATH`                          | Jalur `soffice.exe`, misalnya `C:\Program Files\LibreOffice\program\soffice.exe`                    |
| `DESA_NAMA`, `DESA_KEPALA`, `DESA_KODE`     | Nilai awal profil desa; setelah aplikasi berjalan, atur lewat menu **Profil Desa** (bukan `.env`)   |
| `SEED_PASSWORD`                             | Kata sandi awal akun bawaan (opsional; lihat bagian Akun Awal)                                      |

```bash
# 3. Buat tabel, data awal, dan aset tampilan
php artisan migrate --seed
npm run build

# 4. Jalankan
php artisan serve
```

Buka `http://127.0.0.1:8000`.

## Akun Awal

Seeder membuat dua akun, yaitu `admin` (administrator) dan `operator` (operator). Kata sandi awal ditentukan oleh variabel `SEED_PASSWORD` di `.env`; bila kosong, nilai bawaan pada [`database/seeders/UserSeeder.php`](database/seeders/UserSeeder.php) yang dipakai. **Ganti kata sandi kedua akun melalui menu Kelola Pengguna sebelum aplikasi dipakai sungguhan.**

Seeder juga membuat empat jenis surat (Domisili, Pengantar, Usaha, Tidak Mampu) beserta templat Word contoh pada `storage/app/templat`.

## Menjalankan di Jaringan Lokal

Agar dapat dibuka dari komputer lain di kantor desa, jalankan server pada semua antarmuka jaringan:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Lalu buka `http://<alamat-ip-komputer-server>:8000` dari komputer lain. Izinkan port tersebut pada _firewall_ Windows. Komputer server harus menyala selama jam layanan. Untuk pemakaian harian, disarankan memakai Apache (misalnya XAMPP) dengan `DocumentRoot` mengarah ke folder `public`, dengan `APP_ENV=production` dan `APP_DEBUG=false` pada `.env`.

## Templat Surat

Templat adalah berkas Word biasa dengan penanda berbentuk `${nama}` atau `{{ nama }}`; keduanya dikenali dan boleh dicampur dalam satu templat. Data surat dan warga:

`nomor_surat` `tanggal_surat` `nik` `nama` `tempat_lahir` `tanggal_lahir` `jenis_kelamin` `alamat` `rt` `rw` `pekerjaan` `status_perkawinan` `keperluan` `keterangan`

Data desa (dari menu **Profil Desa**):

`nama_desa` `nama_kecamatan` `nama_kabupaten` `alamat_kantor_desa` `kode_pos` `telepon_desa` `nama_kepala_desa`

Label berkurung yang lazim pada templat buatan sendiri, yaitu `[NAMA DESA]`, `[NAMA KECAMATAN]`, `[NAMA KABUPATEN]`, `[Alamat Kantor Desa]`, `[xxxxx]` (kode pos), `[Nomor Telepon]`, dan `[Nama Terang Kepala Desa]`, juga diisi otomatis bila datanya sudah ada di Profil Desa; bila belum, labelnya dibiarkan sehingga terlihat perlu dilengkapi.

Ketik tiap penanda sekaligus tanpa mengubah format di tengahnya. Penanda yang tidak dikenal dikosongkan (misalnya `{{ golongan_darah }}`, karena datanya tidak ada di aplikasi). Dengan LibreOffice terpasang, **Pratinjau** menampilkan PDF asli; tanpa itu, tampilan perkiraan. Gunakan **Pratinjau** pada halaman Kelola Jenis Surat untuk memeriksa hasilnya sebelum menyimpan.
