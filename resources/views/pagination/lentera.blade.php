@if ($paginator->hasPages())
    @php
        $dasar = 'flex size-8 items-center justify-center rounded-xl text-xs font-medium';
    @endphp

    <nav class="flex items-center gap-2" aria-label="Penomoran halaman">
        @if ($paginator->onFirstPage())
            <span class="{{ $dasar }} bg-surf text-muted opacity-50" aria-disabled="true"><x-ikon nama="kiri" :ukuran="14" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="{{ $dasar }} bg-surf hover:bg-line"><x-ikon nama="kiri" :ukuran="14" /></a>
        @endif

        @foreach ($elements as $elemen)
            @if (is_string($elemen))
                <span class="{{ $dasar }} text-muted">{{ $elemen }}</span>
            @else
                @foreach ($elemen as $halaman => $url)
                    @if ($halaman == $paginator->currentPage())
                        <span class="{{ $dasar }} bg-blue text-white" aria-current="page">{{ $halaman }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="Halaman {{ $halaman }}" class="{{ $dasar }} bg-surf hover:bg-line">{{ $halaman }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="{{ $dasar }} bg-surf hover:bg-line"><x-ikon nama="kanan" :ukuran="14" /></a>
        @else
            <span class="{{ $dasar }} bg-surf text-muted opacity-50" aria-disabled="true"><x-ikon nama="kanan" :ukuran="14" /></span>
        @endif
    </nav>
@endif
