<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HalamanController;
use App\Http\Controllers\ImporWargaController;
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
        Route::get('/dasbor', [HalamanController::class, 'dasbor'])->name('dasbor');
        Route::get('/warga/impor', [ImporWargaController::class, 'form'])->name('warga.impor');
        Route::get('/warga/impor/templat', [ImporWargaController::class, 'templat'])->name('warga.impor.templat');
        Route::post('/warga/impor', [ImporWargaController::class, 'proses'])->name('warga.impor.proses');
        Route::resource('warga', WargaController::class)->except('show');
    });

    Route::middleware('peran:administrator')->group(function () {
        Route::get('/jenis-surat', [HalamanController::class, 'jenisSurat'])->name('jenis-surat.index');
        Route::get('/pengguna', [HalamanController::class, 'pengguna'])->name('pengguna.index');
    });
});
