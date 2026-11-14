<?php

namespace Database\Seeders;

use App\Models\JenisSurat;
use App\Services\TemplatBawaan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class JenisSuratSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('local')->makeDirectory('templat');

        foreach (TemplatBawaan::JENIS as $jenis) {
            $path = 'templat/'.strtolower($jenis['kode']).'.docx';
            TemplatBawaan::tulis($jenis['kode'], Storage::disk('local')->path($path));

            JenisSurat::updateOrCreate(['kode' => $jenis['kode']], [
                'nama' => $jenis['nama'],
                'deskripsi' => $jenis['deskripsi'],
                'templat' => $path,
            ]);
        }
    }
}
