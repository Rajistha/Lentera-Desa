@php
    $angka = fn ($n) => number_format($n, 0, ',', '.');
    $kartu = [
        ['Jumlah Warga', $jumlahWarga, 'warga', true],
        ['Surat Bulan Ini', $suratBulanIni, 'berkas', false],
        ['Jenis Surat', $jumlahJenis, 'berkas', false],
    ];
@endphp

<x-layout-aplikasi judul="Dasbor" subjudul="Ringkasan layanan surat desa">
    <div class="flex items-center gap-4">
        <div class="grid max-w-[860px] flex-1 grid-cols-3 gap-4">
            @foreach ($kartu as [$label, $nilai, $ikon, $utama])
                <section @class(['rounded-[18px] p-5', 'bg-blue text-white' => $utama, 'bg-surf' => ! $utama]) aria-label="{{ $label }}">
                    <p @class(['flex items-center gap-2 text-[13px]', 'text-white' => $utama, 'text-muted' => ! $utama])>
                        <x-ikon :nama="$ikon" :ukuran="20" @class(['text-white' => $utama, 'text-blue' => ! $utama]) />
                        {{ $label }}
                    </p>
                    <p class="mt-3 text-[34px] font-bold leading-none">{{ $angka($nilai) }}</p>
                </section>
            @endforeach
        </div>

        <div class="ml-auto px-6">
            <a href="{{ route('surat.create') }}"
               class="flex h-9 items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
                <x-ikon nama="surat-baru" :ukuran="16" />
                Buat Surat
            </a>
        </div>
    </div>

    <h2 class="mb-3 mt-6 text-[11px] font-bold uppercase tracking-[0.8px]">5 surat terbaru</h2>
    <table class="w-full text-left">
        <thead>
            <tr class="h-11 bg-surf text-[11px] uppercase tracking-[0.8px] text-muted">
                <th class="px-3 font-medium">No. Surat</th>
                <th class="px-3 font-medium">Nama Warga</th>
                <th class="px-3 font-medium">Jenis Surat</th>
                <th class="px-3 font-medium">Tanggal</th>
                <th class="px-3 font-medium">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($terbaru as $surat)
                <tr class="h-[52px] border-b border-line">
                    <td class="px-3">
                        <a href="{{ route('surat.show', $surat) }}" class="flex items-center gap-3 hover:text-blue">
                            <span class="flex size-7 items-center justify-center rounded-lg bg-hl text-blue"><x-ikon nama="berkas" :ukuran="14" /></span>
                            {{ $surat->nomor_surat }}
                        </a>
                    </td>
                    <td class="px-3">
                        <span class="flex items-center gap-3">
                            <span class="flex size-7 items-center justify-center rounded-full bg-hl text-[10px] font-bold text-blue">
                                {{ $surat->warga->inisial }}
                            </span>
                            <span @class(['italic text-muted' => ! $surat->warga->exists])>{{ $surat->warga->nama }}</span>
                        </span>
                    </td>
                    <td class="px-3"><span class="rounded-full bg-hl px-2.5 py-1 text-xs font-medium text-blue">{{ $surat->jenisSurat->nama }}</span></td>
                    <td class="px-3 text-muted">{{ $surat->tanggal_surat->copy()->locale('id')->translatedFormat('d M Y') }}</td>
                    <td class="px-3">
                        <a href="{{ route('surat.show', $surat) }}" title="Lihat" aria-label="Lihat surat {{ $surat->nomor_surat }}"
                           class="flex size-8 items-center justify-center rounded-xl bg-surf text-muted hover:bg-line">
                            <x-ikon nama="mata" :ukuran="16" />
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-3 py-10 text-center text-muted">Belum ada surat yang dibuat</td></tr>
            @endforelse
        </tbody>
    </table>
</x-layout-aplikasi>
