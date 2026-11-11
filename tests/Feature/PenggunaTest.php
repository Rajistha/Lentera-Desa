<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

class PenggunaTest extends TestCase
{
    use SiapkanData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanData();
    }

    private function data(array $ubah = []): array
    {
        return $ubah + ['username' => 'staf1', 'name' => 'Staf Satu', 'role' => 'operator', 'password' => 'rahasia123', 'jabatan' => 'Staf'];
    }

    public function test_menambah_pengguna_menyimpan_kata_sandi_ter_hash_dan_username_huruf_kecil(): void
    {
        $this->actingAs($this->admin())->post('/pengguna', $this->data(['username' => 'Staf1']))
            ->assertRedirect('/pengguna')->assertSessionHas('sukses');

        $pengguna = User::where('username', 'staf1')->firstOrFail();
        $this->assertNotSame('rahasia123', $pengguna->password);
        $this->assertTrue(Hash::check('rahasia123', $pengguna->password));
        $this->assertSame('operator', $pengguna->role);
    }

    public function test_pengguna_baru_dapat_masuk_dengan_hak_sesuai_perannya(): void
    {
        $this->actingAs($this->admin())->post('/pengguna', $this->data());
        auth()->logout();

        $this->post('/login', ['username' => 'staf1', 'password' => 'rahasia123'])->assertRedirect(route('dasbor'));
        $this->get('/pengguna')->assertForbidden();
    }

    public function test_validasi_formulir_pengguna(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/pengguna', [])
            ->assertSessionHasErrors(['username' => 'Username wajib diisi.', 'name' => 'Nama wajib diisi.', 'role' => 'Peran wajib diisi.', 'password' => 'Kata sandi wajib diisi.']);
        $this->actingAs($admin)->post('/pengguna', $this->data(['username' => 'operator']))
            ->assertSessionHasErrors(['username' => 'Username sudah dipakai.']);
        $this->actingAs($admin)->post('/pengguna', $this->data(['username' => 'ab c']))->assertSessionHasErrors('username');
        $this->actingAs($admin)->post('/pengguna', $this->data(['password' => '1234567']))
            ->assertSessionHasErrors(['password' => 'Kata sandi minimal 8 karakter.']);
        $this->actingAs($admin)->post('/pengguna', $this->data(['role' => 'superadmin']))
            ->assertSessionHasErrors(['role' => 'Pilih peran Operator atau Administrator.']);
    }

    public function test_mengubah_tanpa_kata_sandi_mempertahankan_yang_lama_dan_dengan_sandi_menggantinya(): void
    {
        $operator = $this->operator();
        $admin = $this->admin();

        $this->actingAs($admin)->put("/pengguna/{$operator->id}", $this->data(['username' => 'operator', 'name' => 'Nama Baru', 'password' => '']))
            ->assertRedirect('/pengguna');
        $this->assertSame('Nama Baru', $operator->fresh()->name);
        $this->assertTrue(Hash::check('password123', $operator->fresh()->password));

        $this->actingAs($admin)->put("/pengguna/{$operator->id}", $this->data(['username' => 'operator', 'password' => 'sandibaru99']));
        $this->assertTrue(Hash::check('sandibaru99', $operator->fresh()->password));
        $this->assertFalse(Hash::check('password123', $operator->fresh()->password));
    }

    public function test_username_tidak_boleh_berubah_menjadi_milik_pengguna_lain(): void
    {
        $this->actingAs($this->admin())->put("/pengguna/{$this->operator()->id}", $this->data(['username' => 'admin']))
            ->assertSessionHasErrors(['username' => 'Username sudah dipakai.']);
    }

    public function test_administrator_tidak_dapat_mengubah_peran_atau_menghapus_akunnya_sendiri(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put("/pengguna/{$admin->id}", $this->data(['username' => 'admin', 'role' => 'operator']))
            ->assertSessionHasErrors(['role' => 'Anda tidak dapat mengubah peran akun Anda sendiri.']);
        $this->assertSame('administrator', $admin->fresh()->role);

        $this->actingAs($admin)->put("/pengguna/{$admin->id}", $this->data(['username' => 'admin', 'name' => 'Admin Baru', 'role' => 'administrator', 'password' => '']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Admin Baru', $admin->fresh()->name);

        $this->actingAs($admin)->delete("/pengguna/{$admin->id}")->assertSessionHas('galat');
        $this->assertModelExists($admin);
    }

    public function test_pengguna_yang_sudah_membuat_surat_tidak_dapat_dihapus(): void
    {
        $operator = $this->operator();
        $this->surat();

        $this->actingAs($this->admin())->delete("/pengguna/{$operator->id}")
            ->assertSessionHas('galat', "{$operator->name} tidak dapat dihapus karena sudah membuat surat.");
        $this->assertModelExists($operator);
    }

    public function test_pengguna_tanpa_surat_dapat_dihapus_dan_sesinya_tidak_berlaku(): void
    {
        $this->actingAs($this->admin())->post('/pengguna', $this->data());
        $staf = User::where('username', 'staf1')->first();

        $this->actingAs($this->admin())->delete("/pengguna/{$staf->id}")->assertSessionHas('sukses');
        $this->assertModelMissing($staf);

        // actingAs() tetap aktif sepanjang tes; keluarkan dulu agar yang diuji hanya percobaan login staf1.
        auth()->logout();
        $this->post('/login', ['username' => 'staf1', 'password' => 'rahasia123']);
        $this->assertGuest();
    }

    public function test_halaman_pengguna_menandai_akun_sendiri_tanpa_tombol_hapus(): void
    {
        $this->actingAs($this->admin())->get('/pengguna')
            ->assertOk()->assertSee('(Anda)')->assertSee('Hapus Operator Desa')->assertDontSee('Hapus Administrator');
    }
}
