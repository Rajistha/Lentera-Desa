<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use ZipArchive;

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
        $ubah = $this->route('jenisSurat') !== null;

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
                $ubah ? 'nullable' : 'required',
                'file', 'extensions:docx', 'max:2048',
                function (string $atribut, mixed $berkas, \Closure $gagal) {
                    if ($berkas instanceof UploadedFile && ! $this->dokumenWordValid($berkas)) {
                        $gagal('Berkas bukan dokumen Word (.docx) yang valid.');
                    }
                },
            ],
        ];
    }

    /**
     * Templat adalah data: harus .docx asli dan tidak boleh membawa makro.
     * Diperiksa lewat isi zip, bukan MIME, karena sebagian .docx valid
     * terdeteksi sebagai application/zip.
     */
    private function dokumenWordValid(UploadedFile $berkas): bool
    {
        $zip = new ZipArchive;
        if ($zip->open($berkas->getRealPath()) !== true) {
            return false;
        }

        $valid = $zip->locateName('word/document.xml') !== false
            && $zip->locateName('word/vbaProject.bin') === false;
        $zip->close();

        return $valid;
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'kode.regex' => 'Kode hanya boleh berisi huruf dan angka tanpa spasi.',
            'kode.unique' => 'Kode sudah dipakai jenis surat lain.',
            'berkas.required' => 'Templat (.docx) wajib diunggah.',
            'berkas.file' => 'Berkas gagal diunggah.',
            'berkas.extensions' => 'Templat harus berekstensi .docx.',
            'berkas.max' => 'Ukuran templat maksimal 2 MB.',
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
