// Baris ini mungkin sudah ada di app.js Anda.
import './bootstrap';

// Kita bungkus semua logika simulator di sini
document.addEventListener('DOMContentLoaded', function () {
    
    const formA = document.getElementById('simulatorForm'); 
    const formB = document.getElementById('skenarioBForm'); 
    const skenarioBContainer = document.getElementById('skenarioBInputsContainer');
    const showSkenarioBButton = document.getElementById('showSkenarioBForm');


    if (formA) {
        
        const calculateUrl = formA.dataset.calculateUrl; 
        
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        const stepNavButtons = document.querySelectorAll('.step-nav-button');
        const resultsData = document.getElementById('results-data');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const errorMessages = document.querySelectorAll('.error-message');
        
        const warningCard = document.getElementById('cash-warning-card');
        const warningMessage = document.getElementById('cash-warning-message');

        // Variabel untuk menyimpan SEMUA input Skenario A (kecuali token)
        let lastSkenarioAInputs = {};
        
        let currentStep = 1;
        
        // FUNGSI NAVIGASI TAB (TETAP SAMA)
        function showStep(step) {
            tabContents.forEach(content => content.classList.remove('active'));
            tabButtons.forEach(button => {
                button.classList.remove('text-blue-600', 'border-blue-600');
                button.classList.add('text-gray-500', 'border-transparent');
            });

            document.getElementById(`step-${step}`).classList.add('active');
            
            const activeButton = document.querySelector(`.tab-button[data-step="${step}"]`);
            if (activeButton) {
                activeButton.classList.remove('text-gray-500', 'border-transparent');
                activeButton.classList.add('text-blue-600', 'border-blue-600');
            }
            currentStep = step;
        }

        // Event listener untuk tombol navigasi tab (TETAP SAMA)
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const step = parseInt(button.getAttribute('data-step'));
                showStep(step);
            });
        });

        // Event listener untuk tombol Lanjut/Kembali (TETAP SAMA)
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

        // Sinkronisasi Slider (TETAP SAMA)
        const slider = document.getElementById('tingkat_pertumbuhan');
        if (slider) {
            slider.addEventListener('input', function() {
                document.getElementById('pertumbuhan_val').innerText = this.value + '%';
            });
        }
        
        const inflasiSlider = document.getElementById('inflasi_biaya');
        if (inflasiSlider) {
            inflasiSlider.addEventListener('input', function() {
                document.getElementById('inflasi_val').innerText = this.value + '%';
            });
        }
        
        showStep(currentStep);

        // FUNGSI CHART (TETAP SAMA)
        let keuntunganChartInstance = null;
        let cashFlowChartInstance = null;
        let costRevenueChartInstance = null;
        
        function drawKeuntunganChart(labels, data) { 
             const ctx = document.getElementById('keuntunganChart').getContext('2d');
             if (keuntunganChartInstance) { keuntunganChartInstance.destroy(); }
             keuntunganChartInstance = new Chart(ctx, { type: 'bar', data: { labels: labels, datasets: [{ label: 'Laba Bersih Setelah Pajak (Rp)', data: data, backgroundColor: 'rgba(52, 211, 153, 0.8)', borderColor: 'rgba(52, 211, 153, 1)', borderWidth: 1 }] }, options: { responsive: true, scales: { y: { beginAtZero: true, title: { display: true, text: 'Laba Bersih (Rp)' } } }, plugins: { legend: { display: false }, title: { display: true, text: 'Proyeksi Laba Bersih Bulanan (Setelah Pajak)' } } } });
        }
        function drawCashFlowChart(labels, data) {
            const ctx = document.getElementById('cashFlowChart').getContext('2d');
            if (cashFlowChartInstance) { cashFlowChartInstance.destroy(); }
            cashFlowChartInstance = new Chart(ctx, { type: 'line', data: { labels: labels, datasets: [{ label: 'Saldo Kas Bulanan (Rp)', data: data, backgroundColor: 'rgba(75, 192, 192, 0.5)', borderColor: 'rgba(75, 192, 192, 1)', borderWidth: 2, tension: 0.3, fill: true }] }, options: { responsive: true, scales: { y: { title: { display: true, text: 'Saldo Kas (Rp)' } } }, plugins: { title: { display: true, text: 'Proyeksi Saldo Arus Kas Bulanan (Memperhitungkan CAPEX & Modal Kerja)' } } } });
        }
        function drawCostRevenueChart(labels, pendapatanData, biayaTotalData) {
            const ctx = document.getElementById('costRevenueChart').getContext('2d');
            if (costRevenueChartInstance) { costRevenueChartInstance.destroy(); }
            costRevenueChartInstance = new Chart(ctx, { type: 'line', data: { labels: labels, datasets: [ { label: 'Pendapatan (Rp)', data: pendapatanData, backgroundColor: 'rgba(59, 130, 246, 0.5)', borderColor: 'rgba(59, 130, 246, 1)', borderWidth: 2, tension: 0.1, fill: false }, { label: 'Total Biaya (Variabel + Tetap + Pajak) (Rp)', data: biayaTotalData, backgroundColor: 'rgba(239, 68, 68, 0.5)', borderColor: 'rgba(239, 68, 68, 1)', borderWidth: 2, tension: 0.1, fill: false } ] }, options: { responsive: true, scales: { y: { beginAtZero: true, title: { display: true, text: 'Jumlah (Rp)' } } }, plugins: { title: { display: true, text: 'Pendapatan vs. Total Biaya Bulanan (Termasuk Inflasi & Pajak)' } } } });
        }
        
        // FUNGSI UNTUK MENAMPILKAN ERROR VALIDASI (TETAP SAMA)
        function displayValidationErrors(errors) {
            errorMessages.forEach(el => el.innerText = '');

            for (const key in errors) {
                if (errors.hasOwnProperty(key)) {
                    // Cek error untuk input Skenario B (harga_jual & volume_penjualan)
                    const skenarioBInputId = `harga_jual_skenario_b`;
                    let errorElement = document.getElementById(`error-${key}`);
                    
                    if (key === 'harga_jual' || key === 'volume_penjualan') {
                        // Untuk skenario B, kita arahkan error ke input form B yang punya ID berbeda
                        errorElement = document.getElementById(`error-${key}`); 
                    } else {
                        errorElement = document.getElementById(`error-${key}`); 
                    }
                    
                    if (errorElement) {
                        errorElement.innerText = errors[key][0];
                    }
                }
            }
        }
        
        // FUNGSI UNTUK MENGHITUNG DAN MENAMPILKAN SKENARIO B (BARU)
        function calculateSkenarioB(formData) {
            // Tampilkan loading dan redupkan kontainer Skenario B
            loadingIndicator.classList.remove('hidden');
            skenarioBContainer.style.opacity = '0.5';
            
            // Kirim data gabungan ke Controller
            fetch(calculateUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                loadingIndicator.classList.add('hidden');
                skenarioBContainer.style.opacity = '1';

                if (data.status === 'success') {
                    const summary = data.data.summary;
                    
                    // 1. Update Header
                    document.getElementById('skenario-b-header').innerText = 'Skenario B (Revisi)';
                    
                    // 2. Buat Tabel Perbandingan Skenario
                    const tableBody = document.getElementById('comparison-table-body');
                    const rows = tableBody.querySelectorAll('tr');

                    // Data Skenario B yang akan diisi
                    const skenarioBData = {
                        'harga_jual': summary.harga_jual, 
                        'keuntungan_bersih_tahunan': summary.keuntungan_bersih_proyeksi,
                        'saldo_kas_akhir': summary.saldo_kas_akhir,
                    };
                    
                    const metrikKeys = ['harga_jual', 'keuntungan_bersih_tahunan', 'saldo_kas_akhir'];

                    rows.forEach((row, index) => {
                        const key = metrikKeys[index];
                        if (key) {
                            // Kolom ke-3 (index 2) adalah Skenario B
                            const cellB = row.cells[2];
                            // Cek apakah data tersedia (seharusnya selalu ada jika sukses)
                            const value = skenarioBData[key] !== undefined ? skenarioBData[key] : '-';
                            cellB.innerHTML = `Rp. ${value}`;
                        }
                    });

                    // Sukses! Sembunyikan form B dan tampilkan tombol lagi.
                    skenarioBContainer.classList.add('hidden');
                    showSkenarioBButton.classList.remove('hidden');
                    alert('Skenario B berhasil dihitung dan ditambahkan ke tabel perbandingan.');

                }
            })
            .catch(error => {
                console.error('Error Skenario B:', error);
                loadingIndicator.classList.add('hidden');
                skenarioBContainer.style.opacity = '1';
                
                if (error.errors) {
                    // Panggil fungsi validasi
                    displayValidationErrors(error.errors);
                    alert('Validasi Skenario B Gagal! Silakan periksa kolom yang ditandai.');
                } else {
                    alert('Terjadi kesalahan saat mengirim data skenario B ke server.');
                }
            });
        }
        
        // --- HANDLER Skenario A (Permintaan Pertama) ---
        formA.addEventListener('submit', function (e) {
            e.preventDefault();
            
            // 1. Simpan semua input Skenario A sebelum submit
            lastSkenarioAInputs = {};
            const formData = new FormData(formA);
            for (let [key, value] of formData.entries()) {
                if (key !== '_token') { 
                    lastSkenarioAInputs[key] = value;
                }
            }
            
            // ... (Kode visual loading)

            fetch(calculateUrl, {
                method: 'POST',
                body: formData,
                headers: { 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => { 
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                
                if (data.status === 'success') {
                    // ... (Semua logika update metrik, peringatan kas, dan grafik tetap sama)
                    const summary = data.data.summary;
                    const proyeksi = data.data.proyeksi_bulanan;
                    const skenario = data.data.skenario_perbandingan;
                    
                    // 1. Perbarui Ringkasan Metrik Kunci
                    document.getElementById('net-profit').innerText = `Rp. ${summary.keuntungan_bersih_proyeksi}`;
                    document.getElementById('final-cash-balance').innerText = `Rp. ${summary.saldo_kas_akhir}`; 
                    document.getElementById('bep').innerText = `${summary.titik_impas_unit} Unit`;
                    document.getElementById('payback-period').innerText = summary.waktu_balik_modal;
                    document.getElementById('last-update').innerHTML = `<strong>Diperbarui pada ${summary.diperbarui_pada}</strong>`;
                    
                    // Peringatan Kas (TETAP SAMA)
                    warningMessage.innerText = summary.cash_warning_message;
                    if (summary.cash_warning_message.includes('negatif')) {
                        warningCard.classList.remove('hidden');
                        warningCard.classList.replace('bg-yellow-100', 'bg-red-100');
                        warningCard.classList.replace('border-yellow-500', 'border-red-500');
                        warningMessage.classList.replace('text-yellow-800', 'text-red-800');
                    } else {
                        warningCard.classList.add('hidden');
                        warningCard.classList.replace('bg-red-100', 'bg-yellow-100'); 
                        warningCard.classList.replace('border-red-500', 'border-yellow-500'); 
                        warningMessage.classList.replace('text-red-800', 'text-yellow-800');
                    }
                    
                    // 2. Siapkan data untuk Grafik
                    const chartLabels = proyeksi.map(p => p.bulan);
                    const chartDataKeuntungan = proyeksi.map(p => p.keuntungan_bersih);
                    drawKeuntunganChart(chartLabels, chartDataKeuntungan); 
                    const chartDataSaldoKas = proyeksi.map(p => p.saldo_kas);
                    drawCashFlowChart(chartLabels, chartDataSaldoKas); 
                    const chartDataPendapatan = proyeksi.map(p => p.pendapatan);
                    const chartDataTotalBiaya = proyeksi.map(p => p.biaya_variabel + p.biaya_tetap + p.pajak);
                    drawCostRevenueChart(chartLabels, chartDataPendapatan, chartDataTotalBiaya);

                    // 3. Buat Tabel Perbandingan Skenario A
                    const tableBody = document.getElementById('comparison-table-body');
                    tableBody.innerHTML = ''; 
                    document.getElementById('skenario-b-header').innerText = 'Skenario Lain (Belum Dihitung)';
                    
                    const metrik = [
                        { label: 'Harga Jual per Unit (Rp)', key: 'harga_jual' },
                        { label: 'Keuntungan Bersih Tahunan (Rp)', key: 'keuntungan_bersih_tahunan' },
                        { label: 'Saldo Kas Akhir Tahun (Rp)', key: 'saldo_kas_akhir' }
                    ];

                    metrik.forEach(m => {
                        const row = tableBody.insertRow();
                        row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${m.label}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp. ${skenario.skenario_a[m.key]}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td> 
                        `;
                    });

                    // Tampilkan kartu hasil & Tombol Skenario B
                    resultsData.classList.remove('hidden');
                    showSkenarioBButton.classList.remove('hidden'); 
                }
                
                // ... (Kode visual loading akhir)
            })
            .catch(error => {
                // ... (Error handling tetap sama)
            });
        });

        // --- HANDLER UNTUK MEMUNCULKAN FORM SKENARIO B (BARU) ---
        if (showSkenarioBButton) {
            showSkenarioBButton.addEventListener('click', function() {
                // Isi form B dengan nilai A sebagai default
                document.getElementById('harga_jual_skenario_b').value = lastSkenarioAInputs.harga_jual;
                document.getElementById('volume_penjualan_skenario_b').value = lastSkenarioAInputs.volume_penjualan;

                skenarioBContainer.classList.remove('hidden');
                showSkenarioBButton.classList.add('hidden');
            });
        }
        
        // --- HANDLER UNTUK SUBMIT FORM SKENARIO B (BARU) ---
        if (formB) {
            formB.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // 1. Buat FormData dari input Skenario B (Harga Jual & Volume)
                const formBData = new FormData(formB);
                
                // 2. Gabungkan dengan semua input Skenario A yang disimpan
                const combinedData = new FormData();

                // Tambahkan semua data Skenario A (termasuk biaya tetap, CAPEX, dll.)
                for (const key in lastSkenarioAInputs) {
                    combinedData.append(key, lastSkenarioAInputs[key]);
                }
                
                // Tambahkan/timpa data Skenario B (Harga Jual dan Volume)
                combinedData.append('harga_jual', formBData.get('harga_jual'));
                combinedData.append('volume_penjualan', formBData.get('volume_penjualan'));
                // Tambahkan token CSRF
                combinedData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                
                // 3. Panggil fungsi perhitungan Skenario B
                calculateSkenarioB(combinedData);
            });
        }

    } 
});