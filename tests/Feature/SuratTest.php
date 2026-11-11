<?php

namespace Tests\Feature;

use App\Models\Surat;
use App\Services\LayananSurat;
use App\Services\Penanda;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SiapkanData;
use Tests\Support\RendererPalsu;
use Tests\TestCase;

class SuratTest extends TestCase
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
            'warga_id' => $this->warga()->id,
            'jenis_surat_id' => $this->jenisId('SKD'),
            'tanggal_surat' => '2026-11-11',
            'keperluan' => 'Persyaratan kerja',
            'keterangan' => 'Catatan uji',
        ];
    }

    /* ---------- penomoran (AB-01) ---------- */

    public function test_nomor_berurutan_per_jenis_dan_tahun_dengan_format_yang_benar(): void
    {
        $this->assertSame('001/SKD/DS/XI/2026', $this->surat('SKD', '2026-11-11')->nomor_surat);
        $this->assertSame('002/SKD/DS/XI/2026', $this->surat('SKD', '2026-11-12')->nomor_surat);
        $this->assertSame('001/SKU/DS/XI/2026', $this->surat('SKU', '2026-11-12')->nomor_surat, 'jenis lain memulai urutan sendiri');
        $this->assertSame('001/SKD/DS/XII/2025', $this->surat('SKD', '2025-12-31')->nomor_surat, 'tahun lain memulai urutan sendiri, bulan romawi XII');
        $this->assertSame('003/SKD/DS/XI/2026', $this->surat('SKD', '2026-11-13')->nomor_surat);
    }

    public function test_nomor_tidak_pernah_ganda_pada_banyak_surat(): void
    {
        $nomor = collect(range(1, 15))->map(fn () => $this->surat('SKTM')->nomor_surat);

        $this->assertCount(15, $nomor->unique());
        $this->assertSame(range(1, 15), Surat::orderBy('id')->pluck('nomor_urut')->all());
        $this->assertSame('015/SKTM/DS/XI/2026', $nomor->last());
    }

    public function test_nomor_tiga_digit_dan_bulan_romawi_untuk_semua_bulan(): void
    {
        $romawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        foreach ($romawi as $i => $r) {
            $tanggal = sprintf('2027-%02d-15', $i + 1);
            $this->assertSame(sprintf('%03d/SKP/DS/%s/2027', $i + 1, $r), $this->surat('SKP', $tanggal)->nomor_surat);
        }
    }

    /* ---------- buat surat ---------- */

    public function test_membuat_surat_menyimpan_nomor_dan_menghasilkan_pdf(): void
    {
        $respons = $this->actingAs($this->operator())->post('/surat', $this->data());

        $surat = Surat::firstOrFail();
        $respons->assertRedirect(route('surat.show', $surat))->assertSessionHas('sukses');

        $this->assertSame('001/SKD/DS/XI/2026', $surat->nomor_surat);
        $this->assertSame($this->operator()->id, $surat->user_id);
        $this->assertSame('surat/2026/001-SKD-DS-XI-2026.pdf', $surat->berkas_pdf);
        Storage::disk('local')->assertExists($surat->berkas_pdf);
    }

    public function test_surat_tetap_tersimpan_dengan_nomornya_walau_pdf_gagal_dibuat(): void
    {
        RendererPalsu::$gagal = true;

        $this->actingAs($this->operator())->post('/surat', $this->data())->assertSessionHas('galat');

        $surat = Surat::firstOrFail();
        $this->assertSame('001/SKD/DS/XI/2026', $surat->nomor_surat);
        $this->assertNull($surat->berkas_pdf);

        // PDF dapat dibuat ulang setelah masalahnya teratasi.
        RendererPalsu::$gagal = false;
        $this->actingAs($this->operator())->post("/surat/{$surat->id}/buat-ulang-pdf")->assertSessionHas('sukses');
        Storage::disk('local')->assertExists($surat->fresh()->berkas_pdf);
    }

    public function test_validasi_formulir_buat_surat(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->from('/surat/buat')->post('/surat', [])
            ->assertSessionHasErrors(['warga_id', 'jenis_surat_id', 'tanggal_surat', 'keperluan']);

        $this->actingAs($operator)->post('/surat', $this->data(['keperluan' => str_repeat('a', 256)]))
            ->assertSessionHasErrors(['keperluan' => 'Keperluan maksimal 255 karakter.']);
        $this->actingAs($operator)->post('/surat', $this->data(['warga_id' => 99999]))
            ->assertSessionHasErrors('warga_id');
        $this->actingAs($operator)->post('/surat', $this->data(['jenis_surat_id' => 99999]))
            ->assertSessionHasErrors('jenis_surat_id');
        $this->actingAs($operator)->post('/surat', $this->data(['tanggal_surat' => 'bukan-tanggal']))
            ->assertSessionHasErrors('tanggal_surat');

        $this->assertDatabaseCount('surat', 0);
    }

    public function test_keterangan_boleh_kosong(): void
    {
        $this->actingAs($this->operator())->post('/surat', $this->data(['keterangan' => '']))->assertSessionHasNoErrors();
        $this->assertNull(Surat::first()->keterangan);
    }

    /* ---------- saran warga pada formulir buat surat ---------- */

    public function test_saran_warga_dibatasi_sepuluh_dan_melaporkan_total_serta_urut_nama(): void
    {
        foreach (range(1, 25) as $i) {
            $this->warga(['nama' => sprintf('Cari Uji %02d', 26 - $i)]); // dibuat terbalik agar urutan nama teruji
        }
        $this->warga(['nama' => 'Tidak Ikut']);

        $json = $this->actingAs($this->operator())->getJson('/surat/cari-warga?q=Cari Uji')->assertOk()->json();

        $this->assertSame(10, $json['batas']);
        $this->assertSame(25, $json['total'], 'total mencakup yang tidak ditampilkan');
        $this->assertCount(10, $json['data']);
        $this->assertSame('Cari Uji 01', $json['data'][0]['nama']);
        $this->assertSame('Cari Uji 10', $json['data'][9]['nama']);
        $this->assertSame(array_column($json['data'], 'nama'), collect($json['data'])->pluck('nama')->sort()->values()->all());
    }

    public function test_saran_warga_tanpa_pemotongan_bila_hasil_kurang_dari_batas(): void
    {
        $this->warga(['nik' => '3573010101900001', 'nama' => 'Budi Santoso']);
        $this->warga(['nik' => '3573010101900002', 'nama' => 'Siti Aminah']);

        $json = $this->actingAs($this->operator())->getJson('/surat/cari-warga?q=3573')->json();
        $this->assertSame(2, $json['total']);
        $this->assertCount(2, $json['data']);
        $this->assertSame(['id', 'nik', 'nama', 'ttl', 'jenis_kelamin', 'alamat', 'pekerjaan', 'status'], array_keys($json['data'][0]));

        $this->assertSame(1, $this->actingAs($this->operator())->getJson('/surat/cari-warga?q=Siti')->json('total'));
        $this->assertSame(0, $this->actingAs($this->operator())->getJson('/surat/cari-warga?q=tidak-ada')->json('total'));
    }

    public function test_saran_warga_butuh_dua_karakter_dan_hanya_untuk_operator(): void
    {
        $this->warga(['nama' => 'Budi']);

        $this->assertSame(['data' => [], 'total' => 0, 'batas' => 10], $this->actingAs($this->operator())->getJson('/surat/cari-warga?q=B')->json());
        $this->assertSame(['data' => [], 'total' => 0, 'batas' => 10], $this->actingAs($this->operator())->getJson('/surat/cari-warga')->json());
        $this->actingAs($this->admin())->getJson('/surat/cari-warga?q=Bu')->assertForbidden();
    }

    /* ---------- detail dan unduh ---------- */

    public function test_detail_menampilkan_data_surat_dan_tombol_aksi(): void
    {
        $surat = $this->surat();
        $surat->warga->update(['nama' => 'Budi Santoso']);
        app(LayananSurat::class)->buatPdf($surat);

        $this->actingAs($this->operator())->get("/surat/{$surat->id}")
            ->assertOk()->assertSee('001/SKD/DS/XI/2026')->assertSee('Budi Santoso')
            ->assertSee('Persyaratan kerja')->assertSee('Unduh PDF')->assertSee('Ubah')->assertSee('Hapus');
    }

    public function test_pdf_dapat_dilihat_dan_diunduh_berulang_kali(): void
    {
        $surat = $this->surat();
        app(LayananSurat::class)->buatPdf($surat);
        $operator = $this->operator();

        foreach (range(1, 2) as $i) {
            $this->actingAs($operator)->get("/surat/{$surat->id}/unduh")
                ->assertOk()->assertHeader('Content-Type', 'application/pdf')
                ->assertDownload('Surat-001-SKD-DS-XI-2026.pdf');
        }
        $this->actingAs($operator)->get("/surat/{$surat->id}/pdf")
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_unduh_tanpa_pdf_diarahkan_kembali_dengan_pesan(): void
    {
        $surat = $this->surat();

        $this->actingAs($this->operator())->get("/surat/{$surat->id}/unduh")
            ->assertRedirect(route('surat.show', $surat))->assertSessionHas('galat');
    }

    /* ---------- ubah (AB-03) ---------- */

    public function test_mengubah_surat_menjaga_nomor_dan_jenis_serta_membuat_ulang_pdf(): void
    {
        $surat = $this->surat('SKD', '2026-11-11');
        app(LayananSurat::class)->buatPdf($surat);
        $nomor = $surat->nomor_surat;
        $wargaBaru = $this->warga(['nama' => 'Warga Pengganti']);

        $this->actingAs($this->operator())->put("/surat/{$surat->id}", [
            'warga_id' => $wargaBaru->id,
            'tanggal_surat' => '2026-12-05',
            'keperluan' => 'Keperluan baru',
            'keterangan' => 'Keterangan baru',
            // Upaya menyelundupkan perubahan nomor dan jenis harus diabaikan.
            'jenis_surat_id' => $this->jenisId('SKU'),
            'nomor_surat' => '999/XXX',
            'nomor_urut' => 77,
        ])->assertRedirect(route('surat.show', $surat))->assertSessionHas('sukses');

        $surat->refresh();
        $this->assertSame($nomor, $surat->nomor_surat);
        $this->assertSame(1, $surat->nomor_urut);
        $this->assertSame($this->jenisId('SKD'), $surat->jenis_surat_id);
        $this->assertSame($wargaBaru->id, $surat->warga_id);
        $this->assertSame('Keperluan baru', $surat->keperluan);
        $this->assertSame('2026-12-05', $surat->tanggal_surat->toDateString());
        Storage::disk('local')->assertExists($surat->berkas_pdf);
    }

    public function test_pdf_lama_dihapus_bila_pdf_baru_gagal_agar_tidak_ada_berkas_usang(): void
    {
        $surat = $this->surat();
        app(LayananSurat::class)->buatPdf($surat);
        $lama = $surat->fresh()->berkas_pdf;
        Storage::disk('local')->assertExists($lama);

        RendererPalsu::$gagal = true;
        $this->actingAs($this->operator())->put("/surat/{$surat->id}", [
            'warga_id' => $surat->warga_id, 'tanggal_surat' => '2026-11-11', 'keperluan' => 'Diubah',
        ])->assertSessionHas('galat');

        $this->assertSame('Diubah', $surat->fresh()->keperluan, 'perubahan data tetap tersimpan');
        $this->assertNull($surat->fresh()->berkas_pdf);
        Storage::disk('local')->assertMissing($lama);
    }

    public function test_halaman_ubah_menampilkan_data_dan_mengunci_jenis(): void
    {
        $surat = $this->surat();

        $this->actingAs($this->operator())->get("/surat/{$surat->id}/ubah")
            ->assertOk()->assertSee('Ubah Surat')->assertSee('nomor dan jenis surat tidak berubah')
            ->assertSee('Persyaratan kerja')->assertDontSee('name="jenis_surat_id"', false);
    }

    /* ---------- hapus ---------- */

    public function test_menghapus_surat_ikut_menghapus_berkas_pdf(): void
    {
        $surat = $this->surat();
        app(LayananSurat::class)->buatPdf($surat);
        $berkas = $surat->fresh()->berkas_pdf;

        $this->actingAs($this->operator())->delete("/surat/{$surat->id}")
            ->assertRedirect(route('surat.index'))->assertSessionHas('sukses');

        $this->assertModelMissing($surat);
        Storage::disk('local')->assertMissing($berkas);
    }

    public function test_menghapus_surat_tanpa_pdf_tetap_berhasil(): void
    {
        $surat = $this->surat();

        $this->actingAs($this->operator())->delete("/surat/{$surat->id}")->assertSessionHas('sukses');
        $this->assertModelMissing($surat);
    }

    public function test_nomor_surat_terakhir_dipakai_lagi_setelah_dihapus_sesuai_aturan_max_tambah_satu(): void
    {
        $this->surat();
        $kedua = $this->surat();
        $kedua->delete();

        $this->assertSame('002/SKD/DS/XI/2026', $this->surat()->nomor_surat);
    }

    /* ---------- isi templat ---------- */

    public function test_data_templat_memuat_semua_penanda_yang_dikenal(): void
    {
        $surat = $this->surat();
        $data = app(LayananSurat::class)->dataTemplat($surat->load('warga'));

        $this->assertEqualsCanonicalizing(array_keys(Penanda::DAFTAR), array_keys($data));
        $this->assertSame('11 November 2026', $data['tanggal_surat']);
        $this->assertSame('1 Januari 1990', $data['tanggal_lahir']);
        $this->assertSame('Laki-laki', $data['jenis_kelamin']);
        $this->assertSame('Kawin', $data['status_perkawinan']);
    }
}
