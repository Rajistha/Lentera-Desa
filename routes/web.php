<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DasborController;
use App\Http\Controllers\ImporWargaController;
use App\Http\Controllers\JenisSuratController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PratinjauTemplatController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\WargaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'formLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect()->route(
        auth()->user()->role === 'administrator' ? 'jenis-surat.index' : 'dasbor'
    ));

    Route::middleware('peran:operator')->group(function () {
        Route::get('/dasbor', [DasborController::class, 'index'])->name('dasbor');
        Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');
        Route::get('/warga/impor', [ImporWargaController::class, 'form'])->name('warga.impor');
        Route::get('/warga/impor/templat', [ImporWargaController::class, 'templat'])->name('warga.impor.templat');
        Route::post('/warga/impor', [ImporWargaController::class, 'proses'])->name('warga.impor.proses');
        Route::resource('warga', WargaController::class)->except('show');

        Route::get('/surat', [SuratController::class, 'index'])->name('surat.index');
        Route::get('/surat/buat', [SuratController::class, 'create'])->name('surat.create');
        Route::get('/surat/cari-warga', [SuratController::class, 'cariWarga'])->name('surat.cari-warga');
        Route::post('/surat', [SuratController::class, 'store'])->name('surat.store');
        Route::get('/surat/{surat}', [SuratController::class, 'show'])->name('surat.show');
        Route::get('/surat/{surat}/ubah', [SuratController::class, 'edit'])->name('surat.edit');
        Route::put('/surat/{surat}', [SuratController::class, 'update'])->name('surat.update');
        Route::delete('/surat/{surat}', [SuratController::class, 'destroy'])->name('surat.destroy');
        Route::get('/surat/{surat}/pdf', [SuratController::class, 'pdf'])->name('surat.pdf');
        Route::get('/surat/{surat}/unduh', [SuratController::class, 'unduh'])->name('surat.unduh');
        Route::post('/surat/{surat}/buat-ulang-pdf', [SuratController::class, 'buatUlangPdf'])->name('surat.buat-ulang-pdf');
    });

    Route::middleware('peran:administrator')->group(function () {
        Route::get('/jenis-surat/{jenisSurat}/pratinjau', [PratinjauTemplatController::class, 'simpanan'])
            ->name('jenis-surat.pratinjau.simpanan');
        // PUT diterima juga karena formulir ubah memakai method spoofing.
        Route::match(['post', 'put'], '/jenis-surat/pratinjau', [PratinjauTemplatController::class, 'unggahan'])
            ->name('jenis-surat.pratinjau.unggahan');
        Route::get('/jenis-surat/pratinjau/{token}', [PratinjauTemplatController::class, 'tampil'])
            ->where('token', '[A-Za-z0-9]{32}')->name('jenis-surat.pratinjau.tampil');
        Route::get('/jenis-surat/pratinjau/{token}/pdf', [PratinjauTemplatController::class, 'pdf'])
            ->where('token', '[A-Za-z0-9]{32}')->name('jenis-surat.pratinjau.pdf');
        Route::resource('jenis-surat', JenisSuratController::class)
            ->except(['show', 'create'])
            ->parameters(['jenis-surat' => 'jenisSurat']);
        Route::resource('pengguna', PenggunaController::class)->except(['show', 'create']);
    });
});
