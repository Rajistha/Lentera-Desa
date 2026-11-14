@php
    $input = fn ($nama) => 'h-[38px] w-full rounded-full bg-surf px-4 text-[13px] outline-none placeholder:text-muted'
        .($errors->has($nama) ? ' ring-1 ring-error' : '');
    $pemakaian = [
        'Nama desa' => 'nama_desa',
        'Nama kecamatan' => 'nama_kecamatan',
        'Nama kabupaten' => 'nama_kabupaten',
        'Alamat kantor desa' => 'alamat_kantor_desa',
        'Kode pos' => 'kode_pos',
        'Telepon kantor desa' => 'telepon_desa',
        'Nama kepala desa' => 'nama_kepala_desa',
    ];
    $tulisPenanda = fn (string $penanda) => '{{ '.$penanda.' }}';
@endphp

<x-layout-aplikasi judul="Profil Desa" subjudul="Informasi desa yang mengisi kop dan isi surat">
    <x-tab-master aktif="profil-desa" />

    <div class="flex items-start gap-5">
        <form action="{{ route('profil-desa.update') }}" method="post" novalidate
              class="w-full max-w-[792px] rounded-[18px] border border-line bg-card p-6">
            @csrf
            @method('put')

            <div class="grid grid-cols-2 gap-x-4 gap-y-4">
                <x-kolom nama="nama_desa" label="Nama desa">
                    <input id="nama_desa" name="nama_desa" type="text" maxlength="100" placeholder="Contoh: Sukamaju"
                           value="{{ old('nama_desa', $profil->nama_desa) }}" class="{{ $input('nama_desa') }}">
                </x-kolom>

                <x-kolom nama="kode_desa" label="Kode desa (untuk nomor surat)">
                    <input id="kode_desa" name="kode_desa" type="text" maxlength="10" placeholder="DS"
                           value="{{ old('kode_desa', $profil->kode_desa) }}" class="{{ $input('kode_desa') }} uppercase">
                </x-kolom>

                <x-kolom nama="nama_kecamatan" label="Nama kecamatan">
                    <input id="nama_kecamatan" name="nama_kecamatan" type="text" maxlength="100"
                           value="{{ old('nama_kecamatan', $profil->nama_kecamatan) }}" class="{{ $input('nama_kecamatan') }}">
                </x-kolom>

                <x-kolom nama="nama_kabupaten" label="Nama kabupaten">
                    <input id="nama_kabupaten" name="nama_kabupaten" type="text" maxlength="100"
                           value="{{ old('nama_kabupaten', $profil->nama_kabupaten) }}" class="{{ $input('nama_kabupaten') }}">
                </x-kolom>

                <x-kolom nama="alamat_kantor" label="Alamat kantor desa" class="col-span-2">
                    <input id="alamat_kantor" name="alamat_kantor" type="text" maxlength="255" placeholder="Jalan, nomor, dusun"
                           value="{{ old('alamat_kantor', $profil->alamat_kantor) }}" class="{{ $input('alamat_kantor') }}">
                </x-kolom>

                <x-kolom nama="kode_pos" label="Kode pos">
                    <input id="kode_pos" name="kode_pos" type="text" inputmode="numeric" maxlength="5" placeholder="5 digit"
                           value="{{ old('kode_pos', $profil->kode_pos) }}" class="{{ $input('kode_pos') }}">
                </x-kolom>

                <x-kolom nama="telepon" label="Telepon kantor desa">
                    <input id="telepon" name="telepon" type="text" inputmode="tel" maxlength="30"
                           value="{{ old('telepon', $profil->telepon) }}" class="{{ $input('telepon') }}">
                </x-kolom>

                <x-kolom nama="nama_kepala_desa" label="Nama kepala desa" class="col-span-2">
                    <input id="nama_kepala_desa" name="nama_kepala_desa" type="text" maxlength="100" placeholder="Nama terang beserta gelar"
                           value="{{ old('nama_kepala_desa', $profil->nama_kepala_desa) }}" class="{{ $input('nama_kepala_desa') }}">
                </x-kolom>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="flex h-9 items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
                    <x-ikon nama="cek" :ukuran="16" />
                    Simpan
                </button>
            </div>
        </form>

        <aside class="w-[340px] shrink-0 rounded-[18px] border border-line bg-card p-6">
            <h2 class="flex items-center gap-2 text-[15px] font-bold">
                <x-ikon nama="info" :ukuran="16" class="text-blue" />
                Dipakai pada template surat
            </h2>
            <p class="mt-3 text-xs text-muted">Tulis penanda berikut di template Word, dengan kurung dolar atau kurung kurawal ganda:</p>
            <ul class="mt-3 space-y-2">
                @foreach ($pemakaian as $label => $penanda)
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-[13px]">{{ $label }}</span>
                        <span class="rounded-full bg-hl px-2.5 py-1 text-xs text-blue">{{ $tulisPenanda($penanda) }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-xs text-muted">
                Label berkurung seperti [NAMA DESA], [NAMA KECAMATAN], [Alamat Kantor Desa], dan [Nomor Telepon] pada template
                juga diisi otomatis bila datanya sudah diisi di sini.
            </p>
        </aside>
    </div>
</x-layout-aplikasi>
