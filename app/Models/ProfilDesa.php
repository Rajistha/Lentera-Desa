<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class ProfilDesa extends Model
{
    protected $table = 'profil_desa';

    protected $fillable = [
        'nama_desa',
        'nama_kecamatan',
        'nama_kabupaten',
        'alamat_kantor',
        'kode_pos',
        'telepon',
        'nama_kepala_desa',
        'kode_desa',
    ];

    /**
     * Profil yang berlaku. Sebelum administrator menyimpannya, dipakai nilai awal dari
     * konfigurasi (DESA_* di .env) sehingga pemasangan baru tetap berjalan.
     */
    public static function saatIni(): static
    {
        try {
            return static::query()->first() ?? new static(static::bawaan());
        } catch (QueryException $e) {
            // Tabel belum dibuat (migrasi terlewat): jangan membuat halaman surat/rekap galat; pakai nilai awal.
            if (str_contains($e->getMessage(), 'profil_desa') && (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'no such table'))) {
                return new static(static::bawaan());
            }

            throw $e;
        }
    }

    /** @return array<string, string|null> */
    public static function bawaan(): array
    {
        return [
            'nama_desa' => config('lentera.desa.nama'),
            'nama_kecamatan' => null,
            'nama_kabupaten' => null,
            'alamat_kantor' => null,
            'kode_pos' => null,
            'telepon' => null,
            'nama_kepala_desa' => config('lentera.desa.kepala'),
            'kode_desa' => config('lentera.desa.kode'),
        ];
    }

    /**
     * Nilai untuk penanda templat surat.
     *
     * @return array<string, string>
     */
    public function untukTemplat(): array
    {
        return [
            'nama_desa' => (string) $this->nama_desa,
            'nama_kecamatan' => (string) $this->nama_kecamatan,
            'nama_kabupaten' => (string) $this->nama_kabupaten,
            'alamat_kantor_desa' => (string) $this->alamat_kantor,
            'kode_pos' => (string) $this->kode_pos,
            'telepon_desa' => (string) $this->telepon,
            'nama_kepala_desa' => (string) $this->nama_kepala_desa,
        ];
    }
}
