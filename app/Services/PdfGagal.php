<?php

namespace App\Services;

use RuntimeException;

/** Konversi DOCX ke PDF gagal; pesannya aman ditampilkan ke pengguna. */
class PdfGagal extends RuntimeException {}
