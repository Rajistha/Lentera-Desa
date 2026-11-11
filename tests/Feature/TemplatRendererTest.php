<?php

namespace Tests\Feature;

use App\Services\Penanda;
use App\Services\TemplatRenderer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
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

    /* ---------- penanda gaya {{ nama }} ---------- */

    public function test_penanda_gaya_kurawal_ganda_dan_gaya_dolar_terisi_bersamaan(): void
    {
        $templat = $this->templatTeks('A: {{ nama }} | B: {{nik}} | C: ${tempat_lahir} | D: {{ nomor_surat }}');
        $teks = $this->teks($this->isi($templat, ['nama' => 'Budi', 'nik' => '3573', 'tempat_lahir' => 'Malang', 'nomor_surat' => '001/SKD']));

        $this->assertStringContainsString('A: Budi | B: 3573 | C: Malang | D: 001/SKD', $teks);
        $this->assertStringNotContainsString('{{', $teks);
        $this->assertStringNotContainsString('${', $teks);
    }

    public function test_penanda_kurawal_ganda_yang_tidak_dikenal_dikosongkan(): void
    {
        // "agama" dan "kewarganegaraan" ada di templat buatan sendiri tetapi belum ada datanya di aplikasi.
        $teks = $this->teks($this->isi($this->templatTeks('Agama: {{ agama }}.', 'Nama: {{ nama }}'), ['nama' => 'Budi']));

        $this->assertStringContainsString('Agama: .', $teks);
        $this->assertStringContainsString('Nama: Budi', $teks);
    }

    public function test_nilai_berisi_penanda_tidak_diisi_ulang_pada_putaran_kedua(): void
    {
        $xml = $this->isi($this->templatTeks('Nama: ${nama}', 'NIK: {{ nik }}'), ['nama' => 'X {{ nik }} ${nik}', 'nik' => '3573010101900001']);

        $this->assertSame(1, substr_count($this->teks($xml), '3573010101900001'), 'NIK hanya muncul di barisnya sendiri');
        $this->assertStringContainsString('X {{ nik }} ${nik}', $this->teks($xml), 'isi nilai tampil apa adanya');
        $this->assertNotFalse(@simplexml_load_string($xml));
    }

    public function test_karakter_penanda_dipulihkan_setelah_dipakai(): void
    {
        $this->isi($this->templatTeks('{{ nama }}'), ['nama' => 'Budi']);

        $proses = new \ReflectionClass(TemplateProcessor::class);
        $this->assertSame('${', $proses->getStaticPropertyValue('macroOpeningChars'));
        $this->assertSame('}', $proses->getStaticPropertyValue('macroClosingChars'));
        $this->assertStringContainsString('Budi', $this->teks($this->isi($this->templatTeks('${nama}'), ['nama' => 'Budi'])));
    }

    /* ---------- pratinjau tahan terhadap berkas hasil simpan Word/LibreOffice ---------- */

    /** Salinan templat seeder dengan <w:rPrDefault/> dan <w:pPrDefault/> kosong, seperti hasil simpan Word/LibreOffice. */
    private function docxGayaKosong(): string
    {
        $jalur = tempnam(sys_get_temp_dir(), 'kosong').'.docx';
        copy(Storage::disk('local')->path('templat/skd.docx'), $jalur);
        $zip = new ZipArchive;
        $zip->open($jalur);
        $gaya = preg_replace('#<w:docDefaults>.*?</w:docDefaults>#s', '<w:docDefaults><w:rPrDefault/><w:pPrDefault/></w:docDefaults>', $zip->getFromName('word/styles.xml'));
        $zip->addFromString('word/styles.xml', $gaya);
        $zip->close();

        return $jalur;
    }

    public function test_html_pratinjau_tidak_galat_pada_gaya_bawaan_kosong(): void
    {
        $docx = $this->renderer->isi($this->docxGayaKosong(), $this->renderer->dataContoh('SKD'));
        $html = $this->renderer->keHtml($docx);
        File::deleteDirectory(dirname($docx));

        $this->assertIsString($html, 'sebelum perbaikan: TypeError array_key_exists() di PhpWord Styles.php');
        $this->assertStringContainsString('Budi Santoso', $html);
    }

    public function test_berkas_yang_tak_terbaca_pembaca_html_jatuh_ke_teks_biasa_tanpa_galat(): void
    {
        $rusak = $this->berkasZip(['word/document.xml' => '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Halo dunia</w:t></w:r></w:p></w:body></w:document>']);
        $jalur = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rusak-'.bin2hex(random_bytes(4)).'.docx';
        copy($rusak->getRealPath(), $jalur);

        $this->assertNull($this->renderer->keHtml($jalur), 'pembaca PhpWord tidak sanggup: kembali null, bukan melempar');
        $this->assertStringContainsString('Halo dunia', $this->renderer->keTeks($jalur));
        File::delete($jalur);
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
