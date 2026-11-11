<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Halaman sementara untuk langkah 2. Setiap halaman diganti
 * controller aslinya pada langkah masing-masing.
 */
class HalamanController extends Controller
{
    public function dasbor(): View
    {
        return view('halaman-sementara', [
            'judul' => 'Dasbor',
            'subjudul' => 'Ringkasan layanan surat desa',
        ]);
    }

    public function pengguna(): View
    {
        return view('halaman-sementara', [
            'judul' => 'Kelola Pengguna',
            'subjudul' => 'Halaman ini dikerjakan pada langkah 10',
        ]);
    }
}
