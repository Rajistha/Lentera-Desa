<?php

namespace App\Services;

/** Daftar penanda `${...}` yang dikenali templat surat (PRD bagian 10). */
class Penanda
{
    public const DAFTAR = [
        'nomor_surat' => 'Nomor surat',
        'tanggal_surat' => 'Tanggal surat',
        'nik' => 'NIK warga',
        'nama' => 'Nama warga',
        'tempat_lahir' => 'Tempat lahir',
        'tanggal_lahir' => 'Tanggal lahir',
        'jenis_kelamin' => 'Jenis kelamin',
        'alamat' => 'Alamat',
        'rt' => 'RT',
        'rw' => 'RW',
        'pekerjaan' => 'Pekerjaan',
        'status_perkawinan' => 'Status perkawinan',
        'keperluan' => 'Keperluan',
        'keterangan' => 'Keterangan tambahan',
        'nama_desa' => 'Nama desa',
        'nama_kecamatan' => 'Nama kecamatan',
        'nama_kabupaten' => 'Nama kabupaten',
        'alamat_kantor_desa' => 'Alamat kantor desa',
        'kode_pos' => 'Kode pos',
        'telepon_desa' => 'Telepon kantor desa',
        'nama_kepala_desa' => 'Nama kepala desa',
    ];
}
