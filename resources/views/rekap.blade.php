@php
    $maks = max(1, $baris->max('jumlah'));
    $tinggiMaks = 220; // px, tinggi batang tertinggi
    $ringkas = $baris->map(fn ($b) => $b['nama'].' '.$b['jumlah'])->implode(', ');
@endphp

<x-layout-aplikasi judul="Rekap Layanan" subjudul="Jumlah surat per jenis">
    {{-- Kop khusus cetak --}}
    <div class="mb-4 hidden text-center print:block">
        <p class="text-base font-bold uppercase">Pemerintah Desa {{ \App\Models\ProfilDesa::saatIni()->nama_desa }}</p>
        <p class="text-sm">Rekap Layanan Surat — {{ $periode }}</p>
        <p class="text-xs text-muted">Dicetak {{ now()->locale('id')->translatedFormat('j F Y') }}</p>
    </div>

    <form action="{{ route('rekap.index') }}" method="get" aria-label="Pilih periode rekap"
          class="flex items-end gap-4 print:hidden">
        <x-kolom nama="bulan" label="Bulan" class="w-[200px]">
            <x-pilihan nama="bulan" :opsi="$opsiBulan" :nilai="$bulan" :wajib="true" />
        </x-kolom>

        <x-kolom nama="tahun" label="Tahun" class="w-[160px]">
            <x-pilihan nama="tahun" :opsi="$opsiTahun" :nilai="$tahun" :wajib="true" />
        </x-kolom>

        <button type="submit"
                class="flex h-[38px] items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
            <x-ikon nama="cari" :ukuran="16" />
            Tampilkan
        </button>

        <button type="button" onclick="window.print()"
                class="ml-auto flex h-[38px] items-center gap-2 rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">
            <x-ikon nama="cetak" :ukuran="16" />
            Cetak Rekap
        </button>
    </form>

    <p class="mt-5 text-[13px] font-medium">Periode: {{ $periode }}</p>

    <div class="mt-3 flex items-start gap-5">
        <table class="w-[424px] shrink-0 text-left">
            <thead>
                <tr class="h-10 bg-surf text-[11px] uppercase tracking-[0.8px] text-muted">
                    <th class="px-3 font-medium">Jenis Surat</th>
                    <th class="px-3 font-medium">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($baris as $b)
                    <tr class="h-[52px] border-b border-line">
                        <td class="px-3">{{ $b['nama'] }}</td>
                        <td class="px-3">{{ number_format($b['jumlah'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="h-[52px] border-b border-line font-bold">
                    <td class="px-3">TOTAL</td>
                    <td class="px-3">{{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <section class="min-w-0 flex-1 rounded-[18px] border border-line bg-card p-6" aria-labelledby="judul-grafik">
            <h2 id="judul-grafik" class="text-[15px] font-bold">Grafik jumlah surat per jenis</h2>

            @if ($total > 0)
                <div role="img" aria-label="Grafik batang jumlah surat per jenis pada {{ $periode }}: {{ $ringkas }}"
                     class="mt-4 flex items-end justify-around gap-3">
                    @foreach ($baris as $b)
                        <div class="flex min-w-0 max-w-[110px] flex-1 flex-col items-center justify-end">
                            <span class="mb-1.5 text-[13px] font-bold">{{ $b['jumlah'] }}</span>
                            <div class="w-14 rounded-lg bg-blue" style="height: {{ (int) round($b['jumlah'] / $maks * $tinggiMaks) }}px"></div>
                            <span class="mt-2 min-h-8 text-center text-xs text-muted">{{ $b['nama'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-6 flex items-center gap-2 text-xs text-muted">
                    <x-ikon nama="info" :ukuran="16" class="text-blue" />
                    Belum ada surat pada {{ $periode }}.
                </p>
            @endif
        </section>
    </div>
</x-layout-aplikasi>
