/*
 * Komponen formulir kustom: pilihan (pengganti <select>) dan kalender
 * (pengganti <input type="date">). Markup awal dibuat Blade
 * (components/pilihan, components/tanggal); berkas ini menambah perilakunya.
 * Nilai tetap dikirim lewat <input type="hidden">, jadi server tidak berubah.
 */

const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const BULAN_PENDEK = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const HARI = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

const pad = (n) => String(n).padStart(2, '0');
const iso = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`; // m berbasis 0
const angka = (t) => t.y * 10000 + t.m * 100 + t.d;

function urai(teks) {
    const cocok = /^(\d{4})-(\d{2})-(\d{2})$/.exec(teks || '');
    if (!cocok) return null;
    const [y, m, d] = [Number(cocok[1]), Number(cocok[2]) - 1, Number(cocok[3])];
    const tgl = new Date(y, m, d);
    return tgl.getFullYear() === y && tgl.getMonth() === m && tgl.getDate() === d ? { y, m, d } : null;
}

const svg = (isi) =>
    `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${isi}</svg>`;
const IKON_KIRI = svg('<path d="m15 18-6-6 6-6"/>');
const IKON_KANAN = svg('<path d="m9 18 6-6-6-6"/>');

function el(tag, kelas = '', teks = null, atribut = {}) {
    const e = document.createElement(tag);
    if (kelas) e.className = kelas;
    if (teks !== null) e.textContent = teks;
    for (const [k, v] of Object.entries(atribut)) e.setAttribute(k, v);
    return e;
}

/** Satu komponen terbuka pada satu waktu. */
function pasangPenutup(root, tutup) {
    document.addEventListener('click', (e) => { if (!root.contains(e.target)) tutup(); });
    document.addEventListener('komponen-buka', (e) => { if (e.detail !== root) tutup(); });
}

/* ---------- Pilihan (dropdown) ---------- */

function pilihan(root) {
    const nilai = root.querySelector('input[type="hidden"]');
    const tombol = root.querySelector('[role="combobox"]');
    const label = root.querySelector('[data-label]');
    const daftar = root.querySelector('[role="listbox"]');
    const opsi = [...daftar.querySelectorAll('[role="option"]')];
    let aktif = -1;

    const tandaiAktif = () => {
        opsi.forEach((o, i) => o.toggleAttribute('data-aktif', i === aktif));
        opsi[aktif]?.scrollIntoView({ block: 'nearest' });
    };

    const tutup = () => {
        daftar.hidden = true;
        tombol.setAttribute('aria-expanded', 'false');
    };

    const buka = () => {
        document.dispatchEvent(new CustomEvent('komponen-buka', { detail: root }));
        daftar.hidden = false;
        tombol.setAttribute('aria-expanded', 'true');
        aktif = Math.max(0, opsi.findIndex((o) => o.dataset.nilai === nilai.value));
        tandaiAktif();
    };

    const pilih = (o) => {
        nilai.value = o.dataset.nilai;
        label.textContent = o.dataset.label;
        label.classList.toggle('text-muted', o.dataset.nilai === '');
        opsi.forEach((x) => x.setAttribute('aria-selected', String(x === o)));
        nilai.dispatchEvent(new Event('change', { bubbles: true }));
        tutup();
        tombol.focus();
    };

    tombol.addEventListener('click', () => (daftar.hidden ? buka() : tutup()));

    tombol.addEventListener('keydown', (e) => {
        const terbuka = !daftar.hidden;
        if (e.key === 'Escape' && terbuka) { e.preventDefault(); tutup(); return; }
        if (['ArrowDown', 'ArrowUp'].includes(e.key)) {
            e.preventDefault();
            if (!terbuka) return buka();
            aktif = Math.min(opsi.length - 1, Math.max(0, aktif + (e.key === 'ArrowDown' ? 1 : -1)));
            tandaiAktif();
        } else if (e.key === 'Home' && terbuka) {
            e.preventDefault(); aktif = 0; tandaiAktif();
        } else if (e.key === 'End' && terbuka) {
            e.preventDefault(); aktif = opsi.length - 1; tandaiAktif();
        } else if ((e.key === 'Enter' || e.key === ' ') && terbuka) {
            e.preventDefault();
            if (opsi[aktif]) pilih(opsi[aktif]);
        }
    });

    opsi.forEach((o, i) => {
        o.addEventListener('click', () => pilih(o));
        o.addEventListener('mousemove', () => { aktif = i; tandaiAktif(); });
    });

    pasangPenutup(root, tutup);
}

/* ---------- Kalender ---------- */

function kalender(root) {
    const nilai = root.querySelector('input[type="hidden"]');
    const teks = root.querySelector('[data-teks]');
    const buka = root.querySelector('[data-buka]');
    const pesan = root.querySelector('[data-pesan]');
    const panel = root.querySelector('[data-panel]');
    const maks = urai(root.dataset.maks);
    const min = urai(root.dataset.min);
    const tanpaHariIni = root.hasAttribute('data-tanpa-hari-ini');

    const sekarang = new Date();
    const hariIni = { y: sekarang.getFullYear(), m: sekarang.getMonth(), d: sekarang.getDate() };

    let mode = 'hari'; // hari | bulan | tahun
    let tampil = { y: hariIni.y, m: hariIni.m };

    const diluar = (t) => (maks && angka(t) > angka(maks)) || (min && angka(t) < angka(min));
    const bulanDiluar = (y, m) => (maks && (y > maks.y || (y === maks.y && m > maks.m))) || (min && (y < min.y || (y === min.y && m < min.m)));
    const tahunDiluar = (y) => (maks && y > maks.y) || (min && y < min.y);
    const teksTanggal = (t) => `${pad(t.d)}-${pad(t.m + 1)}-${t.y}`;

    const tampilkanPesan = (isi) => {
        pesan.hidden = !isi;
        pesan.querySelector('span').textContent = isi || '';
    };

    const tulisNilai = (iso) => {
        if (nilai.value === iso) return;
        nilai.value = iso;
        nilai.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const sinkronTeks = () => {
        const t = urai(nilai.value);
        teks.value = t ? teksTanggal(t) : '';
    };

    const tutup = () => {
        panel.hidden = true;
        buka.setAttribute('aria-expanded', 'false');
    };

    const pilih = (t) => {
        tulisNilai(iso(t.y, t.m, t.d));
        sinkronTeks();
        tampilkanPesan('');
        tutup();
        teks.focus();
    };

    /* --- mengetik langsung: 31051990, 31-05-1990, atau 31/05/1990 --- */

    const periksaKetikan = ({ saatKeluar = false } = {}) => {
        const digit = teks.value.replace(/\D/g, '').slice(0, 8);
        const ditata = digit.slice(0, 2) + (digit.length > 2 ? '-' + digit.slice(2, 4) : '') + (digit.length > 4 ? '-' + digit.slice(4) : '');
        if (teks.value !== ditata) teks.value = ditata;

        if (digit.length === 0) {
            tulisNilai('');
            tampilkanPesan('');
            return;
        }
        if (digit.length < 8) {
            tulisNilai('');
            tampilkanPesan(saatKeluar ? 'Tulis lengkap dengan format dd-mm-yyyy.' : '');
            return;
        }

        const t = urai(`${digit.slice(4)}-${digit.slice(2, 4)}-${digit.slice(0, 2)}`);
        if (!t || t.y < 1000) {
            tulisNilai('');
            tampilkanPesan('Tanggal tidak valid.');
        } else if (maks && angka(t) > angka(maks)) {
            tulisNilai('');
            tampilkanPesan(maks.y === hariIni.y && maks.m === hariIni.m && maks.d === hariIni.d ? 'Tanggal tidak boleh melewati hari ini.' : `Tanggal tidak boleh setelah ${teksTanggal(maks)}.`);
        } else if (min && angka(t) < angka(min)) {
            tulisNilai('');
            tampilkanPesan(`Tanggal tidak boleh sebelum ${teksTanggal(min)}.`);
        } else {
            tulisNilai(iso(t.y, t.m, t.d));
            tampilkanPesan('');
            if (!panel.hidden) {
                tampil = { y: t.y, m: t.m };
                render(iso(t.y, t.m, t.d));
            }
        }
    };

    /* --- tampilan kalender --- */

    const tombolNav = (ikon, namaAria, aksi, nonaktif) => {
        const b = el('button', 'flex size-8 items-center justify-center rounded-full text-muted hover:bg-hl hover:text-blue disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-muted', null, { type: 'button', 'aria-label': namaAria });
        b.innerHTML = ikon;
        b.disabled = !!nonaktif;
        b.addEventListener('click', aksi);
        return b;
    };

    const geser = (arah) => {
        if (mode === 'hari') {
            const t = new Date(tampil.y, tampil.m + arah, 1);
            tampil = { y: t.getFullYear(), m: t.getMonth() };
        } else {
            tampil.y += mode === 'tahun' ? arah * 12 : arah;
        }
        render();
    };

    function grid(kolom) {
        return el('div', `grid ${kolom} gap-1`);
    }

    function render(fokus = null) {
        panel.replaceChildren();

        const awalTahun = Math.floor(tampil.y / 12) * 12;
        const teksJudul = mode === 'hari' ? `${BULAN[tampil.m]} ${tampil.y}` : mode === 'bulan' ? String(tampil.y) : `${awalTahun} – ${awalTahun + 11}`;

        // Panah dimatikan bila halaman berikut/sebelumnya seluruhnya di luar batas tanggal.
        const indeks = tampil.y * 12 + tampil.m;
        const tanpaSebelum = mode === 'hari' ? min && indeks <= min.y * 12 + min.m : mode === 'bulan' ? min && tampil.y <= min.y : min && awalTahun <= min.y;
        const tanpaBerikut = mode === 'hari' ? maks && indeks >= maks.y * 12 + maks.m : mode === 'bulan' ? maks && tampil.y >= maks.y : maks && awalTahun + 11 >= maks.y;

        const kepala = el('div', 'mb-2 flex items-center justify-between');
        const judul = el('button', 'rounded-full px-3 py-1 text-[13px] font-bold hover:bg-hl', teksJudul, { type: 'button', 'aria-live': 'polite' });
        judul.addEventListener('click', () => { mode = mode === 'hari' ? 'tahun' : mode === 'bulan' ? 'tahun' : 'hari'; render(); });
        kepala.append(
            tombolNav(IKON_KIRI, 'Sebelumnya', () => geser(-1), tanpaSebelum),
            judul,
            tombolNav(IKON_KANAN, 'Berikutnya', () => geser(1), tanpaBerikut),
        );
        panel.append(kepala);

        if (mode === 'tahun') {
            const g = grid('grid-cols-4');
            for (let y = awalTahun; y < awalTahun + 12; y++) {
                const b = el('button', 'h-9 rounded-full text-[13px] ' + (tahunDiluar(y) ? 'cursor-not-allowed text-muted/40' : y === tampil.y ? 'bg-blue font-bold text-white' : 'hover:bg-hl'), String(y), { type: 'button' });
                b.disabled = !!tahunDiluar(y);
                b.addEventListener('click', () => { if (tahunDiluar(y)) return; tampil.y = y; mode = 'bulan'; render(); });
                g.append(b);
            }
            panel.append(g);
        } else if (mode === 'bulan') {
            const g = grid('grid-cols-3');
            BULAN.forEach((nama, m) => {
                const b = el('button', 'h-9 rounded-full text-[13px] ' + (bulanDiluar(tampil.y, m) ? 'cursor-not-allowed text-muted/40' : m === tampil.m ? 'bg-blue font-bold text-white' : 'hover:bg-hl'), nama, { type: 'button' });
                b.disabled = !!bulanDiluar(tampil.y, m);
                b.addEventListener('click', () => { if (bulanDiluar(tampil.y, m)) return; tampil.m = m; mode = 'hari'; render(); });
                g.append(b);
            });
            panel.append(g);
        } else {
            const nama = el('div', 'mb-1 grid grid-cols-7 text-center text-[11px] font-medium text-muted');
            HARI.forEach((h) => nama.append(el('span', 'py-1', h)));
            panel.append(nama);

            const g = grid('grid-cols-7');
            const terpilih = urai(nilai.value);
            const kosongAwal = (new Date(tampil.y, tampil.m, 1).getDay() + 6) % 7; // pekan mulai Senin
            const jumlah = new Date(tampil.y, tampil.m + 1, 0).getDate();
            for (let i = 0; i < kosongAwal; i++) g.append(el('span'));

            for (let d = 1; d <= jumlah; d++) {
                const t = { y: tampil.y, m: tampil.m, d };
                const nonaktif = !!diluar(t);
                const dipilih = terpilih && angka(terpilih) === angka(t);
                const adalahHariIni = angka(hariIni) === angka(t);
                const kelas = nonaktif ? 'cursor-not-allowed text-muted/40'
                    : dipilih ? 'bg-blue font-bold text-white'
                        : adalahHariIni ? 'font-bold text-blue ring-1 ring-blue hover:bg-hl' : 'hover:bg-hl';
                const b = el('button', `flex size-9 items-center justify-center rounded-full text-[13px] ${kelas}`, String(d), {
                    type: 'button',
                    'data-tgl': iso(t.y, t.m, t.d),
                    'aria-label': `${d} ${BULAN[t.m]} ${t.y}`,
                    'aria-pressed': String(!!dipilih),
                });
                b.disabled = nonaktif;
                b.addEventListener('click', () => { if (!nonaktif) pilih(t); });
                b.addEventListener('keydown', (e) => {
                    const langkah = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key];
                    if (langkah === undefined) return;
                    e.preventDefault();
                    const tujuan = new Date(t.y, t.m, t.d + langkah);
                    const tt = { y: tujuan.getFullYear(), m: tujuan.getMonth(), d: tujuan.getDate() };
                    if (diluar(tt)) return;
                    tampil = { y: tt.y, m: tt.m };
                    render(iso(tt.y, tt.m, tt.d));
                });
                g.append(b);
            }
            panel.append(g);

            // "Hari ini" tidak berguna untuk tanggal lahir, jadi disembunyikan di sana.
            if (!tanpaHariIni) {
                const kaki = el('div', 'mt-2 flex justify-end border-t border-line pt-2');
                const hariIniBtn = el('button', 'rounded-full px-3 py-1 text-xs font-medium text-blue hover:bg-hl disabled:cursor-not-allowed disabled:text-muted/40 disabled:hover:bg-transparent', 'Hari ini', { type: 'button' });
                hariIniBtn.disabled = !!diluar(hariIni);
                hariIniBtn.addEventListener('click', () => { if (!diluar(hariIni)) pilih(hariIni); });
                kaki.append(hariIniBtn);
                panel.append(kaki);
            }
        }

        if (fokus) panel.querySelector(`[data-tgl="${fokus}"]`)?.focus();
    }

    const bukaPanel = () => {
        document.dispatchEvent(new CustomEvent('komponen-buka', { detail: root }));
        const terpilih = urai(nilai.value);
        // Tanpa nilai: mulai dari bulan ini, atau bulan terakhir yang diizinkan.
        const acuan = terpilih || (maks && angka(hariIni) > angka(maks) ? maks : hariIni);
        tampil = { y: acuan.y, m: acuan.m };
        mode = 'hari';
        panel.hidden = false;
        buka.setAttribute('aria-expanded', 'true');
        render(terpilih ? iso(terpilih.y, terpilih.m, terpilih.d) : null);
    };

    buka.addEventListener('click', () => (panel.hidden ? bukaPanel() : tutup()));
    teks.addEventListener('input', () => periksaKetikan());
    teks.addEventListener('blur', () => periksaKetikan({ saatKeluar: true }));
    teks.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown' && panel.hidden) { e.preventDefault(); bukaPanel(); }
    });
    root.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.hidden) { e.stopPropagation(); tutup(); teks.focus(); }
    });

    sinkronTeks();
    pasangPenutup(root, tutup);
}

/* ---------- Modal konfirmasi (pengganti dialog konfirmasi bawaan peramban) ---------- */

function modalKonfirmasi() {
    const dlg = document.getElementById('modal-konfirmasi');
    if (!dlg) return;

    const judul = dlg.querySelector('[data-modal-judul]');
    const isi = dlg.querySelector('[data-modal-isi]');
    const ok = dlg.querySelector('[data-modal-ok]');
    const batal = dlg.querySelector('[data-modal-batal]');
    let tertunda = null;

    const tutup = () => {
        tertunda = null;
        if (dlg.open) dlg.close();
    };

    // Form ber-atribut data-konfirmasi ditahan dulu; dikirim setelah pengguna menekan tombol konfirmasi.
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.konfirmasi || form.dataset.terkonfirmasi) return;

        e.preventDefault();
        tertunda = form;
        judul.textContent = form.dataset.konfirmasiJudul || 'Konfirmasi';
        isi.textContent = form.dataset.konfirmasi;
        ok.textContent = form.dataset.konfirmasiTombol || 'Lanjutkan';

        const bahaya = form.dataset.konfirmasiGaya === 'bahaya';
        ok.classList.toggle('bg-error', bahaya);
        ok.classList.toggle('bg-blue', !bahaya);

        dlg.showModal();
        batal.focus(); // fokus awal di tombol yang aman
    });

    ok.addEventListener('click', () => {
        const form = tertunda;
        tutup();
        if (form) {
            form.dataset.terkonfirmasi = '1';
            form.requestSubmit();
        }
    });
    batal.addEventListener('click', tutup);
    dlg.addEventListener('click', (e) => { if (e.target === dlg) tutup(); }); // klik latar gelap
    dlg.addEventListener('close', () => { tertunda = null; });
}

/* ---------- Tombol mata pada kolom kata sandi ---------- */

function tombolSandi(tombol) {
    const input = document.getElementById(tombol.dataset.toggleSandi);
    const lihat = tombol.querySelector('[data-ikon-lihat]');
    const sembunyi = tombol.querySelector('[data-ikon-sembunyi]');

    tombol.addEventListener('click', () => {
        const tampil = input.type === 'password';
        input.type = tampil ? 'text' : 'password';
        tombol.setAttribute('aria-pressed', String(tampil));
        tombol.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        lihat.classList.toggle('hidden', tampil);
        sembunyi.classList.toggle('hidden', !tampil);
        input.focus();
    });
}

/* ---------- Notifikasi status (toast) ---------- */

function toast(el) {
    const hapus = () => {
        el.classList.add('opacity-0', 'translate-x-4');
        setTimeout(() => el.remove(), 200);
    };
    el.querySelector('[data-toast-tutup]')?.addEventListener('click', hapus);

    const ms = Number(el.dataset.otomatis);
    if (ms > 0) {
        let waktu = setTimeout(hapus, ms);
        // Berhenti saat kursor di atasnya agar sempat dibaca.
        el.addEventListener('mouseenter', () => clearTimeout(waktu));
        el.addEventListener('mouseleave', () => { waktu = setTimeout(hapus, 2000); });
    }
}

function mulai() {
    document.querySelectorAll('[data-pilihan]').forEach(pilihan);
    document.querySelectorAll('[data-kalender]').forEach(kalender);
    document.querySelectorAll('[data-toggle-sandi]').forEach(tombolSandi);
    document.querySelectorAll('[data-toast]').forEach(toast);
    modalKonfirmasi();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mulai);
else mulai();
