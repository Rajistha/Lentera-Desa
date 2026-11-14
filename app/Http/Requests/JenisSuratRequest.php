<?php

namespace App\Http\Requests;

use App\Rules\DokumenWord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JenisSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['kode' => strtoupper(trim((string) $this->input('kode')))]);
    }

    public function rules(): array
    {
        return [
            // Kode dipakai di nomor surat dan nama berkas, jadi dibatasi huruf/angka.
            'kode' => [
                'required', 'max:10', 'regex:/^[A-Z0-9]+$/',
                Rule::unique('jenis_surat', 'kode')->ignore($this->route('jenisSurat')),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'berkas' => [
                'bail',
                'nullable',
                'file', 'extensions:docx', 'max:2048',
                new DokumenWord,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'kode.regex' => 'Kode hanya boleh berisi huruf dan angka tanpa spasi.',
            'kode.unique' => 'Kode sudah dipakai jenis surat lain.',
            'berkas.file' => 'Berkas gagal diunggah.',
            'berkas.extensions' => 'Template harus berekstensi .docx.',
            'berkas.max' => 'Ukuran template maksimal 2 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'kode' => 'Kode',
            'nama' => 'Nama',
            'deskripsi' => 'Deskripsi',
        ];
    }
}
