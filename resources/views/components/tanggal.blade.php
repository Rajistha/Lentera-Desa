{{-- Kolom tanggal: bisa diketik (dd-mm-yyyy) atau dipilih dari kalender. $nilai, $maks, $min berformat YYYY-MM-DD.
     Perilaku di resources/js/komponen.js --}}
@props(['nama', 'nilai' => null, 'maks' => null, 'min' => null, 'placeholder' => 'dd-mm-yyyy', 'hariIni' => true])

@php
    $nilai = (string) old($nama, $nilai);
    $galat = $errors->has($nama);
    $teks = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $nilai, $m) ? "{$m[3]}-{$m[2]}-{$m[1]}" : '';
@endphp

<div data-kalender @if ($maks) data-maks="{{ $maks }}" @endif @if ($min) data-min="{{ $min }}" @endif
     @unless ($hariIni) data-tanpa-hari-ini @endunless class="relative">
    <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">

    <div @class(['flex h-[38px] items-center rounded-full bg-surf pl-4 pr-1.5 focus-within:ring-2 focus-within:ring-blue/40', 'ring-1 ring-error' => $galat])>
        <input id="{{ $nama }}" type="text" data-teks inputmode="numeric" autocomplete="off" maxlength="10"
               placeholder="{{ $placeholder }}" value="{{ $teks }}" aria-describedby="{{ $nama }}-pesan"
               class="h-full min-w-0 flex-1 bg-transparent text-[13px] outline-none placeholder:text-muted">
        <button type="button" data-buka aria-haspopup="dialog" aria-expanded="false" aria-label="Buka kalender"
                class="flex size-8 shrink-0 items-center justify-center rounded-full text-muted hover:bg-line hover:text-blue">
            <x-ikon nama="kalender" :ukuran="16" />
        </button>
    </div>

    <p id="{{ $nama }}-pesan" data-pesan role="alert" hidden class="mt-1.5 flex items-center gap-1.5 text-[11px] text-error">
        <x-ikon nama="galat" :ukuran="14" /><span></span>
    </p>

    <div data-panel role="dialog" aria-label="Pilih tanggal" hidden
         class="absolute z-30 mt-1.5 w-[300px] max-w-[calc(100vw-2rem)] rounded-2xl border border-line bg-card p-3 shadow-lg"></div>
</div>
