<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;

class TemplatBawaan
{
    public const JENIS = [
        ['kode' => 'SKD', 'nama' => 'Surat Keterangan Domisili', 'deskripsi' => 'Menerangkan tempat tinggal warga', 'isi' => 'Berdasarkan data yang ada, yang bersangkutan benar bertempat tinggal di alamat tersebut di atas dan berdomisili di desa ini.'],
        ['kode' => 'SKP', 'nama' => 'Surat Pengantar', 'deskripsi' => 'Pengantar warga untuk urusan di instansi lain', 'isi' => 'Surat pengantar ini diberikan kepada yang bersangkutan untuk digunakan sebagai kelengkapan administrasi sesuai keperluan tersebut di atas.'],
        ['kode' => 'SKU', 'nama' => 'Surat Keterangan Usaha', 'deskripsi' => 'Menerangkan warga memiliki usaha', 'isi' => 'Yang bersangkutan adalah benar warga desa ini yang memiliki usaha dan berdomisili di desa ini.'],
        ['kode' => 'SKTM', 'nama' => 'Surat Keterangan Tidak Mampu', 'deskripsi' => 'Menerangkan warga berasal dari keluarga tidak mampu', 'isi' => 'Yang bersangkutan adalah benar warga desa ini yang tergolong keluarga tidak mampu.'],
    ];

    public static function punya(string $kode): bool
    {
        return self::cari($kode) !== null;
    }

    /**
     * Tulis templat bawaan ke $berkas (path absolut). Kode bawaan memakai templat khususnya;
     * kode lain memakai templat umum dengan judul $nama.
     */
    public static function tulis(string $kode, string $berkas, ?string $nama = null): void
    {
        $jenis = self::cari($kode);

        self::buat(
            $berkas,
            $jenis['nama'] ?? ($nama ?: 'Surat Keterangan'),
            $jenis['isi'] ?? 'Yang bersangkutan adalah benar warga desa ini dan surat ini diberikan sesuai keperluan tersebut di atas.',
        );
    }

    private static function cari(string $kode): ?array
    {
        foreach (self::JENIS as $jenis) {
            if (strcasecmp($jenis['kode'], $kode) === 0) {
                return $jenis;
            }
        }

        return null;
    }

    private static function buat(string $berkas, string $judul, string $isi): void
    {
        $word = new PhpWord;
        $word->getSettings()->setThemeFontLang(new Language('id-ID'));
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(10);
        $word->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);

        $bagian = $word->addSection(['marginLeft' => 1134, 'marginRight' => 1134, 'marginTop' => 1134, 'marginBottom' => 1134]);
        $tengah = ['alignment' => 'center'];
        $kanan = ['alignment' => 'right'];

        // Kop surat
        $bagian->addText('PEMERINTAH ${nama_kabupaten}', ['bold' => true, 'size' => 11], $tengah);
        $bagian->addText('KECAMATAN ${nama_kecamatan}', ['bold' => true, 'size' => 11], $tengah);
        $bagian->addText('DESA/KELURAHAN ${nama_desa}', ['bold' => true, 'size' => 14], $tengah);
        $bagian->addText('Alamat: ${alamat_kantor_desa}, Kode Pos ${kode_pos} |', ['italic' => true, 'size' => 7], $tengah);
        $bagian->addText('Telp: ${telepon_desa}', ['italic' => true, 'size' => 7], [
            'alignment' => 'center', 'spaceAfter' => 60,
            'borderBottomSize' => 12, 'borderBottomColor' => '000000', 'borderBottomStyle' => 'single',
        ]);
        $bagian->addTextBreak(1, ['size' => 6]);

        // Judul dan nomor
        $bagian->addText(strtoupper($judul), ['bold' => true, 'underline' => 'single', 'size' => 11], $tengah);
        $bagian->addText('Nomor: ${nomor_surat}', [], ['alignment' => 'center', 'spaceAfter' => 240]);

        $bagian->addText(
            'Yang bertanda tangan di bawah ini, Kepala Desa/Lurah ${nama_desa}, Kecamatan ${nama_kecamatan}, '
            .'${nama_kabupaten}, dengan ini memberikan '.strtolower($judul).' bagi warga dengan data sebagai berikut:',
            [],
            ['alignment' => 'both', 'spaceAfter' => 160]
        );

        $kolom = [
            'Nama' => '${nama}',
            'NIK' => '${nik}',
            'Tempat/Tanggal Lahir' => '${tempat_lahir}, ${tanggal_lahir}',
            'Jenis Kelamin' => '${jenis_kelamin}',
            'Status Perkawinan' => '${status_perkawinan}',
            'Pekerjaan' => '${pekerjaan}',
            'Alamat' => '${alamat}',
        ];

        $tabel = $bagian->addTable();
        foreach ($kolom as $label => $nilai) {
            $tabel->addRow();
            $tabel->addCell(2900)->addText($label);
            $tabel->addCell(300)->addText(':');
            $tabel->addCell(6000)->addText($nilai);
        }

        $bagian->addText($isi, [], ['alignment' => 'both', 'spaceBefore' => 240, 'spaceAfter' => 160]);
        $bagian->addText('Surat ini dibuat untuk keperluan: ${keperluan}', [], ['spaceAfter' => 60]);
        $bagian->addText('${keterangan}', [], ['spaceAfter' => 160]);
        $bagian->addText(
            'Demikian surat ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.',
            [],
            ['alignment' => 'both', 'spaceAfter' => 360]
        );

        // Tanda tangan
        $bagian->addText('${nama_desa}, ${tanggal_surat}', [], $kanan);
        $bagian->addText('Kepala Desa/Lurah ${nama_desa},', [], $kanan);
        $bagian->addTextBreak(3);
        $bagian->addText('(                                                  )', [], $kanan);
        $bagian->addText('${nama_kepala_desa}', ['size' => 8], $kanan);

        IOFactory::createWriter($word, 'Word2007')->save($berkas);
    }
}
