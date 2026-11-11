<?php

namespace Tests\Feature;

use App\Models\Warga;
use App\Services\ImporWarga;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

class ImporWargaTest extends TestCase
{
    use SiapkanData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanData();
    }

    private function baris(string $nik, array $ubah = []): array
    {
        return array_replace([$nik, 'Warga '.substr($nik, -4), 'Malang', '1990-05-31', 'L', 'Kawin', 'Jl. Uji', '1', '2', 'Petani'], $ubah);
    }

    private function xlsx(array $baris, ?array $judul = null, string $nama = 'warga.xlsx'): UploadedFile
    {
        $jalur = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        $penulis = new Writer;
        $penulis->openToFile($jalur);
        $penulis->addRow(Row::fromValues($judul ?? array_keys(ImporWarga::KOLOM)));
        foreach ($baris as $b) {
            $penulis->addRow(Row::fromValues($b));
        }
        $penulis->close();

        return new UploadedFile($jalur, $nama, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function impor(UploadedFile $berkas)
    {
        return $this->actingAs($this->operator())->from('/warga/impor')->post('/warga/impor', ['berkas' => $berkas]);
    }

    public function test_berkas_valid_menyimpan_semua_baris_dan_menormalkan_format(): void
    {
        $this->impor($this->xlsx([
            $this->baris('3573010101900001', [3 => '31-05-1990', 4 => 'Laki-laki', 5 => 'Belum kawin']),
            $this->baris('3573010101900002', [3 => '1992-02-03', 4 => 'perempuan', 5 => 'cerai hidup', 9 => '']),
        ]))->assertRedirect('/warga/impor')->assertSessionHas('laporan_impor');

        $laporan = session('laporan_impor');
        $this->assertSame(2, $laporan['berhasil']);
        $this->assertSame([], $laporan['gagal']);

        $a = Warga::where('nik', '3573010101900001')->first();
        $this->assertSame('1990-05-31', $a->tanggal_lahir->toDateString());
        $this->assertSame('L', $a->jenis_kelamin);
        $this->assertSame('belum_kawin', $a->status_perkawinan);
        $this->assertSame('01', $a->rt);

        $b = Warga::where('nik', '3573010101900002')->first();
        $this->assertSame('P', $b->jenis_kelamin);
        $this->assertSame('cerai_hidup', $b->status_perkawinan);
        $this->assertNull($b->pekerjaan);
    }

    public function test_baris_campuran_valid_disimpan_dan_yang_gagal_dilaporkan_dengan_nomor_baris_excel(): void
    {
        $this->warga(['nik' => '3573010101900011']); // sudah ada di basis data

        $this->impor($this->xlsx([
            $this->baris('3573010101900001'),                                            // baris 2: valid
            $this->baris('3573010101900002'),                                            // baris 3: valid
            ['', '', '', '', '', '', '', '', '', ''],                                    // baris 4: kosong (dilewati)
            $this->baris('123'),                                                         // baris 5: NIK pendek
            $this->baris('3573010101900001'),                                            // baris 6: ganda di dalam berkas
            $this->baris('x', [0 => 3573010101900009]),                                  // baris 7: NIK berupa angka
            $this->baris('3573010101900010', [1 => '', 3 => '2999-01-01', 4 => 'X', 7 => 'ab']), // baris 8: banyak galat
            $this->baris('3573010101900011'),                                            // baris 9: sudah ada di basis data
        ]));

        $laporan = session('laporan_impor');
        $this->assertSame(2, $laporan['berhasil']);
        $this->assertSame([5, 6, 7, 8, 9], array_column($laporan['gagal'], 'baris'));

        $alasan = collect($laporan['gagal'])->keyBy('baris')->map(fn ($g) => implode(' | ', $g['alasan']));
        $this->assertStringContainsString('NIK harus 16 digit angka.', $alasan[5]);
        $this->assertStringContainsString('NIK ganda dalam berkas (sudah ada di baris 2)', $alasan[6]);
        $this->assertStringContainsString('NIK harus berformat teks', $alasan[7]);
        $this->assertStringContainsString('Nama lengkap wajib diisi.', $alasan[8]);
        $this->assertStringContainsString('Tanggal lahir tidak boleh melewati hari ini.', $alasan[8]);
        $this->assertStringContainsString('RT harus berupa angka', $alasan[8]);
        $this->assertStringContainsString('NIK sudah terdaftar.', $alasan[9]);

        $this->assertSame(['3573010101900001', '3573010101900002', '3573010101900011'], Warga::orderBy('nik')->pluck('nik')->all());
    }

    public function test_hasil_impor_ditampilkan_di_halaman(): void
    {
        $this->impor($this->xlsx([$this->baris('3573010101900001'), $this->baris('1')]));

        $this->actingAs($this->operator())->withSession(['laporan_impor' => session('laporan_impor')])->get('/warga/impor')
            ->assertSee('1 baris berhasil disimpan')->assertSee('1 baris gagal')->assertSee('NIK harus 16 digit angka.');
    }

    public function test_judul_kolom_harus_sesuai_templat(): void
    {
        $this->impor($this->xlsx([$this->baris('3573010101900001')], ['NIK', 'Nama']))
            ->assertSessionHasErrors(['berkas' => 'Judul kolom tidak sesuai templat. Unduh templat terbaru dan salin data ke dalamnya.']);
        $this->assertDatabaseCount('warga', 0);
    }

    public function test_berkas_tanpa_data_ditolak(): void
    {
        $this->impor($this->xlsx([]))->assertSessionHasErrors(['berkas' => 'Berkas tidak berisi data warga.']);
    }

    public function test_batas_seribu_baris(): void
    {
        $banyak = array_map(fn ($i) => $this->baris(sprintf('9998%012d', $i)), range(1, 1001));

        $this->impor($this->xlsx($banyak))->assertSessionHasErrors(['berkas' => 'Berkas berisi lebih dari 1000 baris data.']);
        $this->assertDatabaseCount('warga', 0);

        $this->impor($this->xlsx(array_slice($banyak, 0, 1000)));
        $this->assertDatabaseCount('warga', 1000);
    }

    public function test_berkas_bukan_xlsx_atau_terlalu_besar_ditolak(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->from('/warga/impor')->post('/warga/impor', ['berkas' => UploadedFile::fake()->create('warga.csv', 10, 'text/csv')])
            ->assertSessionHasErrors('berkas');
        $this->actingAs($operator)->from('/warga/impor')->post('/warga/impor', ['berkas' => UploadedFile::fake()->create('besar.xlsx', 2049)])
            ->assertSessionHasErrors(['berkas' => 'Ukuran berkas maksimal 2 MB.']);
        $this->actingAs($operator)->from('/warga/impor')->post('/warga/impor', [])
            ->assertSessionHasErrors(['berkas' => 'Pilih berkas Excel (.xlsx) terlebih dahulu.']);
    }

    public function test_templat_dapat_diunduh_dan_hanya_berisi_judul_kolom(): void
    {
        $respons = $this->actingAs($this->operator())->get('/warga/impor/templat');
        $respons->assertOk()->assertDownload('templat-impor-warga.xlsx');

        // Templat kosong tidak boleh terimpor sebagai data (tanpa baris contoh).
        $this->impor(new UploadedFile($respons->baseResponse->getFile()->getPathname(), 'templat.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true))
            ->assertSessionHasErrors(['berkas' => 'Berkas tidak berisi data warga.']);
    }
}
