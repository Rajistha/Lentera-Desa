<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function formLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        if (! Auth::attempt($kredensial)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['password' => 'Username atau kata sandi salah.']);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->beranda($request->user()->role));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function beranda(string $peran): string
    {
        return $peran === 'administrator'
            ? route('jenis-surat.index')
            : route('dasbor');
    }
}
