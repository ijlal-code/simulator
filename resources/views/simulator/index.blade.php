<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Simulator Bisnis - Mode Batch</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Gaya dasar untuk tab */
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="bg-gray-100 p-8">

    <div class="max-w-4xl mx-auto bg-white shadow-xl rounded-lg p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-2">Simulator Proyeksi Bisnis</h1>

        <form id="simulatorForm" class="space-y-6">
            @csrf

            <div class="flex border-b border-gray-200">
                <button type="button" data-step="1" class="tab-button p-3 text-lg font-medium text-blue-600 border-b-2 border-blue-600 hover:text-blue-800 focus:outline-none">
                    Langkah 1: Profil & Pendapatan
                </button>
                <button type="button" data-step="2" class="tab-button p-3 text-lg font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent focus:outline-none">
                    Langkah 2: Biaya Modal & Investasi
                </button>
                <button type="button" data-step="3" class="tab-button p-3 text-lg font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent focus:outline-none">
                    Langkah 3: Biaya Operasional
                </button>
            </div>
            
            <div id="step-1" class="tab-content active space-y-4">
                <h2 class="text-2xl font-semibold mb-4 text-blue-600">Langkah 1: Profil Usaha & Pendapatan</h2>
                
                <div class="flex items-center space-x-4">
                    <label for="harga_jual" class="block text-sm font-medium text-gray-700 w-1/3">Harga Jual per Unit (Rp)</label>
                    <input type="number" name="harga_jual" id="harga_jual" value="100000" min="0" class="flex-1 mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <span class="text-sm text-gray-500">
                        <abbr title="Harga produk atau jasa Anda per unit.">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </abbr>
                    </span>
                </div>

                <div class="flex items-center space-x-4">
                    <label for="volume_penjualan" class="block text-sm font-medium text-gray-700 w-1/3">Target Volume Penjualan (Unit/Bulan)</label>
                    <input type="number" name="volume_penjualan" id="volume_penjualan" value="1000" min="0" class="flex-1 mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <span class="text-sm text-gray-500">
                        <abbr title="Jumlah unit produk yang diharapkan terjual setiap bulan.">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </abbr>
                    </span>
                </div>

                <div class="pt-4 border-t">
                    <label for="tingkat_pertumbuhan" class="block text-sm font-medium text-gray-700">Tingkat Pertumbuhan Tahunan (%)</label>
                    <input type="range" name="tingkat_pertumbuhan" id="tingkat_pertumbuhan" min="0" max="50" step="1" value="10" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-lg">
                    <div class="text-center font-bold text-lg" id="pertumbuhan_val">10%</div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="button" data-next="2" class="step-nav-button bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Lanjut ke Langkah 2 &rarr;
                    </button>
                </div>
            </div>

            <div id="step-2" class="tab-content space-y-4">
                <h2 class="text-2xl font-semibold mb-4 text-blue-600">Langkah 2: Biaya Modal & Investasi</h2>
                
                <div class="flex items-center space-x-4">
                    <label for="capex" class="block text-sm font-medium text-gray-700 w-1/3">Biaya Pembelian Aset Jangka Panjang (CAPEX) (Rp)</label>
                    <input type="number" name="capex" id="capex" value="50000000" min="0" class="flex-1 mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <span class="text-sm text-gray-500">
                        <abbr title="Biaya Pembelian Aset Jangka Panjang (Misalnya: Mesin, Peralatan, Gedung)">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </abbr>
                    </span>
                </div>

                <div class="flex items-center space-x-4">
                    <label for="modal_kerja" class="block text-sm font-medium text-gray-700 w-1/3">Modal Kerja Awal (Kas) (Rp)</label>
                    <input type="number" name="modal_kerja" id="modal_kerja" value="10000000" min="0" class="flex-1 mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
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
                <h2 class="text-2xl font-semibold mb-4 text-blue-600">Langkah 3: Biaya Operasional (Bulanan)</h2>
                
                <div class="flex items-center space-x-4">
                    <label for="cogs" class="block text-sm font-medium text-gray-700 w-1/3">Biaya Langsung Pembuatan Produk (COGS) per Unit (Rp)</label>
                    <input type="number" name="cogs" id="cogs" value="30000" min="0" class="flex-1 mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <span class="text-sm text-gray-500">
                        <abbr title="Cost of Goods Sold (COGS). Biaya langsung bahan baku, tenaga kerja, dll., per unit.">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </abbr>
                    </span>
                </div>

                <div class="flex items-center space-x-4">
                    <label for="biaya_tetap" class="block text-sm font-medium text-gray-700 w-1/3">Biaya Tetap (Sewa, Gaji, dll.) (Rp/Bulan)</label>
                    <input type="number" name="biaya_tetap" id="biaya_tetap" value="5000000" min="0" class="flex-1 mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
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

                <div id="results-data" class="hidden grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                    
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-green-500">
                        <p class="text-sm font-medium text-gray-500">Keuntungan Bersih Proyeksi (Bulanan)</p>
                        <p id="net-profit" class="text-2xl font-bold text-green-600 mt-1">-</p>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-yellow-500">
                        <p class="text-sm font-medium text-gray-500">Titik Impas (BEP)</p>
                        <p id="bep" class="text-2xl font-bold text-yellow-600 mt-1">-</p>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-indigo-500">
                        <p class="text-sm font-medium text-gray-500">Waktu Balik Modal (Payback Period)</p>
                        <p id="payback-period" class="text-2xl font-bold text-indigo-600 mt-1">-</p>
                    </div>
                </div>
            </div>
            
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('simulatorForm');
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            const stepNavButtons = document.querySelectorAll('.step-nav-button');
            const resultsData = document.getElementById('results-data');
            const loadingIndicator = document.getElementById('loadingIndicator');

            let currentStep = 1;
            
            // FUNGSI NAVIGASI TAB
            function showStep(step) {
                // Nonaktifkan semua konten dan tombol
                tabContents.forEach(content => content.classList.remove('active'));
                tabButtons.forEach(button => {
                    button.classList.remove('text-blue-600', 'border-blue-600');
                    button.classList.add('text-gray-500', 'border-transparent');
                });

                // Aktifkan konten yang dipilih
                document.getElementById(`step-${step}`).classList.add('active');
                
                // Aktifkan tombol yang dipilih
                const activeButton = document.querySelector(`.tab-button[data-step="${step}"]`);
                if (activeButton) {
                    activeButton.classList.remove('text-gray-500', 'border-transparent');
                    activeButton.classList.add('text-blue-600', 'border-blue-600');
                }
                currentStep = step;
            }

            // Event listener untuk tombol navigasi tab
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const step = parseInt(button.getAttribute('data-step'));
                    showStep(step);
                });
            });

            // Event listener untuk tombol Lanjut/Kembali
            stepNavButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const nextStep = button.getAttribute('data-next');
                    const prevStep = button.getAttribute('data-prev');
                    
                    if (nextStep) {
                        showStep(parseInt(nextStep));
                    } else if (prevStep) {
                        showStep(parseInt(prevStep));
                    }
                });
            });

            // Sinkronisasi Slider
            document.getElementById('tingkat_pertumbuhan').addEventListener('input', function() {
                document.getElementById('pertumbuhan_val').innerText = this.value + '%';
            });
            
            // Tampilkan langkah 1 saat inisialisasi
            showStep(currentStep);

            // --- FUNGSI SIMULASI BATCH (AJAX) ---
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                
                const calculateUrl = "{{ route('simulator.calculate') }}";
                const formData = new FormData(form);
                const resultsContainer = document.getElementById('projection-results');

                // Tampilkan loading dan sembunyikan data lama
                loadingIndicator.classList.remove('hidden');
                resultsData.classList.add('hidden');
                resultsContainer.style.opacity = '0.5';

                fetch(calculateUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Sembunyikan loading dan tampilkan data baru
                    loadingIndicator.classList.add('hidden');
                    resultsContainer.style.opacity = '1';

                    if (data.status === 'success') {
                        // Perbarui data pada kartu hasil
                        document.getElementById('net-profit').innerText = data.data.keuntungan_bersih_proyeksi;
                        document.getElementById('bep').innerText = data.data.titik_impas_bep;
                        document.getElementById('payback-period').innerText = data.data.waktu_balik_modal;
                        
                        // Perbarui label waktu update (Mode Batch)
                        document.getElementById('last-update').innerHTML = `**Diperbarui pada ${data.data.diperbarui_pada}**`;
                        
                        // Tampilkan kartu hasil
                        resultsData.classList.remove('hidden');
                    } else {
                        // Tampilkan pesan error jika validasi gagal (diimplementasikan di Controller)
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingIndicator.classList.add('hidden');
                    resultsContainer.style.opacity = '1';
                    alert('Terjadi kesalahan saat mengirim data ke server.');
                });
            });
        });
    </script>
</body>
</html>