<?php

namespace Database\Seeders;

use App\Models\ProfilDesa;
use Illuminate\Database\Seeder;

class ProfilDesaSeeder extends Seeder
{
    /** Membuat profil desa dari nilai awal (DESA_* di .env) hanya bila belum ada; tidak menimpa isian administrator. */
    public function run(): void
    {
        if (! ProfilDesa::query()->exists()) {
            ProfilDesa::create(ProfilDesa::bawaan());
        }
    }
}
