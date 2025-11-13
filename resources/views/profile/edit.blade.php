<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Profil Pengguna') }}
                </h2>
                <p class="text-sm text-gray-500">Kelola pengaturan akun dan pantau skenario bisnis yang telah Anda simpan.</p>
            </div>
            <nav class="flex flex-wrap items-center gap-2 text-sm font-medium text-gray-600">
                <a href="#account-settings" class="px-3 py-1 rounded-full bg-gray-100 hover:bg-gray-200">Pengaturan Akun</a>
                <a href="#saved-scenarios" class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 hover:bg-blue-100">
                    Skenario Saya ({{ $simulations->count() }})
                </a>
            </nav>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section id="account-settings" class="space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </section>

            <section id="saved-scenarios" class="p-6 bg-white shadow sm:rounded-lg">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Skenario Tersimpan</h3>
                        <p class="text-sm text-gray-500">Skenario yang Anda simpan melalui simulator dapat dimuat kembali kapan saja.</p>
                    </div>
                    <a href="{{ route('simulator.index') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Buka Simulator
                    </a>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($simulations as $simulation)
                        <article class="border border-gray-200 rounded-lg p-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h4 class="text-base font-semibold text-gray-800">{{ $simulation->nama_skenario }}</h4>
                                    <p class="text-xs text-gray-500">Disimpan pada {{ $simulation->created_at->translatedFormat('d F Y, H:i') }}</p>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-green-700 bg-green-50 rounded-full">
                                    Harga Jual Rp {{ number_format($simulation->harga_jual, 0, ',', '.') }}
                                </span>
                            </div>

                            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <dt class="text-xs text-gray-500 uppercase tracking-wide">Volume Penjualan</dt>
                                    <dd class="text-sm font-semibold text-gray-800">{{ number_format($simulation->volume_penjualan, 0, ',', '.') }} Unit/Bulan</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 uppercase tracking-wide">Modal Kerja</dt>
                                    <dd class="text-sm font-semibold text-gray-800">Rp {{ number_format($simulation->modal_kerja, 0, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 uppercase tracking-wide">Biaya Tetap</dt>
                                    <dd class="text-sm font-semibold text-gray-800">Rp {{ number_format($simulation->biaya_tetap, 0, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 uppercase tracking-wide">Pertumbuhan & Pajak</dt>
                                    <dd class="text-sm font-semibold text-gray-800">
                                        {{ $simulation->tingkat_pertumbuhan }}% / {{ $simulation->tarif_pajak }}%
                                    </dd>
                                </div>
                            </dl>

                            <p class="mt-3 text-sm text-gray-500">Gunakan tombol <strong>Muat Skenario Tersimpan</strong> pada simulator untuk menjalankan ulang data ini.</p>
                        </article>
                    @empty
                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-center text-gray-500 border border-dashed border-gray-200 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <p class="text-sm">Belum ada skenario yang disimpan. Jalankan simulasi lalu tekan "Simpan Skenario" untuk mulai mengarsipkan data.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
