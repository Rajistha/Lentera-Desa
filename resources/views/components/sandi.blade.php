{{-- Kolom kata sandi dengan tombol mata (tampilkan/sembunyikan). Perilaku di resources/js/komponen.js --}}
@props(['nama' => 'password', 'placeholder' => null, 'autocomplete' => 'current-password'])

@php $galat = $errors->has($nama); @endphp

<div class="relative">
    <x-ikon nama="kunci" :ukuran="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-muted" />
    <input id="{{ $nama }}" name="{{ $nama }}" type="password" autocomplete="{{ $autocomplete }}"
           @if ($placeholder) placeholder="{{ $placeholder }}" @endif
           {{ $attributes->class(['h-[38px] w-full rounded-full bg-surf pl-11 pr-11 text-[13px] outline-none placeholder:text-muted', 'ring-1 ring-error' => $galat]) }}>
    <button type="button" data-toggle-sandi="{{ $nama }}" aria-label="Tampilkan kata sandi" aria-pressed="false"
            class="absolute right-2 top-1/2 flex size-7 -translate-y-1/2 items-center justify-center rounded-full text-muted hover:bg-line hover:text-blue">
        <span data-ikon-lihat><x-ikon nama="mata" :ukuran="16" /></span>
        <span data-ikon-sembunyi class="hidden"><x-ikon nama="mata-tutup" :ukuran="16" /></span>
    </button>
</div>
