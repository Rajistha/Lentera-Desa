<?php

namespace Tests\Feature;

use App\Models\Warga;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

class WargaTest extends TestCase
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
            'nik' => '3573010101900001', 'nama' => 'Budi Santoso', 'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '1990-01-01', 'jenis_kelamin' => 'L', 'status_perkawinan' => 'kawin',
            'alamat' => 'Jl. Mawar No. 4', 'rt' => '1', 'rw' => '2', 'pekerjaan' => 'Petani',
        ];
    }

    public function test_operator_menambah_warga_dan_rt_rw_dirapikan_dua_digit(): void
    {
        $this->actingAs($this->operator())->post('/warga', $this->data())
            ->assertRedirect('/warga')->assertSessionHas('sukses');

        $this->assertDatabaseHas('warga', ['nik' => '3573010101900001', 'nama' => 'Budi Santoso', 'rt' => '01', 'rw' => '02']);
    }

    #[DataProvider('aturanValidasi')]
    public function test_validasi_formulir_warga(string $kolom, string $nilai, string $pesan): void
    {
        $this->actingAs($this->operator())->from('/warga/create')
            ->post('/warga', $this->data([$kolom => $nilai]))
            ->assertRedirect('/warga/create')
            ->assertSessionHasErrors([$kolom => $pesan]);

        $this->assertDatabaseCount('warga', 0);
    }

    public static function aturanValidasi(): iterable
    {
        yield 'NIK kosong' => ['nik', '', 'NIK wajib diisi.'];
        yield 'NIK 15 digit' => ['nik', '123456789012345', 'NIK harus 16 digit angka.'];
        yield 'NIK 17 digit' => ['nik', '12345678901234567', 'NIK harus 16 digit angka.'];
        yield 'NIK berisi huruf' => ['nik', '35730101019000AB', 'NIK harus 16 digit angka.'];
        yield 'nama kosong' => ['nama', '', 'Nama lengkap wajib diisi.'];
        yield 'tempat lahir kosong' => ['tempat_lahir', '', 'Tempat lahir wajib diisi.'];
        yield 'tanggal lahir kosong' => ['tanggal_lahir', '', 'Tanggal lahir wajib diisi.'];
        yield 'tanggal lahir melewati hari ini' => ['tanggal_lahir', date('Y-m-d', strtotime('+1 day')), 'Tanggal lahir tidak boleh melewati hari ini.'];
        yield 'tanggal lahir ngawur' => ['tanggal_lahir', '31-31-2020', 'Tanggal lahir tidak valid.'];
        yield 'jenis kelamin kosong' => ['jenis_kelamin', '', 'Jenis kelamin wajib diisi.'];
        yield 'jenis kelamin palsu' => ['jenis_kelamin', 'X', 'Pilihan Jenis kelamin tidak valid.'];
        yield 'status perkawinan palsu' => ['status_perkawinan', 'lajang', 'Pilihan Status perkawinan tidak valid.'];
        yield 'alamat kosong' => ['alamat', '', 'Alamat wajib diisi.'];
        yield 'RT huruf' => ['rt', 'ab', 'RT harus berupa angka (maksimal 3 digit).'];
        yield 'RW huruf' => ['rw', 'x1', 'RW harus berupa angka (maksimal 3 digit).'];
        yield 'RT kosong' => ['rt', '', 'RT wajib diisi.'];
    }

    public function test_pekerjaan_boleh_kosong(): void
    {
        $this->actingAs($this->operator())->post('/warga', $this->data(['pekerjaan' => '']))->assertSessionHasNoErrors();
        $this->assertNull(Warga::first()->pekerjaan);
    }

    public function test_nik_harus_unik(): void
    {
        $this->warga(['nik' => '3573010101900001']);

        $this->actingAs($this->operator())->from('/warga/create')->post('/warga', $this->data())
            ->assertSessionHasErrors(['nik' => 'NIK sudah terdaftar.']);
        $this->assertDatabaseCount('warga', 1);
    }

    public function test_mengubah_warga_tidak_menganggap_nik_sendiri_sebagai_ganda(): void
    {
        $warga = $this->warga(['nik' => '3573010101900001']);

        $this->actingAs($this->operator())->put("/warga/{$warga->id}", $this->data(['nama' => 'Nama Baru']))
            ->assertRedirect('/warga')->assertSessionHasNoErrors();

        $this->assertSame('Nama Baru', $warga->fresh()->nama);
    }

    public function test_mengubah_nik_ke_nik_warga_lain_ditolak(): void
    {
        $this->warga(['nik' => '3573010101900002']);
        $warga = $this->warga(['nik' => '3573010101900001']);

        $this->actingAs($this->operator())->from("/warga/{$warga->id}/edit")
            ->put("/warga/{$warga->id}", $this->data(['nik' => '3573010101900002']))
            ->assertSessionHasErrors(['nik' => 'NIK sudah terdaftar.']);
    }

    public function test_warga_tanpa_surat_dapat_dihapus(): void
    {
        $warga = $this->warga();

        $this->actingAs($this->operator())->delete("/warga/{$warga->id}")
            ->assertRedirect('/warga')->assertSessionHas('sukses');
        $this->assertModelMissing($warga);
    }

    public function test_warga_yang_sudah_punya_surat_tidak_dapat_dihapus(): void
    {
        $warga = $this->warga();
        $this->surat('SKD', '2026-11-11', $warga);

        $this->actingAs($this->operator())->delete("/warga/{$warga->id}")
            ->assertSessionHas('galat', "{$warga->nama} tidak dapat dihapus karena sudah memiliki surat.");
        $this->assertModelExists($warga);
    }

    public function test_pencarian_berdasarkan_nama_dan_nik(): void
    {
        $this->warga(['nik' => '3573010101900001', 'nama' => 'Budi Santoso']);
        $this->warga(['nik' => '3573010101900002', 'nama' => 'Siti Aminah']);
        $operator = $this->operator();

        $this->actingAs($operator)->get('/warga?q=Siti')->assertSee('Siti Aminah')->assertDontSee('Budi Santoso');
        $this->actingAs($operator)->get('/warga?q=3573010101900001')->assertSee('Budi Santoso')->assertDontSee('Siti Aminah');
        $this->actingAs($operator)->get('/warga?q=tidak-ada')->assertSee('Data warga tidak ditemukan');
    }

    public function test_daftar_warga_dibagi_per_halaman(): void
    {
        foreach (range(1, 12) as $i) {
            $this->warga(['nama' => sprintf('Warga %02d', $i)]);
        }

        $this->actingAs($this->operator())->get('/warga')
            ->assertSee('Menampilkan 1-10 dari 12 warga')->assertSee('Warga 01')->assertDontSee('Warga 11');
        $this->actingAs($this->operator())->get('/warga?page=2')
            ->assertSee('Menampilkan 11-12 dari 12 warga')->assertSee('Warga 11');
    }
}
