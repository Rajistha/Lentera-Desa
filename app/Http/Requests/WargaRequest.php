<?php

namespace App\Http\Requests;

use App\Models\Warga;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Ratakan RT/RW angka satu digit menjadi dua digit ("1" → "01"). */
    public static function rapikanRtRw(mixed $nilai): mixed
    {
        return is_string($nilai) && ctype_digit($nilai)
            ? str_pad($nilai, 2, '0', STR_PAD_LEFT)
            : $nilai;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'rt' => self::rapikanRtRw($this->input('rt')),
            'rw' => self::rapikanRtRw($this->input('rw')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nik' => [
                'required', 'digits:16',
                Rule::unique('warga', 'nik')->ignore($this->route('warga')),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'jenis_kelamin' => ['required', Rule::in(array_keys(Warga::JENIS_KELAMIN))],
            'status_perkawinan' => ['required', Rule::in(array_keys(Warga::STATUS_PERKAWINAN))],
            'alamat' => ['required', 'string'],
            'rt' => ['required', 'digits_between:1,3'],
            'rw' => ['required', 'digits_between:1,3'],
            'pekerjaan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'max' => ':attribute maksimal :max karakter.',
            'tanggal_lahir.date' => 'Tanggal lahir tidak valid.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh melewati hari ini.',
            'in' => 'Pilihan :attribute tidak valid.',
            'rt.digits_between' => 'RT harus berupa angka (maksimal 3 digit).',
            'rw.digits_between' => 'RW harus berupa angka (maksimal 3 digit).',
        ];
    }

    public function attributes(): array
    {
        return [
            'nik' => 'NIK',
            'nama' => 'Nama lengkap',
            'tempat_lahir' => 'Tempat lahir',
            'tanggal_lahir' => 'Tanggal lahir',
            'jenis_kelamin' => 'Jenis kelamin',
            'status_perkawinan' => 'Status perkawinan',
            'alamat' => 'Alamat',
            'rt' => 'RT',
            'rw' => 'RW',
            'pekerjaan' => 'Pekerjaan',
        ];
    }
}
