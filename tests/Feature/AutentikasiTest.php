<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

class AutentikasiTest extends TestCase
{
    use SiapkanData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanData();
        RateLimiter::clear('operator|127.0.0.1');
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        foreach (['/', '/dasbor', '/warga', '/surat', '/surat/buat', '/rekap', '/jenis-surat', '/pengguna'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_operator_masuk_ke_dasbor_dan_administrator_ke_kelola_jenis_surat(): void
    {
        $this->post('/login', ['username' => 'operator', 'password' => 'password123'])
            ->assertRedirect(route('dasbor'));
        $this->assertAuthenticatedAs($this->operator());

        $this->post('/logout');
        $this->post('/login', ['username' => 'admin', 'password' => 'password123'])
            ->assertRedirect(route('jenis-surat.index'));
    }

    public function test_login_gagal_menampilkan_pesan_tanpa_membocorkan_penyebab(): void
    {
        $this->from('/login')->post('/login', ['username' => 'operator', 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['password' => 'Username atau kata sandi salah.']);

        $this->from('/login')->post('/login', ['username' => 'tidak-ada', 'password' => 'salah'])
            ->assertSessionHasErrors(['password' => 'Username atau kata sandi salah.']);

        $this->assertGuest();
    }

    public function test_kolom_login_wajib_diisi(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['username' => 'Username wajib diisi.', 'password' => 'Kata sandi wajib diisi.']);
    }

    public function test_percobaan_login_dibatasi_setelah_lima_kegagalan(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post('/login', ['username' => 'operator', 'password' => 'salah'])
                ->assertSessionHasErrors(['password' => 'Username atau kata sandi salah.']);
        }

        // Percobaan keenam ditolak walau kata sandinya benar.
        $this->post('/login', ['username' => 'operator', 'password' => 'password123'])
            ->assertSessionHasErrors('password');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('password'));
        $this->assertGuest();

        // Akun lain tidak terkena batas.
        $this->post('/login', ['username' => 'admin', 'password' => 'password123']);
        $this->assertAuthenticatedAs($this->admin());
    }

    public function test_login_berhasil_menghapus_hitungan_kegagalan(): void
    {
        foreach (range(1, 4) as $i) {
            $this->post('/login', ['username' => 'operator', 'password' => 'salah']);
        }
        $this->post('/login', ['username' => 'operator', 'password' => 'password123']);
        $this->assertAuthenticatedAs($this->operator());

        $this->post('/logout');
        foreach (range(1, 4) as $i) {
            $this->post('/login', ['username' => 'operator', 'password' => 'salah']);
        }
        $this->post('/login', ['username' => 'operator', 'password' => 'password123']);
        $this->assertAuthenticatedAs($this->operator());
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $this->actingAs($this->operator())->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_pengguna_yang_sudah_masuk_tidak_melihat_halaman_login(): void
    {
        $this->actingAs($this->operator())->get('/login')->assertRedirect('/');
    }

    public function test_operator_tidak_bisa_membuka_rute_administrator(): void
    {
        $operator = $this->operator();
        $surat = $this->surat();

        foreach (['/jenis-surat', '/jenis-surat/1/edit', '/jenis-surat/1/pratinjau', '/pengguna', '/pengguna/1/edit'] as $url) {
            $this->actingAs($operator)->get($url)->assertForbidden();
        }
        $this->actingAs($operator)->post('/pengguna', [])->assertForbidden();
        $this->actingAs($operator)->delete('/jenis-surat/1')->assertForbidden();
        $this->assertNotNull($surat->fresh());
    }

    public function test_administrator_tidak_bisa_membuka_rute_operator(): void
    {
        $admin = $this->admin();
        $surat = $this->surat();

        foreach (['/dasbor', '/warga', '/warga/create', '/surat', '/surat/buat', "/surat/{$surat->id}", "/surat/{$surat->id}/ubah", "/surat/{$surat->id}/unduh", '/rekap', '/warga/impor'] as $url) {
            $this->actingAs($admin)->get($url)->assertForbidden();
        }
        $this->actingAs($admin)->delete("/surat/{$surat->id}")->assertForbidden();
        $this->actingAs($admin)->post('/surat', [])->assertForbidden();
        $this->assertNotNull($surat->fresh());
    }

    public function test_kata_sandi_tersimpan_dalam_bentuk_hash(): void
    {
        $this->assertNotSame('password123', $this->operator()->password);
        $this->assertTrue(password_verify('password123', $this->operator()->password));
    }
}
