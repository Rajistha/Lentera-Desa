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

    /** Dua huruf awal nama untuk avatar; "?" bila warganya tidak ada. */
    public function getInisialAttribute(): string
    {
        if (! $this->exists) {
            return '?';
        }

        return collect(preg_split('/\s+/', trim((string) $this->nama)))
            ->take(2)
            ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
            ->implode('');
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class);
    }
}
