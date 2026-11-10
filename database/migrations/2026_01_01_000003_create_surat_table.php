<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_surat_id')->constrained('jenis_surat')->restrictOnDelete();
            $table->foreignId('warga_id')->constrained('warga')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('nomor_urut');
            $table->unsignedSmallInteger('tahun');
            $table->string('nomor_surat');
            $table->string('keperluan', 255);
            $table->text('keterangan')->nullable();
            $table->date('tanggal_surat');
            $table->string('berkas_pdf')->nullable();
            $table->timestamps(); 

            $table->unique(['jenis_surat_id', 'tahun', 'nomor_urut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat');
    }
};