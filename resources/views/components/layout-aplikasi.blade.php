@props(['judul', 'subjudul' => null])

<x-layout-dasar :judul="$judul">
    <div class="flex min-h-screen print:block">
        <x-sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-topbar />

            <main class="flex-1 px-8 py-7 print:p-0">
                <div class="mb-5 flex items-start justify-between gap-4 border-b border-line pb-4">
                    <div>
                        <h1 class="text-[26px] font-bold leading-tight text-blue">{{ $judul }}</h1>
                        @if ($subjudul)
                            <p class="mt-1 text-[13px] text-muted">{{ $subjudul }}</p>
                        @endif
                    </div>
                    @isset($aksi)
                        <div class="shrink-0 pt-1">{{ $aksi }}</div>
                    @endisset
                </div>

                @if (session('sukses'))
                    <div role="status" class="mb-5 flex items-center gap-2 rounded-xl bg-hl px-4 py-3 text-blue">
                        <x-ikon nama="cek" :ukuran="16" />
                        {{ session('sukses') }}
                    </div>
                @endif

                @if (session('galat'))
                    <div role="alert" class="mb-5 flex items-center gap-2 rounded-xl bg-error/10 px-4 py-3 text-error">
                        <x-ikon nama="galat" :ukuran="16" />
                        {{ session('galat') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</x-layout-dasar>
