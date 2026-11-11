<?php

namespace App\Services;

use App\Models\ProfilDesa;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use ZipArchive;

class TemplatRenderer
{
    /**
     * Salin templat dan isi penanda ${...} atau {{ ... }}. Penanda yang tidak dikenal
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

        // Putaran kedua untuk templat bergaya {{ nama }} (dan {{nama}}), yang lazim pada templat buatan sendiri.
        ProsesorTemplat::denganKurawalGanda(function () use ($hasil, $data) {
            $kedua = new ProsesorTemplat($hasil);
            foreach ($kedua->getVariables() as $nama) {
                $kedua->setValue($nama, $this->escape((string) ($data[trim($nama)] ?? '')));
            }
            $kedua->saveAs($hasil);
        });

        $this->isiLabelBerkurung($hasil, $data);

        return $hasil;
    }

    /** Label berkurung yang lazim pada templat buatan sendiri => penanda yang mengisinya. */
    private const LABEL_BERKURUNG = [
        'nama desa' => 'nama_desa',
        'nama kecamatan' => 'nama_kecamatan',
        'nama kabupaten' => 'nama_kabupaten',
        'alamat kantor desa' => 'alamat_kantor_desa',
        'kode pos' => 'kode_pos',
        'xxxxx' => 'kode_pos',
        'nomor telepon' => 'telepon_desa',
        'telepon' => 'telepon_desa',
        'nama kepala desa' => 'nama_kepala_desa',
        'nama terang kepala desa' => 'nama_kepala_desa',
    ];

    /**
     * Templat buatan sendiri sering memakai label seperti [NAMA DESA] atau [Nomor Telepon] sebagai
     * isian. Label itu diganti dengan data profil desa bila datanya sudah diisi administrator; bila
     * belum, label dibiarkan agar terlihat bahwa profil desa perlu dilengkapi.
     */
    private function isiLabelBerkurung(string $docx, array $data): void
    {
        $zip = new ZipArchive;
        if ($zip->open($docx) !== true) {
            return;
        }

        $bagian = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nama = $zip->getNameIndex($i);
            if (preg_match('#^word/(document|header\d*|footer\d*)\.xml$#', $nama)) {
                $bagian[] = $nama;
            }
        }

        foreach ($bagian as $nama) {
            $xml = $zip->getFromName($nama);
            $baru = preg_replace_callback('#(<w:t(?:\s[^>]*)?>)([^<]*)(</w:t>)#', function (array $simpul) use ($data) {
                $isi = preg_replace_callback('/\[\s*([^\[\]]{1,40}?)\s*\]/u', function (array $label) use ($data) {
                    $kunci = self::LABEL_BERKURUNG[mb_strtolower(trim($label[1]))] ?? null;
                    $nilai = $kunci ? trim((string) ($data[$kunci] ?? '')) : '';

                    return $nilai === '' ? $label[0] : $this->escape($nilai);
                }, $simpul[2]);

                return $simpul[1].$isi.$simpul[3];
            }, $xml);

            if ($baru !== null && $baru !== $xml) {
                $zip->addFromString($nama, $baru);
            }
        }
        $zip->close();
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

    /**
     * Tampilan HTML dokumen untuk pratinjau cadangan (bila LibreOffice tidak ada). $docx harus
     * salinan sementara karena berkasnya diperbaiki di tempat. Mengembalikan null bila pembaca
     * PhpWord tidak sanggup membacanya: berkas hasil simpan Word/LibreOffice kerap memuat bagian
     * yang tidak dikenalnya, dan itu tidak boleh membuat halaman pratinjau galat.
     */
    public function keHtml(string $docx): ?string
    {
        try {
            $this->rapikanGayaBawaan($docx);

            $html = IOFactory::createWriter(IOFactory::load($docx), 'HTML')->getContent();

            // Berkas yang tak lengkap tidak melempar galat; PhpWord hanya menghasilkan halaman kosong.
            preg_match('#<body[^>]*>(.*)</body>#si', $html, $badan);
            if (preg_replace('/[\s\x{00A0}]+/u', '', html_entity_decode(strip_tags($badan[1] ?? ''), ENT_QUOTES, 'UTF-8')) === '') {
                return null;
            }

            return $html;
        } catch (\Throwable $e) {
            Log::warning('Pratinjau HTML dari PhpWord gagal', ['galat' => $e->getMessage()]);

            return null;
        }
    }

    /** Teks polos isi dokumen (satu paragraf per baris): cadangan terakhir pratinjau. */
    public function keTeks(string $docx): string
    {
        $zip = new ZipArchive;
        if ($zip->open($docx) !== true) {
            return '';
        }
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $xml = preg_replace(['/<w:tab\s*\/>/', '/<w:br[^>]*\/>|<\/w:p>|<\/w:tr>/'], [' ', "\n"], $xml);
        $teks = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $teks));
    }

    /**
     * Word dan LibreOffice menyimpan <w:rPrDefault/> dan <w:pPrDefault/> kosong; pembaca PhpWord
     * lalu mencoba membaca gaya yang tidak ada dan melempar TypeError. Beri isi kosong yang sah.
     */
    private function rapikanGayaBawaan(string $docx): void
    {
        $zip = new ZipArchive;
        if ($zip->open($docx) !== true) {
            return;
        }

        $gaya = $zip->getFromName('word/styles.xml');
        if ($gaya !== false) {
            $baru = preg_replace(
                ['#<w:rPrDefault\s*/>#', '#<w:pPrDefault\s*/>#'],
                ['<w:rPrDefault><w:rPr/></w:rPrDefault>', '<w:pPrDefault><w:pPr/></w:pPrDefault>'],
                $gaya
            );
            if ($baru !== null && $baru !== $gaya) {
                $zip->addFromString('word/styles.xml', $baru);
            }
        }
        $zip->close();
    }

    public function libreOfficeTersedia(): bool
    {
        return $this->jalurLibreOffice() !== null;
    }

    /** Data contoh untuk pratinjau templat (tidak disimpan). */
    public function dataContoh(string $kode): array
    {
        $tanggal = Carbon::now()->locale('id');
        $desa = ProfilDesa::saatIni();

        return [
            'nomor_surat' => '012/'.$kode.'/'.$desa->kode_desa.'/'.$this->romawi($tanggal->month).'/'.$tanggal->year,
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
        ] + $desa->untukTemplat();
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
        // "$", "{", "}", "[", dan "]" jadi entitas agar nilai tidak terbaca sebagai penanda oleh penggantian berikutnya
        // (tampilannya di Word tetap sama).
        $aman = str_replace(['$', '{', '}', '[', ']'], ['&#36;', '&#123;', '&#125;', '&#91;', '&#93;'], htmlspecialchars($nilai, ENT_QUOTES | ENT_XML1, 'UTF-8'));

        return preg_replace('/\R/u', '</w:t><w:br/><w:t xml:space="preserve">', $aman);
    }
}
