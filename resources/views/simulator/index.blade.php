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
        .error-message { position: absolute; right: 0; top: 100%; }
        /* Gaya untuk Modal (BARU) */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.5); z-index: 1000; display: none;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-slate-200 min-h-screen font-sans">

    <nav class="bg-white/90 backdrop-blur shadow-md fixed top-0 inset-x-0 z-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-10">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ route('simulator.index') }}" class="text-xl font-semibold text-blue-700 tracking-tight">
                    💼 Simulator Bisnis
                </a>
                <div class="flex items-center gap-4 text-sm">
                    @auth
                        <span class="text-gray-600 hidden sm:inline">Halo, <strong>{{ Auth::user()->name }}</strong></span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="rounded-full border border-red-200 px-4 py-1 font-medium text-red-600 transition hover:bg-red-50">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-full border border-blue-200 px-4 py-1 font-medium text-blue-600 transition hover:bg-blue-50">Login</a>
                        <a href="{{ route('register') }}" class="rounded-full bg-blue-600 px-4 py-1 font-semibold text-white shadow hover:bg-blue-700">Daftar</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-24 pb-12 px-3 sm:px-6 lg:px-12">
        <div class="mx-auto w-full max-w-7xl">
            <section class="w-full rounded-3xl bg-white/95 p-6 sm:p-8 shadow-2xl ring-1 ring-blue-50">
                <div class="flex flex-col gap-4 border-b border-dashed border-gray-200 pb-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-blue-400">Simulasi keuangan terpadu</p>
                        <h1 class="text-3xl font-bold text-gray-800 sm:text-4xl">Simulator Proyeksi Bisnis Realistis</h1>
                        <p class="mt-2 text-base text-gray-500">Optimalkan keputusan bisnis Anda dengan proyeksi laba rugi, arus kas, dan analisis perbandingan skenario.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        @auth
                            <button type="button" id="saveScenarioButton" data-save-url="{{ route('simulator.save') }}" class="inline-flex items-center justify-center rounded-full bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:opacity-50" disabled>
                                Simpan Skenario (A)
                            </button>
                            <button type="button" id="loadScenarioButton" data-list-url="{{ route('simulator.list') }}" class="inline-flex items-center justify-center rounded-full bg-gray-900 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-gray-300 transition hover:bg-gray-800">
                                Muat Skenario Tersimpan
                            </button>
                        @else
                            <div id="authRequiredMessage" class="w-full rounded-2xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-700">
                                <a href="{{ route('login') }}" class="font-semibold underline">Login</a> atau daftar untuk menggunakan fitur simpan & muat skenario.
                            </div>
                        @endauth
                    </div>
                </div>

        <form id="simulatorForm" class="mt-8 space-y-6" data-calculate-url="{{ route('simulator.calculate') }}">
            @csrf

            <div class="flex flex-col gap-2 border-b border-gray-100 text-left sm:flex-row">
                <button type="button" data-step="1" class="tab-button rounded-t-xl px-4 py-3 text-left text-base font-semibold text-blue-600 sm:rounded-none sm:border-b-2 sm:border-blue-600">
                    Langkah 1: Profil & Pendapatan
                </button>
                <button type="button" data-step="2" class="tab-button rounded-t-xl px-4 py-3 text-left text-base font-semibold text-gray-500 sm:rounded-none sm:border-b-2 sm:border-transparent">
                    Langkah 2: Modal & Investasi
                </button>
                <button type="button" data-step="3" class="tab-button rounded-t-xl px-4 py-3 text-left text-base font-semibold text-gray-500 sm:rounded-none sm:border-b-2 sm:border-transparent">
                    Langkah 3: Biaya, Pajak & Inflasi
                </button>
            </div>
            
            <div id="step-1" class="tab-content active space-y-4">
                <h2 class="text-2xl font-semibold mb-4 text-blue-600">Langkah 1: Profil Usaha & Pendapatan</h2>
                <div class="input-group flex flex-col gap-2 rounded-2xl border border-gray-100 bg-gray-50/80 p-4 sm:flex-row sm:items-start sm:gap-4">
                    <label for="harga_jual" class="w-full text-sm font-medium text-gray-700 sm:w-1/3">Harga Jual per Unit (Rp)</label>
                    <input type="number" name="harga_jual" id="harga_jual" value="100000" min="0" class="w-full flex-1 rounded-xl border border-gray-200 p-2 shadow-sm focus:border-blue-400 focus:ring-blue-200">
                    <span class="text-sm text-gray-500 sm:w-12 sm:text-right">
                        <abbr title="Harga produk atau jasa Anda per unit.">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </abbr>
                    </span>
                    <div class="error-message text-red-500 text-xs mt-1" id="error-harga_jual"></div>
                </div>
                <div class="input-group flex flex-col gap-2 rounded-2xl border border-gray-100 bg-gray-50/80 p-4 sm:flex-row sm:items-start sm:gap-4">
                    <label for="volume_penjualan" class="w-full text-sm font-medium text-gray-700 sm:w-1/3">Target Volume Penjualan (Unit/Bulan)</label>
                    <input type="number" name="volume_penjualan" id="volume_penjualan" value="1000" min="0" class="w-full flex-1 rounded-xl border border-gray-200 p-2 shadow-sm focus:border-blue-400 focus:ring-blue-200">
                    <span class="text-sm text-gray-500 sm:w-12 sm:text-right">
                        <abbr title="Jumlah unit produk yang diharapkan terjual setiap bulan.">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </abbr>
                    </span>
                    <div class="error-message text-red-500 text-xs mt-1" id="error-volume_penjualan"></div>
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
                <div class="input-group flex flex-col gap-2 rounded-2xl border border-gray-100 bg-gray-50/80 p-4 sm:flex-row sm:items-start sm:gap-4">
                    <label for="capex" class="w-full text-sm font-medium text-gray-700 sm:w-1/3">Biaya Pembelian Aset Jangka Panjang (CAPEX) (Rp)</label>
                    <input type="number" name="capex" id="capex" value="50000000" min="0" class="w-full flex-1 rounded-xl border border-gray-200 p-2 shadow-sm focus:border-blue-400 focus:ring-blue-200">
                    <span class="text-sm text-gray-500 sm:w-12 sm:text-right">
                        <abbr title="Biaya Pembelian Aset Jangka Panjang (Misalnya: Mesin, Peralatan, Gedung)">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </abbr>
                    </span>
                    <div class="error-message text-red-500 text-xs mt-1" id="error-capex"></div>
                </div>
                <div class="input-group flex flex-col gap-2 rounded-2xl border border-gray-100 bg-gray-50/80 p-4 sm:flex-row sm:items-start sm:gap-4">
                    <label for="modal_kerja" class="w-full text-sm font-medium text-gray-700 sm:w-1/3">Modal Kerja Awal (Kas) (Rp)</label>
                    <input type="number" name="modal_kerja" id="modal_kerja" value="10000000" min="0" class="w-full flex-1 rounded-xl border border-gray-200 p-2 shadow-sm focus:border-blue-400 focus:ring-blue-200">
                    <div class="error-message text-red-500 text-xs mt-1" id="error-modal_kerja"></div>
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
                <div class="input-group flex flex-col gap-2 rounded-2xl border border-gray-100 bg-gray-50/80 p-4 sm:flex-row sm:items-start sm:gap-4">
                    <label for="cogs" class="w-full text-sm font-medium text-gray-700 sm:w-1/3">Biaya Langsung Produk (COGS) per Unit (Rp)</label>
                    <input type="number" name="cogs" id="cogs" value="30000" min="0" class="w-full flex-1 rounded-xl border border-gray-200 p-2 shadow-sm focus:border-blue-400 focus:ring-blue-200">
                    <span class="text-sm text-gray-500 sm:w-12 sm:text-right">
                        <abbr title="Cost of Goods Sold (COGS). Biaya langsung bahan baku, tenaga kerja, dll., per unit.">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </abbr>
                    </span>
                    <div class="error-message text-red-500 text-xs mt-1" id="error-cogs"></div>
                </div>
                <div class="input-group flex flex-col gap-2 rounded-2xl border border-gray-100 bg-gray-50/80 p-4 sm:flex-row sm:items-start sm:gap-4">
                    <label for="biaya_tetap" class="w-full text-sm font-medium text-gray-700 sm:w-1/3">Biaya Tetap (Sewa, Gaji, dll.) (Rp/Bulan)</label>
                    <input type="number" name="biaya_tetap" id="biaya_tetap" value="5000000" min="0" class="w-full flex-1 rounded-xl border border-gray-200 p-2 shadow-sm focus:border-blue-400 focus:ring-blue-200">
                    <div class="error-message text-red-500 text-xs mt-1" id="error-biaya_tetap"></div>
                </div>

                <div class="input-group flex flex-col gap-2 rounded-2xl border border-gray-100 bg-gray-50/80 p-4 sm:flex-row sm:items-start sm:gap-4">
                    <label for="tarif_pajak" class="w-full text-sm font-medium text-gray-700 sm:w-1/3">Tarif Pajak Penghasilan (%)</label>
                    <input type="number" name="tarif_pajak" id="tarif_pajak" value="10" min="0" max="100" class="w-full flex-1 rounded-xl border border-gray-200 p-2 shadow-sm focus:border-blue-400 focus:ring-blue-200">
                    <span class="text-sm text-gray-500 sm:w-12 sm:text-right">
                        <abbr title="Asumsi tarif PPh yang berlaku untuk laba sebelum pajak Anda.">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </abbr>
                    </span>
                    <div class="error-message text-red-500 text-xs mt-1" id="error-tarif_pajak"></div>
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

        <div class="mt-12 rounded-3xl border border-blue-100 bg-blue-50/60 p-6 sm:p-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Hasil Proyeksi Keuangan</h2>
            
            <div id="loadingIndicator" class="hidden text-center text-blue-500 font-semibold mb-4">
                <svg class="animate-spin h-5 w-5 mr-3 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Menghitung simulasi...
            </div>

            <div id="projection-results" class="rounded-3xl border border-blue-200 bg-white/80 p-6 shadow-inner">
                <p class="text-gray-500 italic" id="last-update">Tekan "JALANKAN SIMULASI" untuk melihat hasil proyeksi.</p>

                <div id="results-data" class="hidden">
                    
                    <h3 class="text-xl font-semibold mt-6 mb-3 border-b pb-1">Ringkasan Utama</h3>
                    <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-green-100 bg-gradient-to-b from-white to-green-50 p-4 shadow">
                            <p class="text-sm font-medium text-gray-500">Laba Bersih Proyeksi (Tahunan)</p>
                            <p id="net-profit" class="mt-1 text-2xl font-bold text-green-600">-</p>
                        </div>
                        <div class="rounded-2xl border border-teal-100 bg-gradient-to-b from-white to-teal-50 p-4 shadow">
                            <p class="text-sm font-medium text-gray-500">Saldo Kas Akhir (Tahun 1)</p>
                            <p id="final-cash-balance" class="mt-1 text-2xl font-bold text-teal-600">-</p>
                        </div>
                        <div class="rounded-2xl border border-yellow-100 bg-gradient-to-b from-white to-yellow-50 p-4 shadow">
                            <p class="text-sm font-medium text-gray-500">Titik Impas (BEP Bulanan)</p>
                            <p id="bep" class="mt-1 text-2xl font-bold text-yellow-600">-</p>
                        </div>
                        <div class="rounded-2xl border border-indigo-100 bg-gradient-to-b from-white to-indigo-50 p-4 shadow">
                            <p class="text-sm font-medium text-gray-500">Waktu Balik Modal (Payback Period)</p>
                            <p id="payback-period" class="mt-1 text-2xl font-bold text-indigo-600">-</p>
                        </div>

                        <div id="cash-warning-card" class="col-span-1 md:col-span-2 xl:col-span-4 rounded-2xl border-l-4 border-yellow-500 bg-yellow-100 p-4 shadow hidden">
                            <p class="text-sm font-medium text-gray-700">Peringatan Arus Kas</p>
                            <p id="cash-warning-message" class="text-lg font-bold text-yellow-800 mt-1"></p>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold mt-6 mb-3 border-b pb-1">Visualisasi Proyeksi Bulanan (1 Tahun)</h3>
                    
                    <div class="mt-6 rounded-2xl bg-white p-4 shadow">
                        <canvas id="cashFlowChart"></canvas>
                    </div>

                    <div class="mt-6 rounded-2xl bg-white p-4 shadow">
                        <canvas id="costRevenueChart"></canvas>
                    </div>

                    <div class="mt-6 rounded-2xl bg-white p-4 shadow">
                        <canvas id="keuntunganChart"></canvas>
                    </div>


                    <h3 class="text-xl font-semibold mt-6 mb-3 border-b pb-1">Analisis Skenario Perbandingan</h3>
                    
                    <div class="mb-4 flex justify-end">
                        <button type="button" id="showSkenarioBForm" class="hidden rounded-full bg-indigo-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-600">
                            Buat Skenario Perbandingan (B) &rarr;
                        </button>
                    </div>

                    <div id="skenarioBInputsContainer" class="mb-6 hidden rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4 shadow-inner">
                        <h4 class="text-lg font-semibold mb-3 text-indigo-600">Input Skenario B (Hanya ubah Harga Jual & Volume):</h4>

                        <form id="skenarioBForm" class="space-y-4">
                            @csrf
                            <div class="input-group flex flex-col gap-2 sm:flex-row sm:items-start sm:gap-4">
                                <label for="harga_jual_skenario_b" class="w-full text-sm font-medium text-gray-700 sm:w-1/3">Harga Jual per Unit Skenario B (Rp)</label>
                                <input type="number" name="harga_jual" id="harga_jual_skenario_b" min="0" required class="w-full flex-1 rounded-xl border border-gray-200 p-2 shadow-sm focus:border-indigo-400 focus:ring-indigo-200">
                                <div class="error-message text-red-500 text-xs mt-1" id="error-harga_jual_skenario_b"></div>
                            </div>
                            <div class="input-group flex flex-col gap-2 sm:flex-row sm:items-start sm:gap-4">
                                <label for="volume_penjualan_skenario_b" class="w-full text-sm font-medium text-gray-700 sm:w-1/3">Volume Penjualan Skenario B (Unit/Bulan)</label>
                                <input type="number" name="volume_penjualan" id="volume_penjualan_skenario_b" min="0" required class="w-full flex-1 rounded-xl border border-gray-200 p-2 shadow-sm focus:border-indigo-400 focus:ring-indigo-200">
                                <div class="error-message text-red-500 text-xs mt-1" id="error-volume_penjualan_skenario_b"></div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" id="calculateSkenarioBButton" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                    Hitung Skenario B
                                </button>
                            </div>
                        </form>
                    </div>


                    <div class="overflow-x-auto rounded-2xl border border-gray-100 bg-white shadow">
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

        </section>
        </div>
    </main>

    <div id="saveModal" class="modal-overlay hidden">
        <div class="max-w-md mx-auto mt-20 bg-white p-6 rounded-lg shadow-xl">
            <h3 class="text-xl font-bold mb-4">Simpan Skenario Dasar (A)</h3>
            <form id="saveForm">
                @csrf
                <input type="hidden" name="action_type" value="save">
                <div class="mb-4">
                    <label for="nama_skenario" class="block text-sm font-medium text-gray-700">Nama Skenario</label>
                    <input type="text" name="nama_skenario" id="nama_skenario" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <div class="error-message text-red-500 text-xs mt-1" id="error-nama_skenario"></div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" id="closeSaveModal" class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Simpan</button>
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