<?php

namespace Tests\Feature;

use App\Models\ProfilDesa;
use App\Services\LayananSurat;
use App\Services\Penanda;
use App\Services\TemplatRenderer;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;
use ZipArchive;

class ProfilDesaTest extends TestCase
{
    use SiapkanData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanData();
    }

    private function data(array $ubah = []): array
    {
        return $ubah + [
            'nama_desa' => 'Sukamaju',
            'nama_kecamatan' => 'Lowokwaru',
            'nama_kabupaten' => 'Malang',
            'alamat_kantor' => 'Jl. Raya Sukamaju No. 1',
            'kode_pos' => '65141',
            'telepon' => '(0341) 555-123',
            'nama_kepala_desa' => 'Drs. Slamet Riyadi',
            'kode_desa' => 'SKM',
        ];
    }

    private function simpan(array $ubah = [])
    {
        return $this->actingAs($this->admin())->put('/profil-desa', $this->data($ubah));
    }

    private function isiTemplat(string $teks, array $data): string
    {
        $word = new PhpWord;
        foreach (explode("\n", $teks) as $baris) {
            $word->addSection()->addText($baris);
        }
        $templat = tempnam(sys_get_temp_dir(), 'tpl').'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($templat);

        $docx = $this->app->make(TemplatRenderer::class)->isi($templat, $data);
        $zip = new ZipArchive;
        $zip->open($docx);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        File::deleteDirectory(dirname($docx));

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1);
    }

    /* ---------- akses ---------- */

    public function test_hanya_administrator_yang_dapat_membuka_dan_mengubah_profil_desa(): void
    {
        $this->get('/profil-desa')->assertRedirect('/login');
        $this->actingAs($this->operator())->get('/profil-desa')->assertForbidden();
        $this->actingAs($this->operator())->put('/profil-desa', $this->data())->assertForbidden();
        $this->assertDatabaseCount('profil_desa', 0);

        $this->actingAs($this->admin())->get('/profil-desa')->assertOk()->assertSee('Profil Desa');
    }

    public function test_menu_dan_tab_profil_desa_tersedia_bagi_administrator(): void
    {
        $this->actingAs($this->admin())->get('/jenis-surat')->assertSee(route('profil-desa.index'), false);
        $this->actingAs($this->admin())->get('/profil-desa')->assertSee('aria-current="page"', false);
    }

    /* ---------- nilai awal dan penyimpanan ---------- */

    public function test_sebelum_disimpan_formulir_menampilkan_nilai_awal_dari_konfigurasi(): void
    {
        config(['lentera.desa.nama' => 'Desa Awal', 'lentera.desa.kepala' => 'Kades Awal', 'lentera.desa.kode' => 'AW']);

        $this->actingAs($this->admin())->get('/profil-desa')
            ->assertSee('value="Desa Awal"', false)->assertSee('value="Kades Awal"', false)->assertSee('value="AW"', false);
        $this->assertDatabaseCount('profil_desa', 0);
    }

    public function test_menyimpan_membuat_satu_baris_lalu_memperbaruinya(): void
    {
        $this->simpan()->assertRedirect('/profil-desa')->assertSessionHas('sukses', 'Profil desa berhasil disimpan.');
        $this->assertDatabaseCount('profil_desa', 1);
        $this->assertDatabaseHas('profil_desa', ['nama_desa' => 'Sukamaju', 'kode_desa' => 'SKM', 'kode_pos' => '65141']);

        $this->simpan(['nama_desa' => 'Sukamakmur', 'telepon' => ''])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('profil_desa', 1);
        $this->assertSame('Sukamakmur', ProfilDesa::first()->nama_desa);
        $this->assertNull(ProfilDesa::first()->telepon, 'kolom opsional boleh dikosongkan');

        $this->actingAs($this->admin())->get('/profil-desa')->assertSee('value="Sukamakmur"', false);
    }

    public function test_kode_desa_dijadikan_huruf_besar(): void
    {
        $this->simpan(['kode_desa' => ' skm '])->assertSessionHasNoErrors();
        $this->assertSame('SKM', ProfilDesa::first()->kode_desa);
    }

    #[DataProvider('tidakValid')]
    public function test_validasi_profil_desa(string $kolom, string $nilai, string $pesan): void
    {
        $this->actingAs($this->admin())->from('/profil-desa')->put('/profil-desa', $this->data([$kolom => $nilai]))
            ->assertRedirect('/profil-desa')->assertSessionHasErrors([$kolom => $pesan]);
        $this->assertDatabaseCount('profil_desa', 0);
    }

    public static function tidakValid(): iterable
    {
        yield 'nama desa kosong' => ['nama_desa', '', 'Nama desa wajib diisi.'];
        yield 'nama kepala desa kosong' => ['nama_kepala_desa', '', 'Nama kepala desa wajib diisi.'];
        yield 'kode desa kosong' => ['kode_desa', '', 'Kode desa wajib diisi.'];
        yield 'kode desa berspasi' => ['kode_desa', 'S K', 'Kode desa hanya boleh huruf dan angka tanpa spasi, maksimal 10 karakter.'];
        yield 'kode desa memuat garis miring' => ['kode_desa', 'A/B', 'Kode desa hanya boleh huruf dan angka tanpa spasi, maksimal 10 karakter.'];
        yield 'kode desa terlalu panjang' => ['kode_desa', 'ABCDEFGHIJK', 'Kode desa hanya boleh huruf dan angka tanpa spasi, maksimal 10 karakter.'];
        yield 'kode pos bukan 5 digit' => ['kode_pos', '123', 'Kode pos harus 5 digit angka.'];
        yield 'kode pos berisi huruf' => ['kode_pos', '12a45', 'Kode pos harus 5 digit angka.'];
        yield 'telepon berisi huruf' => ['telepon', 'abc-def', 'Telepon hanya boleh berisi angka, spasi, tanda + ( ) -, sepanjang 5 sampai 30 karakter.'];
        yield 'nama desa terlalu panjang' => ['nama_desa', str_repeat('a', 101), 'Nama desa maksimal 100 karakter.'];
    }

    /* ---------- dipakai pada surat ---------- */

    public function test_kode_desa_dipakai_pada_nomor_surat_berikutnya_dan_nomor_lama_tetap(): void
    {
        $lama = $this->surat('SKD', '2026-11-11');
        $this->assertSame('001/SKD/DS/XI/2026', $lama->nomor_surat);

        $this->simpan(['kode_desa' => 'SKM']);

        $this->assertSame('002/SKD/SKM/XI/2026', $this->surat('SKD', '2026-11-12')->nomor_surat);
        $this->assertSame('001/SKD/DS/XI/2026', $lama->fresh()->nomor_surat);
    }

    public function test_data_templat_memuat_semua_penanda_desa_dari_profil(): void
    {
        $this->simpan();
        $data = app(LayananSurat::class)->dataTemplat($this->surat()->load('warga'));

        $this->assertSame('Sukamaju', $data['nama_desa']);
        $this->assertSame('Lowokwaru', $data['nama_kecamatan']);
        $this->assertSame('Malang', $data['nama_kabupaten']);
        $this->assertSame('Jl. Raya Sukamaju No. 1', $data['alamat_kantor_desa']);
        $this->assertSame('65141', $data['kode_pos']);
        $this->assertSame('(0341) 555-123', $data['telepon_desa']);
        $this->assertSame('Drs. Slamet Riyadi', $data['nama_kepala_desa']);
        $this->assertEqualsCanonicalizing(array_keys(Penanda::DAFTAR), array_keys($data));
    }

    public function test_data_contoh_pratinjau_memakai_profil_desa(): void
    {
        $this->simpan();
        $contoh = $this->app->make(TemplatRenderer::class)->dataContoh('SKD');

        $this->assertSame('Sukamaju', $contoh['nama_desa']);
        $this->assertStringContainsString('/SKM/', $contoh['nomor_surat']);
    }

    public function test_penanda_desa_dan_label_berkurung_pada_templat_terisi_dari_profil(): void
    {
        $this->simpan();
        $data = $this->app->make(TemplatRenderer::class)->dataContoh('SKD');

        $teks = $this->isiTemplat(implode("\n", [
            'Penanda: {{ nama_desa }} / ${nama_kecamatan}',
            'KOP: PEMERINTAH KABUPATEN [NAMA KABUPATEN]',
            'KECAMATAN [Nama Kecamatan], DESA [NAMA DESA]',
            'Alamat: [Alamat Kantor Desa], Kode Pos [xxxxx] | Telp: [Nomor Telepon]',
            'Kepala: [Nama Terang Kepala Desa]',
            'Biarkan: [Catatan Lain]',
        ]), $data);

        $this->assertStringContainsString('Penanda: Sukamaju / Lowokwaru', $teks);
        $this->assertStringContainsString('PEMERINTAH KABUPATEN Malang', $teks);
        $this->assertStringContainsString('KECAMATAN Lowokwaru, DESA Sukamaju', $teks);
        $this->assertStringContainsString('Alamat: Jl. Raya Sukamaju No. 1, Kode Pos 65141 | Telp: (0341) 555-123', $teks);
        $this->assertStringContainsString('Kepala: Drs. Slamet Riyadi', $teks);
        $this->assertStringContainsString('Biarkan: [Catatan Lain]', $teks, 'label yang tidak dikenal dibiarkan');
    }

    public function test_label_berkurung_dibiarkan_bila_datanya_belum_diisi(): void
    {
        // Hanya nama desa dan kepala yang terisi (nilai awal); kecamatan, alamat, dan seterusnya kosong.
        $data = $this->app->make(TemplatRenderer::class)->dataContoh('SKD');

        $teks = $this->isiTemplat('Desa [NAMA DESA], kecamatan [NAMA KECAMATAN], pos [xxxxx]', $data);

        $this->assertStringContainsString('Desa '.$data['nama_desa'], $teks);
        $this->assertStringContainsString('kecamatan [NAMA KECAMATAN], pos [xxxxx]', $teks, 'tetap terlihat agar admin melengkapi profil');
    }

    public function test_isi_data_warga_yang_menyerupai_label_berkurung_tidak_diganti(): void
    {
        $this->simpan();
        $data = $this->app->make(TemplatRenderer::class)->dataContoh('SKD');
        $data['alamat'] = 'Dekat [NAMA DESA] lama';

        $teks = $this->isiTemplat('Alamat: {{ alamat }} | Desa: [NAMA DESA]', $data);

        $this->assertStringContainsString('Alamat: Dekat [NAMA DESA] lama', $teks, 'nilai dari data dibiarkan apa adanya');
        $this->assertStringContainsString('Desa: Sukamaju', $teks, 'label milik templat tetap diisi');
    }

    public function test_pdf_surat_memakai_profil_desa_pada_pengisian_templat(): void
    {
        $this->simpan(['nama_desa' => 'Sukamaju Baru']);
        $surat = $this->surat();

        $this->assertNull(app(LayananSurat::class)->buatPdf($surat));
        $this->assertSame('Sukamaju Baru', app(LayananSurat::class)->dataTemplat($surat->load('warga'))['nama_desa']);
    }

    public function test_halaman_tidak_galat_bila_tabel_profil_desa_belum_dibuat(): void
    {
        Schema::drop('profil_desa');

        $this->actingAs($this->operator())->get('/rekap')->assertOk();
        $this->actingAs($this->operator())->get('/surat/buat')->assertOk();
        $this->assertSame('DS', ProfilDesa::saatIni()->kode_desa, 'jatuh ke nilai awal dari konfigurasi');
        $this->assertSame('001/SKD/DS/XI/2026', $this->surat('SKD', '2026-11-11')->nomor_surat);
    }

    /* ---------- seeder dan kop cetak ---------- */

    public function test_seeder_membuat_profil_sekali_dan_tidak_menimpa_isian_administrator(): void
    {
        $this->seed(ProfilDesaSeeder::class);
        $this->assertDatabaseCount('profil_desa', 1);

        $this->simpan(['nama_desa' => 'Diubah Admin']);
        $this->seed(ProfilDesaSeeder::class);

        $this->assertDatabaseCount('profil_desa', 1);
        $this->assertSame('Diubah Admin', ProfilDesa::first()->nama_desa);
    }

    public function test_kop_cetak_rekap_memakai_nama_desa_dari_profil(): void
    {
        $this->simpan(['nama_desa' => 'Sukamaju']);

        $this->actingAs($this->operator())->get('/rekap')->assertSee('Pemerintah Desa Sukamaju');
    }
}
