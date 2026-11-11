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

                {{ $slot }}
            </main>
        </div>
    </div>

    <x-toast />
    <x-overlay-memuat />
    <x-modal-konfirmasi />
</x-layout-dasar>
