<?php

namespace App\Http\Controllers;

use App\Http\Requests\JenisSuratRequest;
use App\Models\JenisSurat;
use App\Services\TemplatBawaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class JenisSuratController extends Controller
{
    public function index(): View
    {
        return $this->tampilkan(new JenisSurat);
    }

    public function edit(JenisSurat $jenisSurat): View
    {
        return $this->tampilkan($jenisSurat);
    }

    public function store(JenisSuratRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $path = $this->pathTemplat($data['kode']);

        if ($request->hasFile('berkas')) {
            Storage::disk('local')->putFileAs('templat', $request->file('berkas'), basename($path));
        } else {
            Storage::disk('local')->makeDirectory('templat');
            TemplatBawaan::tulis($data['kode'], Storage::disk('local')->path($path), $data['nama']);
        }

        JenisSurat::create([
            'kode' => $data['kode'],
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'templat' => $path,
        ]);

        return redirect()->route('jenis-surat.index')->with('sukses', 'Jenis surat berhasil ditambahkan.');
    }

    public function update(JenisSuratRequest $request, JenisSurat $jenisSurat): RedirectResponse
    {
        $data = $request->validated();
        $disk = Storage::disk('local');
        $pathBaru = $this->pathTemplat($data['kode']);
        $pathLama = $jenisSurat->templat;

        if ($request->hasFile('berkas')) {
            $disk->putFileAs('templat', $request->file('berkas'), basename($pathBaru));
            if ($pathLama !== $pathBaru) {
                $disk->delete($pathLama);
            }
        } elseif ($pathLama !== $pathBaru && $disk->exists($pathLama)) {
            // Kode berubah tanpa templat baru: nama berkas mengikuti kode.
            $disk->delete($pathBaru);
            $disk->move($pathLama, $pathBaru);
        }

        $jenisSurat->update([
            'kode' => $data['kode'],
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'templat' => $pathBaru,
        ]);

        return redirect()->route('jenis-surat.index')->with('sukses', 'Jenis surat berhasil diperbarui.');
    }

    public function kembalikan(JenisSurat $jenisSurat): RedirectResponse
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory('templat');

        TemplatBawaan::tulis($jenisSurat->kode, $disk->path($jenisSurat->templat), $jenisSurat->nama);

        return redirect()->route('jenis-surat.edit', $jenisSurat)
            ->with('sukses', 'Template dikembalikan ke bawaan.');
    }

    public function destroy(JenisSurat $jenisSurat): RedirectResponse
    {
        if ($jenisSurat->surat()->exists()) {
            return redirect()->route('jenis-surat.index')
                ->with('galat', "{$jenisSurat->nama} tidak dapat dihapus karena sudah dipakai pada surat.");
        }

        Storage::disk('local')->delete($jenisSurat->templat);
        $jenisSurat->delete();

        return redirect()->route('jenis-surat.index')->with('sukses', 'Jenis surat berhasil dihapus.');
    }

    private function pathTemplat(string $kode): string
    {
        return 'templat/'.strtolower($kode).'.docx';
    }

    private function tampilkan(JenisSurat $jenis): View
    {
        $disk = Storage::disk('local');

        return view('jenis-surat.index', [
            'daftar' => JenisSurat::orderBy('kode')->get(),
            'jenis' => $jenis,
            'ukuranTemplat' => $jenis->exists && $disk->exists($jenis->templat) ? $disk->size($jenis->templat) : null,
        ]);
    }
}
