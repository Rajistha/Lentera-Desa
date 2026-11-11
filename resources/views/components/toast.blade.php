{{-- Notifikasi status setelah aksi (flash "sukses" / "galat"). Perilaku di resources/js/komponen.js --}}
@if (session('sukses') || session('galat'))
    <div class="pointer-events-none fixed right-6 top-20 z-50 flex w-[380px] max-w-[calc(100vw-3rem)] flex-col gap-3 print:hidden">
        @if (session('sukses'))
            <div data-toast data-otomatis="5000" role="status"
                 class="toast-masuk pointer-events-auto flex items-start gap-3 rounded-2xl border border-line bg-card p-4 shadow-lg transition duration-200">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-hl text-blue"><x-ikon nama="cek" :ukuran="16" /></span>
                <div class="min-w-0 flex-1">
                    <p class="text-[13px] font-bold">Berhasil</p>
                    <p class="mt-0.5 break-words text-[13px] text-muted">{{ session('sukses') }}</p>
                </div>
                <button type="button" data-toast-tutup aria-label="Tutup notifikasi"
                        class="flex size-6 shrink-0 items-center justify-center rounded-full text-muted hover:bg-surf hover:text-ink">
                    <x-ikon nama="tutup" :ukuran="14" />
                </button>
            </div>
        @endif

        @if (session('galat'))
            {{-- Pesan galat tidak hilang sendiri: isinya sering perlu dibaca dan ditindaklanjuti. --}}
            <div data-toast role="alert"
                 class="toast-masuk pointer-events-auto flex items-start gap-3 rounded-2xl border border-error/30 bg-card p-4 shadow-lg transition duration-200">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-error/10 text-error"><x-ikon nama="galat" :ukuran="16" /></span>
                <div class="min-w-0 flex-1">
                    <p class="text-[13px] font-bold">Gagal</p>
                    <p class="mt-0.5 break-words text-[13px] text-muted">{{ session('galat') }}</p>
                </div>
                <button type="button" data-toast-tutup aria-label="Tutup notifikasi"
                        class="flex size-6 shrink-0 items-center justify-center rounded-full text-muted hover:bg-surf hover:text-ink">
                    <x-ikon nama="tutup" :ukuran="14" />
                </button>
            </div>
        @endif
    </div>
@endif
