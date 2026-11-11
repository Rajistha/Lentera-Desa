@php
    $ubah = $pengguna->exists;
    // Formulir hanya tampil saat menambah, mengubah, atau ada galat validasi.
    $tampilForm = $ubah || request()->boolean('tambah') || $errors->any();
    $input = fn ($nama) => 'h-[38px] w-full rounded-full bg-surf px-4 text-[13px] outline-none placeholder:text-muted'
        .($errors->has($nama) ? ' ring-1 ring-error' : '');
    $namaPeran = ['operator' => 'Operator', 'administrator' => 'Administrator'];
    $saya = auth()->id();
@endphp

<x-layout-aplikasi judul="Kelola Pengguna" subjudul="Daftar, tambah, dan ubah akun pengguna">
    <x-slot:aksi>
        <a href="{{ route('pengguna.index', ['tambah' => 1]) }}#formulir"
           class="flex h-9 items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
            <x-ikon nama="tambah" :ukuran="16" />
            Tambah Pengguna
        </a>
    </x-slot:aksi>

    <x-tab-master aktif="pengguna" />

    <table class="w-full text-left">
        <thead>
            <tr class="h-10 bg-surf text-[11px] uppercase tracking-[0.8px] text-muted">
                <th class="px-3 font-medium">Username</th>
                <th class="px-3 font-medium">Nama</th>
                <th class="px-3 font-medium">Peran</th>
                <th class="px-3 font-medium">Jabatan</th>
                <th class="px-3 font-medium">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($daftar as $item)
                <tr @class(['h-[52px] border-b border-line', 'bg-side' => $item->is($pengguna)])>
                    <td class="px-3">{{ $item->username }}</td>
                    <td class="px-3">
                        {{ $item->name }}
                        @if ($item->id === $saya)<span class="ml-1.5 text-[11px] text-muted">(Anda)</span>@endif
                    </td>
                    <td class="px-3">
                        <span class="rounded-full bg-hl px-2.5 py-1 text-xs font-medium text-blue">{{ $namaPeran[$item->role] ?? $item->role }}</span>
                    </td>
                    <td class="px-3 text-muted">{{ $item->jabatan ?: '-' }}</td>
                    <td class="px-3">
                        <span class="flex items-center gap-2">
                            <a href="{{ route('pengguna.edit', $item) }}#formulir" title="Ubah" aria-label="Ubah {{ $item->name }}"
                               class="flex size-8 items-center justify-center rounded-xl bg-surf text-muted hover:bg-line">
                                <x-ikon nama="ubah" :ukuran="16" />
                            </a>
                            @if ($item->id !== $saya)
                                <form action="{{ route('pengguna.destroy', $item) }}" method="post"
                                      data-konfirmasi="Akun {{ $item->name }} akan dihapus dan tidak dapat masuk lagi." data-konfirmasi-judul="Hapus pengguna?" data-konfirmasi-tombol="Hapus" data-konfirmasi-gaya="bahaya">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" title="Hapus" aria-label="Hapus {{ $item->name }}"
                                            class="flex size-8 items-center justify-center rounded-xl bg-surf text-error hover:bg-line">
                                        <x-ikon nama="hapus" :ukuran="16" />
                                    </button>
                                </form>
                            @endif
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($tampilForm)
        <form id="formulir" action="{{ $ubah ? route('pengguna.update', $pengguna) : route('pengguna.store') }}"
              method="post" novalidate
              class="mt-5 rounded-[18px] border border-line bg-card p-6">
            @csrf
            @if ($ubah) @method('put') @endif

            <h2 class="text-[15px] font-bold">{{ $ubah ? 'Formulir Ubah Pengguna' : 'Formulir Tambah Pengguna' }}</h2>

            <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-4">
                <x-kolom nama="username" label="Username">
                    <input id="username" name="username" type="text" maxlength="50" autocomplete="off" placeholder="operator1"
                           value="{{ old('username', $pengguna->username) }}" class="{{ $input('username') }}">
                </x-kolom>

                <x-kolom nama="name" label="Nama">
                    <input id="name" name="name" type="text" placeholder="Nama lengkap"
                           value="{{ old('name', $pengguna->name) }}" class="{{ $input('name') }}">
                </x-kolom>

                <x-kolom nama="role" label="Peran">
                    <x-pilihan nama="role" :opsi="$namaPeran" :nilai="$pengguna->role" placeholder="Pilih peran" />
                </x-kolom>

                <x-kolom nama="password" :label="$ubah ? 'Kata sandi baru' : 'Kata sandi'">
                    <x-sandi nama="password" autocomplete="new-password"
                             :placeholder="$ubah ? 'Kosongkan jika tidak diganti' : 'Minimal 8 karakter'" />
                </x-kolom>

                <x-kolom nama="jabatan" label="Jabatan (opsional)" class="col-span-2">
                    <input id="jabatan" name="jabatan" type="text" placeholder="Contoh: Sekretaris desa"
                           value="{{ old('jabatan', $pengguna->jabatan) }}" class="{{ $input('jabatan') }}">
                </x-kolom>
            </div>

            <p class="mt-3 text-[11px] text-muted">Kata sandi minimal 8 karakter{{ $ubah ? '; kosongkan jika tidak diganti' : '' }}.</p>

            <div class="mt-4 flex items-center gap-3">
                <button type="submit"
                        class="flex h-9 items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
                    <x-ikon nama="cek" :ukuran="16" />
                    Simpan
                </button>
                <a href="{{ route('pengguna.index') }}"
                   class="flex h-9 items-center rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">Batal</a>
            </div>
        </form>
    @endif
</x-layout-aplikasi>
