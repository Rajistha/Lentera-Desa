<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_password_kosong_tidak_menghasilkan_kata_sandi_kosong(): void
    {
        putenv('SEED_PASSWORD=');
        $_ENV['SEED_PASSWORD'] = $_SERVER['SEED_PASSWORD'] = '';

        $this->seed(UserSeeder::class);

        foreach (['admin', 'operator'] as $username) {
            $akun = User::where('username', $username)->firstOrFail();
            $this->assertFalse(Hash::check('', $akun->password), "{$username} tidak boleh berkata sandi kosong");
        }

        putenv('SEED_PASSWORD');
        unset($_ENV['SEED_PASSWORD'], $_SERVER['SEED_PASSWORD']);
    }

    public function test_seed_password_dari_env_dipakai_bila_diisi(): void
    {
        putenv('SEED_PASSWORD=sandi-khusus-123');
        $_ENV['SEED_PASSWORD'] = $_SERVER['SEED_PASSWORD'] = 'sandi-khusus-123';

        $this->seed(UserSeeder::class);

        $this->assertTrue(Hash::check('sandi-khusus-123', User::where('username', 'admin')->first()->password));

        putenv('SEED_PASSWORD');
        unset($_ENV['SEED_PASSWORD'], $_SERVER['SEED_PASSWORD']);
    }

    public function test_seeder_dapat_dijalankan_berulang_tanpa_menggandakan_akun(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertSame(2, User::count());
    }
}
