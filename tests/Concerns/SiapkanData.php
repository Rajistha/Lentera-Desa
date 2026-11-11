<?php

namespace Tests\Concerns;

use App\Models\JenisSurat;
use App\Models\Surat;
use App\Models\User;
use App\Models\Warga;
use App\Services\LayananSurat;
use App\Services\TemplatRenderer;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\Support\RendererPalsu;
use ZipArchive;

/**
 * Basis data bersih + akun dan jenis surat dari seeder + disk palsu untuk
 * setiap pengujian. Panggil siapkanData() dari setUp().
 */
trait SiapkanData
{
    use RefreshDatabase;

    private int $urutanNik = 0;

    protected function siapkanData(): void
    {
        Storage::fake('local');
        RendererPalsu::$gagal = false;
        $this->app->bind(TemplatRenderer::class, fn () => new RendererPalsu);
        $this->seed([UserSeeder::class, JenisSuratSeeder::class]);
    }

    protected function operator(): User
    {
        return User::where('username', 'operator')->firstOrFail();
    }

    protected function admin(): User
    {
        return User::where('username', 'admin')->firstOrFail();
    }

    protected function jenisId(string $kode): int
    {
        return JenisSurat::where('kode', $kode)->value('id');
    }

    protected function warga(array $ubah = []): Warga
    {
        return Warga::create($ubah + [
            'nik' => sprintf('9999%012d', ++$this->urutanNik),
            'nama' => 'Warga Uji '.$this->urutanNik,
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jl. Uji No. 1',
            'rt' => '01',
            'rw' => '02',
            'pekerjaan' => 'Petani',
            'status_perkawinan' => 'kawin',
        ]);
    }

    /** Surat dibuat lewat layanan asli (penomoran sungguhan) tanpa PDF. */
    protected function surat(string $kode = 'SKD', string $tanggal = '2026-11-11', ?Warga $warga = null, array $ubah = []): Surat
    {
        return app(LayananSurat::class)->buat($ubah + [
            'warga_id' => ($warga ?? $this->warga())->id,
            'jenis_surat_id' => $this->jenisId($kode),
            'tanggal_surat' => $tanggal,
            'keperluan' => 'Persyaratan kerja',
            'keterangan' => 'Catatan uji',
        ], $this->operator());
    }

    /** Berkas .docx valid (berisi word/document.xml) untuk uji unggah templat. */
    protected function berkasDocx(string $nama = 'templat.docx'): UploadedFile
    {
        $word = new PhpWord;
        $word->addSection()->addText('Nomor: ${nomor_surat}');
        $jalur = tempnam(sys_get_temp_dir(), 'docx');
        IOFactory::createWriter($word, 'Word2007')->save($jalur);

        return new UploadedFile($jalur, $nama, null, null, true);
    }

    /** Zip dengan isi bebas, diberi ekstensi .docx. */
    protected function berkasZip(array $isi, string $nama = 'palsu.docx'): UploadedFile
    {
        $jalur = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive;
        $zip->open($jalur, ZipArchive::OVERWRITE);
        foreach ($isi as $berkas => $konten) {
            $zip->addFromString($berkas, $konten);
        }
        $zip->close();

        return new UploadedFile($jalur, $nama, null, null, true);
    }
}
