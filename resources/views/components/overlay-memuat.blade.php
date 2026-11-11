{{-- Lapisan animasi saat formulir yang memakan waktu dikirim (form ber-atribut data-memuat). Perilaku di resources/js/komponen.js --}}
<div id="overlay-memuat" hidden role="status" aria-live="polite" class="fixed inset-0 z-[70] bg-ink/40 print:hidden">
    <div class="flex h-full items-center justify-center px-4">
        <div class="flex w-[320px] max-w-full flex-col items-center gap-4 rounded-[18px] border border-line bg-card px-8 py-9 text-center shadow-xl">
            <span class="size-11 animate-spin rounded-full border-4 border-hl border-t-blue motion-reduce:animate-none" aria-hidden="true"></span>
            <p data-memuat-teks class="text-[14px] font-bold">Memproses...</p>
            <p class="text-xs text-muted">Mohon tunggu, jangan menutup halaman ini.</p>
        </div>
    </div>
</div>
