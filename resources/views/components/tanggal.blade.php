{{-- Pengganti <input type="date">. $nilai dan $maks/$min berformat YYYY-MM-DD. Perilaku di resources/js/komponen.js --}}
@props(['nama', 'nilai' => null, 'maks' => null, 'min' => null, 'placeholder' => 'Pilih tanggal'])

@php
    $nilai = (string) old($nama, $nilai);
    $galat = $errors->has($nama);
@endphp

<div data-kalender @if ($maks) data-maks="{{ $maks }}" @endif @if ($min) data-min="{{ $min }}" @endif class="relative">
    <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">
    <button type="button" id="{{ $nama }}" aria-haspopup="dialog" aria-expanded="false"
            @class(['flex h-[38px] w-full items-center justify-between gap-2 rounded-full bg-surf px-4 text-left text-[13px] outline-none', 'ring-1 ring-error' => $galat])>
        <span data-label data-placeholder="{{ $placeholder }}">{{ $placeholder }}</span>
        <x-ikon nama="kalender" :ukuran="16" class="text-muted" />
    </button>
    <div data-panel role="dialog" aria-label="Pilih tanggal" hidden
         class="absolute z-30 mt-1.5 w-[300px] rounded-2xl border border-line bg-card p-3 shadow-lg"></div>
</div>
