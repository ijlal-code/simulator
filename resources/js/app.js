// Baris ini mungkin sudah ada di app.js Anda.
import './bootstrap';

// Kita bungkus semua logika simulator di sini
document.addEventListener('DOMContentLoaded', function () {
    
    // PENTING: AMBIL STATUS LOGIN DARI BLADE
    const isLoggedIn = window.isLoggedIn; 
    
    const formA = document.getElementById('simulatorForm'); 
    const formB = document.getElementById('skenarioBForm'); 
    const skenarioBContainer = document.getElementById('skenarioBInputsContainer');
    const showSkenarioBButton = document.getElementById('showSkenarioBForm');
    
    // Elemen Save/Load
    const saveButton = document.getElementById('saveScenarioButton');
    const loadButton = document.getElementById('loadScenarioButton');
    const saveModal = document.getElementById('saveModal');
    const loadModal = document.getElementById('loadModal');
    const saveForm = document.getElementById('saveForm');
    const closeSaveModal = document.getElementById('closeSaveModal');
    const closeLoadModal = document.getElementById('closeLoadModal');
    const savedScenariosDropdown = document.getElementById('savedScenariosDropdown');
    const executeLoadButton = document.getElementById('executeLoadButton');
    const loadingLoad = document.getElementById('loadingLoad');
    const noScenariosMessage = document.getElementById('noScenariosMessage'); 

    // Elemen Umum
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    const stepNavButtons = document.querySelectorAll('.step-nav-button');
    const resultsData = document.getElementById('results-data');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const errorMessages = document.querySelectorAll('.error-message');
    const warningCard = document.getElementById('cash-warning-card');
    const warningMessage = document.getElementById('cash-warning-message');

    const currencyFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    });
    const numberFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

    const sanitizeNumber = (value) => {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        if (typeof value === 'number') {
            return value;
        }

        const parsed = Number(value.toString().replace(/[^0-9-]/g, ''));
        return isNaN(parsed) ? null : parsed;
    };

    const formatCurrency = (value) => {
        const numeric = sanitizeNumber(value);
        return numeric === null ? '-' : currencyFormatter.format(numeric);
    };

    const formatNumber = (value) => {
        const numeric = sanitizeNumber(value);
        return numeric === null ? '-' : numberFormatter.format(numeric);
    };

    // Variabel state
    let lastSkenarioAInputs = {};
    let keuntunganChartInstance = null;
    let cashFlowChartInstance = null;
    let costRevenueChartInstance = null;
    let currentStep = 1;


    // --- FUNGSI NAVIGASI & SLIDER ---

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
    showStep(1); // Mulai dari langkah 1


    // --- FUNGSI CHART & VALIDASI ---
    
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
    
    function fillForm(data) {
        for (const key in data) {
            const input = document.getElementById(key);
            if (input) {
                input.value = data[key];
                
                if (key === 'tingkat_pertumbuhan') {
                    document.getElementById('pertumbuhan_val').innerText = data[key] + '%';
                }
                if (key === 'inflasi_biaya') {
                    document.getElementById('inflasi_val').innerText = data[key] + '%';
                }
            }
        }
        alert("Skenario berhasil dimuat ke dalam form.");
        loadModal.classList.add('hidden');
        showStep(1); 
    }
    
    function displayValidationErrors(errors) {
        errorMessages.forEach(el => el.innerText = '');

        for (const key in errors) {
            if (errors.hasOwnProperty(key)) {
                let errorElement = document.getElementById(`error-${key}`);
                
                // Logic untuk mengarahkan pesan error ke input yang benar (Skenario A/B atau Modal Simpan)
                if (key === 'harga_jual') {
                    errorElement = document.getElementById('skenarioBInputsContainer') && !document.getElementById('skenarioBInputsContainer').classList.contains('hidden') 
                        ? document.getElementById(`error-harga_jual_skenario_b`)
                        : document.getElementById(`error-${key}`);
                } else if (key === 'volume_penjualan') {
                    errorElement = document.getElementById('skenarioBInputsContainer') && !document.getElementById('skenarioBInputsContainer').classList.contains('hidden') 
                        ? document.getElementById(`error-volume_penjualan_skenario_b`)
                        : document.getElementById(`error-${key}`);
                } else if (key === 'nama_skenario') {
                    errorElement = document.getElementById(`error-${key}`);
                }
                
                if (errorElement) {
                    errorElement.innerText = errors[key][0];
                }
            }
        }
    }
    
    function calculateSkenarioB(formData) {
        const calculateUrl = formA.dataset.calculateUrl; 

        loadingIndicator.classList.remove('hidden');
        skenarioBContainer.style.opacity = '0.5';
        
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
                
                document.getElementById('skenario-b-header').innerText = 'Skenario B (Revisi)';
                
                const tableBody = document.getElementById('comparison-table-body');
                const rows = tableBody.querySelectorAll('tr');

                const hargaJualSkenarioB = document.getElementById('harga_jual_skenario_b').value;

                const skenarioBData = {
                    'harga_jual': hargaJualSkenarioB,
                    'keuntungan_bersih_tahunan': summary.keuntungan_bersih_proyeksi,
                    'saldo_kas_akhir': summary.saldo_kas_akhir,
                };
                
                const metrikKeys = ['harga_jual', 'keuntungan_bersih_tahunan', 'saldo_kas_akhir'];

                rows.forEach((row, index) => {
                    const key = metrikKeys[index];
                    if (key) {
                        const cellB = row.cells[2];
                        const value = skenarioBData[key];
                        cellB.innerText = value === undefined ? '-' : formatCurrency(value);
                    }
                });

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
                displayValidationErrors(error.errors);
                alert('Validasi Skenario B Gagal! Silakan periksa kolom yang ditandai.');
            } else {
                alert('Terjadi kesalahan saat mengirim data skenario B ke server.');
            }
        });
    }


    // --- LOGIKA UTAMA & EVENT LISTENERS ---

    if (formA) {
        
        const calculateUrl = formA.dataset.calculateUrl; 
        
        // Cek apakah elemen ada sebelum mencoba mengakses properti data-url
        const saveUrl = saveButton ? saveButton.dataset.saveUrl : null;
        const listUrl = loadButton ? loadButton.dataset.listUrl : null;
        const loadBaseUrl = executeLoadButton.dataset.loadUrl;


        // --- HANDLER Skenario A (Perhitungan) ---
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
            
            errorMessages.forEach(el => el.innerText = '');
            const resultsContainer = document.getElementById('projection-results');

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
            .then(response => { 
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                loadingIndicator.classList.add('hidden');
                resultsContainer.style.opacity = '1';
                
                if (data.status === 'success') {
                    const summary = data.data.summary;
                    const proyeksi = data.data.proyeksi_bulanan;
                    const skenario = data.data.skenario_perbandingan;
                    
                    // 1. Perbarui Ringkasan Metrik Kunci dan Peringatan Kas
                    document.getElementById('net-profit').innerText = formatCurrency(summary.keuntungan_bersih_proyeksi);
                    document.getElementById('final-cash-balance').innerText = formatCurrency(summary.saldo_kas_akhir);
                    document.getElementById('bep').innerText = `${formatNumber(summary.titik_impas_unit)} Unit`;
                    document.getElementById('payback-period').innerText = summary.waktu_balik_modal;
                    document.getElementById('last-update').innerHTML = `<strong>Diperbarui pada ${summary.diperbarui_pada}</strong>`;
                    
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
                    
                    // 2. Siapkan data dan Logika gambar grafik
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatCurrency(skenario.skenario_a[m.key])}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td> 
                        `;
                    });

                    // Tampilkan kartu hasil & Tombol Skenario B
                    resultsData.classList.remove('hidden');
                    showSkenarioBButton.classList.remove('hidden'); 
                    
                    // AKTIFKAN TOMBOL SIMPAN JIKA USER SUDAH LOGIN
                    if (isLoggedIn && saveButton) { 
                         saveButton.disabled = false; 
                    }
                }
            })
            .catch(error => {
                console.error('Error Calculate:', error);
                loadingIndicator.classList.add('hidden');
                resultsContainer.style.opacity = '1';
                
                if (error.errors) {
                    displayValidationErrors(error.errors);
                    const firstErrorField = Object.keys(error.errors)[0];
                    const firstErrorInput = document.getElementById(firstErrorField);
                    if(firstErrorInput) {
                        const stepContainer = firstErrorInput.closest('.tab-content');
                        if (stepContainer) {
                            const stepNumber = stepContainer.id.replace('step-', '');
                            showStep(parseInt(stepNumber));
                        }
                    }
                    alert('Validasi Gagal! Silakan periksa kolom yang ditandai.');
                } else {
                    alert('Terjadi kesalahan saat mengirim data ke server.');
                }
            });
        });

        // --- HANDLER MODAL SIMPAN (Hanya aktif jika login) ---
        if (isLoggedIn && saveButton) {
            const saveUrl = saveButton.dataset.saveUrl;

            saveButton.addEventListener('click', function() {
                saveModal.classList.remove('hidden');
                document.getElementById('nama_skenario').focus();
            });
            closeSaveModal.addEventListener('click', function() {
                saveModal.classList.add('hidden');
            });
            
            saveForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const finalSaveData = new FormData();
                for (const key in lastSkenarioAInputs) {
                    finalSaveData.append(key, lastSkenarioAInputs[key]);
                }
                finalSaveData.append('nama_skenario', document.getElementById('nama_skenario').value);
                finalSaveData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                fetch(saveUrl, {
                    method: 'POST',
                    body: finalSaveData,
                    headers: { 'Accept': 'application/json' }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw err; });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        saveModal.classList.add('hidden');
                        document.getElementById('nama_skenario').value = '';
                        saveButton.disabled = true; 
                    }
                })
                .catch(error => {
                    console.error('Error Save:', error);
                    if (error.errors) {
                        displayValidationErrors(error.errors);
                    } else {
                        alert('Gagal menyimpan skenario ke server.');
                    }
                });
            });
        }


        // --- HANDLER MODAL MUAT (Hanya aktif jika login) ---
        if (isLoggedIn && loadButton) {
            const listUrl = loadButton.dataset.listUrl;
            const loadBaseUrl = executeLoadButton.dataset.loadUrl;

            loadButton.addEventListener('click', function() {
                loadModal.classList.remove('hidden');
                fetchSavedScenarios();
            });
            closeLoadModal.addEventListener('click', function() {
                loadModal.classList.add('hidden');
            });
            
            function fetchSavedScenarios() {
                loadingLoad.classList.remove('hidden');
                noScenariosMessage.classList.add('hidden'); 
                executeLoadButton.disabled = true;
                savedScenariosDropdown.innerHTML = '<option value="">-- Pilih Skenario --</option>';

                fetch(listUrl, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => {
                    loadingLoad.classList.add('hidden');
                    if (data.status === 'success' && data.data.length > 0) {
                        data.data.forEach(sim => {
                            const option = document.createElement('option');
                            option.value = sim.id;
                            option.innerText = `${sim.nama_skenario} (${new Date(sim.created_at).toLocaleDateString()})`;
                            savedScenariosDropdown.appendChild(option);
                        });
                    } else {
                         noScenariosMessage.classList.remove('hidden'); 
                         savedScenariosDropdown.innerHTML = '<option value="">-- Tidak ada skenario tersimpan --</option>';
                    }
                })
                .catch(err => {
                    loadingLoad.classList.add('hidden');
                    console.error('Failed to fetch list:', err);
                    alert('Gagal memuat daftar skenario.');
                });
            }
            
            savedScenariosDropdown.addEventListener('change', function() {
                executeLoadButton.disabled = !this.value;
            });

            executeLoadButton.addEventListener('click', function() {
                const simulationId = savedScenariosDropdown.value;
                if (!simulationId) return;

                const loadSpecificUrl = `${loadBaseUrl}/${simulationId}`;

                loadingLoad.classList.remove('hidden');
                executeLoadButton.disabled = true;

                fetch(loadSpecificUrl, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        fillForm(data.data);
                        resultsData.classList.add('hidden');
                        saveButton.disabled = false; // Aktifkan lagi tombol simpan setelah form diisi
                    } else {
                        alert(data.message);
                    }
                    loadingLoad.classList.add('hidden');
                    executeLoadButton.disabled = false;
                })
                .catch(err => {
                    console.error('Error Load:', err);
                    loadingLoad.classList.add('hidden');
                    executeLoadButton.disabled = false;
                    alert('Terjadi kesalahan saat memuat data skenario.');
                });
            });
        }
        
        // --- HANDLER SKENARIO B ---
        if (showSkenarioBButton) {
            showSkenarioBButton.addEventListener('click', function() {
                // Logika tampil form Skenario B
                document.getElementById('harga_jual_skenario_b').value = lastSkenarioAInputs.harga_jual;
                document.getElementById('volume_penjualan_skenario_b').value = lastSkenarioAInputs.volume_penjualan;
                skenarioBContainer.classList.remove('hidden');
                showSkenarioBButton.classList.add('hidden');
            });
        }
        
        if (formB) {
            formB.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // 1. Buat FormData dari input Skenario B (Harga Jual & Volume)
                const formBData = new FormData(formB);
                
                // 2. Gabungkan dengan semua input Skenario A yang disimpan
                const combinedData = new FormData();

                for (const key in lastSkenarioAInputs) {
                    combinedData.append(key, lastSkenarioAInputs[key]);
                }
                
                combinedData.append('harga_jual', formBData.get('harga_jual'));
                combinedData.append('volume_penjualan', formBData.get('volume_penjualan'));
                combinedData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                
                // 3. Panggil fungsi perhitungan Skenario B
                calculateSkenarioB(combinedData);
            });
        }

    } 
});