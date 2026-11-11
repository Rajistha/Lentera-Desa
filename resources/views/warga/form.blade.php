@php
    $ubah = $warga->exists;
    $input = fn ($nama) => 'h-[38px] w-full rounded-full bg-surf px-4 text-[13px] outline-none placeholder:text-muted'
        .($errors->has($nama) ? ' ring-1 ring-error' : '');
@endphp

<x-layout-aplikasi :judul="$ubah ? 'Ubah Data Warga' : 'Tambah Warga'" subjudul="Semua kolom dalam dua kolom; validasi muncul per kolom">
    <div class="flex items-start gap-5">
        <form action="{{ $ubah ? route('warga.update', $warga) : route('warga.store') }}" method="post" novalidate
              class="w-full max-w-[792px] rounded-[18px] border border-line bg-card p-6">
            @csrf
            @if ($ubah) @method('put') @endif

            <div class="grid grid-cols-2 gap-x-4 gap-y-4">
                <x-kolom nama="nik" label="NIK">
                    <input id="nik" name="nik" type="text" inputmode="numeric" maxlength="16"
                           value="{{ old('nik', $warga->nik) }}" class="{{ $input('nik') }}" autofocus>
                </x-kolom>

                <x-kolom nama="nama" label="Nama lengkap">
                    <input id="nama" name="nama" type="text" placeholder="Nama sesuai KTP"
                           value="{{ old('nama', $warga->nama) }}" class="{{ $input('nama') }}">
                </x-kolom>

                <x-kolom nama="tempat_lahir" label="Tempat lahir">
                    <input id="tempat_lahir" name="tempat_lahir" type="text" placeholder="Kota kelahiran"
                           value="{{ old('tempat_lahir', $warga->tempat_lahir) }}" class="{{ $input('tempat_lahir') }}">
                </x-kolom>

                <x-kolom nama="tanggal_lahir" label="Tanggal lahir">
                    <x-tanggal nama="tanggal_lahir" :nilai="$warga->tanggal_lahir?->toDateString()" :maks="now()->toDateString()" :hari-ini="false" />
                </x-kolom>

                <x-kolom nama="jenis_kelamin" label="Jenis kelamin">
                    <x-pilihan nama="jenis_kelamin" :opsi="\App\Models\Warga::JENIS_KELAMIN" :nilai="$warga->jenis_kelamin" placeholder="Pilih jenis kelamin" />
                </x-kolom>

                <x-kolom nama="status_perkawinan" label="Status perkawinan">
                    <x-pilihan nama="status_perkawinan" :opsi="\App\Models\Warga::STATUS_PERKAWINAN" :nilai="$warga->status_perkawinan" placeholder="Pilih status" />
                </x-kolom>

                <x-kolom nama="alamat" label="Alamat" class="col-span-2">
                    <textarea id="alamat" name="alamat" rows="3" placeholder="Jalan, nomor, dusun"
                              class="w-full rounded-xl bg-surf px-4 py-3 text-[13px] outline-none placeholder:text-muted {{ $errors->has('alamat') ? 'ring-1 ring-error' : '' }}">{{ old('alamat', $warga->alamat) }}</textarea>
                </x-kolom>

                <x-kolom nama="rt" label="RT">
                    <input id="rt" name="rt" type="text" inputmode="numeric" maxlength="3" placeholder="00"
                           value="{{ old('rt', $warga->rt) }}" class="{{ $input('rt') }}">
                </x-kolom>

                <x-kolom nama="rw" label="RW">
                    <input id="rw" name="rw" type="text" inputmode="numeric" maxlength="3" placeholder="00"
                           value="{{ old('rw', $warga->rw) }}" class="{{ $input('rw') }}">
                </x-kolom>

                <x-kolom nama="pekerjaan" label="Pekerjaan (opsional)" class="col-span-2">
                    <input id="pekerjaan" name="pekerjaan" type="text" placeholder="Pekerjaan"
                           value="{{ old('pekerjaan', $warga->pekerjaan) }}" class="{{ $input('pekerjaan') }}">
                </x-kolom>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="flex h-9 items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
                    <x-ikon nama="cek" :ukuran="16" />
                    Simpan
                </button>
                <a href="{{ route('warga.index') }}"
                   class="flex h-9 items-center rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">Batal</a>
            </div>
        </form>

        <aside class="w-[320px] shrink-0 rounded-[18px] border border-line bg-card p-6">
            <h2 class="flex items-center gap-2 text-[15px] font-bold">
                <x-ikon nama="info" :ukuran="16" class="text-blue" />
                Catatan Validasi
            </h2>
            <ul class="mt-4 space-y-3">
                @foreach (['Kolom wajib tidak boleh kosong', 'NIK 16 digit angka dan unik', 'RT dan RW berupa angka', 'Tanggal lahir tidak melewati hari ini', 'Setelah disimpan, kembali ke Data Warga'] as $catatan)
                    <li class="flex items-start gap-2.5">
                        <x-ikon nama="cek" :ukuran="16" class="mt-px text-blue" />
                        {{ $catatan }}
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>
</x-layout-aplikasi>
