<?php

namespace App\Http\Controllers;

use App\Services\ImporWarga;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImporWargaController extends Controller
{
    public function form(): View
    {
        return view('warga.impor', ['maksBaris' => ImporWarga::MAKS_BARIS]);
    }

    public function templat(ImporWarga $impor): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'templat-warga-');
        $impor->buatTemplat($path);

        return response()
            ->download($path, 'template-impor-warga.xlsx')
            ->deleteFileAfterSend();
    }

    public function proses(Request $request, ImporWarga $impor): RedirectResponse
    {
        $request->validate([
            'berkas' => ['required', 'file', 'extensions:xlsx', 'mimes:xlsx', 'max:2048'],
        ], [
            'berkas.required' => 'Pilih berkas Excel (.xlsx) terlebih dahulu.',
            'berkas.file' => 'Berkas gagal diunggah.',
            'berkas.extensions' => 'Berkas harus berekstensi .xlsx.',
            'berkas.mimes' => 'Berkas harus berformat Excel (.xlsx).',
            'berkas.max' => 'Ukuran berkas maksimal 2 MB.',
        ]);

        try {
            $laporan = $impor->proses($request->file('berkas')->getRealPath());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['berkas' => $e->getMessage()]);
        } catch (IOException) {
            return back()->withErrors(['berkas' => 'Berkas tidak dapat dibaca. Pastikan berkasnya valid.']);
        }

        return redirect()->route('warga.impor')->with('laporan_impor', $laporan);
    }
}
