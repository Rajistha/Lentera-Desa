<?php

namespace App\Http\Controllers;

use App\Http\Requests\PenggunaRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function index(): View
    {
        return $this->tampilkan(new User);
    }

    public function edit(User $pengguna): View
    {
        return $this->tampilkan($pengguna);
    }

    public function store(PenggunaRequest $request): RedirectResponse
    {
        // Model meng-hash kata sandi lewat cast "hashed".
        User::create($request->validated());

        return redirect()->route('pengguna.index')->with('sukses', 'Pengguna berhasil ditambahkan.');
    }

    public function update(PenggunaRequest $request, User $pengguna): RedirectResponse
    {
        $data = $request->validated();

        // Administrator tidak boleh menurunkan perannya sendiri agar sistem tidak kehilangan pengelola.
        if ($pengguna->is($request->user()) && $data['role'] !== $pengguna->role) {
            return back()->withInput()->withErrors(['role' => 'Anda tidak dapat mengubah peran akun Anda sendiri.']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $pengguna->update($data);

        return redirect()->route('pengguna.index')->with('sukses', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(User $pengguna): RedirectResponse
    {
        if ($pengguna->is(auth()->user())) {
            return redirect()->route('pengguna.index')->with('galat', 'Anda tidak dapat menghapus akun yang sedang Anda pakai.');
        }

        if ($pengguna->surat()->exists()) {
            return redirect()->route('pengguna.index')
                ->with('galat', "{$pengguna->name} tidak dapat dihapus karena sudah membuat surat.");
        }

        $pengguna->delete();

        return redirect()->route('pengguna.index')->with('sukses', 'Pengguna berhasil dihapus.');
    }

    private function tampilkan(User $pengguna): View
    {
        return view('pengguna.index', [
            'daftar' => User::orderBy('role')->orderBy('username')->get(),
            'pengguna' => $pengguna,
        ]);
    }
}
