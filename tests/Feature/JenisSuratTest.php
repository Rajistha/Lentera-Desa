<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SiapkanData;
use Tests\Support\RendererPalsu;
use Tests\TestCase;

class JenisSuratTest extends TestCase
{
    use SiapkanData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanData();
    }

    private function simpan(array $data, ?UploadedFile $berkas = null)
    {
        return $this->actingAs($this->admin())->from('/jenis-surat?tambah=1')
            ->post('/jenis-surat', $data + ($berkas ? ['berkas' => $berkas] : []));
    }

    public function test_seeder_menyediakan_empat_jenis_beserta_templatnya(): void
    {
        $this->assertSame(['SKD', 'SKP', 'SKTM', 'SKU'], JenisSurat::orderBy('kode')->pluck('kode')->all());

        foreach (JenisSurat::all() as $jenis) {
            Storage::disk('local')->assertExists($jenis->templat);
        }
    }

    public function test_administrator_menambah_jenis_surat_dengan_templat_docx(): void
    {
        $this->simpan(['kode' => 'skl', 'nama' => 'Surat Keterangan Lahir', 'deskripsi' => 'Uji'], $this->berkasDocx())
            ->assertRedirect('/jenis-surat')->assertSessionHas('sukses');

        $jenis = JenisSurat::where('kode', 'SKL')->firstOrFail();
        $this->assertSame('templat/skl.docx', $jenis->templat);
        Storage::disk('local')->assertExists('templat/skl.docx');
    }

    public function test_templat_wajib_saat_menambah(): void
    {
        $this->simpan(['kode' => 'SKL', 'nama' => 'Uji'])->assertSessionHasErrors(['berkas' => 'Templat (.docx) wajib diunggah.']);
        $this->assertDatabaseMissing('jenis_surat', ['kode' => 'SKL']);
    }

    public function test_berkas_bukan_docx_asli_ditolak_dan_tidak_tersimpan(): void
    {
        $kasus = [
            'teks berekstensi docx' => [UploadedFile::fake()->createWithContent('a.docx', 'bukan zip'), 'Berkas bukan dokumen Word (.docx) yang valid.'],
            'zip tanpa document.xml' => [$this->berkasZip(['x.txt' => 'a']), 'Berkas bukan dokumen Word (.docx) yang valid.'],
            'berisi makro vba' => [$this->berkasZip(['word/document.xml' => '<w/>', 'word/vbaProject.bin' => 'x']), 'Berkas bukan dokumen Word (.docx) yang valid.'],
            'ekstensi pdf' => [UploadedFile::fake()->create('a.pdf', 10), 'Templat harus berekstensi .docx.'],
            'lebih dari 2 MB' => [UploadedFile::fake()->create('besar.docx', 2049), 'Ukuran templat maksimal 2 MB.'],
        ];

        foreach ($kasus as [$berkas, $pesan]) {
            $this->simpan(['kode' => 'XYZ', 'nama' => 'Uji'], $berkas)->assertSessionHasErrors(['berkas' => $pesan]);
            Storage::disk('local')->assertMissing('templat/xyz.docx');
        }
        $this->assertDatabaseMissing('jenis_surat', ['kode' => 'XYZ']);
    }

    public function test_kode_harus_unik_dan_hanya_huruf_angka(): void
    {
        $this->simpan(['kode' => 'skd', 'nama' => 'Dobel'], $this->berkasDocx())
            ->assertSessionHasErrors(['kode' => 'Kode sudah dipakai jenis surat lain.']);
        $this->simpan(['kode' => 'AB C', 'nama' => 'Uji'], $this->berkasDocx())
            ->assertSessionHasErrors(['kode' => 'Kode hanya boleh berisi huruf dan angka tanpa spasi.']);
        $this->simpan(['kode' => '../../x', 'nama' => 'Uji'], $this->berkasDocx())->assertSessionHasErrors('kode');
        $this->simpan(['kode' => 'ABCDEFGHIJK', 'nama' => 'Uji'], $this->berkasDocx())->assertSessionHasErrors('kode');
    }

    public function test_mengubah_tanpa_berkas_mempertahankan_templat_dan_nama_berkas_mengikuti_kode(): void
    {
        $jenis = JenisSurat::where('kode', 'SKP')->first();
        $isiLama = Storage::disk('local')->get('templat/skp.docx');

        $this->actingAs($this->admin())->put("/jenis-surat/{$jenis->id}", ['kode' => 'SKPX', 'nama' => 'Pengantar Baru', 'deskripsi' => ''])
            ->assertRedirect('/jenis-surat');

        $jenis->refresh();
        $this->assertSame('templat/skpx.docx', $jenis->templat);
        Storage::disk('local')->assertMissing('templat/skp.docx');
        $this->assertSame($isiLama, Storage::disk('local')->get('templat/skpx.docx'));
    }

    public function test_mengganti_templat_menimpa_berkas(): void
    {
        $jenis = JenisSurat::where('kode', 'SKU')->first();
        $sebelum = Storage::disk('local')->get($jenis->templat);

        $this->actingAs($this->admin())->put("/jenis-surat/{$jenis->id}", ['kode' => 'SKU', 'nama' => $jenis->nama, 'berkas' => $this->berkasDocx()])
            ->assertSessionHasNoErrors();

        $this->assertNotSame($sebelum, Storage::disk('local')->get($jenis->templat));
    }

    public function test_jenis_yang_sudah_dipakai_tidak_dapat_dihapus(): void
    {
        $jenis = JenisSurat::where('kode', 'SKD')->first();
        $this->surat('SKD');

        $this->actingAs($this->admin())->delete("/jenis-surat/{$jenis->id}")
            ->assertSessionHas('galat', "{$jenis->nama} tidak dapat dihapus karena sudah dipakai pada surat.");
        $this->assertModelExists($jenis);
        Storage::disk('local')->assertExists($jenis->templat);
    }

    public function test_jenis_yang_belum_dipakai_dihapus_beserta_berkasnya(): void
    {
        $jenis = JenisSurat::where('kode', 'SKTM')->first();

        $this->actingAs($this->admin())->delete("/jenis-surat/{$jenis->id}")->assertSessionHas('sukses');
        $this->assertModelMissing($jenis);
        Storage::disk('local')->assertMissing($jenis->templat);
    }

    public function test_pratinjau_templat_tersimpan_memakai_data_contoh(): void
    {
        RendererPalsu::$gagal = true; // mode HTML cadangan; mode PDF diuji terpisah
        $jenis = JenisSurat::where('kode', 'SKD')->first();
        $admin = $this->admin();

        $respons = $this->actingAs($admin)->get("/jenis-surat/{$jenis->id}/pratinjau");
        $respons->assertRedirect();

        $this->actingAs($admin)->get($respons->headers->get('Location'))
            ->assertOk()->assertSee('Pratinjau Templat')->assertSee('Budi Santoso')
            ->assertSee('3573010101900001')->assertSee('sandbox=""', false);
    }

    public function test_pratinjau_berkas_yang_belum_disimpan_dan_penolakan_berkas_rusak(): void
    {
        RendererPalsu::$gagal = true; // mode HTML cadangan; mode PDF diuji terpisah
        $admin = $this->admin();

        $ok = $this->actingAs($admin)->post('/jenis-surat/pratinjau', ['kode' => 'abc', 'nama' => 'Uji', 'berkas' => $this->berkasDocx()]);
        $ok->assertRedirect();
        $this->actingAs($admin)->get($ok->headers->get('Location'))->assertOk()->assertSee('012/ABC/DS/');

        $this->actingAs($admin)->from('/jenis-surat')->post('/jenis-surat/pratinjau', ['berkas' => $this->berkasZip(['x' => 'y'])])
            ->assertSessionHasErrors('berkas');
    }

    private function bukaPratinjauSimpanan(JenisSurat $jenis): string
    {
        return $this->actingAs($this->admin())->get("/jenis-surat/{$jenis->id}/pratinjau")->headers->get('Location');
    }

    public function test_pratinjau_dengan_libreoffice_menampilkan_pdf_asli_di_dalam_kertas(): void
    {
        $url = $this->bukaPratinjauSimpanan(JenisSurat::where('kode', 'SKD')->first());

        $this->actingAs($this->admin())->get($url)->assertOk()
            ->assertSee('sematan=1', false)->assertSee('Buka PDF di Tab Baru')->assertDontSee('LibreOffice tidak ditemukan');
    }

    public function test_pratinjau_tanpa_libreoffice_memakai_html_cadangan_dan_tidak_galat_pada_templat_hasil_simpan_word(): void
    {
        RendererPalsu::$gagal = true;
        $jenis = JenisSurat::where('kode', 'SKP')->first();

        // Templat bergaya bawaan kosong dan berpenanda {{ nama }}, seperti hasil simpan Word/LibreOffice.
        $zip = new \ZipArchive;
        $zip->open(Storage::disk('local')->path($jenis->templat));
        $zip->addFromString('word/styles.xml', preg_replace('#<w:docDefaults>.*?</w:docDefaults>#s', '<w:docDefaults><w:rPrDefault/><w:pPrDefault/></w:docDefaults>', $zip->getFromName('word/styles.xml')));
        $zip->addFromString('word/document.xml', str_replace('${nama}', '{{ nama }}', $zip->getFromName('word/document.xml')));
        $zip->close();

        $this->actingAs($this->admin())->get($this->bukaPratinjauSimpanan($jenis))->assertOk()
            ->assertSee('LibreOffice tidak ditemukan')->assertSee('sandbox=""', false)->assertSee('Budi Santoso');
    }

    public function test_pratinjau_jatuh_ke_teks_biasa_bila_berkas_tak_terbaca_pembaca_html(): void
    {
        RendererPalsu::$gagal = true;
        $jenis = JenisSurat::where('kode', 'SKU')->first();
        $rusak = $this->berkasZip([
            'word/document.xml' => '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Surat untuk {{ nama }}</w:t></w:r></w:p></w:body></w:document>',
        ]);
        Storage::disk('local')->put($jenis->templat, file_get_contents($rusak->getRealPath()));

        $this->actingAs($this->admin())->get($this->bukaPratinjauSimpanan($jenis))->assertOk()
            ->assertSee('Tata letak tidak dapat ditampilkan')->assertSee('Surat untuk Budi Santoso');
    }

    public function test_pdf_sematan_yang_gagal_menampilkan_pesan_singkat_bukan_mengalihkan(): void
    {
        $url = $this->bukaPratinjauSimpanan(JenisSurat::where('kode', 'SKD')->first());
        RendererPalsu::$gagal = true;
        $admin = $this->admin();

        $this->actingAs($admin)->get($url.'/pdf?sematan=1')->assertOk()->assertSee('PDF contoh belum dapat dibuat');
        $this->actingAs($admin)->get($url.'/pdf')->assertRedirect($url)->assertSessionHas('galat');
    }

    public function test_token_pratinjau_palsu_atau_kedaluwarsa_tidak_membuka_berkas(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/jenis-surat/pratinjau/abc')->assertNotFound();
        $this->actingAs($admin)->get('/jenis-surat/pratinjau/'.str_repeat('a', 32))->assertRedirect('/jenis-surat');
        $this->actingAs($admin)->get('/jenis-surat/pratinjau/..%2f..%2f..%2f.env')->assertNotFound();
    }
}
