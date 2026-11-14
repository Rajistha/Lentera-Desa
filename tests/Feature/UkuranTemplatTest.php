<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

class UkuranTemplatTest extends TestCase
{
    use SiapkanData;

    public function test_templat_lebih_dari_2mb_ditolak(): void
    {
        $this->siapkanData();
        $besar = UploadedFile::fake()->create('besar.docx', 2049, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->actingAs($this->admin())->from('/jenis-surat?tambah=1')
            ->post('/jenis-surat', ['kode' => 'BSR', 'nama' => 'Uji', 'berkas' => $besar])
            ->assertSessionHasErrors(['berkas' => 'Ukuran template maksimal 2 MB.']);
    }
}
