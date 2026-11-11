<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PenggunaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['username' => strtolower(trim((string) $this->input('username')))]);
    }

    public function rules(): array
    {
        $ubah = $this->route('pengguna') !== null;

        return [
            'username' => [
                'required', 'max:50', 'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($this->route('pengguna')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['operator', 'administrator'])],
            // Saat mengubah, kosong berarti kata sandi lama dipertahankan.
            'password' => [$ubah ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'username.regex' => 'Username hanya boleh huruf kecil, angka, titik, garis bawah, dan strip.',
            'username.unique' => 'Username sudah dipakai.',
            'role.in' => 'Pilih peran Operator atau Administrator.',
            'password.min' => 'Kata sandi minimal :min karakter.',
        ];
    }

    public function attributes(): array
    {
        return [
            'username' => 'Username',
            'name' => 'Nama',
            'role' => 'Peran',
            'password' => 'Kata sandi',
            'jabatan' => 'Jabatan',
        ];
    }
}
