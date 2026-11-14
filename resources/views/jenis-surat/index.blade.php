@php
    $ubah = $jenis->exists;
    // Formulir hanya tampil saat menambah, mengubah, atau ada galat validasi.
    $tampilForm = $ubah || request()->boolean('tambah') || $errors->any();
    $input = fn ($nama) => 'h-[38px] w-full rounded-full bg-surf px-4 text-[13px] outline-none placeholder:text-muted'
        .($errors->has($nama) ? ' ring-1 ring-error' : '');
@endphp

<x-layout-aplikasi judul="Kelola Jenis Surat" subjudul="Daftar, tambah, dan ubah jenis surat beserta templat Word">
    <x-slot:aksi>
        <a href="{{ route('jenis-surat.index', ['tambah' => 1]) }}#formulir"
           class="flex h-9 items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
            <x-ikon nama="tambah" :ukuran="16" />
            Tambah Jenis
        </a>
    </x-slot:aksi>

    <x-tab-master aktif="jenis-surat" />

    <div class="flex items-start gap-5">
        <div class="min-w-0 flex-1">
            <table class="w-full text-left">
                <thead>
                    <tr class="h-10 bg-surf text-[11px] uppercase tracking-[0.8px] text-muted">
                        <th class="px-3 font-medium">Kode</th>
                        <th class="px-3 font-medium">Nama</th>
                        <th class="px-3 font-medium">Templat</th>
                        <th class="px-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftar as $item)
                        <tr @class(['h-[52px] border-b border-line', 'bg-side' => $item->is($jenis)])>
                            <td class="px-3">
                                <span class="rounded-full bg-hl px-2.5 py-1 text-xs font-medium text-blue">{{ $item->kode }}</span>
                            </td>
                            <td class="px-3">{{ $item->nama }}</td>
                            <td class="px-3 text-muted">{{ basename($item->templat) }}</td>
                            <td class="px-3">
                                <span class="flex items-center gap-2">
                                    <a href="{{ route('jenis-surat.pratinjau.simpanan', $item) }}" title="Pratinjau" aria-label="Pratinjau {{ $item->nama }}"
                                       class="flex size-8 items-center justify-center rounded-xl bg-surf text-muted hover:bg-line">
                                        <x-ikon nama="mata" :ukuran="16" />
                                    </a>
                                    <a href="{{ route('jenis-surat.edit', $item) }}#formulir" title="Ubah" aria-label="Ubah {{ $item->nama }}"
                                       class="flex size-8 items-center justify-center rounded-xl bg-surf text-muted hover:bg-line">
                                        <x-ikon nama="ubah" :ukuran="16" />
                                    </a>
                                    <form action="{{ route('jenis-surat.destroy', $item) }}" method="post"
                                          data-konfirmasi="Jenis surat {{ $item->nama }} akan dihapus beserta berkas templatnya." data-konfirmasi-judul="Hapus jenis surat?" data-konfirmasi-tombol="Hapus" data-konfirmasi-gaya="bahaya">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" title="Hapus" aria-label="Hapus {{ $item->nama }}"
                                                class="flex size-8 items-center justify-center rounded-xl bg-surf text-error hover:bg-line">
                                            <x-ikon nama="hapus" :ukuran="16" />
                                        </button>
                                    </form>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-10 text-center text-muted">Belum ada jenis surat</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tampilForm)
        <form id="formulir" action="{{ $ubah ? route('jenis-surat.update', $jenis) : route('jenis-surat.store') }}"
              method="post" enctype="multipart/form-data" novalidate
              class="w-[470px] shrink-0 rounded-[18px] border border-line bg-card p-6">
            @csrf
            @if ($ubah) @method('put') @endif

            <h2 class="text-[15px] font-bold">{{ $ubah ? 'Formulir Ubah Jenis Surat' : 'Formulir Tambah Jenis Surat' }}</h2>

            <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-4">
                <x-kolom nama="kode" label="Kode">
                    <input id="kode" name="kode" type="text" maxlength="10" placeholder="SKD"
                           value="{{ old('kode', $jenis->kode) }}" class="{{ $input('kode') }} uppercase">
                </x-kolom>

                <x-kolom nama="nama" label="Nama">
                    <input id="nama" name="nama" type="text" placeholder="Surat Domisili"
                           value="{{ old('nama', $jenis->nama) }}" class="{{ $input('nama') }}">
                </x-kolom>

                <x-kolom nama="deskripsi" label="Deskripsi" class="col-span-2">
                    <textarea id="deskripsi" name="deskripsi" rows="2" placeholder="Menerangkan tempat tinggal warga"
                              class="w-full rounded-xl bg-surf px-4 py-3 text-[13px] outline-none placeholder:text-muted {{ $errors->has('deskripsi') ? 'ring-1 ring-error' : '' }}">{{ old('deskripsi', $jenis->deskripsi) }}</textarea>
                </x-kolom>

                <x-kolom nama="berkas" label="Unggah templat (.docx), opsional" class="col-span-2">
                    <div @class(['relative flex flex-col items-center gap-1.5 rounded-2xl border-2 border-dashed px-6 py-7 text-center hover:bg-side',
                                 'border-line' => ! $errors->has('berkas'), 'border-error' => $errors->has('berkas')])>
                        <x-ikon nama="unggah" :ukuran="24" class="text-blue" />
                        <span class="font-medium">Tarik berkas atau klik untuk unggah</span>
                        <span class="text-xs text-muted">Format .docx, maksimal 2 MB</span>
                        <input id="berkas" name="berkas" type="file" aria-label="Berkas templat"
                               accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                               class="absolute inset-0 size-full cursor-pointer opacity-0"
                               onchange="const n = document.getElementById('berkas-dipilih'); const f = this.files[0]; const besar = f && f.size > 2 * 1024 * 1024; if (besar) { this.value = ''; } n.textContent = besar ? 'Ukuran templat maksimal 2 MB, ' + f.name + ' terlalu besar.' : (f ? f.name + ' · dipilih' : ''); n.classList.toggle('hidden', ! f); n.classList.toggle('bg-hl', ! besar); n.classList.toggle('text-blue', ! besar); n.classList.toggle('bg-error/10', !! besar); n.classList.toggle('text-error', !! besar)">
                    </div>
                </x-kolom>
            </div>

            <p id="berkas-dipilih" class="mt-3 hidden rounded-full bg-hl px-4 py-2 text-xs font-medium text-blue"></p>

            @if ($ubah)
                <p class="mt-3 flex items-center justify-between rounded-full bg-surf px-4 py-2 text-xs">
                    <span class="flex items-center gap-2">
                        <x-ikon nama="berkas" :ukuran="14" class="text-blue" />
                        {{ basename($jenis->templat) }}@if ($ukuranTemplat !== null) · {{ number_format($ukuranTemplat / 1024, 0, ',', '.') }} KB @endif
                    </span>
                    @if ($ukuranTemplat !== null)
                        <span class="font-medium text-blue">Terpasang</span>
                    @else
                        <span class="font-medium text-error">Berkas hilang</span>
                    @endif
                </p>
                <p class="mt-2 text-[11px] text-muted">Kosongkan unggahan jika tidak ingin mengganti templat</p>
            @else
                <p class="mt-3 text-[11px] text-muted">Tanpa unggahan, jenis surat memakai templat bawaan.</p>
            @endif

            <details class="mt-3 text-[13px]">
                <summary class="flex cursor-pointer list-none items-center gap-2 font-medium text-blue">
                    <x-ikon nama="info" :ukuran="16" />Lihat daftar penanda (placeholder)
                </summary>
                <p class="mt-2 text-xs text-muted">Ketik penanda di Word sekaligus tanpa mengubah format di tengahnya.</p>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach (\App\Services\Penanda::DAFTAR as $nama => $arti)
                        <li class="rounded-full bg-hl px-2.5 py-1 text-xs text-blue" title="{{ $arti }}">${{ '{' }}{{ $nama }}{{ '}' }}</li>
                    @endforeach
                </ul>
            </details>

            <div class="mt-5 flex items-center gap-3">
                <button type="submit"
                        class="flex h-9 items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
                    <x-ikon nama="cek" :ukuran="16" />
                    Simpan
                </button>
                @if ($ubah)<input type="hidden" name="jenis_id" value="{{ $jenis->id }}">@endif
                <button type="submit" formaction="{{ route('jenis-surat.pratinjau.unggahan') }}" @unless ($ubah) formtarget="_blank" @endunless formnovalidate
                        class="flex h-9 items-center gap-2 rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">
                    <x-ikon nama="mata" :ukuran="16" />
                    Pratinjau
                </button>
                <a href="{{ route('jenis-surat.index') }}"
                   class="flex h-9 items-center rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">Batal</a>
            </div>
            @if ($ubah)
                <button type="submit" form="form-bawaan"
                        class="mt-3 text-xs font-medium text-blue hover:underline">Kembalikan templat ke bawaan</button>
            @endif
        </form>
        @if ($ubah)
            <form id="form-bawaan" action="{{ route('jenis-surat.bawaan', $jenis) }}" method="post"
                  data-konfirmasi="Templat {{ $jenis->nama }} yang Anda unggah akan diganti dengan templat bawaan." data-konfirmasi-judul="Kembalikan ke bawaan?" data-konfirmasi-tombol="Kembalikan">
                @csrf
            </form>
        @endif
        @endif
    </div>
</x-layout-aplikasi>
