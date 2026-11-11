{{-- Pembungkus PDF untuk tab baru: peramban memakai judul dan ikon halaman ini, bukan ikon penampil PDF. --}}
<x-layout-dasar :judul="'Pratinjau '.$info['nama']">
    <iframe title="Pratinjau PDF {{ $info['nama'] }}"
            src="{{ route('jenis-surat.pratinjau.pdf', [$token, 'sematan' => 1]) }}"
            class="fixed inset-0 size-full border-0 bg-surf"></iframe>
</x-layout-dasar>
