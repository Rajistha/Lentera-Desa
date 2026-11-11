@props(['nama', 'label'])

<div {{ $attributes }}>
    <label for="{{ $nama }}" class="text-xs text-muted">{{ $label }}</label>
    <div class="mt-1.5">{{ $slot }}</div>
    @error($nama)
        <p class="mt-1.5 flex items-center gap-1.5 text-[11px] text-error">
            <x-ikon nama="galat" :ukuran="14" />{{ $message }}
        </p>
    @enderror
</div>
