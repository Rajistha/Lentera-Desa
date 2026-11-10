<div align="center">

# 🏮 Lentera Desa

**Layanan Terpadu Administrasi Desa**

Aplikasi web lokal untuk membuat surat desa dari templat Word: data warga terisi otomatis, nomor surat otomatis, dan siap dicetak sebagai PDF.

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white)
![Status](https://img.shields.io/badge/status-dalam%20pengembangan-2563C9)

[Fitur](#-fitur) · [Tangkapan Layar](#-tangkapan-layar) · [Instalasi](#-instalasi) · [Status](#-status-pengembangan)

</div>

---

## 📌 Tentang
Perangkat desa sering mengetik ulang data warga untuk setiap surat, mencatat nomor surat secara manual, dan menyimpan arsip di kertas. Lentera Desa menyatukan semuanya dalam satu aplikasi yang berjalan di komputer kantor desa, tanpa internet.

## ✨ Fitur
| Peran | Fitur |
|---|---|
| **Operator** | Kelola data warga · Buat surat dengan data terisi otomatis · Nomor surat otomatis · Unduh dan cetak ulang PDF · Arsip dengan filter · Rekap bulanan |
| **Administrator** | Kelola jenis surat dan unggah templat Word (.docx) · Pratinjau templat · Kelola akun pengguna |

## 🔄 Cara Kerja
1. Operator mencari warga berdasarkan NIK atau nama.
2. Memilih jenis surat dan mengisi keperluan.
3. Sistem memberi nomor surat otomatis, mengisi templat Word, dan membuat PDF.
4. Operator mencetak surat untuk ditandatangani kepala desa.
5. Surat tersimpan di arsip dan masuk ke rekap bulanan.

## 🖼️ Tangkapan Layar
| Login | Dasbor |
|---|---|
| ![Login](docs/img/login.png) | ![Dasbor](docs/img/dasbor.png) |

| Buat Surat | Arsip Surat |
|---|---|
| ![Buat Surat](docs/img/buat-surat.png) | ![Arsip](docs/img/arsip.png) |

## 🧰 Teknologi
- **Laravel** (pola MVC, Blade, Eloquent)
- **MySQL** — basis data `lentera_desa`
- **PhpWord** — mengisi penanda pada templat Word
- **LibreOffice** (mode headless) — mengubah Word menjadi PDF

## ⚙️ Instalasi
**Persyaratan:** PHP, Composer, MySQL, dan LibreOffice terpasang di komputer server (Laragon atau XAMPP direkomendasikan).

```bash
git clone <alamat-repositori>
cd lentera-desa
composer install
cp .env.example .env
php artisan key:generate
```

Buat basis data `lentera_desa`, lalu atur `.env`:

```env
DB_DATABASE=lentera_desa
LIBREOFFICE_PATH="C:\Program Files\LibreOffice\program\soffice.exe"
```

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

Buka `http://localhost:8000`. Dari komputer lain di jaringan kantor, gunakan `http://IP-server:8000`.
Akun awal dibuat oleh seeder (lihat `database/seeders`).

## 🚧 Status Pengembangan
- [ ] Setup proyek
- [ ] Database (migrasi, model, seeder)
- [ ] Login, hak akses, tata letak
- [ ] Data warga
- [ ] Jenis surat dan unggah templat
- [ ] Pengisi templat, PDF, pratinjau
- [ ] Buat surat dan penomoran
- [ ] Detail surat
- [ ] Ubah dan hapus surat
- [ ] Arsip dan filter
- [ ] Kelola pengguna
- [ ] Dasbor dan rekap
- [ ] Pengujian dan finalisasi

## 👤 Penulis
**Brillian Sondang Siholasi Tamba** · 255150707111056
Fakultas Ilmu Komputer, Universitas Brawijaya · Dosen pengampu: Novia Ratnasari
