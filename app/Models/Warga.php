<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warga extends Model
{
    public const JENIS_KELAMIN = [
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
    ];

    public const STATUS_PERKAWINAN = [
        'belum_kawin' => 'Belum kawin',
        'kawin' => 'Kawin',
        'cerai_hidup' => 'Cerai hidup',
        'cerai_mati' => 'Cerai mati',
    ];

    protected $table = 'warga';

    protected $fillable = [
        'nik',
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'rt',
        'rw',
        'pekerjaan',
        'status_perkawinan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class);
    }
}
