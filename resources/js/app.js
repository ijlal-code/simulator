import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const state = {
        isLoggedIn: Boolean(window.isLoggedIn),
        lastInputs: {},
        hasLatestResult: false,
        charts: {
            keuntungan: null,
            cashFlow: null,
            costRevenue: null,
            cashComponent: null,
        },
    };

    const dom = {
        formA: document.getElementById('simulatorForm'),
        formB: document.getElementById('skenarioBForm'),
        skenarioBContainer: document.getElementById('skenarioBInputsContainer'),
        showSkenarioBButton: document.getElementById('showSkenarioBForm'),
        closeSkenarioBButton: document.getElementById('closeSkenarioBForm'),
        skenarioBMessage: document.getElementById('skenario-b-message'),
        saveButton: document.getElementById('saveScenarioButton'),
        loadButton: document.getElementById('loadScenarioButton'),
        loadModal: document.getElementById('loadModal'),
        saveCard: document.getElementById('saveCard'),
        saveForm: document.getElementById('saveForm'),
        cancelSaveCard: document.getElementById('cancelSaveCard'),
        closeSaveCard: document.getElementById('closeSaveCard'),
        saveCardDismissArea: document.querySelector('[data-save-card-dismiss]'),
        namaSkenarioInput: document.getElementById('nama_skenario'),
        saveSummaryFields: document.querySelectorAll('[data-save-summary]'),
        inputSummaryFields: document.querySelectorAll('[data-input-summary]'),
        savedScenariosDropdown: document.getElementById('savedScenariosDropdown'),
        executeLoadButton: document.getElementById('executeLoadButton'),
        closeLoadModal: document.getElementById('closeLoadModal'),
        loadingLoad: document.getElementById('loadingLoad'),
        noScenariosMessage: document.getElementById('noScenariosMessage'),
        tabButtons: document.querySelectorAll('.tab-button'),
        tabContents: document.querySelectorAll('.tab-content'),
        stepNavButtons: document.querySelectorAll('.step-nav-button'),
        resultsData: document.getElementById('results-data'),
        resultsContainer: document.getElementById('projection-results'),
        loadingIndicator: document.getElementById('loadingIndicator'),
        warningCard: document.getElementById('cash-warning-card'),
        warningMessage: document.getElementById('cash-warning-message'),
        errorMessages: document.querySelectorAll('.error-message'),
        sliderPrice: document.getElementById('kenaikan_harga_jual_tahunan'),
        sliderInflasiCogs: document.getElementById('inflasi_cogs_tahunan'),
        sliderInflasiTetap: document.getElementById('inflasi_biaya_tetap_tahunan'),
        ebitdaField: document.getElementById('ebitda-year1'),
        grossMarginField: document.getElementById('gross-margin'),
        netMarginField: document.getElementById('net-margin'),
        debtToEquityField: document.getElementById('debt-to-equity'),
        interestCoverageField: document.getElementById('interest-coverage'),
        cfoYear1Field: document.getElementById('cfo-year1'),
        cfiYear1Field: document.getElementById('cfi-year1'),
        cffYear1Field: document.getElementById('cff-year1'),
        projectionDurationLabel: document.getElementById('projection-duration-label'),
        yearlyProjectionBody: document.getElementById('yearly-projection-body'),
        saveStatusMessage: document.getElementById('saveStatusMessage'),
    };

    if (!dom.formA) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const numberFormatter = new Intl.NumberFormat('id-ID');
    const setHidden = (element, hidden) => {
        if (!element) return;
        element.classList.toggle('hidden', hidden);
    };
    const disableElement = (element, disabled) => {
        if (element) {
            element.disabled = disabled;
        }
    };
    const formatRupiah = (value) => `Rp. ${numberFormatter.format(Math.round(Number(value) || 0))}`;
    const formatUnit = (value) => `${numberFormatter.format(Math.round(Number(value) || 0))} Unit`;
    const formatPercent = (value) => `${numberFormatter.format(Number(value) || 0)}%`;
    const formatYears = (value) => `${numberFormatter.format(Number(value) || 0)} Tahun`;
    const formatMonths = (value) => `${numberFormatter.format(Number(value) || 0)} Bulan`;
    const formatDays = (value) => `${numberFormatter.format(Math.round(Number(value) || 0))} Hari`;
    const formatPeople = (value) => `${numberFormatter.format(Math.round(Number(value) || 0))} Orang`;
    const setSkenarioBMessage = (message = '', variant = 'info') => {
        if (!dom.skenarioBMessage) return;
        const colorClasses = ['text-emerald-600', 'text-red-600', 'text-indigo-600'];
        dom.skenarioBMessage.classList.remove(...colorClasses);
        if (!message) {
            dom.skenarioBMessage.classList.add('hidden');
            dom.skenarioBMessage.innerText = '';
            return;
        }
        const variantMap = {
            success: 'text-emerald-600',
            error: 'text-red-600',
            info: 'text-indigo-600',
        };
        dom.skenarioBMessage.classList.remove('hidden');
        dom.skenarioBMessage.classList.add(variantMap[variant] || variantMap.info);
        dom.skenarioBMessage.innerText = message;
    };

    const showStep = (step) => {
        dom.tabContents.forEach((content) => content.classList.remove('active'));
        dom.tabButtons.forEach((button) => {
            button.classList.remove('text-blue-600', 'border-blue-600');
            button.classList.add('text-gray-500', 'border-transparent');
        });

        const activeTab = document.getElementById(`step-${step}`);
        const activeButton = document.querySelector(`.tab-button[data-step="${step}"]`);

        if (activeTab) activeTab.classList.add('active');
        if (activeButton) {
            activeButton.classList.remove('text-gray-500', 'border-transparent');
            activeButton.classList.add('text-blue-600', 'border-blue-600');
        }
    };

    const updateSliderPreview = (slider, targetId) => {
        if (!slider) return;
        const preview = document.getElementById(targetId);
        const updateText = () => {
            if (preview) {
                preview.innerText = `${slider.value}%`;
            }
        };
        slider.addEventListener('input', updateText);
        updateText();
    };

    const clearValidationErrors = () => {
        dom.errorMessages.forEach((el) => (el.innerText = ''));
    };

    const displayValidationErrors = (errors) => {
        clearValidationErrors();
        Object.entries(errors).forEach(([field, messages]) => {
            let target = document.getElementById(`error-${field}`);

            if (field === 'harga_jual' && dom.skenarioBContainer && !dom.skenarioBContainer.classList.contains('hidden')) {
                target = document.getElementById('error-harga_jual_skenario_b');
            }
            if (field === 'anggaran_marketing' && dom.skenarioBContainer && !dom.skenarioBContainer.classList.contains('hidden')) {
                target = document.getElementById('error-anggaran_marketing_skenario_b');
            }

            if (target) {
                target.innerText = messages[0];
            }
        });
    };

    const destroyChart = (chartInstance) => {
        if (chartInstance) {
            chartInstance.destroy();
        }
    };

    const drawKeuntunganChart = (labels, data) => {
        const ctx = document.getElementById('keuntunganChart')?.getContext('2d');
        if (!ctx) return;
        destroyChart(state.charts.keuntungan);
        state.charts.keuntungan = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Laba Bersih Setelah Pajak (Rp)',
                        data,
                        backgroundColor: 'rgba(52, 211, 153, 0.8)',
                        borderColor: 'rgba(52, 211, 153, 1)',
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Laba Bersih (Rp)' },
                    },
                },
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Proyeksi Laba Bersih Bulanan (Setelah Pajak)' },
                },
            },
        });
    };

    const drawCashFlowChart = (labels, data) => {
        const ctx = document.getElementById('cashFlowChart')?.getContext('2d');
        if (!ctx) return;
        destroyChart(state.charts.cashFlow);
        state.charts.cashFlow = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Saldo Kas Bulanan (Rp)',
                        data,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: { title: { display: true, text: 'Saldo Kas (Rp)' } },
                },
                plugins: {
                    title: { display: true, text: 'Proyeksi Saldo Arus Kas Bulanan (Memperhitungkan CAPEX & Modal Kerja)' },
                },
            },
        });
    };

    const drawCostRevenueChart = (labels, pendapatanData, biayaTotalData) => {
        const ctx = document.getElementById('costRevenueChart')?.getContext('2d');
        if (!ctx) return;
        destroyChart(state.charts.costRevenue);
        state.charts.costRevenue = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Pendapatan (Rp)',
                        data: pendapatanData,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: false,
                    },
                    {
                        label: 'Total Biaya (Variabel + Tetap + Pajak) (Rp)',
                        data: biayaTotalData,
                        backgroundColor: 'rgba(239, 68, 68, 0.5)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Jumlah (Rp)' },
                    },
                },
                plugins: {
                    title: { display: true, text: 'Pendapatan vs. Total Biaya Bulanan (Termasuk Inflasi & Pajak)' },
                },
            },
        });
    };

    const drawCashComponentChart = (labels, cfoData, cfiData, cffData) => {
        const ctx = document.getElementById('cashComponentChart')?.getContext('2d');
        if (!ctx) return;
        destroyChart(state.charts.cashComponent);
        state.charts.cashComponent = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'CFO',
                        data: cfoData,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    },
                    {
                        label: 'CFI',
                        data: cfiData,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    },
                    {
                        label: 'CFF',
                        data: cffData,
                        backgroundColor: 'rgba(99, 102, 241, 0.7)',
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: { title: { display: true, text: 'Arus Kas (Rp)' } },
                },
                plugins: {
                    title: { display: true, text: 'Komponen Arus Kas (Tahun 1)' },
                },
            },
        });
    };

    const updateWarningCard = (message) => {
        if (!dom.warningCard || !dom.warningMessage) return;
        dom.warningMessage.innerText = message;
        if (message.includes('negatif')) {
            dom.warningCard.classList.remove('hidden');
            dom.warningCard.classList.replace('bg-yellow-100', 'bg-red-100');
            dom.warningCard.classList.replace('border-yellow-500', 'border-red-500');
            dom.warningMessage.classList.replace('text-yellow-800', 'text-red-800');
        } else {
            dom.warningCard.classList.add('hidden');
            dom.warningCard.classList.replace('bg-red-100', 'bg-yellow-100');
            dom.warningCard.classList.replace('border-red-500', 'border-yellow-500');
            dom.warningMessage.classList.replace('text-red-800', 'text-yellow-800');
        }
    };

    const buildComparisonTable = (skenario) => {
        const tableBody = document.getElementById('comparison-table-body');
        const header = document.getElementById('skenario-b-header');
        if (!tableBody || !header) return;

        tableBody.innerHTML = '';
        header.innerText = 'Skenario Lain (Belum Dihitung)';

        const rows = [
            { label: 'Harga Jual per Unit (Rp)', key: 'harga_jual' },
            { label: 'Keuntungan Bersih Tahunan (Rp)', key: 'keuntungan_bersih_tahunan' },
            { label: 'Saldo Kas Akhir Proyeksi (Rp)', key: 'saldo_kas_akhir' },
        ];

        rows.forEach((row) => {
            const tr = tableBody.insertRow();
            const baseValue = skenario?.skenario_a?.[row.key] ?? 0;
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${row.label}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatRupiah(baseValue)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
            `;
        });
    };

    const updateSaveSummary = () => {
        const formatters = {
            harga_jual: formatRupiah,
            modal_kerja: formatRupiah,
            capex: formatRupiah,
            anggaran_marketing: formatRupiah,
            kapasitas_bulanan: formatUnit,
        };
        dom.saveSummaryFields.forEach((field) => {
            const key = field.dataset.saveSummary;
            const value = state.lastInputs[key];
            field.innerText = formatters[key] ? formatters[key](value) : numberFormatter.format(Number(value) || 0);
        });
    };

    const updateInputSummary = () => {
        const formatterMap = {
            harga_jual: formatRupiah,
            anggaran_marketing: formatRupiah,
            biaya_per_lead: formatRupiah,
            tingkat_konversi: formatPercent,
            kapasitas_bulanan: formatUnit,
            modal_kerja: formatRupiah,
            modal_disetor_pemilik: formatRupiah,
            capex: formatRupiah,
            cogs: formatRupiah,
            jumlah_karyawan: formatPeople,
            gaji_per_karyawan: formatRupiah,
            jumlah_pinjaman: formatRupiah,
            bunga_pinjaman_tahunan: formatPercent,
            tenor_pinjaman_bulan: formatMonths,
            masa_manfaat_aset_tahun: formatYears,
            kenaikan_harga_jual_tahunan: formatPercent,
            inflasi_cogs_tahunan: formatPercent,
            inflasi_biaya_tetap_tahunan: formatPercent,
            durasi_proyeksi_tahun: formatYears,
            tarif_pajak: formatPercent,
            hari_piutang: formatDays,
            hari_utang_usaha: formatDays,
        };

        dom.inputSummaryFields?.forEach((field) => {
            const key = field.dataset.inputSummary;
            const rawValue = state.lastInputs[key];
            const formatter = formatterMap[key] || ((val) => numberFormatter.format(Number(val) || 0));
            field.innerText = rawValue !== undefined && rawValue !== '' ? formatter(rawValue) : '-';
        });
    };

    const setSaveStatusMessage = (message = '', variant = 'info') => {
        if (!dom.saveStatusMessage) return;
        const colorMap = {
            success: 'text-emerald-600',
            error: 'text-red-600',
            info: 'text-blue-600',
        };

        dom.saveStatusMessage.classList.remove('text-emerald-600', 'text-red-600', 'text-blue-600');

        if (!message) {
            dom.saveStatusMessage.classList.add('hidden');
            dom.saveStatusMessage.innerText = '';
            return;
        }

        dom.saveStatusMessage.classList.remove('hidden');
        dom.saveStatusMessage.classList.add(colorMap[variant] || colorMap.info);
        dom.saveStatusMessage.innerText = message;
    };

    const openSaveCard = () => {
        if (!state.hasLatestResult) {
            alert('Jalankan simulasi terlebih dahulu sebelum menyimpan skenario.');
            return;
        }
        updateSaveSummary();
        setSaveStatusMessage('');
        setHidden(dom.saveCard, false);
        dom.namaSkenarioInput?.focus();
    };

    const closeSaveCard = () => {
        dom.saveForm?.reset();
        setSaveStatusMessage('');
        setHidden(dom.saveCard, true);
    };

    const fillForm = (data) => {
        Object.entries(data).forEach(([key, value]) => {
            const input = document.getElementById(key);
            if (input) {
                input.value = value;
                if (key === 'kenaikan_harga_jual_tahunan') {
                    document.getElementById('kenaikan_harga_val').innerText = `${value}%`;
                }
                if (key === 'inflasi_cogs_tahunan') {
                    document.getElementById('inflasi_cogs_val').innerText = `${value}%`;
                }
                if (key === 'inflasi_biaya_tetap_tahunan') {
                    document.getElementById('inflasi_biaya_tetap_val').innerText = `${value}%`;
                }
            }
        });
        alert('Skenario berhasil dimuat ke dalam form. Jalankan simulasi untuk melihat hasilnya.');
        setHidden(dom.resultsData, true);
        state.hasLatestResult = false;
        if (dom.saveButton) {
            disableElement(dom.saveButton, true);
        }
        dom.showSkenarioBButton?.classList.add('hidden');
        dom.skenarioBContainer?.classList.add('hidden');
        setSkenarioBMessage('', 'info');
        setSkenarioBMessage('', 'info');
        showStep(1);
    };

    const calculateSkenarioB = (formData) => {
        const calculateUrl = dom.formA.dataset.calculateUrl;
        setHidden(dom.loadingIndicator, false);
        dom.skenarioBContainer.style.opacity = '0.5';
        setSkenarioBMessage('Sedang menghitung Skenario B...', 'info');

        fetch(calculateUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    return response.json().then((err) => {
                        throw err;
                    });
                }
                return response.json();
            })
            .then((data) => {
                setHidden(dom.loadingIndicator, true);
                dom.skenarioBContainer.style.opacity = '1';

                if (data.status === 'success') {
                    const summary = data.data.summary;
                    document.getElementById('skenario-b-header').innerText = 'Skenario B (Revisi)';
                    const tableRows = document.querySelectorAll('#comparison-table-body tr');
                    const mapping = {
                        harga_jual: Number(document.getElementById('harga_jual_skenario_b').value || 0),
                        keuntungan_bersih_tahunan: Number(summary.keuntungan_bersih_proyeksi || 0),
                        saldo_kas_akhir: Number(summary.saldo_kas_akhir || 0),
                    };

                    const keys = ['harga_jual', 'keuntungan_bersih_tahunan', 'saldo_kas_akhir'];
                    tableRows.forEach((row, index) => {
                        const key = keys[index];
                        row.children[2].innerHTML = formatRupiah(mapping[key] ?? 0);
                    });

                    setSkenarioBMessage('Skenario B berhasil dihitung dan ditambahkan ke tabel perbandingan.', 'success');
                }
            })
            .catch((error) => {
                console.error('Error Skenario B:', error);
                setHidden(dom.loadingIndicator, true);
                dom.skenarioBContainer.style.opacity = '1';

                if (error.errors) {
                    displayValidationErrors(error.errors);
                    setSkenarioBMessage('Validasi Skenario B gagal. Periksa kembali input Anda.', 'error');
                } else {
                    setSkenarioBMessage('Terjadi kesalahan saat menghitung skenario B.', 'error');
                }
            });
    };

    const handleCalculationSuccess = (payload) => {
        const { summary, proyeksi_bulanan: proyeksi, proyeksi_tahunan: proyeksiTahunan = [], skenario_perbandingan: skenario } = payload;

        document.getElementById('net-profit').innerText = formatRupiah(summary.keuntungan_bersih_proyeksi);
        document.getElementById('final-cash-balance').innerText = formatRupiah(summary.saldo_kas_akhir);
        document.getElementById('bep').innerText = formatUnit(summary.titik_impas_unit);
        document.getElementById('payback-period').innerText = summary.waktu_balik_modal;
        document.getElementById('last-update').innerHTML = `<strong>Diperbarui pada ${summary.diperbarui_pada}</strong>`;
        updateInputSummary();
        updateWarningCard(summary.cash_warning_message);

        const displayPercent = (value) => (value === null || value === undefined ? '-' : `${Number(value).toFixed(2)}%`);
        dom.ebitdaField && (dom.ebitdaField.innerText = formatRupiah(summary.ebitda_tahun_pertama));
        dom.grossMarginField && (dom.grossMarginField.innerText = displayPercent(summary.gross_profit_margin));
        dom.netMarginField && (dom.netMarginField.innerText = displayPercent(summary.net_profit_margin));
        dom.debtToEquityField &&
            (dom.debtToEquityField.innerText = summary.debt_to_equity !== null && summary.debt_to_equity !== undefined
                ? Number(summary.debt_to_equity).toFixed(2)
                : summary.has_debt
                ? 'Tidak terdefinisi'
                : 'Tidak ada utang');
        dom.interestCoverageField &&
            (dom.interestCoverageField.innerText = summary.interest_coverage_ratio !== null && summary.interest_coverage_ratio !== undefined
                ? Number(summary.interest_coverage_ratio).toFixed(2)
                : 'Bebas bunga');

        if (summary.cash_flow_breakdown_year1) {
            dom.cfoYear1Field && (dom.cfoYear1Field.innerText = formatRupiah(summary.cash_flow_breakdown_year1.operasi));
            dom.cfiYear1Field && (dom.cfiYear1Field.innerText = formatRupiah(summary.cash_flow_breakdown_year1.investasi));
            dom.cffYear1Field && (dom.cffYear1Field.innerText = formatRupiah(summary.cash_flow_breakdown_year1.pendanaan));
        }
        if (dom.projectionDurationLabel) {
            dom.projectionDurationLabel.innerText = summary.durasi_proyeksi_tahun
                ? formatYears(summary.durasi_proyeksi_tahun)
                : '-';
        }

        const labels = proyeksi.map((item) => item.bulan);
        drawKeuntunganChart(labels, proyeksi.map((item) => item.keuntungan_bersih));
        drawCashFlowChart(labels, proyeksi.map((item) => item.saldo_kas));
        drawCostRevenueChart(
            labels,
            proyeksi.map((item) => item.pendapatan),
            proyeksi.map(
                (item) =>
                    item.biaya_variabel +
                    item.biaya_tetap +
                    (item.depresiasi || 0) +
                    (item.biaya_bunga || 0) +
                    item.pajak
            )
        );
        drawCashComponentChart(
            labels,
            proyeksi.map((item) => item.arus_kas_operasi),
            proyeksi.map((item) => item.arus_kas_investasi),
            proyeksi.map((item) => item.arus_kas_pendanaan)
        );

        if (dom.yearlyProjectionBody) {
            dom.yearlyProjectionBody.innerHTML = '';
            proyeksiTahunan.forEach((row) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">${row.tahun}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">${formatRupiah(row.pendapatan)}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">${formatRupiah(row.cogs)}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">${formatRupiah(row.ebitda)}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">${formatRupiah(row.laba_bersih)}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">${formatRupiah(row.arus_kas_operasi)}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">${formatRupiah(row.arus_kas_investasi)}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">${formatRupiah(row.arus_kas_pendanaan)}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">${formatRupiah(row.saldo_kas_akhir)}</td>
                `;
                dom.yearlyProjectionBody.appendChild(tr);
            });
        }

        buildComparisonTable(skenario);
        setHidden(dom.resultsData, false);
        dom.showSkenarioBButton?.classList.remove('hidden');

        state.hasLatestResult = true;
        if (state.isLoggedIn && dom.saveButton) {
            disableElement(dom.saveButton, false);
        }
    };

    // Tab events
    dom.tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const step = Number(button.dataset.step);
            showStep(step);
        });
    });

    dom.stepNavButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const nextStep = button.getAttribute('data-next');
            const prevStep = button.getAttribute('data-prev');
            if (nextStep) showStep(Number(nextStep));
            if (prevStep) showStep(Number(prevStep));
        });
    });

    showStep(1);
    updateSliderPreview(dom.sliderPrice, 'kenaikan_harga_val');
    updateSliderPreview(dom.sliderInflasiCogs, 'inflasi_cogs_val');
    updateSliderPreview(dom.sliderInflasiTetap, 'inflasi_biaya_tetap_val');

    const calculateUrl = dom.formA.dataset.calculateUrl;
    const saveUrl = dom.saveButton?.dataset.saveUrl;
    const listUrl = dom.loadButton?.dataset.listUrl;
    const loadBaseUrl = dom.executeLoadButton?.dataset.loadUrl;

    dom.formA.addEventListener('submit', (event) => {
        event.preventDefault();
        clearValidationErrors();

        const formData = new FormData(dom.formA);
        state.lastInputs = {};
        for (const [key, value] of formData.entries()) {
            if (key !== '_token') {
                state.lastInputs[key] = value;
            }
        }

        dom.showSkenarioBButton?.classList.add('hidden');
        dom.skenarioBContainer?.classList.add('hidden');

        setHidden(dom.loadingIndicator, false);
        setHidden(dom.resultsData, true);
        if (dom.resultsContainer) {
            dom.resultsContainer.style.opacity = '0.5';
        }

        fetch(calculateUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    return response.json().then((err) => {
                        throw err;
                    });
                }
                return response.json();
            })
            .then((data) => {
                setHidden(dom.loadingIndicator, true);
                if (dom.resultsContainer) {
                    dom.resultsContainer.style.opacity = '1';
                }
                if (data.status === 'success') {
                    handleCalculationSuccess(data.data);
                }
            })
            .catch((error) => {
                console.error('Error Calculate:', error);
                setHidden(dom.loadingIndicator, true);
                if (dom.resultsContainer) {
                    dom.resultsContainer.style.opacity = '1';
                }
                state.hasLatestResult = false;
                if (dom.saveButton) {
                    disableElement(dom.saveButton, true);
                }

                if (error.errors) {
                    displayValidationErrors(error.errors);
                    const firstErrorField = Object.keys(error.errors)[0];
                    const firstInput = document.getElementById(firstErrorField);
                    if (firstInput) {
                        const stepContainer = firstInput.closest('.tab-content');
                        if (stepContainer) {
                            const stepNumber = Number(stepContainer.id.replace('step-', ''));
                            showStep(stepNumber);
                        }
                    }
                    alert('Validasi gagal. Mohon periksa kolom yang ditandai.');
                } else {
                    alert('Terjadi kesalahan saat mengirim data ke server.');
                }
            });
    });

    if (state.isLoggedIn && dom.saveButton && dom.saveForm && saveUrl) {
        dom.saveButton.addEventListener('click', openSaveCard);
        [dom.cancelSaveCard, dom.closeSaveCard].forEach((button) => button?.addEventListener('click', closeSaveCard));
        dom.saveCardDismissArea?.addEventListener('click', closeSaveCard);

        dom.saveForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!state.hasLatestResult) {
                alert('Jalankan simulasi terlebih dahulu.');
                return;
            }

            const finalSaveData = new FormData();
            Object.entries(state.lastInputs).forEach(([key, value]) => {
                finalSaveData.append(key, value);
            });
            finalSaveData.append('nama_skenario', dom.namaSkenarioInput.value.trim());
            finalSaveData.append('_token', csrfToken);

            fetch(saveUrl, {
                method: 'POST',
                body: finalSaveData,
                headers: { Accept: 'application/json' },
            })
                .then((response) => {
                    if (!response.ok) {
                        return response.json().then((err) => {
                            throw err;
                        });
                    }
                    return response.json();
                })
                .then((data) => {
                    if (data.status === 'success') {
                        setSaveStatusMessage(data.message, 'success');
                        state.hasLatestResult = false;
                        disableElement(dom.saveButton, true);
                    }
                })
                .catch((error) => {
                    console.error('Error Save:', error);
                    if (error.errors) {
                        displayValidationErrors(error.errors);
                        setSaveStatusMessage('Validasi gagal. Periksa kembali nama skenario Anda.', 'error');
                    } else {
                        setSaveStatusMessage('Gagal menyimpan skenario. Silakan coba lagi.', 'error');
                    }
                });
        });
    }

    if (state.isLoggedIn && dom.loadButton && listUrl && loadBaseUrl) {
        const fetchSavedScenarios = () => {
            setHidden(dom.loadingLoad, false);
            setHidden(dom.noScenariosMessage, true);
            disableElement(dom.executeLoadButton, true);
            dom.savedScenariosDropdown.innerHTML = '<option value="">-- Pilih Skenario --</option>';

            fetch(listUrl, { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((data) => {
                    setHidden(dom.loadingLoad, true);
                    if (data.status === 'success' && data.data.length > 0) {
                        data.data.forEach((sim) => {
                            const option = document.createElement('option');
                            option.value = sim.id;
                            option.innerText = `${sim.nama_skenario} (${new Date(sim.created_at).toLocaleDateString()})`;
                            dom.savedScenariosDropdown.appendChild(option);
                        });
                    } else {
                        setHidden(dom.noScenariosMessage, false);
                        dom.savedScenariosDropdown.innerHTML = '<option value="">-- Tidak ada skenario tersimpan --</option>';
                    }
                })
                .catch((error) => {
                    console.error('Failed to fetch list:', error);
                    setHidden(dom.loadingLoad, true);
                    alert('Gagal memuat daftar skenario.');
                });
        };

        dom.loadButton.addEventListener('click', () => {
            setHidden(dom.loadModal, false);
            fetchSavedScenarios();
        });
        dom.closeLoadModal?.addEventListener('click', () => setHidden(dom.loadModal, true));

        dom.savedScenariosDropdown.addEventListener('change', (event) => {
            disableElement(dom.executeLoadButton, !event.target.value);
        });

        dom.executeLoadButton.addEventListener('click', () => {
            const simulationId = dom.savedScenariosDropdown.value;
            if (!simulationId) return;
            const url = `${loadBaseUrl}/${simulationId}`;
            setHidden(dom.loadingLoad, false);
            disableElement(dom.executeLoadButton, true);

            fetch(url, { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 'success') {
                        fillForm(data.data);
                        setHidden(dom.loadModal, true);
                    } else {
                        alert(data.message);
                    }
                    setHidden(dom.loadingLoad, true);
                    disableElement(dom.executeLoadButton, false);
                })
                .catch((error) => {
                    console.error('Error Load:', error);
                    setHidden(dom.loadingLoad, true);
                    disableElement(dom.executeLoadButton, false);
                    alert('Terjadi kesalahan saat memuat data skenario.');
                });
        });
    }

    if (dom.showSkenarioBButton) {
        dom.showSkenarioBButton.addEventListener('click', () => {
            document.getElementById('harga_jual_skenario_b').value = state.lastInputs.harga_jual || '';
            document.getElementById('anggaran_marketing_skenario_b').value = state.lastInputs.anggaran_marketing || '';
            dom.skenarioBContainer.classList.remove('hidden');
            dom.showSkenarioBButton.classList.add('hidden');
            setSkenarioBMessage('', 'info');
        });
    }

    dom.closeSkenarioBButton?.addEventListener('click', () => {
        dom.skenarioBContainer?.classList.add('hidden');
        dom.showSkenarioBButton?.classList.remove('hidden');
        setSkenarioBMessage('', 'info');
    });

    if (dom.formB) {
        dom.formB.addEventListener('submit', (event) => {
            event.preventDefault();
            const formBData = new FormData(dom.formB);
            const combined = new FormData();
            Object.entries(state.lastInputs).forEach(([key, value]) => combined.append(key, value));
            combined.append('harga_jual', formBData.get('harga_jual'));
            combined.append('anggaran_marketing', formBData.get('anggaran_marketing'));
            combined.append('_token', csrfToken);
            calculateSkenarioB(combined);
        });
    }
});
