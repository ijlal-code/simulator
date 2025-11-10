// Baris ini mungkin sudah ada di app.js Anda.
import './bootstrap';

// Kita bungkus semua logika simulator di sini
document.addEventListener('DOMContentLoaded', function () {
    
    const form = document.getElementById('simulatorForm');

    // MODIFIKASI 1: Cek apakah form simulator ada di halaman ini
    if (form) {
        
        // MODIFIKASI 2: Ambil URL dari atribut 'data-calculate-url' di form
        const calculateUrl = form.dataset.calculateUrl; 
        
        // --- Mulai dari sini, ini adalah kode yang Anda berikan ---
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

        // Sinkronisasi Slider (Pastikan id 'tingkat_pertumbuhan' ada)
        const slider = document.getElementById('tingkat_pertumbuhan');
        if (slider) {
            slider.addEventListener('input', function() {
                document.getElementById('pertumbuhan_val').innerText = this.value + '%';
            });
        }
        
        // Tampilkan langkah 1 saat inisialisasi
        showStep(currentStep);

        // Deklarasi variabel global untuk Chart agar bisa dihancurkan/dibuat ulang
        let keuntunganChartInstance = null;
        
        // FUNGSI UNTUK MENGGAMBAR GRAFIK
        function drawChart(labels, data) {
            const ctx = document.getElementById('keuntunganChart').getContext('2d');

            // Hancurkan instance chart lama jika ada
            if (keuntunganChartInstance) {
                keuntunganChartInstance.destroy();
            }

            // 'Chart' akan terbaca secara global karena Anda memuatnya dari CDN di <head>
            keuntunganChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels, // Label: Bulan 1, Bulan 2, ...
                    datasets: [{
                        label: 'Keuntungan Bersih (Rp)',
                        data: data, // Data: [1000000, 1050000, ...]
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
                                text: 'Keuntungan Bersih (Rp)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Proyeksi Keuntungan Bersih Bulanan'
                        }
                    }
                }
            });
        }
        
        // --- FUNGSI SIMULASI BATCH (AJAX) ---
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            
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
                    // Script ini membaca CSRF Token dari <meta> tag di <head>
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    // Tangani error validasi (422) atau server error (500)
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
                    document.getElementById('bep').innerText = `${summary.titik_impas_unit} Unit`;
                    document.getElementById('payback-period').innerText = summary.waktu_balik_modal;
                    document.getElementById('last-update').innerHTML = `<strong>Diperbarui pada ${summary.diperbarui_pada}</strong>`;
                    
                    // 2. Siapkan data untuk Grafik
                    const chartLabels = proyeksi.map(p => p.bulan);
                    const chartData = proyeksi.map(p => p.keuntungan_bersih);
                    drawChart(chartLabels, chartData);

                    // 3. Buat Tabel Perbandingan Skenario
                    const tableBody = document.getElementById('comparison-table-body');
                    tableBody.innerHTML = ''; // Kosongkan tabel
                    
                    const metrik = [
                        { label: 'Harga Jual per Unit (Rp)', key: 'harga_jual' },
                        { label: 'Keuntungan Bersih Tahunan (Rp)', key: 'keuntungan_bersih_tahunan' }
                    ];

                    metrik.forEach(m => {
                        const row = tableBody.insertRow();
                        row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${m.label}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp. ${skenario.skenario_a[m.key]}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp. ${skenario.skenario_b[m.key]}</td>
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
                    let errorMessages = Object.values(error.errors).map(e => e.join('\n')).join('\n');
                    alert('Validasi Gagal:\n' + errorMessages);
                } else {
                    alert('Terjadi kesalahan saat mengirim data ke server.');
                }
            });
        });

    } // --- Tutup dari 'if (form)' ---
});