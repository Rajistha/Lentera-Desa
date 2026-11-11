<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // "?:" (bukan nilai bawaan env) agar SEED_PASSWORD yang dikosongkan tidak menghasilkan kata sandi kosong.
        $password = env('SEED_PASSWORD') ?: 'password123';

        User::updateOrCreate(['username' => 'admin'], [
            'name' => 'Administrator',
            'password' => $password,
            'role' => 'administrator',
            'jabatan' => 'Pengelola Sistem',
        ]);

        User::updateOrCreate(['username' => 'operator'], [
            'name' => 'Operator Desa',
            'password' => $password,
            'role' => 'operator',
            'jabatan' => 'Sekretaris Desa',
        ]);
    }
}
