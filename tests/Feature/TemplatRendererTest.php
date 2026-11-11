<?php

namespace Tests\Feature;

use App\Services\Penanda;
use App\Services\TemplatRenderer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;
use ZipArchive;

class TemplatRendererTest extends TestCase
{
    use SiapkanData;

    private TemplatRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanData();
        $this->renderer = $this->app->make(TemplatRenderer::class);
    }

    /** Isi document.xml hasil pengisian templat; folder sementara dibersihkan. */
    private function isi(string $templat, array $data): string
    {
        $docx = $this->renderer->isi($templat, $data);
        $zip = new ZipArchive;
        $zip->open($docx);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        File::deleteDirectory(dirname($docx));

        return $xml;
    }

    private function templatTeks(string ...$baris): string
    {
        $word = new PhpWord;
        $bagian = $word->addSection();
        foreach ($baris as $teks) {
            $bagian->addText($teks);
        }
        $jalur = tempnam(sys_get_temp_dir(), 'tpl').'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($jalur);

        return $jalur;
    }

    private function teks(string $xml): string
    {
        return html_entity_decode(preg_replace('/\s+/', ' ', strip_tags(str_replace('</w:p>', "\n", $xml))), ENT_QUOTES | ENT_XML1);
    }

    public function test_semua_penanda_templat_seeder_terisi(): void
    {
        $xml = $this->isi(Storage::disk('local')->path('templat/skd.docx'), $this->renderer->dataContoh('SKD'));
        $teks = $this->teks($xml);

        $this->assertStringContainsString('3573010101900001', $teks);
        $this->assertStringContainsString('Budi Santoso', $teks);
        $this->assertStringContainsString('012/SKD/DS/', $teks);
        $this->assertStringNotContainsString('${', $teks, 'tidak boleh ada penanda tersisa');
    }

    public function test_penanda_tidak_dikenal_dikosongkan(): void
    {
        $teks = $this->teks($this->isi($this->templatTeks('Halo ${nama}, kode ${rahasia_server} selesai.'), ['nama' => 'Budi']));

        $this->assertStringContainsString('Halo Budi, kode selesai.', $teks, 'penanda tak dikenal hilang, bukan tampil mentah');
        $this->assertStringNotContainsString('rahasia_server', $teks);
    }

    public function test_nilai_diperlakukan_sebagai_teks_bukan_xml_atau_penanda(): void
    {
        $nilai = 'Budi & "Sons" <b>Tebal</b> ${nik}';
        $xml = $this->isi($this->templatTeks('Nama: ${nama}', 'NIK: ${nik}'), ['nama' => $nilai, 'nik' => '3573010101900001']);

        $this->assertNotFalse(@simplexml_load_string($xml), 'XML tetap valid');
        $teks = $this->teks($xml);
        $this->assertStringContainsString('Nama: '.$nilai, $teks, 'karakter khusus tampil apa adanya');
        $this->assertSame(1, substr_count($teks, '3573010101900001'), 'penanda di dalam nilai tidak diganti ulang');
        $this->assertStringNotContainsString('<b>', $xml);
    }

    public function test_baris_baru_menjadi_pemisah_baris_word(): void
    {
        $xml = $this->isi($this->templatTeks('${keterangan}'), ['keterangan' => "Baris satu\nBaris dua\r\nBaris tiga"]);

        $this->assertSame(2, substr_count($xml, '<w:br/>'));
        $this->assertNotFalse(@simplexml_load_string($xml));
    }

    public function test_templat_asli_tidak_berubah_setelah_diisi(): void
    {
        $jalur = Storage::disk('local')->path('templat/skd.docx');
        $sebelum = md5_file($jalur);

        $this->isi($jalur, $this->renderer->dataContoh('SKD'));

        $this->assertSame($sebelum, md5_file($jalur));
    }

    public function test_data_contoh_dan_angka_romawi(): void
    {
        $this->assertSame(['I', 'IV', 'IX', 'XII'], array_map([$this->renderer, 'romawi'], [1, 4, 9, 12]));
        $this->assertEqualsCanonicalizing(array_keys(Penanda::DAFTAR), array_keys($this->renderer->dataContoh('SKD')));
    }

    /**
     * Regresi: di server web Windows lingkungan turunan Symfony Process menyusut setelah Laravel
     * di-boot (SYSTEMROOT, TEMP, USERPROFILE hilang) dan LibreOffice berhenti diam-diam dengan kode 1.
     * Lingkungan harus diteruskan secara eksplisit, tanpa rahasia aplikasi.
     */
    public function test_lingkungan_libreoffice_eksplisit_lengkap_dan_tanpa_rahasia(): void
    {
        putenv('APP_KEY=rahasia-uji');
        putenv('DB_PASSWORD=rahasia-uji');
        putenv('VARIABEL_UJI_LO=ada');

        $metode = new \ReflectionMethod(TemplatRenderer::class, 'lingkunganLibreOffice');
        $metode->setAccessible(true);
        $env = $metode->invoke($this->renderer);

        foreach (['APP_KEY', 'DB_PASSWORD', 'MAIL_PASSWORD', 'REDIS_PASSWORD', 'AWS_SECRET_ACCESS_KEY'] as $rahasia) {
            $this->assertFalse($env[$rahasia], "{$rahasia} tidak boleh diwariskan ke LibreOffice");
        }
        $this->assertSame('ada', $env['VARIABEL_UJI_LO'], 'variabel proses lain diteruskan apa adanya');

        $kunci = array_map('strtoupper', array_keys($env));
        $this->assertContains('PATH', $kunci);
        $this->assertNotEmpty($env['TEMP'] ?: $env['TMP'] ?? null, 'folder sementara selalu terdefinisi');

        putenv('APP_KEY');
        putenv('DB_PASSWORD');
        putenv('VARIABEL_UJI_LO');
    }

    public function test_html_pratinjau_dibuat_tanpa_libreoffice(): void
    {
        $docx = $this->renderer->isi(Storage::disk('local')->path('templat/skd.docx'), $this->renderer->dataContoh('SKD'));
        $html = $this->renderer->keHtml($docx);
        File::deleteDirectory(dirname($docx));

        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('SURAT KETERANGAN DOMISILI', $html);
    }
}
