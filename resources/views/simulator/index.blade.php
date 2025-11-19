<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Simulator Bisnis - Mode Batch</title>
    
    @vite('resources/css/app.css')

    <script>
        // Gunakan nilai yang dikirim dari controller (Auth::check())
        window.isLoggedIn = @json($isLoggedIn ?? false); 
        // Kirim route login/register jika user belum login
        window.loginRoute = @json($loginRoute ?? '#');
        window.registerRoute = @json($registerRoute ?? '#');
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* Gaya dasar untuk tab */
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        /* Tambahkan gaya untuk penanganan error */
        .input-group { position: relative; }
        .error-message { position: relative; margin-top: 0.25rem; }
        /* Gaya untuk Modal (BARU) */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.5); z-index: 1000;
            display: flex; align-items: center; justify-content: center;
        }
    </style>
    @php($appTitle = 'Simulator Proyeksi Bisnis Realistis')
</head>
<body class="bg-slate-50 min-h-screen">

    <header class="bg-white shadow-md border-b border-slate-100">
        <div class="w-full px-4 sm:px-6 lg:px-12 py-5 flex flex-col gap-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="text-center lg:text-left">
                    <p class="text-xs font-semibold uppercase tracking-widest text-blue-500">{{ $appTitle }}</p>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Dashboard Simulasi &amp; Proyeksi</h1>
                    <p class="text-sm text-gray-500">Susun asumsi bisnis Anda dan dapatkan ringkasan instan.</p>
                </div>
                <nav class="flex flex-wrap items-center justify-center lg:justify-end gap-3 text-sm text-gray-700">
                    @auth
                    <a href="{{ route('profile.edit') }}" class="font-medium text-blue-600 hover:underline">
                        Halo, {{ Auth::user()->name }}!
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-full border border-red-200 text-red-600 hover:bg-red-50 transition">Logout</button>
                    </form>
                    @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-full border border-blue-200 text-blue-600 hover:bg-blue-50 transition">Login</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-full border border-emerald-200 text-emerald-600 hover:bg-emerald-50 transition">Register</a>
                    @endauth
                </nav>
            </div>
        </div>
    </header>

    <main class="w-full px-3 sm:px-6 lg:px-12 py-8">
        <div class="w-full bg-white shadow-2xl rounded-2xl border border-slate-100 p-5 sm:p-8 space-y-6">
            <div class="space-y-4 mb-2 pb-4 border-b border-slate-100">
                <div class="flex flex-col gap-3 w-full">
                    @auth
                    <button type="button" id="saveScenarioButton" data-save-url="{{ route('simulator.save') }}" class="bg-blue-600 text-white px-5 py-3 rounded-xl font-semibold hover:bg-blue-700 disabled:opacity-40 w-full sm:w-auto" disabled>
                        Simpan Skenario (A)
                    </button>
                    <button type="button" id="loadScenarioButton" data-list-url="{{ route('simulator.list') }}" class="bg-gray-700 text-white px-5 py-3 rounded-xl font-semibold hover:bg-gray-800 w-full sm:w-auto">
                        Muat Skenario Tersimpan
                    </button>
                    @else
                    <div id="authRequiredMessage" class="text-yellow-700 border border-yellow-200 bg-yellow-50 px-4 py-3 rounded-xl text-center">
                        <a href="{{ route('login') }}" class="font-bold underline">Login</a> atau Daftar untuk menggunakan fitur Simpan &amp; Muat.
                    </div>
                    @endauth
                </div>
                <div class="text-center sm:text-left">
                    <h2 class="text-2xl font-bold text-gray-800">{{ $appTitle }}</h2>
                    <p class="text-sm text-gray-500">Ikuti 3 langkah sederhana untuk mendapatkan proyeksi terperinci.</p>
                </div>
            </div>


        <form id="simulatorForm" class="space-y-6" data-calculate-url="{{ route('simulator.calculate') }}">
            @csrf
            
            <div class="flex border-b border-gray-200">
                <button type="button" data-step="1" class="tab-button p-3 text-lg font-medium text-blue-600 border-b-2 border-blue-600 hover:text-blue-800 focus:outline-none">
                    Langkah 1: Profil & Pendapatan
                </button>
                <button type="button" data-step="2" class="tab-button p-3 text-lg font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent focus:outline-none">
                    Langkah 2: Modal & Investasi
                </button>
                <button type="button" data-step="3" class="tab-button p-3 text-lg font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent focus:outline-none">
                    Langkah 3: Biaya, Pajak & Inflasi
                </button>
            </div>
            
            <div id="step-1" class="tab-content active space-y-4">
                <h2 class="text-2xl font-semibold mb-4 text-blue-600">Langkah 1: Profil Usaha & Pendapatan</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2 input-group">
                        <div class="flex items-center justify-between gap-2">
                            <label for="harga_jual" class="text-sm font-medium text-gray-700">Harga Jual per Unit (Rp)</label>
                            <span class="text-xs text-gray-500">
                                <abbr title="Harga produk atau jasa Anda per unit.">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </abbr>
                            </span>
                        </div>
                        <input type="number" name="harga_jual" id="harga_jual" value="100000" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <div class="error-message text-red-500 text-xs" id="error-harga_jual"></div>
                    </div>
                    <div class="flex flex-col gap-2 input-group">
                        <div class="flex items-center justify-between gap-2">
                            <label for="anggaran_marketing" class="text-sm font-medium text-gray-700">Anggaran Marketing Bulanan (Rp)</label>
                            <span class="text-xs text-gray-500">
                                <abbr title="Total budget akuisisi pelanggan per bulan.">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </abbr>
                            </span>
                        </div>
                        <input type="number" name="anggaran_marketing" id="anggaran_marketing" value="20000000" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <div class="error-message text-red-500 text-xs" id="error-anggaran_marketing"></div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col gap-2 input-group">
                        <div class="flex items-center justify-between gap-2">
                            <label for="biaya_per_lead" class="text-sm font-medium text-gray-700">Biaya per Prospek (CPL)</label>
                            <span class="text-xs text-gray-500">
                                <abbr title="Biaya rata-rata untuk mendapatkan satu prospek.">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </abbr>
                            </span>
                        </div>
                        <input type="number" name="biaya_per_lead" id="biaya_per_lead" value="20000" min="1" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <div class="error-message text-red-500 text-xs" id="error-biaya_per_lead"></div>
                    </div>
                    <div class="flex flex-col gap-2 input-group">
                        <div class="flex items-center justify-between gap-2">
                            <label for="tingkat_konversi" class="text-sm font-medium text-gray-700">Tingkat Konversi Penjualan (%)</label>
                            <span class="text-xs text-gray-500">
                                <abbr title="Persentase prospek yang berhasil menjadi pelanggan.">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </abbr>
                            </span>
                        </div>
                        <input type="number" name="tingkat_konversi" id="tingkat_konversi" value="3" min="0" max="100" step="0.1" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <div class="error-message text-red-500 text-xs" id="error-tingkat_konversi"></div>
                    </div>
                    <div class="flex flex-col gap-2 input-group">
                        <div class="flex items-center justify-between gap-2">
                            <label for="kapasitas_bulanan" class="text-sm font-medium text-gray-700">Kapasitas Penjualan/Bulan (Unit)</label>
                            <span class="text-xs text-gray-500">
                                <abbr title="Jumlah maksimum unit yang bisa diproduksi atau dilayani per bulan.">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </abbr>
                            </span>
                        </div>
                        <input type="number" name="kapasitas_bulanan" id="kapasitas_bulanan" value="1200" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <div class="error-message text-red-500 text-xs" id="error-kapasitas_bulanan"></div>
                    </div>
                </div>
                <div class="pt-4 border-t input-group">
                    <label for="kenaikan_harga_jual_tahunan" class="block text-sm font-medium text-gray-700">Penyesuaian Harga Jual Tahunan (%)</label>
                    <input type="range" name="kenaikan_harga_jual_tahunan" id="kenaikan_harga_jual_tahunan" min="0" max="30" step="0.5" value="4" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-lg">
                    <div class="text-center font-bold text-lg" id="kenaikan_harga_val">4%</div>
                    <div class="error-message text-red-500 text-xs mt-1" id="error-kenaikan_harga_jual_tahunan"></div>
                </div>
                <div class="pt-4 border-t input-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Faktor Musiman per Bulan (%)</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach (['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'] as $index => $label)
                        <div class="flex flex-col gap-1">
                            <label for="faktor_musim_{{ $index + 1 }}" class="text-xs font-semibold text-gray-600">{{ $label }}</label>
                            <input type="number" name="faktor_musim_{{ $index + 1 }}" id="faktor_musim_{{ $index + 1 }}" value="100" min="0" max="300" step="1" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                            <div class="error-message text-red-500 text-xs" id="error-faktor_musim_{{ $index + 1 }}"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-col gap-2 input-group">
                    <label for="durasi_proyeksi_tahun" class="text-sm font-medium text-gray-700">Durasi Proyeksi (Tahun)</label>
                    <input type="number" name="durasi_proyeksi_tahun" id="durasi_proyeksi_tahun" value="3" min="1" max="5" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <div class="error-message text-red-500 text-xs" id="error-durasi_proyeksi_tahun"></div>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" data-next="2" class="step-nav-button bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Lanjut ke Langkah 2 &rarr;
                    </button>
                </div>
            </div>

            <div id="step-2" class="tab-content space-y-4">
                <h2 class="text-2xl font-semibold mb-4 text-blue-600">Langkah 2: Biaya Modal & Investasi</h2>
                <div class="flex flex-col gap-2 input-group">
                    <div class="flex items-center justify-between gap-2">
                        <label for="capex" class="text-sm font-medium text-gray-700">Biaya Pembelian Aset Jangka Panjang (CAPEX) (Rp)</label>
                        <span class="text-xs text-gray-500">
                            <abbr title="Biaya Pembelian Aset Jangka Panjang (Misalnya: Mesin, Peralatan, Gedung)">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </abbr>
                        </span>
                    </div>
                    <input type="number" name="capex" id="capex" value="50000000" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <div class="error-message text-red-500 text-xs" id="error-capex"></div>
                </div>
                <div class="flex flex-col gap-2 input-group">
                    <label for="modal_kerja" class="text-sm font-medium text-gray-700">Modal Kerja Awal (Kas) (Rp)</label>
                    <input type="number" name="modal_kerja" id="modal_kerja" value="10000000" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <div class="error-message text-red-500 text-xs" id="error-modal_kerja"></div>
                </div>
                <div class="flex flex-col gap-2 input-group">
                    <label for="modal_disetor_pemilik" class="text-sm font-medium text-gray-700">Modal Disetor Pemilik (Rp)</label>
                    <input type="number" name="modal_disetor_pemilik" id="modal_disetor_pemilik" value="25000000" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <div class="error-message text-red-500 text-xs" id="error-modal_disetor_pemilik"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2 input-group">
                        <label for="hari_piutang" class="text-sm font-medium text-gray-700">Hari Piutang (DSO)</label>
                        <input type="number" name="hari_piutang" id="hari_piutang" value="30" min="0" max="180" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <div class="error-message text-red-500 text-xs" id="error-hari_piutang"></div>
                    </div>
                    <div class="flex flex-col gap-2 input-group">
                        <label for="hari_utang_usaha" class="text-sm font-medium text-gray-700">Hari Utang Usaha (DPO)</label>
                        <input type="number" name="hari_utang_usaha" id="hari_utang_usaha" value="45" min="0" max="180" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <div class="error-message text-red-500 text-xs" id="error-hari_utang_usaha"></div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col gap-2 input-group">
                        <label for="jumlah_pinjaman" class="text-sm font-medium text-gray-700">Pinjaman Bank (Rp)</label>
                        <input type="number" name="jumlah_pinjaman" id="jumlah_pinjaman" value="20000000" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <div class="error-message text-red-500 text-xs" id="error-jumlah_pinjaman"></div>
                    </div>
                    <div class="flex flex-col gap-2 input-group">
                        <label for="bunga_pinjaman_tahunan" class="text-sm font-medium text-gray-700">Bunga Pinjaman Tahunan (%)</label>
                        <input type="number" name="bunga_pinjaman_tahunan" id="bunga_pinjaman_tahunan" value="12" min="0" max="100" step="0.1" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <div class="error-message text-red-500 text-xs" id="error-bunga_pinjaman_tahunan"></div>
                    </div>
                    <div class="flex flex-col gap-2 input-group">
                        <label for="tenor_pinjaman_bulan" class="text-sm font-medium text-gray-700">Tenor Pinjaman (Bulan)</label>
                        <input type="number" name="tenor_pinjaman_bulan" id="tenor_pinjaman_bulan" value="36" min="0" max="600" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <div class="error-message text-red-500 text-xs" id="error-tenor_pinjaman_bulan"></div>
                    </div>
                </div>
                <div class="flex flex-col gap-2 input-group">
                    <label for="masa_manfaat_aset_tahun" class="text-sm font-medium text-gray-700">Masa Manfaat Aset (Tahun)</label>
                    <input type="number" name="masa_manfaat_aset_tahun" id="masa_manfaat_aset_tahun" value="5" min="1" max="30" step="0.5" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <div class="error-message text-red-500 text-xs" id="error-masa_manfaat_aset_tahun"></div>
                </div>
                <div class="flex justify-between pt-4">
                    <button type="button" data-prev="1" class="step-nav-button bg-gray-400 text-white px-4 py-2 rounded-md hover:bg-gray-500">
                        &larr; Kembali
                    </button>
                    <button type="button" data-next="3" class="step-nav-button bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Lanjut ke Langkah 3 &rarr;
                    </button>
                </div>
            </div>

            <div id="step-3" class="tab-content space-y-4">
                <h2 class="text-2xl font-semibold mb-4 text-blue-600">Langkah 3: Biaya Operasional, Pajak & Inflasi (Bulanan)</h2>
                <div class="flex flex-col gap-2 input-group">
                    <div class="flex items-center justify-between gap-2">
                        <label for="cogs" class="text-sm font-medium text-gray-700">Biaya Langsung Produk (COGS) per Unit (Rp)</label>
                        <span class="text-xs text-gray-500">
                            <abbr title="Cost of Goods Sold (COGS). Biaya langsung bahan baku, tenaga kerja, dll., per unit.">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </abbr>
                        </span>
                    </div>
                    <input type="number" name="cogs" id="cogs" value="30000" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <div class="error-message text-red-500 text-xs" id="error-cogs"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2 input-group">
                        <label for="jumlah_karyawan" class="text-sm font-medium text-gray-700">Jumlah Karyawan</label>
                        <input type="number" name="jumlah_karyawan" id="jumlah_karyawan" value="8" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <div class="error-message text-red-500 text-xs" id="error-jumlah_karyawan"></div>
                    </div>
                    <div class="flex flex-col gap-2 input-group">
                        <label for="gaji_per_karyawan" class="text-sm font-medium text-gray-700">Rata-rata Gaji per Karyawan (Rp/Bulan)</label>
                        <input type="number" name="gaji_per_karyawan" id="gaji_per_karyawan" value="3500000" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <div class="error-message text-red-500 text-xs" id="error-gaji_per_karyawan"></div>
                    </div>
                </div>

                <div class="flex flex-col gap-2 pt-4 border-t input-group">
                    <div class="flex items-center justify-between gap-2">
                        <label for="tarif_pajak" class="text-sm font-medium text-gray-700">Tarif Pajak Penghasilan (%)</label>
                        <span class="text-xs text-gray-500">
                            <abbr title="Asumsi tarif PPh yang berlaku untuk laba sebelum pajak Anda.">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </abbr>
                        </span>
                    </div>
                    <input type="number" name="tarif_pajak" id="tarif_pajak" value="10" min="0" max="100" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <div class="error-message text-red-500 text-xs" id="error-tarif_pajak"></div>
                </div>
                
                <div class="pt-4 border-t input-group">
                    <label for="inflasi_cogs_tahunan" class="block text-sm font-medium text-gray-700">Inflasi COGS Tahunan (%)</label>
                    <input type="range" name="inflasi_cogs_tahunan" id="inflasi_cogs_tahunan" min="0" max="20" step="0.5" value="5" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-lg">
                    <div class="text-center font-bold text-lg" id="inflasi_cogs_val">5%</div>
                    <div class="error-message text-red-500 text-xs mt-1" id="error-inflasi_cogs_tahunan"></div>
                </div>
                <div class="input-group">
                    <label for="inflasi_biaya_tetap_tahunan" class="block text-sm font-medium text-gray-700">Inflasi Biaya Tetap Tahunan (%)</label>
                    <input type="range" name="inflasi_biaya_tetap_tahunan" id="inflasi_biaya_tetap_tahunan" min="0" max="15" step="0.5" value="3" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-lg">
                    <div class="text-center font-bold text-lg" id="inflasi_biaya_tetap_val">3%</div>
                    <div class="error-message text-red-500 text-xs mt-1" id="error-inflasi_biaya_tetap_tahunan"></div>
                </div>

                <div class="flex justify-between pt-4">
                    <button type="button" data-prev="2" class="step-nav-button bg-gray-400 text-white px-4 py-2 rounded-md hover:bg-gray-500">
                        &larr; Kembali
                    </button>
                    <button type="submit" id="calculateButton" class="bg-green-600 text-white px-6 py-3 rounded-md font-bold text-xl hover:bg-green-700">
                        JALANKAN SIMULASI & PROYEKSI (BATCH)
                    </button>
                </div>
            </div>

        </form>

        <div class="mt-12 pt-8 border-t-4 border-blue-500">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Hasil Proyeksi Keuangan</h2>
            
            <div id="loadingIndicator" class="hidden text-center text-blue-500 font-semibold mb-4">
                <svg class="animate-spin h-5 w-5 mr-3 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Menghitung simulasi...
            </div>

            <div id="projection-results" class="bg-blue-50 p-6 rounded-lg shadow-inner border border-blue-200">
                <p class="text-gray-500 italic" id="last-update">Tekan "JALANKAN SIMULASI" untuk melihat hasil proyeksi.</p>

                <div id="results-data" class="hidden">

                    <div class="bg-gradient-to-br from-slate-50 via-white to-blue-50 border border-blue-100 rounded-xl p-5 shadow mb-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-blue-500">Detail Input Pengguna</p>
                        <h3 class="text-xl font-semibold text-gray-900 mt-1">Asumsi yang Anda Masukkan</h3>
                        <p class="text-sm text-gray-600 mb-4">Ringkasan ini membantu memastikan bahwa hasil simulasi sesuai dengan angka yang baru saja Anda masukkan.</p>
                        <ul class="space-y-4 text-sm text-gray-700">
                            <li class="bg-white border border-blue-100 rounded-lg p-4 shadow-sm">
                                <p class="font-semibold text-gray-900">Mesin pendapatan & permintaan</p>
                                <p>Harga jual <span data-input-summary="harga_jual" class="font-semibold text-gray-900">-</span> per unit didukung anggaran marketing <span data-input-summary="anggaran_marketing" class="font-semibold text-gray-900">-</span> dengan CPL <span data-input-summary="biaya_per_lead" class="font-semibold text-gray-900">-</span> dan konversi <span data-input-summary="tingkat_konversi" class="font-semibold text-gray-900">-</span>. Musiman 12 bulan dan kapasitas <span data-input-summary="kapasitas_bulanan" class="font-semibold text-gray-900">-</span> unit mengendalikan volume aktual.</p>
                            </li>
                            <li class="bg-white border border-emerald-100 rounded-lg p-4 shadow-sm">
                                <p class="font-semibold text-gray-900">Modal kerja & investasi aset</p>
                                <p>Modal kerja awal <span data-input-summary="modal_kerja" class="font-semibold text-gray-900">-</span>, belanja aset <span data-input-summary="capex" class="font-semibold text-gray-900">-</span> disusutkan selama <span data-input-summary="masa_manfaat_aset_tahun" class="font-semibold text-gray-900">-</span>. Siklus kas dikendalikan oleh hari piutang <span data-input-summary="hari_piutang" class="font-semibold text-gray-900">-</span> dan hari utang usaha <span data-input-summary="hari_utang_usaha" class="font-semibold text-gray-900">-</span>.</p>
                            </li>
                            <li class="bg-white border border-amber-100 rounded-lg p-4 shadow-sm">
                                <p class="font-semibold text-gray-900">Struktur pendanaan</p>
                                <p>Modal pribadi sebesar <span data-input-summary="modal_disetor_pemilik" class="font-semibold text-gray-900">-</span> berpadu dengan pinjaman bank <span data-input-summary="jumlah_pinjaman" class="font-semibold text-gray-900">-</span> berbunga <span data-input-summary="bunga_pinjaman_tahunan" class="font-semibold text-gray-900">-</span> dan tenor <span data-input-summary="tenor_pinjaman_bulan" class="font-semibold text-gray-900">-</span>.</p>
                            </li>
                            <li class="bg-white border border-orange-100 rounded-lg p-4 shadow-sm">
                                <p class="font-semibold text-gray-900">Struktur biaya operasional</p>
                                <p>COGS per unit <span data-input-summary="cogs" class="font-semibold text-gray-900">-</span> dikombinasikan dengan <span data-input-summary="jumlah_karyawan" class="font-semibold text-gray-900">-</span> karyawan bergaji rata-rata <span data-input-summary="gaji_per_karyawan" class="font-semibold text-gray-900">-</span> per bulan.</p>
                            </li>
                            <li class="bg-white border border-indigo-100 rounded-lg p-4 shadow-sm">
                                <p class="font-semibold text-gray-900">Asumsi pajak & inflasi</p>
                                <p>Tarif pajak <span data-input-summary="tarif_pajak" class="font-semibold text-gray-900">-</span>, inflasi COGS <span data-input-summary="inflasi_cogs_tahunan" class="font-semibold text-gray-900">-</span>, inflasi gaji & biaya tetap <span data-input-summary="inflasi_biaya_tetap_tahunan" class="font-semibold text-gray-900">-</span>. Harga jual disesuaikan <span data-input-summary="kenaikan_harga_jual_tahunan" class="font-semibold text-gray-900">-</span> per tahun selama <span data-input-summary="durasi_proyeksi_tahun" class="font-semibold text-gray-900">-</span>.</p>
                            </li>
                        </ul>
                    </div>

                    <h3 class="text-xl font-semibold mt-6 mb-3 border-b pb-1">Ringkasan Utama</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-green-500">
                            <p class="text-sm font-medium text-gray-500">Laba Bersih Proyeksi (Tahunan)</p>
                            <p id="net-profit" class="text-2xl font-bold text-green-600 mt-1">-</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-teal-500">
                            <p class="text-sm font-medium text-gray-500">Saldo Kas Akhir (Akhir Proyeksi)</p>
                            <p id="final-cash-balance" class="text-2xl font-bold text-teal-600 mt-1">-</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-yellow-500">
                            <p class="text-sm font-medium text-gray-500">Titik Impas (BEP Bulanan)</p>
                            <p id="bep" class="text-2xl font-bold text-yellow-600 mt-1">-</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-indigo-500">
                            <p class="text-sm font-medium text-gray-500">Waktu Balik Modal (Payback Period)</p>
                            <p id="payback-period" class="text-2xl font-bold text-indigo-600 mt-1">-</p>
                        </div>
                        
                        <div id="cash-warning-card" class="col-span-1 md:col-span-4 bg-yellow-100 p-4 rounded-lg shadow-md border-l-4 border-yellow-500 hidden">
                            <p class="text-sm font-medium text-gray-700">Peringatan Arus Kas</p>
                            <p id="cash-warning-message" class="text-lg font-bold text-yellow-800 mt-1"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-emerald-500">
                            <p class="text-sm font-medium text-gray-500">EBITDA Tahun Pertama</p>
                            <p id="ebitda-year1" class="text-2xl font-bold text-emerald-600 mt-1">-</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-purple-500">
                            <p class="text-sm font-medium text-gray-500">Gross Profit Margin (Tahun 1)</p>
                            <p id="gross-margin" class="text-2xl font-bold text-purple-600 mt-1">-</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-pink-500">
                            <p class="text-sm font-medium text-gray-500">Net Profit Margin (Tahun 1)</p>
                            <p id="net-margin" class="text-2xl font-bold text-pink-600 mt-1">-</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-slate-500">
                            <p class="text-sm font-medium text-gray-500">Debt-to-Equity Ratio</p>
                            <p id="debt-to-equity" class="text-2xl font-bold text-slate-700 mt-1">-</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-fuchsia-500">
                            <p class="text-sm font-medium text-gray-500">Interest Coverage Ratio</p>
                            <p id="interest-coverage" class="text-2xl font-bold text-fuchsia-600 mt-1">-</p>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-lg shadow-md border border-slate-100 mb-8">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Arus kas Tahun 1</p>
                                <h4 class="text-lg font-semibold text-gray-900">Ringkasan Operating, Investing & Financing</h4>
                            </div>
                            <p class="text-sm text-gray-500">Detail bulanan menampilkan Tahun 1. Durasi proyeksi: <span id="projection-duration-label" class="font-semibold text-gray-900">-</span>.</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">Operasi</p>
                                <p id="cfo-year1" class="text-2xl font-bold text-emerald-700 mt-1">-</p>
                            </div>
                            <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                                <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide">Investasi</p>
                                <p id="cfi-year1" class="text-2xl font-bold text-blue-700 mt-1">-</p>
                            </div>
                            <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-100">
                                <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wide">Pendanaan</p>
                                <p id="cff-year1" class="text-2xl font-bold text-indigo-700 mt-1">-</p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold mt-6 mb-3 border-b pb-1">Visualisasi Proyeksi Bulanan (1 Tahun)</h3>

                    <div class="bg-white p-4 rounded-lg shadow-md mt-6">
                        <canvas id="cashFlowChart"></canvas>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-md mt-6">
                        <canvas id="costRevenueChart"></canvas>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-md mt-6">
                        <canvas id="cashComponentChart"></canvas>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-md mt-6">
                        <canvas id="keuntunganChart"></canvas>
                    </div>

                    <h3 class="text-xl font-semibold mt-8 mb-3 border-b pb-1">Ringkasan Proyeksi Tahunan</h3>
                    <p class="text-sm text-gray-500 mb-4">Tabel berikut menunjukkan ringkasan Pro-Forma Income Statement dan Cash Flow hingga akhir periode perencanaan.</p>
                    <div class="overflow-x-auto bg-white rounded-lg shadow-md">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pendapatan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">COGS</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">EBITDA</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Laba Bersih</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CFO</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CFI</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CFF</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Kas Akhir</th>
                                </tr>
                            </thead>
                            <tbody id="yearly-projection-body" class="bg-white divide-y divide-gray-200"></tbody>
                        </table>
                    </div>


                    <h3 class="text-xl font-semibold mt-6 mb-3 border-b pb-1">Analisis Skenario Perbandingan</h3>
                    
                    <div class="flex justify-end mb-4">
                        <button type="button" id="showSkenarioBForm" class="bg-indigo-500 text-white px-4 py-2 rounded-md hover:bg-indigo-600 hidden">
                            Buat Skenario Perbandingan (B) &rarr;
                        </button>
                    </div>
                    
                    <div id="skenarioBInputsContainer" class="bg-gray-100 p-4 rounded-lg shadow-inner mb-6 hidden">
                        <h4 class="text-lg font-semibold mb-3 text-indigo-600">Input Skenario B (Atur Harga & Anggaran):</h4>
                        <p id="skenario-b-message" class="hidden text-sm font-semibold mb-3" aria-live="polite"></p>

                        <form id="skenarioBForm" class="space-y-4">
                            @csrf
                            <div class="flex flex-col gap-2 input-group">
                                <label for="harga_jual_skenario_b" class="text-sm font-medium text-gray-700">Harga Jual per Unit Skenario B (Rp)</label>
                                <input type="number" name="harga_jual" id="harga_jual_skenario_b" min="0" required class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                                <div class="error-message text-red-500 text-xs" id="error-harga_jual_skenario_b"></div>
                            </div>
                            <div class="flex flex-col gap-2 input-group">
                                <label for="anggaran_marketing_skenario_b" class="text-sm font-medium text-gray-700">Anggaran Marketing Skenario B (Rp/Bulan)</label>
                                <input type="number" name="anggaran_marketing" id="anggaran_marketing_skenario_b" min="0" required class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                                <div class="error-message text-red-500 text-xs" id="error-anggaran_marketing_skenario_b"></div>
                            </div>

                            <div class="flex flex-col gap-3 pt-2">
                                <button type="button" id="closeSkenarioBForm" class="text-sm font-medium text-gray-500 hover:text-gray-700">Tutup Form Skenario B</button>
                                <button type="submit" id="calculateSkenarioBButton" class="bg-indigo-600 text-white px-4 py-2 rounded-md font-medium hover:bg-indigo-700">
                                    Hitung Skenario B
                                </button>
                            </div>
                        </form>
                    </div>


                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metrik</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skenario Dasar (A)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" id="skenario-b-header">Skenario Lain (Belum Dihitung)</th> 
                            </tr>
                        </thead>
                        <tbody id="comparison-table-body" class="bg-white divide-y divide-gray-200">
                        </tbody>
                    </table>
                </div>
           </div>
            
        </div>
    </main>

    <div id="saveCard" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" data-save-card-dismiss></div>
        <div class="relative max-w-lg w-full mx-4 bg-white rounded-2xl shadow-2xl p-6 space-y-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-blue-500">Simpan skenario</p>
                    <h3 class="text-2xl font-bold text-gray-900">Berikan nama sebelum diarsipkan</h3>
                    <p class="mt-1 text-sm text-gray-500">Periksa ringkasan input Anda lalu beri nama yang mudah diingat.</p>
                </div>
                <button type="button" id="closeSaveCard" class="text-gray-400 hover:text-gray-600">
                    <span class="sr-only">Tutup</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="p-4 rounded-lg bg-blue-50">
                    <dt class="text-xs font-semibold text-blue-700 tracking-wide">Harga Jual</dt>
                    <dd class="text-lg font-bold text-blue-900" data-save-summary="harga_jual">-</dd>
                </div>
                <div class="p-4 rounded-lg bg-indigo-50">
                    <dt class="text-xs font-semibold text-indigo-700 tracking-wide">Anggaran Marketing</dt>
                    <dd class="text-lg font-bold text-indigo-900" data-save-summary="anggaran_marketing">-</dd>
                </div>
                <div class="p-4 rounded-lg bg-emerald-50">
                    <dt class="text-xs font-semibold text-emerald-700 tracking-wide">Modal Kerja</dt>
                    <dd class="text-lg font-bold text-emerald-900" data-save-summary="modal_kerja">-</dd>
                </div>
                <div class="p-4 rounded-lg bg-amber-50">
                    <dt class="text-xs font-semibold text-amber-700 tracking-wide">Kapasitas Bulanan</dt>
                    <dd class="text-lg font-bold text-amber-900" data-save-summary="kapasitas_bulanan">-</dd>
                </div>
            </dl>

            <form id="saveForm" class="space-y-4">
                @csrf
                <div>
                    <label for="nama_skenario" class="block text-sm font-medium text-gray-700">Nama Skenario</label>
                    <input type="text" name="nama_skenario" id="nama_skenario" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <div class="error-message text-red-500 text-xs mt-1" id="error-nama_skenario"></div>
                </div>
                <p id="saveStatusMessage" class="hidden text-sm font-semibold"></p>
                <div class="flex flex-col gap-3">
                    <button type="button" id="cancelSaveCard" class="w-full px-4 py-2 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">Batal</button>
                    <button type="submit" class="w-full px-4 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700">Simpan Skenario</button>
                </div>
            </form>
        </div>
    </div>

    <div id="loadModal" class="modal-overlay hidden">
        <div class="max-w-xl mx-auto mt-20 bg-white p-6 rounded-lg shadow-xl">
            <h3 class="text-xl font-bold mb-4">Muat Skenario Tersimpan (Milik Anda)</h3>
            <div id="loadingLoad" class="text-center text-blue-500 mb-4 hidden">Memuat daftar skenario...</div>
            <select id="savedScenariosDropdown" class="w-full p-2 border border-gray-300 rounded-md mb-4">
                <option value="">-- Pilih Skenario --</option>
            </select>
            <div id="noScenariosMessage" class="text-center text-gray-500 mb-4 hidden">Anda belum memiliki skenario tersimpan.</div> 
            <div class="flex justify-end space-x-4">
                <button type="button" id="closeLoadModal" class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400">Tutup</button>
                <button type="button" id="executeLoadButton" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50" data-load-url="{{ url('/simulator/load') }}" disabled>Muat & Isi Form</button>
            </div>
        </div>
    </div>

    @vite('resources/js/app.js')
</body>
</html>