<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuratRequest;
use App\Models\JenisSurat;
use App\Models\Surat;
use App\Models\Warga;
use App\Services\LayananSurat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SuratController extends Controller
{
    public function create(Request $request): View
    {
        $terpilih = Warga::find(old('warga_id', $request->query('warga')));

        return view('surat.buat', [
            'jenisSurat' => JenisSurat::orderBy('nama')->get(),
            'terpilih' => $terpilih ? self::ringkasWarga($terpilih) : null,
        ]);
    }

    public function store(SuratRequest $request, LayananSurat $layanan): RedirectResponse
    {
        $surat = $layanan->buat($request->validated(), $request->user());
        $galatPdf = $layanan->buatPdf($surat);

        $redirect = redirect()->route('surat.show', $surat);

        // Surat tetap tersimpan dengan nomornya walau PDF gagal; PDF bisa dibuat ulang.
        return $galatPdf === null
            ? $redirect->with('sukses', "Surat {$surat->nomor_surat} berhasil dibuat.")
            : $redirect->with('galat', "Surat {$surat->nomor_surat} tersimpan, tetapi PDF belum dibuat: {$galatPdf}");
    }

    public function show(Surat $surat, LayananSurat $layanan): View
    {
        return view('surat.detail', [
            'surat' => $surat->load('jenisSurat', 'warga', 'user'),
            'pdfAda' => $layanan->jalurPdf($surat) !== null,
        ]);
    }

    /** Tampilkan PDF di peramban (untuk pratinjau di halaman detail). */
    public function pdf(Surat $surat, LayananSurat $layanan): BinaryFileResponse|RedirectResponse
    {
        return $this->kirimPdf($surat, $layanan, false);
    }

    /** Unduh PDF; dapat diulang kapan saja (KF-11). */
    public function unduh(Surat $surat, LayananSurat $layanan): BinaryFileResponse|RedirectResponse
    {
        return $this->kirimPdf($surat, $layanan, true);
    }

    /** Buat ulang PDF dari data surat dan templat saat ini; nomor tidak berubah. */
    public function buatUlangPdf(Surat $surat, LayananSurat $layanan): RedirectResponse
    {
        $galat = $layanan->buatPdf($surat);

        return redirect()->route('surat.show', $surat)->with(
            $galat === null ? 'sukses' : 'galat',
            $galat === null ? 'PDF berhasil dibuat ulang.' : "PDF gagal dibuat: {$galat}",
        );
    }

    private function kirimPdf(Surat $surat, LayananSurat $layanan, bool $unduh): BinaryFileResponse|RedirectResponse
    {
        $jalur = $layanan->jalurPdf($surat);

        if ($jalur === null) {
            return redirect()->route('surat.show', $surat)
                ->with('galat', 'PDF surat ini belum tersedia. Gunakan tombol Buat Ulang PDF.');
        }

        $nama = 'Surat-'.str_replace('/', '-', $surat->nomor_surat).'.pdf';

        return $unduh
            ? response()->download($jalur, $nama, ['Content-Type' => 'application/pdf'])
            : response()->file($jalur, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$nama.'"']);
    }

    /** Pencarian warga (NIK atau nama) untuk formulir buat surat. */
    public function cariWarga(Request $request): JsonResponse
    {
        $kata = trim((string) $request->query('q'));

        if (mb_strlen($kata) < 2) {
            return response()->json([]);
        }

        $like = '%'.addcslashes($kata, '%_\\').'%';

        return response()->json(
            Warga::where(fn ($q) => $q->where('nik', 'like', $like)->orWhere('nama', 'like', $like))
                ->orderBy('nama')
                ->limit(8)
                ->get()
                ->map(fn (Warga $w) => self::ringkasWarga($w))
        );
    }

    private static function ringkasWarga(Warga $w): array
    {
        return [
            'id' => $w->id,
            'nik' => $w->nik,
            'nama' => $w->nama,
            'ttl' => $w->tempat_lahir.', '.$w->tanggal_lahir->copy()->locale('id')->translatedFormat('d F Y'),
            'jenis_kelamin' => Warga::JENIS_KELAMIN[$w->jenis_kelamin] ?? '',
            'alamat' => $w->alamat.', RT '.$w->rt.'/RW '.$w->rw,
            'pekerjaan' => $w->pekerjaan ?: '-',
            'status' => Warga::STATUS_PERKAWINAN[$w->status_perkawinan] ?? '',
        ];
    }
}
