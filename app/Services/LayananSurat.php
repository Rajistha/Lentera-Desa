<?php

namespace App\Services;

use App\Models\JenisSurat;
use App\Models\Surat;
use App\Models\User;
use App\Models\Warga;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LayananSurat
{
    public function __construct(private TemplatRenderer $renderer) {}

    /**
     * Simpan surat baru dengan nomor otomatis (AB-01): berurutan per jenis dan
     * tahun, format 012/SKD/DS/XI/2026. Dalam satu transaksi, baris jenis surat
     * dikunci sehingga dua operator tidak mendapat nomor yang sama; kolom unik
     * (jenis_surat_id, tahun, nomor_urut) menjadi pengaman terakhir.
     */
    public function buat(array $data, User $pembuat): Surat
    {
        $tanggal = Carbon::parse($data['tanggal_surat']);

        for ($percobaan = 1; ; $percobaan++) {
            try {
                return DB::transaction(function () use ($data, $pembuat, $tanggal) {
                    $jenis = JenisSurat::whereKey($data['jenis_surat_id'])->lockForUpdate()->firstOrFail();

                    $urut = 1 + (int) Surat::where('jenis_surat_id', $jenis->id)
                        ->where('tahun', $tanggal->year)
                        ->max('nomor_urut');

                    return Surat::create([
                        'jenis_surat_id' => $jenis->id,
                        'warga_id' => $data['warga_id'],
                        'user_id' => $pembuat->id,
                        'nomor_urut' => $urut,
                        'tahun' => $tanggal->year,
                        'nomor_surat' => $this->susunNomor($urut, $jenis->kode, $tanggal),
                        'keperluan' => $data['keperluan'],
                        'keterangan' => $data['keterangan'] ?? null,
                        'tanggal_surat' => $tanggal->toDateString(),
                    ]);
                });
            } catch (UniqueConstraintViolationException $e) {
                if ($percobaan >= 3) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Buat PDF surat dari templat jenisnya dan catat lokasinya.
     *
     * @return string|null null bila berhasil; bila gagal, pesan yang aman ditampilkan
     */
    public function buatPdf(Surat $surat): ?string
    {
        $surat->loadMissing('jenisSurat', 'warga');
        $disk = Storage::disk('local');

        if (! $disk->exists($surat->jenisSurat->templat)) {
            return 'Berkas templat jenis surat tidak ditemukan. Hubungi administrator.';
        }

        $relatif = 'surat/'.$surat->tahun.'/'.str_replace('/', '-', $surat->nomor_surat).'.pdf';

        try {
            $this->renderer->buatPdf($disk->path($surat->jenisSurat->templat), $this->dataTemplat($surat), $disk->path($relatif));
        } catch (PdfGagal $e) {
            return $e->getMessage();
        } catch (\Throwable $e) {
            Log::error('Pengisian templat gagal', ['surat' => $surat->id, 'galat' => $e->getMessage()]);

            return 'PDF gagal dibuat karena templat tidak dapat diproses. Hubungi administrator.';
        }

        $surat->update(['berkas_pdf' => $relatif]);

        return null;
    }

    /** Jalur absolut PDF surat bila berkasnya benar-benar ada; selain itu null. */
    public function jalurPdf(Surat $surat): ?string
    {
        $disk = Storage::disk('local');

        return $surat->berkas_pdf && $disk->exists($surat->berkas_pdf)
            ? $disk->path($surat->berkas_pdf)
            : null;
    }

    /** Nilai penanda templat untuk satu surat. */
    public function dataTemplat(Surat $surat): array
    {
        $warga = $surat->warga;
        $tanggal = fn (Carbon $t) => $t->copy()->locale('id')->translatedFormat('j F Y');

        return [
            'nomor_surat' => $surat->nomor_surat,
            'tanggal_surat' => $tanggal($surat->tanggal_surat),
            'nik' => $warga->nik,
            'nama' => $warga->nama,
            'tempat_lahir' => $warga->tempat_lahir,
            'tanggal_lahir' => $tanggal($warga->tanggal_lahir),
            'jenis_kelamin' => Warga::JENIS_KELAMIN[$warga->jenis_kelamin] ?? '',
            'alamat' => $warga->alamat,
            'rt' => $warga->rt,
            'rw' => $warga->rw,
            'pekerjaan' => $warga->pekerjaan ?? '',
            'status_perkawinan' => Warga::STATUS_PERKAWINAN[$warga->status_perkawinan] ?? '',
            'keperluan' => $surat->keperluan,
            'keterangan' => $surat->keterangan ?? '',
            'nama_desa' => config('lentera.desa.nama'),
            'nama_kepala_desa' => config('lentera.desa.kepala'),
        ];
    }

    private function susunNomor(int $urut, string $kode, Carbon $tanggal): string
    {
        return sprintf(
            '%03d/%s/%s/%s/%d',
            $urut,
            $kode,
            config('lentera.desa.kode'),
            $this->renderer->romawi($tanggal->month),
            $tanggal->year,
        );
    }
}
