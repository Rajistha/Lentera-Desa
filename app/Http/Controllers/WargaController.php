<?php

namespace App\Http\Controllers;

use App\Http\Requests\WargaRequest;
use App\Models\Warga;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WargaController extends Controller
{
    public function index(Request $request): View
    {
        $kata = trim((string) $request->query('q'));
        $like = '%'.addcslashes($kata, '%_\\').'%';

        $daftar = Warga::query()
            ->when($kata !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('nik', 'like', $like)->orWhere('nama', 'like', $like)
            ))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('warga.index', ['daftar' => $daftar, 'kata' => $kata]);
    }

    public function create(): View
    {
        return view('warga.form', ['warga' => new Warga]);
    }

    public function store(WargaRequest $request): RedirectResponse
    {
        Warga::create($request->validated());

        return redirect()->route('warga.index')->with('sukses', 'Data warga berhasil ditambahkan.');
    }

    public function edit(Warga $warga): View
    {
        return view('warga.form', ['warga' => $warga]);
    }

    public function update(WargaRequest $request, Warga $warga): RedirectResponse
    {
        $warga->update($request->validated());

        return redirect()->route('warga.index')->with('sukses', 'Data warga berhasil diperbarui.');
    }

    public function destroy(Warga $warga): RedirectResponse
    {
        if ($warga->surat()->exists()) {
            return back()->with('galat', "{$warga->nama} tidak dapat dihapus karena sudah memiliki surat.");
        }

        $warga->delete();

        return redirect()->route('warga.index')->with('sukses', 'Data warga berhasil dihapus.');
    }
}
