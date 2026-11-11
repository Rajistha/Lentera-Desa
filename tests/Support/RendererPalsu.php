<?php

namespace Tests\Support;

use App\Services\PdfGagal;
use App\Services\TemplatRenderer;

/**
 * Pengisi templat asli, tetapi konversi PDF diganti berkas PDF sederhana agar
 * pengujian tidak bergantung pada LibreOffice di komputer penguji.
 */
class RendererPalsu extends TemplatRenderer
{
    /** Atur true untuk mensimulasikan LibreOffice yang gagal/tidak ada. */
    public static bool $gagal = false;

    public function kePdf(string $docx): string
    {
        if (self::$gagal) {
            throw new PdfGagal('LibreOffice tidak ditemukan (simulasi pengujian).');
        }

        $pdf = preg_replace('/\.docx$/i', '.pdf', $docx);
        file_put_contents($pdf, '%PDF-1.4 berkas uji');

        return $pdf;
    }

    public function libreOfficeTersedia(): bool
    {
        return ! self::$gagal;
    }
}
