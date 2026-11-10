<?php

namespace Database\Seeders;

use App\Models\JenisSurat;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Seeder;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\IOFactory;

class JenisSuratSeeder extends Seeder
{
    private const JENIS = [
        ['kode' => 'SKD', 'nama' => 'Surat Keterangan Domisili', 'deskripsi' => 'Menerangkan tempat tinggal warga', 'isi' => 'bertempat tinggal di alamat tersebut di atas dan berdomisili di desa ini.'],
        ['kode' => 'SKP', 'nama' => 'Surat Pengantar', 'deskripsi' => 'Pengantar warga untuk urusan di instansi lain', 'isi' => 'adalah benar warga desa ini dan bermaksud mengurus keperluan di instansi terkait.'],
        ['kode' => 'SKU', 'nama' => 'Surat Keterangan Usaha', 'deskripsi' => 'Menerangkan warga memiliki usaha', 'isi' => 'adalah benar warga desa ini yang memiliki usaha dan berdomisili di desa ini.'],
        ['kode' => 'SKTM', 'nama' => 'Surat Keterangan Tidak Mampu', 'deskripsi' => 'Menerangkan warga berasal dari keluarga tidak mampu', 'isi' => 'adalah benar warga desa ini yang tergolong keluarga tidak mampu.'],
    ];

    public function run(): void
    {
        Storage::disk('local')->makeDirectory('templat');

        foreach (self::JENIS as $jenis) {
            $path = 'templat/' . strtolower($jenis['kode']) . '.docx';
            $this->buatTemplat(Storage::disk('local')->path($path), $jenis['nama'], $jenis['isi']);

            JenisSurat::updateOrCreate(['kode' => $jenis['kode']], [
                'nama' => $jenis['nama'],
                'deskripsi' => $jenis['deskripsi'],
                'templat' => $path,
            ]);
        }
    }

    private function buatTemplat(string $berkas, string $judul, string $isi): void
    {
        $word = new PhpWord();
        $word->getSettings()->setThemeFontLang(new Language('id-ID'));
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(12);

        $bagian = $word->addSection();
        $tengah = ['alignment' => 'center'];

        $bagian->addText('PEMERINTAH DESA ${nama_desa}', ['bold' => true, 'size' => 14], $tengah);
        $bagian->addTextBreak();
        $bagian->addText(strtoupper($judul), ['bold' => true, 'underline' => 'single'], $tengah);
        $bagian->addText('Nomor: ${nomor_surat}', [], $tengah);
        $bagian->addTextBreak();

        $bagian->addText('Yang bertanda tangan di bawah ini, Kepala Desa ${nama_desa}, menerangkan bahwa:');
        $bagian->addTextBreak();

        $kolom = [
            'Nama' => '${nama}',
            'NIK' => '${nik}',
            'Tempat/Tanggal Lahir' => '${tempat_lahir}, ${tanggal_lahir}',
            'Jenis Kelamin' => '${jenis_kelamin}',
            'Pekerjaan' => '${pekerjaan}',
            'Status Perkawinan' => '${status_perkawinan}',
            'Alamat' => '${alamat}, RT ${rt} / RW ${rw}',
        ];

        $tabel = $bagian->addTable();
        foreach ($kolom as $label => $nilai) {
            $tabel->addRow();
            $tabel->addCell(3000)->addText($label);
            $tabel->addCell(300)->addText(':');
            $tabel->addCell(6000)->addText($nilai);
        }

        $bagian->addTextBreak();
        $bagian->addText('Orang tersebut ' . $isi);
        $bagian->addText('Keperluan: ${keperluan}');
        $bagian->addText('${keterangan}');
        $bagian->addTextBreak();
        $bagian->addText('Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya.');
        $bagian->addTextBreak(2);

        $bagian->addText('${nama_desa}, ${tanggal_surat}', [], ['alignment' => 'right']);
        $bagian->addText('Kepala Desa', [], ['alignment' => 'right']);
        $bagian->addTextBreak(3);
        $bagian->addText('${nama_kepala_desa}', ['bold' => true], ['alignment' => 'right']);

        IOFactory::createWriter($word, 'Word2007')->save($berkas);
    }
}
