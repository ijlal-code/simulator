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
        <div class="w-full px-4 sm:px-6 lg:px-12 py-5 flex flex-col gap-4 text-center lg:text-left">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-blue-500">{{ $appTitle }}</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Dashboard Simulasi &amp; Proyeksi</h1>
                <p class="text-sm text-gray-500">Susun asumsi bisnis Anda dan dapatkan ringkasan instan.</p>
            </div>
            <div class="flex flex-wrap items-center justify-center lg:justify-end gap-3 text-sm text-gray-700">
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
                        <label for="volume_penjualan" class="text-sm font-medium text-gray-700">Target Volume Penjualan (Unit/Bulan)</label>
                        <span class="text-xs text-gray-500">
                            <abbr title="Jumlah unit produk yang diharapkan terjual setiap bulan.">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </abbr>
                        </span>
                    </div>
                    <input type="number" name="volume_penjualan" id="volume_penjualan" value="1000" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <div class="error-message text-red-500 text-xs" id="error-volume_penjualan"></div>
                </div>
                <div class="pt-4 border-t input-group">
                    <label for="tingkat_pertumbuhan" class="block text-sm font-medium text-gray-700">Tingkat Pertumbuhan Tahunan (%)</label>
                    <input type="range" name="tingkat_pertumbuhan" id="tingkat_pertumbuhan" min="0" max="50" step="1" value="10" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-lg">
                    <div class="text-center font-bold text-lg" id="pertumbuhan_val">10%</div>
                    <div class="error-message text-red-500 text-xs mt-1" id="error-tingkat_pertumbuhan"></div>
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
                <div class="flex flex-col gap-2 input-group">
                    <label for="biaya_tetap" class="text-sm font-medium text-gray-700">Biaya Tetap (Sewa, Gaji, dll.) (Rp/Bulan)</label>
                    <input type="number" name="biaya_tetap" id="biaya_tetap" value="5000000" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <div class="error-message text-red-500 text-xs" id="error-biaya_tetap"></div>
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
                    <label for="inflasi_biaya" class="block text-sm font-medium text-gray-700">Asumsi Inflasi Biaya Tahunan (%)</label>
                    <input type="range" name="inflasi_biaya" id="inflasi_biaya" min="0" max="20" step="0.5" value="5" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-lg">
                    <div class="text-center font-bold text-lg" id="inflasi_val">5%</div>
                    <div class="error-message text-red-500 text-xs mt-1" id="error-inflasi_biaya"></div>
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
                    
                    <h3 class="text-xl font-semibold mt-6 mb-3 border-b pb-1">Ringkasan Utama</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-green-500">
                            <p class="text-sm font-medium text-gray-500">Laba Bersih Proyeksi (Tahunan)</p>
                            <p id="net-profit" class="text-2xl font-bold text-green-600 mt-1">-</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-teal-500"> 
                            <p class="text-sm font-medium text-gray-500">Saldo Kas Akhir (Tahun 1)</p>
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

                    <h3 class="text-xl font-semibold mt-6 mb-3 border-b pb-1">Visualisasi Proyeksi Bulanan (1 Tahun)</h3>
                    
                    <div class="bg-white p-4 rounded-lg shadow-md mt-6">
                        <canvas id="cashFlowChart"></canvas>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-md mt-6">
                        <canvas id="costRevenueChart"></canvas>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-md mt-6">
                        <canvas id="keuntunganChart"></canvas>
                    </div>


                    <h3 class="text-xl font-semibold mt-6 mb-3 border-b pb-1">Analisis Skenario Perbandingan</h3>
                    
                    <div class="flex justify-end mb-4">
                        <button type="button" id="showSkenarioBForm" class="bg-indigo-500 text-white px-4 py-2 rounded-md hover:bg-indigo-600 hidden">
                            Buat Skenario Perbandingan (B) &rarr;
                        </button>
                    </div>
                    
                    <div id="skenarioBInputsContainer" class="bg-gray-100 p-4 rounded-lg shadow-inner mb-6 hidden">
                        <h4 class="text-lg font-semibold mb-3 text-indigo-600">Input Skenario B (Hanya ubah Harga Jual & Volume):</h4>
                        <p id="skenario-b-message" class="hidden text-sm font-semibold mb-3" aria-live="polite"></p>

                        <form id="skenarioBForm" class="space-y-4">
                            @csrf
                            <div class="flex flex-col gap-2 input-group">
                                <label for="harga_jual_skenario_b" class="text-sm font-medium text-gray-700">Harga Jual per Unit Skenario B (Rp)</label>
                                <input type="number" name="harga_jual" id="harga_jual_skenario_b" min="0" required class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                                <div class="error-message text-red-500 text-xs" id="error-harga_jual_skenario_b"></div>
                            </div>
                            <div class="flex flex-col gap-2 input-group">
                                <label for="volume_penjualan_skenario_b" class="text-sm font-medium text-gray-700">Volume Penjualan Skenario B (Unit/Bulan)</label>
                                <input type="number" name="volume_penjualan" id="volume_penjualan_skenario_b" min="0" required class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                                <div class="error-message text-red-500 text-xs" id="error-volume_penjualan_skenario_b"></div>
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
                    <dt class="text-xs font-semibold text-indigo-700 tracking-wide">Volume Penjualan</dt>
                    <dd class="text-lg font-bold text-indigo-900" data-save-summary="volume_penjualan">-</dd>
                </div>
                <div class="p-4 rounded-lg bg-emerald-50">
                    <dt class="text-xs font-semibold text-emerald-700 tracking-wide">Modal Kerja</dt>
                    <dd class="text-lg font-bold text-emerald-900" data-save-summary="modal_kerja">-</dd>
                </div>
                <div class="p-4 rounded-lg bg-amber-50">
                    <dt class="text-xs font-semibold text-amber-700 tracking-wide">CAPEX</dt>
                    <dd class="text-lg font-bold text-amber-900" data-save-summary="capex">-</dd>
                </div>
            </dl>

            <form id="saveForm" class="space-y-4">
                @csrf
                <div>
                    <label for="nama_skenario" class="block text-sm font-medium text-gray-700">Nama Skenario</label>
                    <input type="text" name="nama_skenario" id="nama_skenario" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <div class="error-message text-red-500 text-xs mt-1" id="error-nama_skenario"></div>
                </div>
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