<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfilDesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['kode_desa' => strtoupper(trim((string) $this->input('kode_desa')))]);
    }

    public function rules(): array
    {
        return [
            'nama_desa' => ['required', 'string', 'max:100'],
            'nama_kecamatan' => ['nullable', 'string', 'max:100'],
            'nama_kabupaten' => ['nullable', 'string', 'max:100'],
            'alamat_kantor' => ['nullable', 'string', 'max:255'],
            'kode_pos' => ['nullable', 'digits:5'],
            'telepon' => ['nullable', 'regex:/^[0-9+()\-\s]{5,30}$/'],
            'nama_kepala_desa' => ['required', 'string', 'max:100'],
            // Dipakai pada nomor surat dan dipisahkan garis miring, jadi hanya huruf dan angka.
            'kode_desa' => ['required', 'regex:/^[A-Z0-9]{1,10}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'kode_pos.digits' => 'Kode pos harus 5 digit angka.',
            'telepon.regex' => 'Telepon hanya boleh berisi angka, spasi, tanda + ( ) -, sepanjang 5 sampai 30 karakter.',
            'kode_desa.regex' => 'Kode desa hanya boleh huruf dan angka tanpa spasi, maksimal 10 karakter.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_desa' => 'Nama desa',
            'nama_kecamatan' => 'Nama kecamatan',
            'nama_kabupaten' => 'Nama kabupaten',
            'alamat_kantor' => 'Alamat kantor desa',
            'kode_pos' => 'Kode pos',
            'telepon' => 'Telepon',
            'nama_kepala_desa' => 'Nama kepala desa',
            'kode_desa' => 'Kode desa',
        ];
    }
}
