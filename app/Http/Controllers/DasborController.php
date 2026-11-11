<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use App\Models\Surat;
use App\Models\Warga;
use Illuminate\View\View;

class DasborController extends Controller
{
    /** Ringkasan layanan: jumlah warga, surat bulan berjalan, jenis surat, dan 5 surat terbaru (KF-19). */
    public function index(): View
    {
        $awal = now()->startOfMonth();

        return view('dasbor', [
            'jumlahWarga' => Warga::count(),
            'suratBulanIni' => Surat::whereDate('tanggal_surat', '>=', $awal->toDateString())
                ->whereDate('tanggal_surat', '<=', $awal->copy()->endOfMonth()->toDateString())
                ->count(),
            'jumlahJenis' => JenisSurat::count(),
            'terbaru' => Surat::with('warga:id,nama', 'jenisSurat:id,nama')->latest('id')->limit(5)->get(),
        ]);
    }
}
