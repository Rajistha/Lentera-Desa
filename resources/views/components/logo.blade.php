@props(['ukuran' => 'kecil'])

@php $besar = $ukuran === 'besar'; @endphp

<span {{ $attributes->class(['flex items-center justify-center bg-blue text-white', 'size-14 rounded-2xl' => $besar, 'size-8 rounded-[10px]' => ! $besar]) }}>
    <x-ikon nama="berkas" :ukuran="$besar ? 28 : 18" />
</span>
