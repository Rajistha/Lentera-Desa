<?php

namespace Tests\Feature;

use App\Models\Surat;
use App\Services\LayananSurat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

/**
 * Warga yang punya surat tidak bisa dihapus lewat aplikasi, tetapi tabel warga dapat dikosongkan langsung di
 * basis data dengan pemeriksaan kunci asing dimatikan. Aplikasi tidak boleh galat 500 karenanya.
 */
class WargaHilangTest extends TestCase
{
    use SiapkanData;

    private Surat $surat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanData();

        $this->surat = $this->surat();
        app(LayananSurat::class)->buatPdf($this->surat);

        // Mengosongkan tabel warga seperti TRUNCATE di phpMyAdmin dengan pemeriksaan kunci dimatikan. Di SQLite,
        // pemeriksaan ditunda sampai COMMIT; tes selalu di-rollback sehingga pelanggarannya tidak pernah dipaksakan.
        DB::statement('PRAGMA defer_foreign_keys = ON');
        DB::table('warga')->delete();
    }

    public function test_tabel_warga_memang_kosong_tetapi_surat_masih_menunjuknya(): void
    {
        $this->assertSame(0, DB::table('warga')->count());
        $this->assertSame(1, Surat::count());
        $this->assertFalse($this->surat->fresh()->warga->exists);
    }

    public function test_halaman_tidak_galat_dan_menandai_warga_yang_hilang(): void
    {
        $operator = $this->operator();

        foreach (['/surat', '/dasbor', "/surat/{$this->surat->id}", "/surat/{$this->surat->id}/ubah", '/surat?q=Warga', '/rekap'] as $url) {
            $this->actingAs($operator)->get($url)->assertOk();
        }

        $this->actingAs($operator)->get('/surat')->assertSee('Data tidak ditemukan')->assertSee('001/SKD/DS/XI/2026');
        $this->actingAs($operator)->get('/dasbor')->assertSee('Data tidak ditemukan');
    }

    public function test_detail_memberi_tahu_agar_surat_diubah_dan_memakai_nilai_pengganti(): void
    {
        $this->actingAs($this->operator())->get("/surat/{$this->surat->id}")
            ->assertOk()
            ->assertSee('Warga pada surat ini sudah tidak ada di data warga')
            ->assertSee('Tekan Ubah dan pilih warga yang benar');
    }

    public function test_pdf_tidak_dibuat_ulang_tanpa_warga_dan_tidak_galat(): void
    {
        $pesan = app(LayananSurat::class)->buatPdf($this->surat->fresh());
        $this->assertSame('Data warga untuk surat ini sudah tidak ada. Ubah surat dan pilih warga yang benar.', $pesan);

        $this->actingAs($this->operator())->post("/surat/{$this->surat->id}/buat-ulang-pdf")
            ->assertRedirect(route('surat.show', $this->surat))
            ->assertSessionHas('galat', fn ($galat) => str_contains($galat, 'Data warga untuk surat ini sudah tidak ada'));
    }

    public function test_surat_dapat_diperbaiki_dengan_memilih_warga_yang_benar(): void
    {
        $baru = $this->warga(['nama' => 'Warga Pengganti']);

        $this->actingAs($this->operator())->put("/surat/{$this->surat->id}", [
            'warga_id' => $baru->id,
            'tanggal_surat' => '2026-11-11',
            'keperluan' => 'Diperbaiki',
        ])->assertRedirect(route('surat.show', $this->surat))->assertSessionHas('sukses');

        $surat = $this->surat->fresh();
        $this->assertTrue($surat->warga->exists);
        $this->assertSame('Warga Pengganti', $surat->warga->nama);
        Storage::disk('local')->assertExists($surat->berkas_pdf);

        $this->actingAs($this->operator())->get("/surat/{$surat->id}")
            ->assertOk()->assertSee('Warga Pengganti')->assertDontSee('sudah tidak ada di data warga');
    }

    public function test_inisial_avatar_dua_huruf_dan_tanda_tanya_bila_tidak_ada(): void
    {
        $this->assertSame('WP', $this->warga(['nama' => 'Warga Pengganti Baru'])->inisial);
        $this->assertSame('?', $this->surat->fresh()->warga->inisial);
    }
}
