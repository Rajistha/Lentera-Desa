@php $laporan = session('laporan_impor'); @endphp

<x-layout-aplikasi judul="Impor Warga dari Excel" subjudul="Tambahkan banyak warga sekaligus dari berkas .xlsx">
    @if ($laporan)
        <section class="mb-5 rounded-[18px] border border-line bg-card p-6" aria-labelledby="judul-laporan">
            <h2 id="judul-laporan" class="text-[15px] font-bold">Hasil impor</h2>
            <p class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1">
                <span class="flex items-center gap-1.5 font-medium text-blue">
                    <x-ikon nama="cek" :ukuran="16" />{{ number_format($laporan['berhasil'], 0, ',', '.') }} baris berhasil disimpan
                </span>
                <span @class(['flex items-center gap-1.5 font-medium', 'text-error' => count($laporan['gagal']), 'text-muted' => ! count($laporan['gagal'])])>
                    <x-ikon nama="galat" :ukuran="16" />{{ number_format(count($laporan['gagal']), 0, ',', '.') }} baris gagal
                </span>
            </p>

            @if (count($laporan['gagal']))
                <table class="mt-4 w-full text-left">
                    <thead>
                        <tr class="h-10 bg-surf text-[11px] uppercase tracking-[0.8px] text-muted">
                            <th class="w-20 px-3 font-medium">Baris</th>
                            <th class="w-48 px-3 font-medium">NIK</th>
                            <th class="px-3 font-medium">Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($laporan['gagal'] as $gagal)
                            <tr class="border-b border-line align-top">
                                <td class="px-3 py-3">{{ $gagal['baris'] }}</td>
                                <td class="px-3 py-3 text-muted">{{ $gagal['nik'] !== '' ? $gagal['nik'] : '—' }}</td>
                                <td class="px-3 py-3">
                                    <ul class="space-y-1 text-error">
                                        @foreach ($gagal['alasan'] as $alasan)
                                            <li>{{ $alasan }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-3 text-xs text-muted">Perbaiki baris yang gagal di berkas, lalu unggah ulang. Baris yang sudah tersimpan akan ditolak sebagai NIK ganda, jadi hapus dulu dari berkas.</p>
            @endif

            <a href="{{ route('warga.index') }}" class="mt-4 inline-flex h-9 items-center rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">Lihat Data Warga</a>
        </section>
    @endif

    <div class="flex items-start gap-5">
        <form action="{{ route('warga.impor.proses') }}" method="post" enctype="multipart/form-data" novalidate data-memuat="Mengimpor data warga..."
              class="w-full max-w-[792px] rounded-[18px] border border-line bg-card p-6">
            @csrf

            <p class="text-xs text-muted">Langkah 1</p>
            <a href="{{ route('warga.impor.templat') }}"
               class="mt-1.5 inline-flex h-9 items-center gap-2 rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">
                <x-ikon nama="unduh" :ukuran="16" />
                Unduh Template Excel
            </a>

            <p class="mt-6 text-xs text-muted">Langkah 2: isi template, lalu unggah</p>
            <label for="berkas"
                   @class(['mt-1.5 flex cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 border-dashed px-6 py-10 text-center hover:bg-side',
                           'border-line' => ! $errors->has('berkas'), 'border-error' => $errors->has('berkas')])>
                <x-ikon nama="unggah" :ukuran="24" class="text-blue" />
                <span class="font-medium">Tarik berkas atau klik</span>
                <span class="text-xs text-muted">.xlsx, maksimal 2 MB, maksimal {{ number_format($maksBaris, 0, ',', '.') }} baris</span>
                <span id="nama-berkas" class="hidden rounded-full bg-hl px-3 py-1 text-xs font-medium text-blue"></span>
                <input id="berkas" name="berkas" type="file" class="sr-only"
                       accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                       onchange="const n = document.getElementById('nama-berkas'); n.textContent = this.files[0]?.name ?? ''; n.classList.toggle('hidden', ! this.files.length)">
            </label>
            @error('berkas')
                <p class="mt-1.5 flex items-center gap-1.5 text-[11px] text-error">
                    <x-ikon nama="galat" :ukuran="14" />{{ $message }}
                </p>
            @enderror

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="flex h-9 items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
                    <x-ikon nama="cek" :ukuran="16" />
                    Impor
                </button>
                <a href="{{ route('warga.index') }}"
                   class="flex h-9 items-center rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">Batal</a>
            </div>
        </form>

        <aside class="w-[320px] shrink-0 rounded-[18px] border border-line bg-card p-6">
            <h2 class="flex items-center gap-2 text-[15px] font-bold">
                <x-ikon nama="info" :ukuran="16" class="text-blue" />
                Catatan Impor
            </h2>
            <ul class="mt-4 space-y-3">
                @foreach (['Tiap baris divalidasi seperti formulir warga', 'NIK 16 digit, unik di basis data dan di dalam berkas', 'Ketik NIK sebagai teks (kolom NIK di template sudah berformat teks)', 'Baris yang valid disimpan, baris yang gagal dilewati dan dilaporkan'] as $catatan)
                    <li class="flex items-start gap-2.5">
                        <x-ikon nama="cek" :ukuran="16" class="mt-px text-blue" />
                        {{ $catatan }}
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>
</x-layout-aplikasi>
