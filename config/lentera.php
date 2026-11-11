<?php

return [
    /*
     | Jalur soffice.exe (LibreOffice) untuk mengubah DOCX ke PDF.
     | Bila hanya "soffice", dicari lewat PATH sistem.
     */
    'libreoffice' => env('LIBREOFFICE_PATH', 'soffice'),

    // Batas waktu satu konversi PDF (detik); peluncuran pertama bisa lambat.
    'libreoffice_timeout' => (int) env('LIBREOFFICE_TIMEOUT', 60),

    // Data desa yang mengisi penanda ${nama_desa} dan ${nama_kepala_desa}; kode desa dipakai di nomor surat.
    'desa' => [
        'nama' => env('DESA_NAMA', 'Lentera'),
        'kepala' => env('DESA_KEPALA', 'Nama Kepala Desa'),
        'kode' => env('DESA_KODE', 'DS'),
    ],
];
