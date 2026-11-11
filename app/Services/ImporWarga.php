<?php

namespace App\Services;

use App\Http\Requests\WargaRequest;
use App\Models\Warga;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Cell\EmptyCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

class ImporWarga
{
    public const MAKS_BARIS = 1000;

    /** Urutan kolom di berkas = urutan di sini. [judul kolom => nama field] */
    public const KOLOM = [
        'NIK' => 'nik',
        'Nama Lengkap' => 'nama',
        'Tempat Lahir' => 'tempat_lahir',
        'Tanggal Lahir' => 'tanggal_lahir',
        'Jenis Kelamin' => 'jenis_kelamin',
        'Status Perkawinan' => 'status_perkawinan',
        'Alamat' => 'alamat',
        'RT' => 'rt',
        'RW' => 'rw',
        'Pekerjaan' => 'pekerjaan',
    ];

    /**
     * Baca berkas .xlsx, simpan baris yang valid dalam satu transaksi,
     * lewati baris yang gagal.
     *
     * @return array{berhasil: int, gagal: list<array{baris: int, nik: string, alasan: list<string>}>}
     *
     * @throws InvalidArgumentException bila berkas tidak sesuai templat atau terlalu banyak baris
     */
    public function proses(string $path): array
    {
        $baris = $this->baca($path);

        $permintaan = new WargaRequest;
        $berhasil = [];
        $gagal = [];
        $nikTerlihat = [];

        foreach ($baris as $nomor => $nilai) {
            $data = $this->normalisasi($nilai);
            $validator = Validator::make(
                $data,
                $permintaan->rules(),
                $permintaan->messages(),
                $permintaan->attributes(),
            );

            $alasan = $validator->errors()->all();

            if (is_float($nilai['nik'] ?? null) || is_int($nilai['nik'] ?? null)) {
                $alasan = ['NIK harus berformat teks, bukan angka (Excel memotong angka lebih dari 15 digit).'];
            } elseif (isset($nikTerlihat[$data['nik']])) {
                $alasan[] = "NIK ganda dalam berkas (sudah ada di baris {$nikTerlihat[$data['nik']]}).";
            }

            if ($data['nik'] !== '') {
                $nikTerlihat[$data['nik']] ??= $nomor;
            }

            if ($alasan) {
                $gagal[] = ['baris' => $nomor, 'nik' => $data['nik'], 'alasan' => array_values(array_unique($alasan))];
            } else {
                $berhasil[] = $validator->validated();
            }
        }

        DB::transaction(function () use ($berhasil) {
            foreach ($berhasil as $data) {
                Warga::create($data);
            }
        });

        return ['berhasil' => count($berhasil), 'gagal' => $gagal];
    }

    /** Tulis templat kosong (judul kolom + sheet petunjuk) ke berkas sementara. */
    public function buatTemplat(string $path): void
    {
        $teks = (new Style)->setFormat('@');

        $penulis = new Writer;
        $penulis->openToFile($path);
        $penulis->getCurrentSheet()->setName('Data Warga');
        $penulis->addRow(Row::fromValues(array_keys(self::KOLOM)));

        // Kolom A (NIK) diberi format teks agar Excel tidak memotong 16 digit.
        for ($i = 0; $i < self::MAKS_BARIS; $i++) {
            $penulis->addRow(new Row([new EmptyCell(null, $teks)]));
        }

        $penulis->addNewSheetAndMakeItCurrent()->setName('Petunjuk');
        foreach ([
            ['Petunjuk pengisian'],
            ['Isi data mulai baris 2 pada sheet "Data Warga". Jangan mengubah judul kolom.'],
            ['NIK: 16 digit angka, ketik sebagai teks (kolom NIK sudah berformat teks).'],
            ['Tanggal Lahir: tulis dengan format 1990-05-31 atau 31-05-1990.'],
            ['Jenis Kelamin: L atau P (boleh juga Laki-laki / Perempuan).'],
            ['Status Perkawinan: Belum kawin, Kawin, Cerai hidup, atau Cerai mati.'],
            ['RT dan RW: angka, contoh 01 dan 02.'],
            ['Pekerjaan boleh dikosongkan; kolom lain wajib diisi.'],
            ['Maksimal '.self::MAKS_BARIS.' baris per berkas.'],
            [''],
            ['Contoh baris'],
            ['3573010101900001', 'Budi Santoso', 'Malang', '1990-01-01', 'L', 'Kawin', 'Jl. Mawar No. 4', '01', '02', 'Petani'],
        ] as $isi) {
            $penulis->addRow(Row::fromValues($isi));
        }

        $penulis->close();
    }

