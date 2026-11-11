<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use App\Models\Surat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekapController extends Controller
{
    public const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /** Rekap bulanan jumlah surat per jenis (KF-18); dasar bulan adalah tanggal surat. */
    public function index(Request $request): View
    {
        $sekarang = now();

        // Dari tahun surat tertua sampai terbaru (dan tahun berjalan); tanpa fungsi SQL khusus MySQL.
        $tahunTersedia = collect([
            $sekarang->year,
            (int) substr((string) Surat::min('tanggal_surat'), 0, 4),
            (int) substr((string) Surat::max('tanggal_surat'), 0, 4),
        ])->filter();
        $tahunTersedia = collect(range($tahunTersedia->min(), $tahunTersedia->max()))->sortDesc()->values();

        // Nilai di luar daftar (mis. URL diubah manual) kembali ke bulan/tahun berjalan.
        $bulan = (int) $request->query('bulan', $sekarang->month);
        $tahun = (int) $request->query('tahun', $sekarang->year);
        if (! isset(self::BULAN[$bulan])) {
            $bulan = $sekarang->month;
        }
        if (! $tahunTersedia->contains($tahun)) {
            $tahun = $sekarang->year;
        }

        $awal = Carbon::create($tahun, $bulan, 1);
        $jumlah = Surat::query()
            ->whereDate('tanggal_surat', '>=', $awal->toDateString())
            ->whereDate('tanggal_surat', '<=', $awal->copy()->endOfMonth()->toDateString())
            ->selectRaw('jenis_surat_id, COUNT(*) as jumlah')
            ->groupBy('jenis_surat_id')
            ->pluck('jumlah', 'jenis_surat_id');

        // Semua jenis ditampilkan (termasuk yang 0), terbanyak di atas, lalu urut nama.
        $baris = JenisSurat::orderBy('nama')->get()
            ->map(fn (JenisSurat $j) => ['nama' => $j->nama, 'jumlah' => (int) ($jumlah[$j->id] ?? 0)])
            ->sortByDesc('jumlah')->values();

        return view('rekap', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'opsiBulan' => self::BULAN,
            'opsiTahun' => $tahunTersedia->mapWithKeys(fn ($t) => [$t => (string) $t])->all(),
            'baris' => $baris,
            'total' => $baris->sum('jumlah'),
            'periode' => self::BULAN[$bulan].' '.$tahun,
        ]);
    }
}
