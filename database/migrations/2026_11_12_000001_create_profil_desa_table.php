<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris saja: profil desa yang mengisi kop dan penanda pada templat surat.
        Schema::create('profil_desa', function (Blueprint $table) {
            $table->id();
            $table->string('nama_desa', 100);
            $table->string('nama_kecamatan', 100)->nullable();
            $table->string('nama_kabupaten', 100)->nullable();
            $table->string('alamat_kantor')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('telepon', 30)->nullable();
            $table->string('nama_kepala_desa', 100);
            $table->string('kode_desa', 10)->default('DS'); // dipakai pada nomor surat, mis. 012/SKD/DS/XI/2026
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_desa');
    }
};
