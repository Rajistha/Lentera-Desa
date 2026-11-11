<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use App\Rules\DokumenWord;
use App\Services\PdfGagal;
use App\Services\TemplatRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pratinjau templat dengan data contoh. Berkas yang dipratinjau disalin ke
 * folder sementara dan diakses lewat token acak, sehingga templat yang baru
 * dipilih di formulir bisa dilihat sebelum disimpan (KF-20).
 */
class PratinjauTemplatController extends Controller
{
    private const FOLDER = 'pratinjau';

    /** Pratinjau templat yang sudah tersimpan (dibuka dari daftar). */
    public function simpanan(JenisSurat $jenisSurat): RedirectResponse
    {
        return $this->pratinjauSimpanan($jenisSurat, 'daftar');
    }

    private function pratinjauSimpanan(JenisSurat $jenisSurat, string $asal): RedirectResponse
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($jenisSurat->templat)) {
            return back()->with('galat', 'Berkas templat tidak ditemukan. Unggah ulang templatnya.');
        }

        return $this->buatToken(
            $disk->path($jenisSurat->templat),
            $jenisSurat->kode,
            $jenisSurat->nama,
            basename($jenisSurat->templat),
            $jenisSurat->id,
            $asal,
        );
    }

    /** Pratinjau dari formulir: berkas yang baru dipilih, atau templat tersimpan bila kosong. */
    public function unggahan(Request $request): RedirectResponse
    {
        $request->validate([
            'berkas' => ['nullable', 'bail', 'file', 'extensions:docx', 'max:2048', new DokumenWord],
        ], [
            'berkas.file' => 'Berkas gagal diunggah.',
            'berkas.extensions' => 'Templat harus berekstensi .docx.',
            'berkas.max' => 'Ukuran templat maksimal 2 MB.',
        ]);

        $jenis = JenisSurat::find($request->input('jenis_id'));

        if ($request->hasFile('berkas')) {
            $berkas = $request->file('berkas');
            $kode = strtoupper(trim((string) $request->input('kode'))) ?: ($jenis?->kode ?? 'XXX');
            $nama = trim((string) $request->input('nama')) ?: ($jenis?->nama ?? 'Jenis surat baru');

            return $this->buatToken($berkas->getRealPath(), $kode, $nama, $berkas->getClientOriginalName(), $jenis?->id, 'formulir');
        }

        if ($jenis) {
            return $this->pratinjauSimpanan($jenis, 'formulir');
        }

        return back()->with('galat', 'Pilih berkas templat (.docx) untuk dipratinjau.');
    }

    public function tampil(string $token, TemplatRenderer $renderer): View|RedirectResponse
    {
        $info = $this->baca($token);
        if (! $info) {
            return redirect()->route('jenis-surat.index')
                ->with('galat', 'Pratinjau sudah kedaluwarsa. Buka kembali dari formulir.');
        }

        // Dengan LibreOffice, pratinjau adalah PDF asli (persis hasil akhir). Tanpa itu, tampilan HTML
        // perkiraan; bila pembaca HTML juga gagal, teks biasa. Halaman tidak boleh galat karena isi berkas.
        $bisaPdf = $renderer->libreOfficeTersedia();
        $html = $teks = null;

        if (! $bisaPdf) {
            $docx = $renderer->isi($info['jalur'], $renderer->dataContoh($info['kode']));
            try {
                $html = $renderer->keHtml($docx);
                $teks = $html === null ? $renderer->keTeks($docx) : null;
            } finally {
                File::deleteDirectory(dirname($docx));
            }

            // Satu halaman A4 (794 x 1123 px pada 96 dpi) dengan margin 2,54 cm.
            $kertas = '<style>html{background:#fff}body{box-sizing:border-box;width:794px;min-height:1123px;margin:0;padding:96px}</style>';
            $html = $html === null ? null : str_replace('</head>', $kertas.'</head>', $html);
        }

        return view('jenis-surat.pratinjau', [
            'token' => $token,
            'info' => $info,
            'html' => $html,
            'teks' => $teks,
            'bisaPdf' => $bisaPdf,
        ]);
    }

    /** Halaman pembungkus PDF untuk dibuka di tab baru (judul dan ikon tab mengikuti aplikasi). */
    public function lihat(string $token): View|RedirectResponse
    {
        $info = $this->baca($token);
        if (! $info) {
            return redirect()->route('jenis-surat.index')
                ->with('galat', 'Pratinjau sudah kedaluwarsa. Buka kembali dari formulir.');
        }

        return view('jenis-surat.pratinjau-tab', ['token' => $token, 'info' => $info]);
    }

    public function pdf(Request $request, string $token, TemplatRenderer $renderer): BinaryFileResponse|RedirectResponse|Response
    {
        $info = $this->baca($token);
        if (! $info) {
            return redirect()->route('jenis-surat.index')
                ->with('galat', 'Pratinjau sudah kedaluwarsa. Buka kembali dari formulir.');
        }

        $docx = $renderer->isi($info['jalur'], $renderer->dataContoh($info['kode']));

        try {
            $pdf = $renderer->kePdf($docx);
            // Pindahkan keluar dari folder yang akan dihapus; dihapus lagi setelah terkirim.
            $kirim = tempnam(sys_get_temp_dir(), 'pratinjau-');
            File::move($pdf, $kirim);
        } catch (PdfGagal $e) {
            // Dimuat di dalam bingkai halaman pratinjau: tampilkan pesan singkat, bukan mengalihkan
            // sehingga seluruh halaman tertanam di dalam bingkai.
            if ($request->boolean('sematan')) {
                return response('<!doctype html><meta charset="utf-8"><body style="font:13px sans-serif;color:#2b3445;padding:24px">'
                    .'<p><strong>PDF contoh belum dapat dibuat.</strong></p><p>'.e($e->getMessage()).'</p></body>');
            }

            return redirect()->route('jenis-surat.pratinjau.tampil', $token)->with('galat', $e->getMessage());
        } finally {
            File::deleteDirectory(dirname($docx));
        }

        return response()
            ->file($kirim, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="pratinjau-templat.pdf"'])
            ->deleteFileAfterSend();
    }

    private function buatToken(string $sumber, string $kode, string $nama, string $berkas, ?int $jenisId, string $asal): RedirectResponse
    {
        $disk = Storage::disk('local');
        $this->bersihkan();

        $token = Str::random(32);
        $disk->putFileAs(self::FOLDER, new \Illuminate\Http\File($sumber), $token.'.docx');
        $disk->put(self::FOLDER.'/'.$token.'.json', json_encode([
            'kode' => $kode, 'nama' => $nama, 'berkas' => $berkas, 'jenis_id' => $jenisId, 'asal' => $asal,
        ], JSON_UNESCAPED_UNICODE));

        return redirect()->route('jenis-surat.pratinjau.tampil', $token);
    }

    /** @return array{kode: string, nama: string, berkas: string, jenis_id: ?int, asal: string, jalur: string}|null */
    private function baca(string $token): ?array
    {
        $disk = Storage::disk('local');
        $json = self::FOLDER.'/'.$token.'.json';
        $docx = self::FOLDER.'/'.$token.'.docx';

        if (! $disk->exists($json) || ! $disk->exists($docx)) {
            return null;
        }

        return json_decode($disk->get($json), true) + ['jalur' => $disk->path($docx)];
    }

    /** Buang berkas pratinjau yang lebih dari 2 jam. */
    private function bersihkan(): void
    {
        $disk = Storage::disk('local');

        foreach ($disk->files(self::FOLDER) as $berkas) {
            if ($disk->lastModified($berkas) < now()->subHours(2)->getTimestamp()) {
                $disk->delete($berkas);
            }
        }
    }
}
