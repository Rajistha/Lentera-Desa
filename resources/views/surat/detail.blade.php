@php
    $wargaAda = $surat->warga->exists;
    $baris = [
        'Nomor surat' => $surat->nomor_surat,
        'Jenis surat' => $surat->jenisSurat->nama,
        'Nama warga' => $surat->warga->nama,
        'NIK' => $surat->warga->nik,
        'Alamat' => $wargaAda ? $surat->warga->alamat.', RT '.$surat->warga->rt.'/RW '.$surat->warga->rw : '-',
        'Keperluan' => $surat->keperluan,
        'Keterangan' => $surat->keterangan ?: '-',
        'Tanggal surat' => $surat->tanggal_surat->copy()->locale('id')->translatedFormat('d M Y'),
        'Dibuat oleh' => $surat->user->name.' ('.($surat->user->role === 'administrator' ? 'Administrator' : 'Operator').')',
    ];
@endphp

<x-layout-aplikasi judul="Detail Surat" :subjudul="'No. '.$surat->nomor_surat">
    <x-slot:aksi>
        <a href="{{ route('surat.index') }}"
           class="flex h-9 items-center gap-2 rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">
            <x-ikon nama="kiri" :ukuran="16" />
            Kembali ke Arsip
        </a>
    </x-slot:aksi>

    <div class="flex items-start gap-5">
        <section class="w-full max-w-[440px] shrink-0 rounded-[18px] border border-line bg-card p-6" aria-labelledby="judul-data">
            <h2 id="judul-data" class="text-[11px] font-bold uppercase tracking-[0.8px]">Data surat</h2>
            @unless ($wargaAda)
                <p role="alert" class="mt-3 flex items-start gap-2 rounded-xl bg-error/10 px-4 py-3 text-xs text-error">
                    <x-ikon nama="galat" :ukuran="14" class="mt-px" />
                    Warga pada surat ini sudah tidak ada di data warga. Tekan Ubah dan pilih warga yang benar agar PDF dapat dibuat.
                </p>
            @endunless
            <dl class="mt-4 space-y-3">
                @foreach ($baris as $label => $isi)
                    <div>
                        <dt class="text-[11px] text-muted">{{ $label }}</dt>
                        <dd class="text-[13px] font-medium">{{ $isi }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="min-w-0 max-w-[560px] flex-1 rounded-[18px] bg-surf p-6" aria-label="Pratinjau PDF">
            <h2 class="text-center text-[11px] font-bold uppercase tracking-[0.8px]">Pratinjau PDF</h2>

            @if ($pdfAda)
                {{-- Kertas A4 (210 x 297 mm) --}}
                <div class="mt-4 w-full overflow-hidden rounded-sm border border-line bg-white shadow-sm" style="aspect-ratio: 210 / 297">
                    <iframe title="Pratinjau PDF surat {{ $surat->nomor_surat }}"
                            src="{{ route('surat.pdf', $surat) }}#toolbar=0&navpanes=0"
                            class="size-full border-0"></iframe>
                </div>
            @else
                <div class="mt-4 flex items-center justify-center rounded-sm border border-dashed border-line bg-card/60 px-6 text-center" style="aspect-ratio: 210 / 297">
                    <div>
                        <x-ikon nama="info" :ukuran="24" class="mx-auto text-blue" />
                        <p class="mt-2 font-medium">PDF belum tersedia</p>
                        <p class="mt-1 text-xs text-muted">Gunakan tombol Buat Ulang PDF di bawah.</p>
                    </div>
                </div>
            @endif
        </section>
    </div>

    <div class="mt-5 flex items-center gap-3">
        @if ($pdfAda)
            <a href="{{ route('surat.unduh', $surat) }}"
               class="flex h-9 items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
                <x-ikon nama="unduh" :ukuran="16" />
                Unduh PDF
            </a>
        @endif

        <a href="{{ route('surat.edit', $surat) }}"
           class="flex h-9 items-center gap-2 rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">
            <x-ikon nama="ubah" :ukuran="16" />
            Ubah
        </a>

        <form action="{{ route('surat.buat-ulang-pdf', $surat) }}" method="post" data-memuat="Membuat ulang PDF...">
            @csrf
            <button type="submit"
                    @class(['flex h-9 items-center gap-2 rounded-full px-5 text-[13px] font-medium',
                            'bg-surf hover:bg-line' => $pdfAda,
                            'bg-blue text-white hover:bg-blue/90' => ! $pdfAda])>
                <x-ikon nama="ulang" :ukuran="16" />
                Buat Ulang PDF
            </button>
        </form>

        <form action="{{ route('surat.destroy', $surat) }}" method="post"
              data-konfirmasi="Surat {{ $surat->nomor_surat }} dan berkas PDF-nya akan dihapus. Tindakan ini tidak dapat dibatalkan." data-konfirmasi-judul="Hapus surat?" data-konfirmasi-tombol="Hapus" data-konfirmasi-gaya="bahaya">
            @csrf
            @method('delete')
            <button type="submit" class="flex h-9 items-center gap-2 rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">
                <x-ikon nama="hapus" :ukuran="16" class="text-error" />
                Hapus
            </button>
        </form>
    </div>
</x-layout-aplikasi>
