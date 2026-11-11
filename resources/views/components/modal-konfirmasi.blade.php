{{-- Dialog konfirmasi bersama (keluar, hapus). Dipicu form ber-atribut data-konfirmasi; perilaku di resources/js/komponen.js --}}
<dialog id="modal-konfirmasi" aria-labelledby="modal-judul" aria-describedby="modal-isi"
        class="m-auto w-[420px] max-w-[calc(100vw-2rem)] rounded-[18px] border border-line bg-card p-6 text-ink shadow-xl backdrop:bg-ink/40 print:hidden">
    <h2 id="modal-judul" data-modal-judul class="text-[15px] font-bold"></h2>
    <p id="modal-isi" data-modal-isi class="mt-2 text-[13px] text-muted"></p>
    <div class="mt-5 flex justify-end gap-3">
        <button type="button" data-modal-batal
                class="flex h-9 items-center rounded-full bg-surf px-5 text-[13px] font-medium hover:bg-line">Batal</button>
        <button type="button" data-modal-ok
                class="flex h-9 items-center rounded-full bg-blue px-5 text-[13px] font-medium text-white hover:opacity-90"></button>
    </div>
</dialog>
