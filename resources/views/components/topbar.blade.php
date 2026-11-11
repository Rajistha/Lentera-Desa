@php $pengguna = auth()->user(); @endphp

<header class="sticky top-0 z-40 flex h-16 shrink-0 items-center justify-between border-b border-line bg-card px-8">
    <div>
        @if ($pengguna->role === 'operator')
            <form action="{{ route('surat.index') }}" method="get" role="search"
                  class="flex h-10 w-[380px] items-center gap-2.5 rounded-full bg-surf px-4">
                <x-ikon nama="cari" :ukuran="16" class="text-muted" />
                <input type="search" name="q" placeholder="Cari NIK, nama, atau nomor surat..."
                       aria-label="Cari NIK, nama, atau nomor surat"
                       class="w-full bg-transparent text-[13px] outline-none placeholder:text-muted">
            </form>
        @endif
    </div>

    <div class="flex items-center gap-3">
        <span class="max-w-48 truncate text-xs text-muted">{{ $pengguna->name }}</span>
        <span class="flex size-8 items-center justify-center rounded-full bg-hl text-blue">
            <x-ikon nama="pengguna" :ukuran="16" />
        </span>
        <form action="{{ route('logout') }}" method="post">
            @csrf
            <button type="submit"
                    class="flex h-9 items-center gap-2 rounded-full bg-surf px-4 text-[13px] font-medium hover:bg-line">
                <x-ikon nama="keluar" :ukuran="16" />
                Keluar
            </button>
        </form>
    </div>
</header>
