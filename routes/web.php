<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HalamanController;
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
    });

    Route::middleware('peran:administrator')->group(function () {
        Route::get('/jenis-surat', [HalamanController::class, 'jenisSurat'])->name('jenis-surat.index');
        Route::get('/pengguna', [HalamanController::class, 'pengguna'])->name('pengguna.index');
    });
});
