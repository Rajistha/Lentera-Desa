<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Services\LayananSurat;
use Illuminate\Support\Carbon;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

class ArsipRekapTest extends TestCase
{
    use SiapkanData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanData();
        Carbon::setTestNow('2026-11-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /*
     * Data tetap (dibuat berurutan, jadi nomor urut mengikuti urutan pembuatan per jenis dan tahun):
     *  id  jenis  tanggal      warga  nomor surat
     *   1  SKD    2026-11-01   Satu   001/SKD/DS/XI/2026
     *   2  SKD    2026-11-15   Dua    002/SKD/DS/XI/2026
     *   3  SKD    2026-11-30   Satu   003/SKD/DS/XI/2026
     *   4  SKU    2026-11-10   Satu   001/SKU/DS/XI/2026
     *   5  SKU    2026-11-20   Dua    002/SKU/DS/XI/2026
     *   6  SKTM   2026-11-25   Dua    001/SKTM/DS/XI/2026
     *   7  SKD    2026-10-31   Satu   004/SKD/DS/X/2026
     *   8  SKD    2026-10-01   Dua    005/SKD/DS/X/2026
     *   9  SKU    2026-12-01   Satu   003/SKU/DS/XII/2026
     *  10  SKU    2025-11-12   Dua    001/SKU/DS/XI/2025
     */
    private function isiData(): void
    {
        $satu = $this->warga(['nik' => '3573000000000001', 'nama' => 'Arsip Satu']);
        $dua = $this->warga(['nik' => '3573000000000002', 'nama' => 'Arsip Dua']);

        foreach ([
            [$satu, 'SKD', '2026-11-01'], [$dua, 'SKD', '2026-11-15'], [$satu, 'SKD', '2026-11-30'],
            [$satu, 'SKU', '2026-11-10'], [$dua, 'SKU', '2026-11-20'], [$dua, 'SKTM', '2026-11-25'],
            [$satu, 'SKD', '2026-10-31'], [$dua, 'SKD', '2026-10-01'],
            [$satu, 'SKU', '2026-12-01'], [$dua, 'SKU', '2025-11-12'],
        ] as [$warga, $kode, $tanggal]) {
            $this->surat($kode, $tanggal, $warga);
        }
    }

    /** Nomor surat pada tabel (arsip atau dasbor), sesuai urutan tampil. */
    private function nomorDi(string $url): array
    {
        $isi = $this->actingAs($this->operator())->get($url)->assertOk()->getContent();
        preg_match_all('/aria-label="Lihat surat ([^"]+)"/', $isi, $cocok);

        return $cocok[1];
    }

    /** Baris tabel rekap [nama jenis => jumlah], termasuk baris TOTAL. */
    private function rekap(string $query = ''): array
    {
        $isi = $this->actingAs($this->operator())->get('/rekap'.$query)->assertOk()->getContent();
        preg_match_all('/<td class="px-3">([^<]+)<\/td>\s*<td class="px-3">([\d.]+)<\/td>/', $isi, $cocok);

        return array_combine(array_map('trim', $cocok[1]), array_map(fn ($n) => (int) str_replace('.', '', $n), $cocok[2]));
    }

    private function nama(string $kode): string
    {
        return JenisSurat::where('kode', $kode)->value('nama');
    }

    /* ---------- arsip ---------- */

    public function test_arsip_diurutkan_dari_tanggal_terbaru(): void
    {
        $this->isiData();

        $this->assertSame([
            '003/SKU/DS/XII/2026', '003/SKD/DS/XI/2026', '001/SKTM/DS/XI/2026', '002/SKU/DS/XI/2026', '002/SKD/DS/XI/2026',
            '001/SKU/DS/XI/2026', '001/SKD/DS/XI/2026', '004/SKD/DS/X/2026', '005/SKD/DS/X/2026', '001/SKU/DS/XI/2025',
        ], $this->nomorDi('/surat'));
    }

    public function test_arsip_dibagi_sepuluh_per_halaman_dan_filter_ikut_ke_halaman_berikutnya(): void
    {
        $this->isiData();
        foreach (range(1, 3) as $i) {
            $this->surat('SKP', '2026-11-0'.$i);
        }
        $operator = $this->operator();

        $this->actingAs($operator)->get('/surat')->assertSee('Menampilkan 1-10 dari 13 surat');
        $this->assertCount(3, $this->nomorDi('/surat?page=2'));
        $this->actingAs($operator)->get('/surat?page=2')->assertSee('Menampilkan 11-13 dari 13 surat');
        $this->actingAs($operator)->get('/surat?dari=2025-01-01')->assertSee('dari=2025-01-01&amp;page=2', false);
    }

    public function test_filter_jenis(): void
    {
        $this->isiData();

        $this->assertCount(5, $this->nomorDi('/surat?jenis='.$this->jenisId('SKD')));
        $this->assertCount(4, $this->nomorDi('/surat?jenis='.$this->jenisId('SKU')));
        $this->assertCount(1, $this->nomorDi('/surat?jenis='.$this->jenisId('SKTM')));
        $this->assertCount(0, $this->nomorDi('/surat?jenis='.$this->jenisId('SKP')));
    }

    public function test_filter_rentang_tanggal_inklusif_di_kedua_ujung(): void
    {
        $this->isiData();

        $this->assertCount(6, $this->nomorDi('/surat?dari=2026-11-01&sampai=2026-11-30'), 'tanggal 1 dan 30 ikut');
        $this->assertCount(1, $this->nomorDi('/surat?dari=2026-11-15&sampai=2026-11-15'), 'satu hari');
        $this->assertCount(3, $this->nomorDi('/surat?sampai=2026-10-31'), 'hanya batas akhir');
        $this->assertCount(1, $this->nomorDi('/surat?dari=2026-12-01'), 'hanya batas awal');
        $this->assertCount(0, $this->nomorDi('/surat?dari=2027-01-01'));
    }

    public function test_pencarian_nama_nik_dan_nomor_surat(): void
    {
        $this->isiData();

        $this->assertCount(5, $this->nomorDi('/surat?q=Arsip+Satu'));
        $this->assertCount(5, $this->nomorDi('/surat?q=3573000000000002'));
        $this->assertSame(['001/SKD/DS/XI/2026'], $this->nomorDi('/surat?q=001/SKD'));
        $this->assertCount(10, $this->nomorDi('/surat?q=Arsip'));
        $this->assertCount(0, $this->nomorDi('/surat?q=tidak-ada'));
    }

    public function test_kombinasi_filter(): void
    {
        $this->isiData();

        $this->assertSame(
            ['003/SKD/DS/XI/2026', '002/SKD/DS/XI/2026', '001/SKD/DS/XI/2026'],
            $this->nomorDi('/surat?jenis='.$this->jenisId('SKD').'&dari=2026-11-01')
        );
        $this->assertSame(
            ['003/SKD/DS/XI/2026', '001/SKD/DS/XI/2026'],
            $this->nomorDi('/surat?jenis='.$this->jenisId('SKD').'&dari=2026-11-01&q=Satu')
        );
    }

    public function test_filter_tidak_valid_diabaikan_dan_galatnya_ditampilkan(): void
    {
        $this->isiData();
        $operator = $this->operator();

        $this->actingAs($operator)->get('/surat?dari=2026-11-15&sampai=2026-11-01')
            ->assertSee('Tanggal akhir harus sama dengan atau setelah tanggal awal.');
        $this->assertCount(5, $this->nomorDi('/surat?dari=2026-11-15&sampai=2026-11-01'), 'hanya "dari" yang dipakai');

        $this->actingAs($operator)->get('/surat?jenis=999')->assertSee('Jenis surat tidak tersedia.');
        $this->assertCount(10, $this->nomorDi('/surat?jenis=999'));

        $this->actingAs($operator)->get('/surat?dari=abc')->assertSee('Tanggal awal tidak valid.');
        $this->assertCount(10, $this->nomorDi('/surat?dari=abc'));
    }

    public function test_pesan_hasil_kosong_dan_tombol_reset(): void
    {
        $operator = $this->operator();
        $this->actingAs($operator)->get('/surat')->assertSee('Belum ada surat yang dibuat')->assertDontSee('Reset');

        $this->isiData();
        $this->actingAs($operator)->get('/surat?q=tidak-ada')->assertSee('Surat tidak ditemukan')->assertSee('Reset');
        $this->actingAs($operator)->get('/surat')->assertDontSee('>Reset<', false);
    }

    public function test_ikon_unduh_nonaktif_bila_pdf_belum_ada(): void
    {
        $surat = $this->surat();
        $operator = $this->operator();

        $this->actingAs($operator)->get('/surat')->assertSee('PDF belum tersedia')->assertDontSee('Unduh PDF '.$surat->nomor_surat);

        app(LayananSurat::class)->buatPdf($surat);
        $this->actingAs($operator)->get('/surat')->assertSee('Unduh PDF '.$surat->nomor_surat);
    }

    /* ---------- dasbor ---------- */

    public function test_dasbor_menampilkan_ringkasan_dan_lima_surat_terbaru(): void
    {
        $this->isiData();
        $isi = $this->actingAs($this->operator())->get('/dasbor')->assertOk()->getContent();

        preg_match_all('/text-\[34px\] font-bold leading-none">([\d.]+)</', $isi, $kartu);
        $this->assertSame(['2', '6', '4'], $kartu[1], 'warga, surat bulan ini (November 2026), jenis surat');

        $this->assertSame([
            '001/SKU/DS/XI/2025', '003/SKU/DS/XII/2026', '005/SKD/DS/X/2026', '004/SKD/DS/X/2026', '001/SKTM/DS/XI/2026',
        ], $this->nomorDi('/dasbor'));
        $this->assertStringNotContainsString('Pintasan', $isi);
    }

    public function test_dasbor_tanpa_surat(): void
    {
        $this->actingAs($this->operator())->get('/dasbor')->assertOk()->assertSee('Belum ada surat yang dibuat');
    }

    /* ---------- rekap ---------- */

    public function test_rekap_bulan_berjalan_per_jenis_dengan_total(): void
    {
        $this->isiData();

        $this->assertSame([
            $this->nama('SKD') => 3, $this->nama('SKU') => 2, $this->nama('SKTM') => 1, $this->nama('SKP') => 0, 'TOTAL' => 6,
        ], $this->rekap(), 'urut terbanyak ke sedikit; jenis tanpa surat tetap tampil 0');
    }

    public function test_rekap_batas_bulan_dan_tahun_lain(): void
    {
        $this->isiData();

        $this->assertSame(2, $this->rekap('?bulan=10&tahun=2026')['TOTAL'], '1 dan 31 Oktober');
        $this->assertSame(1, $this->rekap('?bulan=12&tahun=2026')['TOTAL'], '1 Desember tidak ikut November');
        $this->assertSame([$this->nama('SKU') => 1], array_filter($this->rekap('?bulan=11&tahun=2025'), fn ($n, $k) => $n > 0 && $k !== 'TOTAL', ARRAY_FILTER_USE_BOTH));
    }

    public function test_rekap_bulan_tanpa_surat_menampilkan_keterangan_tanpa_grafik(): void
    {
        $this->isiData();

        $this->assertSame(0, $this->rekap('?bulan=1&tahun=2026')['TOTAL']);
        $this->actingAs($this->operator())->get('/rekap?bulan=1&tahun=2026')
            ->assertSee('Belum ada surat pada Januari 2026.')->assertDontSee('role="img"', false);
        $this->actingAs($this->operator())->get('/rekap')->assertSee('role="img"', false);
    }

    public function test_rekap_menyediakan_pilihan_tahun_dari_data_dan_kembali_ke_default_bila_tidak_valid(): void
    {
        $this->isiData();

        $this->actingAs($this->operator())->get('/rekap')->assertSee('data-nilai="2025"', false)->assertSee('data-nilai="2026"', false);

        foreach (['?bulan=13', '?bulan=0', '?bulan=abc', '?tahun=1999', '?tahun=9999&bulan=11'] as $query) {
            $this->actingAs($this->operator())->get('/rekap'.$query)->assertOk();
        }
        $this->assertSame($this->rekap(), $this->rekap('?bulan=13&tahun=1999'));
    }

    public function test_rekap_menyiapkan_tampilan_cetak(): void
    {
        $this->actingAs($this->operator())->get('/rekap')
            ->assertSee('window.print()', false)->assertSee('Rekap Layanan Surat')->assertSee('print:hidden', false);
    }
}
