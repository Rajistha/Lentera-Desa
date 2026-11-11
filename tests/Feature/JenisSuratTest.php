<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SiapkanData;
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
        $admin = $this->admin();

        $ok = $this->actingAs($admin)->post('/jenis-surat/pratinjau', ['kode' => 'abc', 'nama' => 'Uji', 'berkas' => $this->berkasDocx()]);
        $ok->assertRedirect();
        $this->actingAs($admin)->get($ok->headers->get('Location'))->assertOk()->assertSee('012/ABC/DS/');

        $this->actingAs($admin)->from('/jenis-surat')->post('/jenis-surat/pratinjau', ['berkas' => $this->berkasZip(['x' => 'y'])])
            ->assertSessionHasErrors('berkas');
    }

    public function test_token_pratinjau_palsu_atau_kedaluwarsa_tidak_membuka_berkas(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/jenis-surat/pratinjau/abc')->assertNotFound();
        $this->actingAs($admin)->get('/jenis-surat/pratinjau/'.str_repeat('a', 32))->assertRedirect('/jenis-surat');
        $this->actingAs($admin)->get('/jenis-surat/pratinjau/..%2f..%2f..%2f.env')->assertNotFound();
    }
}
