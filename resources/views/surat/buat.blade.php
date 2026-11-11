@php
    $ubah = $surat->exists;
    $input = fn ($nama) => 'h-[38px] w-full rounded-full bg-surf px-4 text-[13px] outline-none placeholder:text-muted'
        .($errors->has($nama) ? ' ring-1 ring-error' : '');
    $baris = [
        'nik' => 'NIK',
        'nama' => 'Nama',
        'ttl' => 'Tempat, tanggal lahir',
        'jenis_kelamin' => 'Jenis kelamin',
        'alamat' => 'Alamat',
        'pekerjaan' => 'Pekerjaan',
        'status' => 'Status perkawinan',
    ];
@endphp

<x-layout-aplikasi :judul="$ubah ? 'Ubah Surat' : 'Buat Surat'"
                   :subjudul="$ubah ? 'No. '.$surat->nomor_surat.' · nomor dan jenis surat tidak berubah' : 'Pilih warga dan jenis surat; data warga terisi otomatis'">
    <div class="flex items-start gap-5">
        <form action="{{ $ubah ? route('surat.update', $surat) : route('surat.store') }}" method="post" novalidate
              data-memuat="{{ $ubah ? 'Menyimpan perubahan dan membuat ulang PDF...' : 'Membuat surat dan PDF...' }}"
              class="w-full max-w-[732px] rounded-[18px] border border-line bg-card p-6">
            @csrf
            @if ($ubah) @method('put') @endif

            <x-kolom nama="warga_id" label="Cari warga (NIK atau nama)">
                <div class="relative">
                    <x-ikon nama="cari" :ukuran="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-muted" />
                    <input id="warga_id" type="text" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="hasil-warga"
                           placeholder="Ketik minimal 2 huruf nama atau NIK"
                           value="{{ $terpilih['nama'] ?? '' }}"
                           class="{{ $input('warga_id') }} pl-11">
                    <input type="hidden" name="warga_id" id="warga_id_nilai" value="{{ $terpilih['id'] ?? '' }}">
                    <ul id="hasil-warga" role="listbox" hidden
                        class="absolute z-10 mt-1 w-full overflow-hidden rounded-2xl border border-line bg-card py-1 shadow-lg"></ul>
                </div>
            </x-kolom>

            <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-4">
                <x-kolom nama="jenis_surat_id" label="Jenis surat">
                    @if ($ubah)
                        {{-- Nomor surat memuat kode jenis, jadi jenis tidak dapat diganti (AB-03). --}}
                        <div class="flex h-[38px] items-center rounded-full bg-surf px-4 text-[13px] text-muted">{{ $surat->jenisSurat->nama }}</div>
                    @else
                        <x-pilihan nama="jenis_surat_id" :opsi="$jenisSurat->pluck('nama', 'id')->all()" placeholder="Pilih jenis surat" />
                    @endif
                </x-kolom>

                <x-kolom nama="tanggal_surat" label="Tanggal surat">
                    <x-tanggal nama="tanggal_surat" :nilai="$surat->tanggal_surat?->toDateString() ?? now()->toDateString()" />
                </x-kolom>

                <x-kolom nama="keperluan" label="Keperluan" class="col-span-2">
                    <input id="keperluan" name="keperluan" type="text" maxlength="255" placeholder="Contoh: Persyaratan kerja"
                           value="{{ old('keperluan', $surat->keperluan) }}" class="{{ $input('keperluan') }}">
                </x-kolom>

                <x-kolom nama="keterangan" label="Keterangan tambahan (opsional)" class="col-span-2">
                    <textarea id="keterangan" name="keterangan" rows="4" placeholder="Catatan untuk isi surat"
                              class="w-full rounded-xl bg-surf px-4 py-3 text-[13px] outline-none placeholder:text-muted {{ $errors->has('keterangan') ? 'ring-1 ring-error' : '' }}">{{ old('keterangan', $surat->keterangan) }}</textarea>
                </x-kolom>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="flex h-9 items-center gap-2 rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:bg-blue/90">
                    <x-ikon :nama="$ubah ? 'cek' : 'surat-baru'" :ukuran="16" />
                    {{ $ubah ? 'Simpan Perubahan' : 'Buat Surat' }}
                </button>
                <a href="{{ $ubah ? route('surat.show', $surat) : route('dasbor') }}"
                   class="flex h-9 items-center rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">Batal</a>
            </div>
        </form>

        <aside class="w-[320px] shrink-0 rounded-[18px] bg-surf p-6" aria-label="Data warga">
            <h2 class="flex items-center gap-2 text-[15px] font-bold">
                <x-ikon nama="pengguna" :ukuran="16" class="text-blue" />
                Data Warga
            </h2>
            <p id="panel-kosong" class="mt-4 text-xs text-muted" @if ($terpilih) hidden @endif>Pilih warga untuk menampilkan datanya.</p>
            <dl id="panel-data" class="mt-4 space-y-3" @unless ($terpilih) hidden @endunless>
                @foreach ($baris as $kunci => $label)
                    <div>
                        <dt class="text-[11px] text-muted">{{ $label }}</dt>
                        <dd data-isi="{{ $kunci }}" class="text-[13px] font-medium">{{ $terpilih[$kunci] ?? '' }}</dd>
                    </div>
                @endforeach
            </dl>
        </aside>
    </div>

    <script>
        (() => {
            const cari = document.getElementById('warga_id');
            const nilai = document.getElementById('warga_id_nilai');
            const daftar = document.getElementById('hasil-warga');
            const panelKosong = document.getElementById('panel-kosong');
            const panelData = document.getElementById('panel-data');
            let tunggu, permintaan = 0;

            const tampilkanPanel = (w) => {
                panelKosong.hidden = !!w;
                panelData.hidden = !w;
                if (!w) return;
                panelData.querySelectorAll('[data-isi]').forEach((el) => { el.textContent = w[el.dataset.isi] ?? ''; });
            };

            const tutup = () => { daftar.hidden = true; cari.setAttribute('aria-expanded', 'false'); };

            const pilih = (w) => {
                nilai.value = w.id;
                cari.value = w.nama;
                tampilkanPanel(w);
                tutup();
            };

            const tampilkanHasil = (hasil) => {
                daftar.replaceChildren();
                if (!hasil.length) {
                    const kosong = document.createElement('li');
                    kosong.className = 'px-4 py-2 text-xs text-muted';
                    kosong.textContent = 'Data warga tidak ditemukan';
                    daftar.append(kosong);
                }
                hasil.forEach((w) => {
                    const li = document.createElement('li');
                    li.setAttribute('role', 'option');
                    li.tabIndex = 0;
                    li.className = 'cursor-pointer px-4 py-2 hover:bg-hl focus:bg-hl focus:outline-none';
                    const nama = document.createElement('span');
                    nama.className = 'block text-[13px] font-medium';
                    nama.textContent = w.nama;
                    const nik = document.createElement('span');
                    nik.className = 'block text-[11px] text-muted';
                    nik.textContent = w.nik;
                    li.append(nama, nik);
                    li.addEventListener('click', () => pilih(w));
                    li.addEventListener('keydown', (e) => { if (e.key === 'Enter') pilih(w); });
                    daftar.append(li);
                });
                daftar.hidden = false;
                cari.setAttribute('aria-expanded', 'true');
            };

            cari.addEventListener('input', () => {
                // Mengetik ulang membatalkan warga yang sudah dipilih.
                nilai.value = '';
                tampilkanPanel(null);
                clearTimeout(tunggu);
                const q = cari.value.trim();
                if (q.length < 2) return tutup();
                tunggu = setTimeout(async () => {
                    const saya = ++permintaan;
                    try {
                        const res = await fetch(@js(route('surat.cari-warga')) + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
                        if (res.ok && saya === permintaan) tampilkanHasil(await res.json());
                    } catch (e) { /* jaringan putus: biarkan daftar tertutup */ }
                }, 250);
            });

            document.addEventListener('click', (e) => { if (!daftar.contains(e.target) && e.target !== cari) tutup(); });
            cari.addEventListener('keydown', (e) => { if (e.key === 'Escape') tutup(); });
        })();
    </script>
</x-layout-aplikasi>
