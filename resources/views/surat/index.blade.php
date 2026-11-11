@php
    $aksi = 'flex size-8 items-center justify-center rounded-xl bg-surf text-muted hover:bg-line';
@endphp

<x-layout-aplikasi judul="Arsip Surat" subjudul="Cari dan saring surat yang sudah dibuat">
    <form action="{{ route('surat.index') }}" method="get" role="search" aria-label="Filter arsip surat"
          class="grid grid-cols-[1fr_1fr_1fr_1.1fr_auto] items-start gap-x-4">
        <x-kolom nama="jenis" label="Jenis surat">
            <x-pilihan nama="jenis" :opsi="$jenisSurat" :nilai="$filter['jenis'] ?? null" placeholder="Semua jenis" />
        </x-kolom>

        <x-kolom nama="dari" label="Dari tanggal">
            <x-tanggal nama="dari" :nilai="$filter['dari'] ?? null" :maks="$filter['sampai'] ?? null" placeholder="dd-mm-yyyy" />
        </x-kolom>

        <x-kolom nama="sampai" label="Sampai tanggal">
            <x-tanggal nama="sampai" :nilai="$filter['sampai'] ?? null" :min="$filter['dari'] ?? null" placeholder="dd-mm-yyyy" />
        </x-kolom>

        <x-kolom nama="q" label="Nama, NIK, atau nomor surat">
            <div class="relative">
                <x-ikon nama="cari" :ukuran="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-muted" />
                <input id="q" name="q" type="search" maxlength="100" placeholder="Cari nama atau NIK"
                       value="{{ $filter['q'] ?? '' }}"
                       class="h-[38px] w-full rounded-full bg-surf pl-11 pr-4 text-[13px] outline-none placeholder:text-muted">
            </div>
        </x-kolom>

        <div class="flex items-center gap-2 pt-[22px]">
            <button type="submit"
                    class="flex h-[38px] items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
                <x-ikon nama="cari" :ukuran="16" />
                Terapkan
            </button>
            @if ($adaFilter)
                <a href="{{ route('surat.index') }}" class="flex h-[38px] items-center rounded-full bg-surf px-4 text-[13px] font-medium hover:bg-line">Reset</a>
            @endif
        </div>
    </form>

    <div class="mt-5">
        <table class="w-full text-left">
            <thead>
                <tr class="h-11 bg-surf text-[11px] uppercase tracking-[0.8px] text-muted">
                    <th class="px-3 font-medium">No. Surat</th>
                    <th class="px-3 font-medium">Nama Warga</th>
                    <th class="px-3 font-medium">Jenis</th>
                    <th class="px-3 font-medium">Tanggal</th>
                    <th class="px-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $surat)
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
                        <td class="px-3">
                            <span class="rounded-full bg-hl px-2.5 py-1 text-xs font-medium text-blue">{{ $surat->jenisSurat->nama }}</span>
                        </td>
                        <td class="px-3 text-muted">{{ $surat->tanggal_surat->copy()->locale('id')->translatedFormat('d M Y') }}</td>
                        <td class="px-3">
                            <span class="flex items-center gap-2">
                                <a href="{{ route('surat.show', $surat) }}" title="Lihat" aria-label="Lihat surat {{ $surat->nomor_surat }}" class="{{ $aksi }}">
                                    <x-ikon nama="mata" :ukuran="16" />
                                </a>
                                @if ($surat->berkas_pdf)
                                    <a href="{{ route('surat.unduh', $surat) }}" title="Unduh PDF" aria-label="Unduh PDF {{ $surat->nomor_surat }}" class="{{ $aksi }}">
                                        <x-ikon nama="unduh" :ukuran="16" />
                                    </a>
                                @else
                                    <span title="PDF belum tersedia" aria-label="PDF belum tersedia" class="{{ $aksi }} cursor-not-allowed opacity-40">
                                        <x-ikon nama="unduh" :ukuran="16" />
                                    </span>
                                @endif
                                <a href="{{ route('surat.edit', $surat) }}" title="Ubah" aria-label="Ubah surat {{ $surat->nomor_surat }}" class="{{ $aksi }}">
                                    <x-ikon nama="ubah" :ukuran="16" />
                                </a>
                                <form action="{{ route('surat.destroy', $surat) }}" method="post"
                                      data-konfirmasi="Surat {{ $surat->nomor_surat }} dan berkas PDF-nya akan dihapus. Tindakan ini tidak dapat dibatalkan." data-konfirmasi-judul="Hapus surat?" data-konfirmasi-tombol="Hapus" data-konfirmasi-gaya="bahaya">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" title="Hapus" aria-label="Hapus surat {{ $surat->nomor_surat }}" class="{{ $aksi }} text-error">
                                        <x-ikon nama="hapus" :ukuran="16" />
                                    </button>
                                </form>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-10 text-center text-muted">
                            {{ $adaFilter ? 'Surat tidak ditemukan' : 'Belum ada surat yang dibuat' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5 flex items-center justify-between">
        <p class="text-xs text-muted">
            @if ($daftar->total() > 0)
                Menampilkan {{ $daftar->firstItem() }}-{{ $daftar->lastItem() }} dari {{ number_format($daftar->total(), 0, ',', '.') }} surat
            @endif
        </p>
        {{ $daftar->links('pagination.lentera') }}
    </div>
</x-layout-aplikasi>
