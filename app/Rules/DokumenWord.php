<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

/**
 * Templat adalah data: harus .docx asli dan tidak boleh membawa makro.
 * Diperiksa lewat isi zip, bukan MIME, karena sebagian .docx valid
 * terdeteksi sebagai application/zip.
 */
class DokumenWord implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $this->valid($value)) {
            $fail('Berkas bukan dokumen Word (.docx) yang valid.');
        }
    }

    private function valid(UploadedFile $berkas): bool
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
}
