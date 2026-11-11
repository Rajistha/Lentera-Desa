<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class TemplatRenderer
{
    /**
     * Salin templat dan isi penanda ${...}. Penanda yang tidak dikenal
     * dikosongkan (AB-06). Nilai diperlakukan sebagai teks, bukan kode.
     *
     * @param  array<string, string|null>  $data  nama penanda => nilai
     * @return string jalur berkas .docx sementara (hapus foldernya setelah dipakai)
     */
    public function isi(string $templat, array $data): string
    {
        $proses = new TemplateProcessor($templat);

        foreach ($proses->getVariables() as $nama) {
            $proses->setValue($nama, $this->escape((string) ($data[$nama] ?? '')));
        }

        $hasil = $this->folderSementara().DIRECTORY_SEPARATOR.'surat.docx';
        $proses->saveAs($hasil);

        return $hasil;
    }

    /** Ubah .docx ke PDF dengan LibreOffice; kembalikan jalur PDF di folder yang sama. */
    public function kePdf(string $docx): string
    {
        $soffice = $this->jalurLibreOffice();
        if ($soffice === null) {
            throw new PdfGagal('LibreOffice tidak ditemukan. Pasang LibreOffice atau periksa LIBREOFFICE_PATH di berkas .env.');
        }

        $folder = dirname($docx);
        $profil = storage_path('framework/libreoffice-profil');
        File::ensureDirectoryExists($profil);

        $proses = new Process([
            $soffice,
            '--headless', '--norestore', '--nolockcheck',
            // Profil terpisah agar tidak bentrok dengan LibreOffice yang sedang dibuka
            // atau dengan akun layanan web server yang tidak punya folder profil.
            '-env:UserInstallation=file:///'.ltrim(str_replace([' ', '\\'], ['%20', '/'], $profil), '/'),
            '--convert-to', 'pdf',
            '--outdir', $folder,
            $docx,
        ], $folder, $this->lingkunganLibreOffice(), null, config('lentera.libreoffice_timeout'));

        try {
            $proses->run();
        } catch (\Throwable $e) {
            Log::error('Konversi PDF gagal dijalankan', ['galat' => $e->getMessage()]);
            throw new PdfGagal('Pembuatan PDF melebihi batas waktu atau gagal dijalankan.', 0, $e);
        }

        $pdf = preg_replace('/\.docx$/i', '.pdf', $docx);
        if (! $proses->isSuccessful() || ! is_file($pdf)) {
            Log::error('Konversi PDF gagal', [
                'kode_keluar' => $proses->getExitCode(),
                'perintah' => $proses->getCommandLine(),
                'keluaran' => $proses->getOutput(),
                'galat' => $proses->getErrorOutput(),
                'pdf_ada' => is_file($pdf),
            ]);
            throw new PdfGagal('Proses konversi LibreOffice berhenti dengan galat. Periksa log aplikasi untuk rinciannya.');
        }

        return $pdf;
    }

    /** Isi templat lalu simpan sebagai PDF di $tujuan (jalur absolut). */
    public function buatPdf(string $templat, array $data, string $tujuan): void
    {
        $docx = $this->isi($templat, $data);

        try {
            $pdf = $this->kePdf($docx);
            File::ensureDirectoryExists(dirname($tujuan));
            File::move($pdf, $tujuan);
        } finally {
            File::deleteDirectory(dirname($docx));
        }
    }

    /** Tampilan HTML dokumen (tanpa LibreOffice) untuk pratinjau. */
    public function keHtml(string $docx): string
    {
        return IOFactory::createWriter(IOFactory::load($docx), 'HTML')->getContent();
    }

    public function libreOfficeTersedia(): bool
    {
        return $this->jalurLibreOffice() !== null;
    }

    /** Data contoh untuk pratinjau templat (tidak disimpan). */
    public function dataContoh(string $kode): array
    {
        $tanggal = Carbon::now()->locale('id');

        return [
            'nomor_surat' => '012/'.$kode.'/'.config('lentera.desa.kode').'/'.$this->romawi($tanggal->month).'/'.$tanggal->year,
            'tanggal_surat' => $tanggal->translatedFormat('j F Y'),
            'nik' => '3573010101900001',
            'nama' => 'Budi Santoso',
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '1 Januari 1990',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Mawar No. 4',
            'rt' => '01',
            'rw' => '02',
            'pekerjaan' => 'Petani',
            'status_perkawinan' => 'Kawin',
            'keperluan' => 'Persyaratan kerja',
            'keterangan' => 'Contoh keterangan tambahan.',
            'nama_desa' => config('lentera.desa.nama'),
            'nama_kepala_desa' => config('lentera.desa.kepala'),
        ];
    }

    public function romawi(int $bulan): string
    {
        return ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$bulan - 1];
    }

    /**
     * Lingkungan untuk proses LibreOffice. Pada server web (php -S, Apache) Symfony Process
     * menyaring lingkungan turunan lewat $_SERVER; setelah Laravel di-boot, variabel inti
     * Windows (SYSTEMROOT, TEMP, USERPROFILE, APPDATA) bisa hilang dan LibreOffice berhenti
     * dengan kode 1 tanpa pesan. Karena itu lingkungan proses diteruskan secara eksplisit,
     * tanpa rahasia aplikasi yang tidak dibutuhkan LibreOffice.
     *
     * @return array<string, string|false>
     */
    private function lingkunganLibreOffice(): array
    {
        $lingkungan = getenv() ?: [];

        foreach (['APP_KEY', 'DB_PASSWORD', 'MAIL_PASSWORD', 'REDIS_PASSWORD', 'AWS_SECRET_ACCESS_KEY'] as $rahasia) {
            $lingkungan[$rahasia] = false; // false = tidak diwariskan ke proses anak
        }

        // Pastikan folder sementara selalu terdefinisi.
        $sementara = sys_get_temp_dir();
        $lingkungan['TEMP'] = $lingkungan['TEMP'] ?? $sementara;
        $lingkungan['TMP'] = $lingkungan['TMP'] ?? $sementara;

        return $lingkungan;
    }

    private function jalurLibreOffice(): ?string
    {
        $jalur = (string) config('lentera.libreoffice');

        if ($jalur !== '' && is_file($jalur)) {
            return $jalur;
        }

        return $jalur !== '' ? (new ExecutableFinder)->find($jalur) : null;
    }

    private function folderSementara(): string
    {
        $folder = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lentera-'.bin2hex(random_bytes(8));
        File::makeDirectory($folder, 0700, true);

        return $folder;
    }

    /** Nilai masuk ke XML Word: escape, dan baris baru jadi pemisah baris Word. */
    private function escape(string $nilai): string
    {
        // "$" jadi &#36; agar nilai tidak terbaca sebagai penanda oleh penggantian berikutnya.
        $aman = str_replace('$', '&#36;', htmlspecialchars($nilai, ENT_QUOTES | ENT_XML1, 'UTF-8'));

        return preg_replace('/\R/u', '</w:t><w:br/><w:t xml:space="preserve">', $aman);
    }
}