    /** @return array<int, array<string, mixed>> nomor baris Excel => nilai per field */
    private function baca(string $path): array
    {
        // Pertahankan baris kosong agar nomor baris di laporan sama dengan nomor baris di Excel.
        $opsi = new Options;
        $opsi->SHOULD_PRESERVE_EMPTY_ROWS = true;

        $pembaca = new Reader($opsi);
        $pembaca->open($path);

        $hasil = [];
        try {
            foreach ($pembaca->getSheetIterator() as $lembar) {
                foreach ($lembar->getRowIterator() as $nomor => $baris) {
                    $sel = $baris->toArray();

                    if ($nomor === 1) {
                        $this->periksaJudul($sel);

                        continue;
                    }

                    $nilai = [];
                    foreach (array_values(self::KOLOM) as $i => $field) {
                        $nilai[$field] = $sel[$i] ?? null;
                    }

                    if (collect($nilai)->every(fn ($v) => $v === null || trim((string) ($v instanceof DateTimeInterface ? 'x' : $v)) === '')) {
                        continue;
                    }

                    if (count($hasil) >= self::MAKS_BARIS) {
                        throw new InvalidArgumentException('Berkas berisi lebih dari '.self::MAKS_BARIS.' baris data.');
                    }

                    $hasil[$nomor] = $nilai;
                }

                break; // hanya sheet pertama
            }
        } finally {
            $pembaca->close();
        }

        if (! $hasil) {
            throw new InvalidArgumentException('Berkas tidak berisi data warga.');
        }

        return $hasil;
    }

    private function periksaJudul(array $sel): void
    {
        $dibaca = array_map(fn ($v) => mb_strtolower(trim((string) $v)), array_slice($sel, 0, count(self::KOLOM)));
        $harus = array_map('mb_strtolower', array_keys(self::KOLOM));

        if ($dibaca !== $harus) {
            throw new InvalidArgumentException('Judul kolom tidak sesuai templat. Unduh templat terbaru dan salin data ke dalamnya.');
        }
    }

    /** Ubah nilai sel menjadi string seperti input formulir. */
    private function normalisasi(array $n): array
    {
        $teks = fn ($v) => is_scalar($v) ? trim((string) $v) : '';

        $tanggal = $n['tanggal_lahir'];
        if ($tanggal instanceof DateTimeInterface) {
            $tanggal = $tanggal->format('Y-m-d');
        } else {
            $tanggal = $teks($tanggal);
            foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
                if (Carbon::canBeCreatedFromFormat($tanggal, $format)
                    && ($coba = Carbon::createFromFormat('!'.$format, $tanggal))->format($format) === $tanggal) {
                    $tanggal = $coba->toDateString();
                    break;
                }
            }
        }

        $kelamin = mb_strtolower($teks($n['jenis_kelamin']));
        $kelamin = match (true) {
            in_array($kelamin, ['l', 'laki-laki', 'laki laki', 'pria'], true) => 'L',
            in_array($kelamin, ['p', 'perempuan', 'wanita'], true) => 'P',
            default => $teks($n['jenis_kelamin']),
        };

        $status = str_replace(' ', '_', mb_strtolower($teks($n['status_perkawinan'])));

        return [
            'nik' => $teks($n['nik']),
            'nama' => $teks($n['nama']),
            'tempat_lahir' => $teks($n['tempat_lahir']),
            'tanggal_lahir' => $tanggal,
            'jenis_kelamin' => $kelamin,
            'status_perkawinan' => $status,
            'alamat' => $teks($n['alamat']),
            'rt' => WargaRequest::rapikanRtRw($teks($n['rt'])),
            'rw' => WargaRequest::rapikanRtRw($teks($n['rw'])),
            'pekerjaan' => $teks($n['pekerjaan']) ?: null,
        ];
    }
}
