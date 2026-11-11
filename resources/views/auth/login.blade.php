<x-layout-dasar judul="Masuk">
    <main class="flex min-h-screen items-center justify-center bg-side px-4">
        <div class="w-full max-w-[440px] rounded-[28px] border border-line bg-card px-10 py-10 shadow-[0_20px_50px_-20px_rgba(37,99,201,0.25)]">
            <div class="flex flex-col items-center text-center">
                <x-logo ukuran="besar" />
                <h1 class="mt-3 text-[26px] font-bold text-blue">Lentera Desa</h1>
                <p class="mt-1 text-[13px] text-muted">Layanan Terpadu Administrasi Desa</p>
            </div>

            <form action="{{ route('login.proses') }}" method="post" class="mt-6" novalidate>
                @csrf

                <div>
                    <label for="username" class="text-xs text-muted">Username</label>
                    <div class="relative mt-1.5">
                        <x-ikon nama="pengguna" :ukuran="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-muted" />
                        <input id="username" name="username" type="text" value="{{ old('username') }}"
                               autocomplete="username" autofocus required
                               @class(['h-[38px] w-full rounded-full bg-surf pl-11 pr-4 text-[13px] outline-none', 'ring-1 ring-error' => $errors->has('username')])>
                    </div>
                    @error('username')
                        <p class="mt-1.5 flex items-center gap-1.5 text-[11px] text-error">
                            <x-ikon nama="galat" :ukuran="14" />{{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label for="password" class="text-xs text-muted">Kata sandi</label>
                    <div class="mt-1.5">
                        <x-sandi nama="password" required />
                    </div>
                    @error('password')
                        <p class="mt-1.5 flex items-center gap-1.5 text-[11px] text-error">
                            <x-ikon nama="galat" :ukuran="14" />{{ $message }}
                        </p>
                    @enderror
                </div>

                <button type="submit"
                        class="mt-5 h-9 w-full rounded-full bg-blue text-[13px] font-medium text-white hover:bg-blue/90">
                    Masuk
                </button>
            </form>

            <p class="mt-5 text-center text-[11px] text-muted">Sistem hanya dapat diakses melalui jaringan lokal desa</p>
        </div>
    </main>
</x-layout-dasar>
