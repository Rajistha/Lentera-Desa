<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warga_id' => ['required', 'exists:warga,id'],
            'jenis_surat_id' => ['required', 'exists:jenis_surat,id'],
            'tanggal_surat' => ['required', 'date'],
            'keperluan' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'warga_id.required' => 'Pilih warga dari hasil pencarian.',
            'warga_id.exists' => 'Warga yang dipilih tidak terdaftar.',
            'jenis_surat_id.required' => 'Pilih jenis surat.',
            'jenis_surat_id.exists' => 'Jenis surat yang dipilih tidak tersedia.',
            'tanggal_surat.date' => 'Tanggal surat tidak valid.',
            'keperluan.max' => 'Keperluan maksimal :max karakter.',
            'keterangan.max' => 'Keterangan maksimal :max karakter.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tanggal_surat' => 'Tanggal surat',
            'keperluan' => 'Keperluan',
        ];
    }
}
