{{-- Pengganti <select>. $opsi: [nilai => teks]. Perilaku di resources/js/komponen.js --}}
@props(['nama', 'opsi', 'nilai' => null, 'placeholder' => 'Pilih', 'wajib' => false])

@php
    $nilai = (string) old($nama, $nilai);
    $terpilih = $nilai !== '' ? ($opsi[$nilai] ?? null) : null;
    $galat = $errors->has($nama);
    $item = 'group flex cursor-pointer items-center justify-between rounded-xl px-3 py-2 text-[13px] data-[aktif]:bg-surf aria-selected:bg-hl aria-selected:font-medium aria-selected:text-blue';
@endphp

<div data-pilihan class="relative">
    <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">
    <button type="button" id="{{ $nama }}" role="combobox" aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $nama }}-daftar"
            @class(['flex h-[38px] w-full items-center justify-between gap-2 rounded-full bg-surf px-4 text-left text-[13px] outline-none', 'ring-1 ring-error' => $galat])>
        <span data-label @class(['truncate', 'text-muted' => $terpilih === null])>{{ $terpilih ?? $placeholder }}</span>
        <x-ikon nama="bawah" :ukuran="16" class="text-muted" />
    </button>
    <ul id="{{ $nama }}-daftar" role="listbox" hidden
        class="absolute z-30 mt-1.5 max-h-64 w-full overflow-auto rounded-2xl border border-line bg-card p-1.5 shadow-lg">
        @unless ($wajib)
            <li role="option" data-nilai="" data-label="{{ $placeholder }}" aria-selected="{{ $terpilih === null ? 'true' : 'false' }}"
                class="{{ $item }} text-muted">
                {{ $placeholder }}
            </li>
        @endunless
        @foreach ($opsi as $kunci => $teks)
            <li role="option" data-nilai="{{ $kunci }}" data-label="{{ $teks }}" aria-selected="{{ (string) $kunci === $nilai ? 'true' : 'false' }}"
                class="{{ $item }}">
                {{ $teks }}
                <x-ikon nama="cek" :ukuran="16" class="hidden group-aria-selected:block" />
            </li>
        @endforeach
    </ul>
</div>
