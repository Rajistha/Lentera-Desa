<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Surat extends Model
{
    protected $table = 'surat';

    protected $fillable = [
        'jenis_surat_id',
        'warga_id',
        'user_id',
        'nomor_urut',
        'tahun',
        'nomor_surat',
        'keperluan',
        'keterangan',
        'tanggal_surat',
        'berkas_pdf',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
        ];
    }

    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class);
    }

    /**
     * Warga pada surat. Kunci asing RESTRICT mencegah warga yang punya surat dihapus, tetapi data dapat
     * dikosongkan langsung di basis data (mis. TRUNCATE di phpMyAdmin dengan pemeriksaan kunci dimatikan).
     * Agar halaman tidak galat, warga yang hilang diganti objek kosong bertanda (->exists === false).
     */
    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class)->withDefault(fn (Warga $warga) => $warga->forceFill([
            'nama' => 'Data tidak ditemukan',
            'nik' => '-',
            'alamat' => '-',
            'rt' => '-',
            'rw' => '-',
        ]));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
