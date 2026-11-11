<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilDesaRequest;
use App\Models\ProfilDesa;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfilDesaController extends Controller
{
    public function index(): View
    {
        return view('profil-desa.index', ['profil' => ProfilDesa::saatIni()]);
    }

    public function update(ProfilDesaRequest $request): RedirectResponse
    {
        $profil = ProfilDesa::query()->first() ?? new ProfilDesa;
        $profil->fill($request->validated())->save();

        return redirect()->route('profil-desa.index')->with('sukses', 'Profil desa berhasil disimpan.');
    }
}
