<x-layout-aplikasi judul="Data Warga" subjudul="Kelola data kependudukan warga">
    <div class="flex items-center justify-between gap-4">
        <form action="{{ route('warga.index') }}" method="get" role="search"
              class="flex h-10 w-[380px] items-center gap-2.5 rounded-full bg-surf px-4">
            <x-ikon nama="cari" :ukuran="16" class="text-muted" />
            <input type="search" name="q" value="{{ $kata }}" placeholder="Cari NIK atau nama..."
                   aria-label="Cari NIK atau nama"
                   class="w-full bg-transparent text-[13px] outline-none placeholder:text-muted">
        </form>

        <div class="flex items-center gap-3">
            <a href="{{ route('warga.impor') }}"
               class="flex h-9 items-center gap-2 rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">
                <x-ikon nama="unggah" :ukuran="16" />
                Impor Excel
            </a>
            <a href="{{ route('warga.create') }}"
               class="flex h-9 items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
                <x-ikon nama="tambah" :ukuran="16" />
                Tambah Warga
            </a>
        </div>
    </div>

    <div class="mt-5 overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="h-11 bg-surf text-[11px] uppercase tracking-[0.8px] text-muted">
                    <th class="px-3 font-medium">NIK</th>
                    <th class="px-3 font-medium">Nama</th>
                    <th class="px-3 font-medium">Alamat</th>
                    <th class="px-3 font-medium">RT/RW</th>
                    <th class="px-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $warga)
                    <tr class="h-[52px] border-b border-line">
                        <td class="px-3">{{ $warga->nik }}</td>
                        <td class="px-3">
                            <span class="flex items-center gap-3">
                                <span class="flex size-7 items-center justify-center rounded-full bg-hl text-[10px] font-bold text-blue">
                                    {{ collect(preg_split('/\s+/', trim($warga->nama)))->take(2)->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('') }}
                                </span>
                                {{ $warga->nama }}
                            </span>
                        </td>
                        <td class="max-w-[260px] truncate px-3 text-muted">{{ $warga->alamat }}</td>
                        <td class="px-3 text-muted">{{ $warga->rt }}/{{ $warga->rw }}</td>
                        <td class="px-3">
                            <span class="flex items-center gap-2">
                                <a href="{{ route('warga.edit', $warga) }}" title="Ubah" aria-label="Ubah {{ $warga->nama }}"
                                   class="flex size-8 items-center justify-center rounded-xl bg-surf text-muted hover:bg-line">
                                    <x-ikon nama="ubah" :ukuran="16" />
                                </a>
                                <form action="{{ route('warga.destroy', $warga) }}" method="post"
                                      data-konfirmasi="Data {{ $warga->nama }} akan dihapus." data-konfirmasi-judul="Hapus data warga?" data-konfirmasi-tombol="Hapus" data-konfirmasi-gaya="bahaya">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" title="Hapus" aria-label="Hapus {{ $warga->nama }}"
                                            class="flex size-8 items-center justify-center rounded-xl bg-surf text-error hover:bg-line">
                                        <x-ikon nama="hapus" :ukuran="16" />
                                    </button>
                                </form>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-10 text-center text-muted">Data warga tidak ditemukan</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5 flex items-center justify-between">
        <p class="text-xs text-muted">
            @if ($daftar->total() > 0)
                Menampilkan {{ $daftar->firstItem() }}-{{ $daftar->lastItem() }} dari {{ number_format($daftar->total(), 0, ',', '.') }} warga
            @endif
        </p>
        {{ $daftar->links('pagination.lentera') }}
    </div>
</x-layout-aplikasi>
