@php
    $pengguna = auth()->user();

    $menu = $pengguna->role === 'administrator'
        ? [
            ['Kelola Jenis Surat', 'jenis-surat.index', 'berkas', 'jenis-surat*'],
            ['Kelola Pengguna', 'pengguna.index', 'pengguna', 'pengguna*'],
        ]
        : [
            ['Dasbor', 'dasbor', 'dasbor', 'dasbor'],
            ['Data Warga', 'warga.index', 'warga', 'warga*'],
            ['Buat Surat', 'surat.create', 'surat-baru', 'surat/buat'],
            ['Arsip Surat', 'surat.index', 'arsip', fn () => request()->is('surat') || (request()->is('surat/*') && ! request()->is('surat/buat'))],
            ['Rekap Layanan', 'rekap.index', 'rekap', 'rekap*'],
        ];

    $peran = $pengguna->role === 'administrator' ? 'Administrator' : 'Operator';
@endphp

<aside class="sticky top-0 flex h-screen print:hidden w-60 shrink-0 flex-col self-start overflow-y-auto bg-side px-5 py-6">
    <a href="{{ url('/') }}" class="mb-6 flex items-center gap-3 px-1">
        <x-logo />
        <span class="text-lg font-bold text-blue">Lentera Desa</span>
    </a>

    <nav class="flex flex-col gap-1" aria-label="Menu utama">
        @foreach ($menu as [$teks, $rute, $ikon, $pola])
            @php $aktif = $pola instanceof Closure ? $pola() : request()->is($pola); @endphp
            <a href="{{ route($rute) }}"
               @class(['flex h-[42px] items-center gap-3 rounded-xl px-3.5 text-sm',
                       'bg-hl font-bold text-blue' => $aktif,
                       'font-medium text-ink hover:bg-surf' => ! $aktif])
               @if ($aktif) aria-current="page" @endif>
                <x-ikon :nama="$ikon" @class(['text-blue' => $aktif, 'text-muted' => ! $aktif]) />
                {{ $teks }}
            </a>
        @endforeach
    </nav>

    <div class="mt-auto flex items-center gap-3 px-1">
        <span class="flex size-9 items-center justify-center rounded-full bg-hl text-sm font-bold text-blue">
            {{ mb_strtoupper(mb_substr($pengguna->name, 0, 1)) }}
        </span>
        <div class="min-w-0">
            <p class="truncate text-[13px] font-bold">{{ $pengguna->name }}</p>
            <p class="text-[11px] text-muted">{{ $peran }}</p>
        </div>
    </div>
</aside>
