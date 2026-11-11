@props(['aktif'])

@php
    $tab = 'flex h-9 items-center gap-2 rounded-full px-5 text-[13px] font-medium';
@endphp

<nav class="mb-5 flex items-center gap-3" aria-label="Kelola master">
    <a href="{{ route('jenis-surat.index') }}" @if ($aktif === 'jenis-surat') aria-current="page" @endif
       @class([$tab, 'bg-blue text-white' => $aktif === 'jenis-surat', 'bg-surf hover:bg-line' => $aktif !== 'jenis-surat'])>
        <x-ikon nama="berkas" :ukuran="16" />Jenis Surat
    </a>
    <a href="{{ route('pengguna.index') }}" @if ($aktif === 'pengguna') aria-current="page" @endif
       @class([$tab, 'bg-blue text-white' => $aktif === 'pengguna', 'bg-surf hover:bg-line' => $aktif !== 'pengguna'])>
        <x-ikon nama="pengguna" :ukuran="16" />Pengguna
    </a>
    <a href="{{ route('profil-desa.index') }}" @if ($aktif === 'profil-desa') aria-current="page" @endif
       @class([$tab, 'bg-blue text-white' => $aktif === 'profil-desa', 'bg-surf hover:bg-line' => $aktif !== 'profil-desa'])>
        <x-ikon nama="desa" :ukuran="16" />Profil Desa
    </a>
</nav>
