<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;

/**
 * TemplateProcessor PhpWord menyimpan karakter penanda sebagai properti statis protected
 * (bawaan "${" dan "}") tanpa setter publik. Subkelas ini membuka akses secukupnya agar
 * templat bergaya {{ nama }} dapat diisi, dan memulihkan nilai awalnya setelah dipakai
 * supaya tidak bocor ke pemakaian berikutnya dalam proses yang sama.
 */
class ProsesorTemplat extends TemplateProcessor
{
    /** Jalankan $isi dengan penanda bergaya {{ nama }}; karakter awal dipulihkan sesudahnya. */
    public static function denganKurawalGanda(callable $isi): mixed
    {
        $awal = [self::$macroOpeningChars, self::$macroClosingChars];
        self::$macroOpeningChars = '{{';
        self::$macroClosingChars = '}}';

        try {
            return $isi();
        } finally {
            [self::$macroOpeningChars, self::$macroClosingChars] = $awal;
        }
    }
}
