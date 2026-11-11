@php
    $baris = [
        'Nomor surat' => $surat->nomor_surat,
        'Jenis surat' => $surat->jenisSurat->nama,
        'Nama warga' => $surat->warga->nama,
        'NIK' => $surat->warga->nik,
        'Alamat' => $surat->warga->alamat.', RT '.$surat->warga->rt.'/RW '.$surat->warga->rw,
        'Keperluan' => $surat->keperluan,
        'Keterangan' => $surat->keterangan ?: '-',
        'Tanggal surat' => $surat->tanggal_surat->copy()->locale('id')->translatedFormat('d M Y'),
        'Dibuat oleh' => $surat->user->name.' ('.($surat->user->role === 'administrator' ? 'Administrator' : 'Operator').')',
    ];
@endphp

<x-layout-aplikasi judul="Detail Surat" :subjudul="'No. '.$surat->nomor_surat">
    <div class="flex items-start gap-5">
        <section class="w-full max-w-[440px] rounded-[18px] border border-line bg-card p-6" aria-labelledby="judul-data">
            <h2 id="judul-data" class="text-[11px] font-bold uppercase tracking-[0.8px]">Data surat</h2>
            <dl class="mt-4 space-y-3">
                @foreach ($baris as $label => $isi)
                    <div>
                        <dt class="text-[11px] text-muted">{{ $label }}</dt>
                        <dd class="text-[13px] font-medium">{{ $isi }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="min-w-0 flex-1 rounded-[18px] bg-surf p-6" aria-label="Berkas PDF">
            <h2 class="text-center text-[11px] font-bold uppercase tracking-[0.8px]">Pratinjau PDF</h2>
            <p class="mt-6 flex items-center justify-center gap-2 text-xs text-muted">
                <x-ikon nama="info" :ukuran="16" class="text-blue" />
                @if ($surat->berkas_pdf)
                    PDF sudah dibuat. Pratinjau dan unduhan menyusul di langkah berikutnya.
                @else
                    PDF belum dibuat.
                @endif
            </p>
        </section>
    </div>
</x-layout-aplikasi>
