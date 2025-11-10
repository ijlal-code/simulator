// Baris ini mungkin sudah ada di app.js Anda.
import './bootstrap';

// Kita bungkus semua logika simulator di sini
document.addEventListener('DOMContentLoaded', function () {
    
    const form = document.getElementById('simulatorForm');

    if (form) {
        
        const calculateUrl = form.dataset.calculateUrl; 
        
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        const stepNavButtons = document.querySelectorAll('.step-nav-button');
        const resultsData = document.getElementById('results-data');
        const loadingIndicator = document.getElementById('loadingIndicator');
        // Ambil semua elemen untuk pesan error
        const errorMessages = document.querySelectorAll('.error-message');


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

        // Sinkronisasi Slider Pertumbuhan
        const slider = document.getElementById('tingkat_pertumbuhan');
        if (slider) {
            slider.addEventListener('input', function() {
                document.getElementById('pertumbuhan_val').innerText = this.value + '%';
            });
        }
        
        // Sinkronisasi Slider Inflasi (BARU)
        const inflasiSlider = document.getElementById('inflasi_biaya');
        if (inflasiSlider) {
            inflasiSlider.addEventListener('input', function() {
                document.getElementById('inflasi_val').innerText = this.value + '%';
            });
        }
        
        // Tampilkan langkah 1 saat inisialisasi
        showStep(currentStep);

        // Deklarasi variabel global untuk Chart
        let keuntunganChartInstance = null;
        let cashFlowChartInstance = null;
        let costRevenueChartInstance = null;
        
        // FUNGSI UNTUK MENGGAMBAR GRAFIK KEUNTUNGAN
        function drawKeuntunganChart(labels, data) {
            const ctx = document.getElementById('keuntunganChart').getContext('2d');

            if (keuntunganChartInstance) {
                keuntunganChartInstance.destroy();
            }

            keuntunganChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels, 
                    datasets: [{
                        label: 'Laba Bersih Setelah Pajak (Rp)',
                        data: data, 
                        backgroundColor: 'rgba(52, 211, 153, 0.8)', // Green
                        borderColor: 'rgba(52, 211, 153, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Laba Bersih (Rp)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Proyeksi Laba Bersih Bulanan (Setelah Pajak)'
                        }
                    }
                }
            });
        }

        // FUNGSI UNTUK MENGGAMBAR GRAFIK ARUS KAS (BARU)
        function drawCashFlowChart(labels, data) {
            const ctx = document.getElementById('cashFlowChart').getContext('2d');

            if (cashFlowChartInstance) {
                cashFlowChartInstance.destroy();
            }

            cashFlowChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Saldo Kas Bulanan (Rp)',
                        data: data,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)', // Teal
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true, // Area di bawah garis
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            title: {
                                display: true,
                                text: 'Saldo Kas (Rp)'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Proyeksi Saldo Arus Kas Bulanan (Memperhitungkan CAPEX & Modal Kerja)'
                        }
                    }
                }
            });
        }

        // FUNGSI UNTUK MENGGAMBAR GRAFIK PENDAPATAN VS BIAYA (BARU)
        function drawCostRevenueChart(labels, pendapatanData, biayaTotalData) {
            const ctx = document.getElementById('costRevenueChart').getContext('2d');

            if (costRevenueChartInstance) {
                costRevenueChartInstance.destroy();
            }

            costRevenueChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Pendapatan (Rp)',
                            data: pendapatanData,
                            backgroundColor: 'rgba(59, 130, 246, 0.5)', // Blue
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: false,
                        },
                        {
                            label: 'Total Biaya (Variabel + Tetap + Pajak) (Rp)',
                            data: biayaTotalData,
                            backgroundColor: 'rgba(239, 68, 68, 0.5)', // Red
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah (Rp)'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Pendapatan vs. Total Biaya Bulanan (Termasuk Inflasi & Pajak)'
                        }
                    }
                }
            });
        }
        
        // FUNGSI UNTUK MENAMPILKAN ERROR VALIDASI (BARU)
        function displayValidationErrors(errors) {
            // Hapus semua pesan error yang ada
            errorMessages.forEach(el => el.innerText = '');

            // Loop melalui errors dari respons Laravel
            for (const key in errors) {
                if (errors.hasOwnProperty(key)) {
                    const errorElement = document.getElementById(`error-${key}`);
                    if (errorElement) {
                        // Tampilkan pesan error pertama untuk field tersebut
                        errorElement.innerText = errors[key][0];
                    }
                }
            }
        }
        
        // --- FUNGSI SIMULASI BATCH (AJAX) ---
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            
            // Hapus error sebelumnya
            errorMessages.forEach(el => el.innerText = '');

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
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                // Sembunyikan loading dan tampilkan data baru
                loadingIndicator.classList.add('hidden');
                resultsContainer.style.opacity = '1';

                if (data.status === 'success') {
                    const summary = data.data.summary;
                    const proyeksi = data.data.proyeksi_bulanan;
                    const skenario = data.data.skenario_perbandingan;
                    
                    // 1. Perbarui Ringkasan Metrik Kunci
                    document.getElementById('net-profit').innerText = `Rp. ${summary.keuntungan_bersih_proyeksi}`;
                    document.getElementById('final-cash-balance').innerText = `Rp. ${summary.saldo_kas_akhir}`; // BARU
                    document.getElementById('bep').innerText = `${summary.titik_impas_unit} Unit`;
                    document.getElementById('payback-period').innerText = summary.waktu_balik_modal;
                    document.getElementById('last-update').innerHTML = `<strong>Diperbarui pada ${summary.diperbarui_pada}</strong>`;
                    
                    // 2. Siapkan data untuk Grafik
                    const chartLabels = proyeksi.map(p => p.bulan);
                    
                    // Data Keuntungan Bersih (Laba Setelah Pajak)
                    const chartDataKeuntungan = proyeksi.map(p => p.keuntungan_bersih);
                    drawKeuntunganChart(chartLabels, chartDataKeuntungan); 

                    // Data Saldo Kas
                    const chartDataSaldoKas = proyeksi.map(p => p.saldo_kas);
                    drawCashFlowChart(chartLabels, chartDataSaldoKas); 

                    // Data Pendapatan dan Total Biaya
                    const chartDataPendapatan = proyeksi.map(p => p.pendapatan);
                    // Total Biaya = Biaya Variabel + Biaya Tetap + Pajak
                    const chartDataTotalBiaya = proyeksi.map(p => p.biaya_variabel + p.biaya_tetap + p.pajak);
                    drawCostRevenueChart(chartLabels, chartDataPendapatan, chartDataTotalBiaya);


                    // 3. Buat Tabel Perbandingan Skenario
                    const tableBody = document.getElementById('comparison-table-body');
                    tableBody.innerHTML = ''; 
                    
                    // Perbarui header Skenario B
                    document.getElementById('skenario-b-header').innerText = 'Skenario Lain (Belum Dihitung)';
                    
                    const metrik = [
                        { label: 'Harga Jual per Unit (Rp)', key: 'harga_jual' },
                        { label: 'Keuntungan Bersih Tahunan (Rp)', key: 'keuntungan_bersih_tahunan' },
                        { label: 'Saldo Kas Akhir Tahun (Rp)', key: 'saldo_kas_akhir' }
                    ];

                    metrik.forEach(m => {
                        const row = tableBody.insertRow();
                        // Skenario B diisi "-" 
                        row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${m.label}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp. ${skenario.skenario_a[m.key]}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td> 
                        `;
                    });

                    // Tampilkan kartu hasil
                    resultsData.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                loadingIndicator.classList.add('hidden');
                resultsContainer.style.opacity = '1';
                
                // Menampilkan error validasi dari Laravel
                if (error.errors) {
                    displayValidationErrors(error.errors);
                    // Pindah ke tab yang berisi error pertama
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

    } 
});