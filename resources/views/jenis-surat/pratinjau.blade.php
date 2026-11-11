@php
    // Kembali ke tempat pratinjau dibuka: daftar, atau formulir (ubah/tambah).
    $dariFormulir = ($info['asal'] ?? 'daftar') === 'formulir';
    $kembali = match (true) {
        ! $dariFormulir => route('jenis-surat.index'),
        (bool) $info['jenis_id'] => route('jenis-surat.edit', $info['jenis_id']).'#formulir',
        default => route('jenis-surat.index', ['tambah' => 1]).'#formulir',
    };
@endphp

<x-layout-aplikasi judul="Pratinjau Templat" :subjudul="$info['nama'].' ('.$info['berkas'].') dengan data contoh'">
    <x-slot:aksi>
        <div class="flex items-center gap-3">
            @if ($bisaPdf)
                <a href="{{ route('jenis-surat.pratinjau.pdf', $token) }}" target="_blank" rel="noopener"
                   class="flex h-9 items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
                    <x-ikon nama="berkas" :ukuran="16" />
                    Lihat PDF Contoh
                </a>
            @endif
            <a href="{{ $kembali }}"
               class="flex h-9 items-center gap-2 rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">
                <x-ikon nama="kiri" :ukuran="16" />
                {{ $dariFormulir ? 'Kembali ke Formulir' : 'Kembali ke Daftar' }}
            </a>
        </div>
    </x-slot:aksi>

    @unless ($bisaPdf)
        <p class="mb-5 flex items-center gap-2 rounded-xl bg-surf px-4 py-3 text-xs text-muted">
            <x-ikon nama="info" :ukuran="16" class="shrink-0 text-blue" />
            LibreOffice belum terpasang di server ini, jadi PDF contoh belum bisa dibuat. Tampilan di bawah dibuat langsung dari templat dan bisa sedikit berbeda dari hasil PDF.
        </p>
    @endunless

    <div class="flex items-start gap-5">
        <div class="min-w-0 flex-1">
            <table class="w-full text-left">
                <thead>
                    <tr class="h-10 bg-surf text-[11px] uppercase tracking-[0.8px] text-muted">
                        <th class="px-3 font-medium">Penanda</th>
                        <th class="px-3 font-medium">Isi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (\App\Services\Penanda::DAFTAR as $nama => $arti)
                        <tr class="h-10 border-b border-line">
                            <td class="px-3"><span class="rounded-full bg-hl px-2.5 py-1 text-xs text-blue">${{ '{' }}{{ $nama }}{{ '}' }}</span></td>
                            <td class="px-3 text-muted">{{ $arti }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <section class="w-[560px] shrink-0 rounded-[18px] bg-surf p-6" aria-label="Pratinjau surat">
            <h2 class="text-center text-[11px] font-bold uppercase tracking-[0.8px]">Pratinjau dengan data contoh</h2>
            {{-- Kertas A4 (210 x 297 mm). Isi dirender pada lebar A4 asli lalu diskalakan agar muat. --}}
            <div id="kertas" class="relative mt-4 w-full overflow-hidden rounded-sm border border-line bg-white shadow-sm"
                 style="aspect-ratio: 210 / 297">
                {{-- Diisolasi dalam iframe tersandbox: isi berasal dari templat unggahan. --}}
                <iframe id="isi-kertas" title="Pratinjau surat" sandbox="" srcdoc="{{ $html }}"
                        class="absolute left-0 top-0 border-0 bg-white"
                        style="width: 794px; height: 1123px; transform-origin: 0 0"></iframe>
            </div>
            <p class="mt-3 text-center text-xs text-muted">Ukuran kertas A4 · Data contoh tidak disimpan</p>
        </section>

        <script>
            (() => {
                const kertas = document.getElementById('kertas');
                const isi = document.getElementById('isi-kertas');
                const sesuaikan = () => { isi.style.transform = `scale(${kertas.clientWidth / 794})`; };
                sesuaikan();
                new ResizeObserver(sesuaikan).observe(kertas);
            })();
        </script>
    </div>
</x-layout-aplikasi>
