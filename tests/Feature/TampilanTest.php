<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\Concerns\SiapkanData;
use Tests\Support\RendererPalsu;
use Tests\TestCase;

/** Perilaku tampilan yang dijanjikan ke pengguna: modal, toast, ikon mata, tanpa dialog bawaan peramban. */
class TampilanTest extends TestCase
{
    use SiapkanData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanData();
    }

    /* ---------- tidak ada dialog/kontrol bawaan peramban ---------- */

    public function test_tidak_ada_dialog_bawaan_peramban_di_view_maupun_javascript(): void
    {
        $pelanggaran = [];

        foreach ([resource_path('views'), resource_path('js')] as $folder) {
            foreach (File::allFiles($folder) as $berkas) {
                $isi = $berkas->getContents();

                if (preg_match('/(^|[^A-Za-z_.])(confirm|alert|prompt)\s*\(/m', $isi, $cocok)) {
                    $pelanggaran[] = $berkas->getRelativePathname().': '.trim($cocok[0]);
                }
                if (preg_match('/onsubmit\s*=\s*"return/i', $isi)) {
                    $pelanggaran[] = $berkas->getRelativePathname().': onsubmit inline';
                }
            }
        }

        $this->assertSame([], $pelanggaran, 'Gunakan modal konfirmasi (data-konfirmasi), bukan dialog bawaan peramban.');
    }

    public function test_formulir_memakai_komponen_pilihan_dan_kalender_bukan_kontrol_bawaan(): void
    {
        $pelanggaran = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            if ($berkas->getFilename() === 'pilihan.blade.php' || $berkas->getFilename() === 'tanggal.blade.php') {
                continue;
            }
            $isi = $berkas->getContents();
            if (str_contains($isi, '<select') || preg_match('/type="date"/', $isi)) {
                $pelanggaran[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggaran, 'Pakai <x-pilihan> dan <x-tanggal>.');
    }

    /* ---------- ikon mata ---------- */

    public function test_halaman_login_punya_tombol_mata_untuk_kata_sandi(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('data-toggle-sandi="password"', false)
            ->assertSee('aria-label="Tampilkan kata sandi"', false)
            ->assertSee('type="password"', false);
    }

    public function test_formulir_pengguna_juga_punya_tombol_mata(): void
    {
        $this->actingAs($this->admin())->get('/pengguna?tambah=1')->assertSee('data-toggle-sandi="password"', false);
    }

    /* ---------- kolom tanggal: bisa diketik dan dipilih ---------- */

    public function test_tanggal_lahir_dapat_diketik_dan_tampil_dd_mm_yyyy_pada_halaman_ubah(): void
    {
        $warga = $this->warga(['tanggal_lahir' => '1990-05-31']);

        $this->actingAs($this->operator())->get("/warga/{$warga->id}/edit")->assertOk()
            ->assertSee('data-teks', false)
            ->assertSee('placeholder="dd-mm-yyyy"', false)
            ->assertSee('value="31-05-1990"', false)   // yang dilihat dan diketik pengguna
            ->assertSee('value="1990-05-31"', false)   // yang dikirim ke server
            ->assertSee('data-maks="'.now()->toDateString().'"', false)
            ->assertSee('data-tanpa-hari-ini', false);
    }

    public function test_tombol_hari_ini_hanya_disembunyikan_pada_tanggal_lahir(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->get('/surat/buat')->assertOk()->assertDontSee('data-tanpa-hari-ini', false);
        $this->actingAs($operator)->get('/warga/create')->assertOk()->assertSee('data-tanpa-hari-ini', false);
    }

    public function test_tanggal_lahir_kosong_pada_formulir_tambah_dan_nilai_lama_dipertahankan_setelah_galat(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->get('/warga/create')->assertSee('value=""', false);

        $this->actingAs($operator)->from('/warga/create')->post('/warga', [
            'nik' => '123', 'nama' => 'X', 'tempat_lahir' => 'Malang', 'tanggal_lahir' => '1957-05-31',
            'jenis_kelamin' => 'L', 'status_perkawinan' => 'kawin', 'alamat' => 'Jl', 'rt' => '1', 'rw' => '1',
        ]);

        $this->actingAs($operator)->get('/warga/create')
            ->assertSee('value="31-05-1957"', false)->assertSee('value="1957-05-31"', false);
    }

    /* ---------- modal konfirmasi ---------- */

    public function test_modal_konfirmasi_dan_formulir_keluar_tersedia_di_semua_halaman_aplikasi(): void
    {
        foreach ([[$this->operator(), '/dasbor'], [$this->admin(), '/jenis-surat']] as [$pengguna, $url]) {
            $this->actingAs($pengguna)->get($url)->assertOk()
                ->assertSee('id="modal-konfirmasi"', false)
                ->assertSee('data-konfirmasi-judul="Keluar dari aplikasi?"', false);
        }
    }

    public function test_semua_tombol_hapus_memakai_modal_konfirmasi(): void
    {
        $surat = $this->surat();
        $operator = $this->operator();
        $admin = $this->admin();

        foreach ([
            [$operator, '/warga', 'Hapus data warga?'],
            [$operator, '/surat', 'Hapus surat?'],
            [$operator, "/surat/{$surat->id}", 'Hapus surat?'],
            [$admin, '/jenis-surat', 'Hapus jenis surat?'],
            [$admin, '/pengguna', 'Hapus pengguna?'],
        ] as [$pengguna, $url, $judul]) {
            $this->actingAs($pengguna)->get($url)->assertOk()
                ->assertSee('data-konfirmasi-judul="'.$judul.'"', false)
                ->assertSee('data-konfirmasi-gaya="bahaya"', false);
        }
    }

    /* ---------- daftar yang bisa digulir tidak menggulir seluruh halaman ---------- */

    public function test_kotak_daftar_menahan_gulir_agar_tidak_menggulir_halaman(): void
    {
        $operator = $this->operator();

        // Saran warga (kotak bergulir sendiri) dan dropdown pilihan memakai overscroll-contain.
        $buat = $this->actingAs($operator)->get('/surat/buat')->getContent();
        $this->assertMatchesRegularExpression('/id="hasil-warga"[^>]*overscroll-contain/s', $buat);
        $this->assertMatchesRegularExpression('/role="listbox"[^>]*overscroll-contain/s', $buat);

        $this->actingAs($operator)->get('/warga/create')->assertSee('overscroll-contain', false);
    }

    public function test_javascript_tidak_memakai_scrollintoview_yang_ikut_menggulir_halaman(): void
    {
        $pelanggaran = [];

        foreach ([resource_path('views'), resource_path('js')] as $folder) {
            foreach (File::allFiles($folder) as $berkas) {
                // komentar boleh menyebut namanya; yang dilarang adalah pemanggilannya
                if (preg_match('/scrollIntoView\s*\(/', preg_replace('#//.*|/\*.*?\*/#s', '', $berkas->getContents()))) {
                    $pelanggaran[] = $berkas->getRelativePathname();
                }
            }
        }

        $this->assertSame([], $pelanggaran, 'Gulir kotak lewat scrollTop, bukan scrollIntoView() yang ikut menggulir halaman.');
    }

    /* ---------- animasi memuat pada aksi yang memakan waktu ---------- */

    public function test_lapisan_memuat_tersedia_di_halaman_aplikasi_dan_tersembunyi_di_awal(): void
    {
        $this->actingAs($this->operator())->get('/dasbor')->assertOk()
            ->assertSee('id="overlay-memuat"', false)
            ->assertSee('animate-spin', false)
            ->assertSee('data-memuat-teks', false);

        $this->assertMatchesRegularExpression('/<div id="overlay-memuat" hidden /', $this->actingAs($this->operator())->get('/dasbor')->getContent());
    }

    public function test_aksi_yang_membuat_pdf_atau_mengimpor_memakai_animasi_memuat(): void
    {
        $operator = $this->operator();
        $surat = $this->surat();

        $this->actingAs($operator)->get('/surat/buat')->assertSee('data-memuat="Membuat surat dan PDF..."', false);
        $this->actingAs($operator)->get("/surat/{$surat->id}/ubah")->assertSee('data-memuat="Menyimpan perubahan dan membuat ulang PDF..."', false);
        $this->actingAs($operator)->get("/surat/{$surat->id}")->assertSee('data-memuat="Membuat ulang PDF..."', false);
        $this->actingAs($operator)->get('/warga/impor')->assertSee('data-memuat="Mengimpor data warga..."', false);
    }

    public function test_tombol_hapus_tidak_memakai_animasi_memuat_karena_cepat_dan_memakai_konfirmasi(): void
    {
        $surat = $this->surat();

        $isi = $this->actingAs($this->operator())->get("/surat/{$surat->id}")->getContent();

        $this->assertStringNotContainsString('data-memuat="Menghapus', $isi);
        $this->assertStringContainsString('data-konfirmasi-judul="Hapus surat?"', $isi);
    }

    /* ---------- toast status ---------- */

    public function test_toast_sukses_tampil_setelah_surat_dibuat(): void
    {
        $this->actingAs($this->operator())->followingRedirects()->post('/surat', [
            'warga_id' => $this->warga()->id, 'jenis_surat_id' => $this->jenisId('SKD'),
            'tanggal_surat' => '2026-11-11', 'keperluan' => 'Kerja',
        ])->assertOk()
            ->assertSee('data-toast', false)->assertSee('role="status"', false)
            ->assertSee('Berhasil')->assertSee('Surat 001/SKD/DS/XI/2026 berhasil dibuat.')
            ->assertSee('data-otomatis="5000"', false);
    }

    public function test_toast_galat_tampil_dan_tidak_hilang_sendiri_saat_pdf_gagal(): void
    {
        RendererPalsu::$gagal = true;

        $this->actingAs($this->operator())->followingRedirects()->post('/surat', [
            'warga_id' => $this->warga()->id, 'jenis_surat_id' => $this->jenisId('SKD'),
            'tanggal_surat' => '2026-11-11', 'keperluan' => 'Kerja',
        ])->assertOk()
            ->assertSee('role="alert"', false)->assertSee('Gagal')
            ->assertSee('tersimpan, tetapi PDF belum dibuat')
            ->assertDontSee('data-otomatis', false);
    }

    public function test_status_tampil_setelah_tambah_ubah_dan_hapus_di_modul_lain(): void
    {
        $operator = $this->operator();
        $warga = $this->warga();

        $this->actingAs($operator)->followingRedirects()->put("/warga/{$warga->id}", [
            'nik' => $warga->nik, 'nama' => 'Nama Baru', 'tempat_lahir' => 'Malang', 'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L', 'status_perkawinan' => 'kawin', 'alamat' => 'Jl', 'rt' => '1', 'rw' => '1',
        ])->assertSee('Data warga berhasil diperbarui.')->assertSee('data-toast', false);

        $this->actingAs($operator)->followingRedirects()->delete("/warga/{$warga->id}")
            ->assertSee('Data warga berhasil dihapus.')->assertSee('data-toast', false);

        $surat = $this->surat();
        $this->actingAs($operator)->followingRedirects()->delete("/surat/{$surat->id}")
            ->assertSee("Surat {$surat->nomor_surat} berhasil dihapus.")->assertSee('data-toast', false);
    }

    public function test_tidak_ada_toast_bila_tidak_ada_status_dan_spanduk_lama_sudah_tiada(): void
    {
        $this->actingAs($this->operator())->get('/dasbor')
            ->assertDontSee('data-toast', false)->assertDontSee('rounded-xl bg-hl px-4 py-3 text-blue', false);
    }

    public function test_pesan_toast_di_escape_agar_aman_dari_xss(): void
    {
        $this->actingAs($this->operator())->withSession(['sukses' => '<script>alert(1)</script>'])->get('/dasbor')
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }
}
