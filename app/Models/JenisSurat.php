<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisSurat extends Model
{
    protected $table = 'jenis_surat';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'templat',
    ];

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class);
    }
}
