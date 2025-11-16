<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Carbon\Carbon;
use App\Models\Simulation;
use Illuminate\Support\Facades\Auth;

class SimulatorController extends Controller
{
    /**
     * Tampilkan view simulator dan cek status login.
     */
    public function index()
    {
        // KIRIM STATUS OTENTIKASI DAN ROUTE KE VIEW
        return view('simulator.index', [
            'isLoggedIn' => Auth::check(),
            'loginRoute' => route('login'), 
            'registerRoute' => route('register'), 
        ]);
    }

    /**
     * Memproses semua data input dari form dan menjalankan Business Logic Engine (BLE).
     */
    public function calculate(Request $request)
    {
        // --- 1. VALIDASI INPUT ---
        $request->validate([
            'harga_jual' => 'required|numeric|min:1',
            'volume_penjualan' => 'required|numeric|min:1',
            'capex' => 'required|numeric|min:0',
            'modal_kerja' => 'required|numeric|min:0',
            'modal_disetor_pemilik' => 'required|numeric|min:0',
            'jumlah_pinjaman' => 'required|numeric|min:0',
            'bunga_pinjaman_tahunan' => 'required|numeric|min:0|max:100',
            'tenor_pinjaman_bulan' => 'required|integer|min:0|max:600',
            'masa_manfaat_aset_tahun' => 'required|numeric|min:1|max:30',
            'cogs' => 'required|numeric|min:0',
            'biaya_tetap' => 'required|numeric|min:0',
            'tingkat_pertumbuhan' => 'required|numeric|min:0|max:50',
            'kenaikan_harga_jual_tahunan' => 'required|numeric|min:0|max:50',
            'tarif_pajak' => 'required|numeric|min:0|max:100',
            'inflasi_cogs_tahunan' => 'required|numeric|min:0|max:50',
            'inflasi_biaya_tetap_tahunan' => 'required|numeric|min:0|max:50',
            'durasi_proyeksi_tahun' => 'required|integer|min:1|max:5',
            'inflasi_biaya' => 'nullable|numeric|min:0|max:50',
        ]);

        // --- 2. AMBIL DATA INPUT ---
        $hargaJual = $request->input('harga_jual');
        $volumeAwal = $request->input('volume_penjualan');
        $capex = $request->input('capex');
        $modalKerja = $request->input('modal_kerja');
        $modalDisetorPemilik = $request->input('modal_disetor_pemilik');
        $jumlahPinjaman = $request->input('jumlah_pinjaman');
        $bungaPinjamanTahunan = $request->input('bunga_pinjaman_tahunan') / 100;
        $tenorPinjamanBulan = (int) $request->input('tenor_pinjaman_bulan');
        $masaManfaatAset = max(1, (float) $request->input('masa_manfaat_aset_tahun'));
        $cogs = $request->input('cogs');
        $biayaTetap = $request->input('biaya_tetap');
        $pertumbuhan = $request->input('tingkat_pertumbuhan') / 100;
        $kenaikanHargaTahunan = $request->input('kenaikan_harga_jual_tahunan') / 100;
        $tarifPajak = $request->input('tarif_pajak') / 100;
        $inflasiCogs = $request->input('inflasi_cogs_tahunan') / 100;
        $inflasiBiayaTetap = $request->input('inflasi_biaya_tetap_tahunan') / 100;
        $durasiTahun = max(1, (int) $request->input('durasi_proyeksi_tahun'));

        // Inisialisasi variabel iterasi bulanan
        $proyeksiLengkap = [];
        $totalBulanProyeksi = $durasiTahun * 12;
        $volumePenjualanSaatIni = $volumeAwal;
        $cogsSaatIni = $cogs;
        $hargaJualSaatIni = $hargaJual;
        $biayaTetapSaatIni = $biayaTetap;
        $totalKeuntunganAfterTax = 0;
        $saldoKas = $modalKerja;
        $loanOutstanding = $jumlahPinjaman;
        $monthlyPrincipalPayment = $tenorPinjamanBulan > 0 && $jumlahPinjaman > 0
            ? $jumlahPinjaman / $tenorPinjamanBulan
            : 0;
        $biayaDepresiasi = $masaManfaatAset > 0 ? $capex / ($masaManfaatAset * 12) : 0;
        $pertumbuhanBulanan = $pertumbuhan / 12;
        $inflasiHargaBulanan = $kenaikanHargaTahunan / 12;
        $inflasiCogsBulanan = $inflasiCogs / 12;
        $inflasiBiayaTetapBulanan = $inflasiBiayaTetap / 12;
        $sukuBungaBulanan = $bungaPinjamanTahunan / 12;
        $bulanKasNegatifPertama = null;
        $kasNegatifMessage = "Arus kas tetap sehat sepanjang periode proyeksi.";
        $paybackMonth = null;
        $cumulativeCFOAndCFI = 0;
        $yearlyTotals = [];


        // --- 3. JALANKAN BUSINESS LOGIC ENGINE (BLE) ---
        for ($i = 1; $i <= $totalBulanProyeksi; $i++) {

            $pendapatan = $volumePenjualanSaatIni * $hargaJualSaatIni;
            $biayaVariabel = $volumePenjualanSaatIni * $cogsSaatIni;
            $keuntunganKotor = $pendapatan - $biayaVariabel;
            $ebit = $keuntunganKotor - $biayaTetapSaatIni - $biayaDepresiasi;
            $ebitda = $ebit + $biayaDepresiasi;
            $biayaBunga = $loanOutstanding > 0 ? $loanOutstanding * $sukuBungaBulanan : 0;
            $labaSebelumPajak = $ebit - $biayaBunga;

            $pajakBulanIni = $labaSebelumPajak > 0 ? $labaSebelumPajak * $tarifPajak : 0;
            $keuntunganBersihSetelahPajak = $labaSebelumPajak - $pajakBulanIni;
            $arusKasOperasi = $keuntunganBersihSetelahPajak + $biayaDepresiasi;

            $arusKasInvestasi = 0;
            if ($i === 1) {
                $arusKasInvestasi -= $capex;
            }

            $arusKasPendanaan = 0;
            if ($i === 1) {
                $arusKasPendanaan += $jumlahPinjaman + $modalDisetorPemilik;
            }

            $pembayaranPokok = 0;
            if ($loanOutstanding > 0 && $tenorPinjamanBulan > 0 && $i <= $tenorPinjamanBulan) {
                $pembayaranPokok = min($monthlyPrincipalPayment, $loanOutstanding);
                $loanOutstanding -= $pembayaranPokok;
                $arusKasPendanaan -= $pembayaranPokok;
            }

            $perubahanKas = $arusKasOperasi + $arusKasInvestasi + $arusKasPendanaan;
            $saldoKas += $perubahanKas;

            if ($saldoKas < 0 && $bulanKasNegatifPertama === null) {
                $bulanKasNegatifPertama = $i;
                $tahunNegatif = intdiv($i - 1, 12) + 1;
                $bulanDalamTahun = (($i - 1) % 12) + 1;
                $kasNegatifMessage = "Kritis! Saldo kas negatif pada Tahun $tahunNegatif Bulan $bulanDalamTahun. Tambah modal kerja atau revisi rencana.";
            }

            $totalKeuntunganAfterTax += $keuntunganBersihSetelahPajak;
            $cumulativeCFOAndCFI += ($arusKasOperasi + $arusKasInvestasi);
            if ($paybackMonth === null && $cumulativeCFOAndCFI >= 0) {
                $paybackMonth = $i;
            }

            $proyeksiLengkap[] = [
                'bulan' => "Bulan $i",
                'pendapatan' => round($pendapatan),
                'biaya_variabel' => round($biayaVariabel),
                'biaya_tetap' => round($biayaTetapSaatIni),
                'depresiasi' => round($biayaDepresiasi),
                'gross_profit' => round($keuntunganKotor),
                'ebit' => round($ebit),
                'ebitda' => round($ebitda),
                'biaya_bunga' => round($biayaBunga),
                'laba_sebelum_pajak' => round($labaSebelumPajak),
                'pajak' => round($pajakBulanIni),
                'keuntungan_bersih' => round($keuntunganBersihSetelahPajak),
                'arus_kas_operasi' => round($arusKasOperasi),
                'arus_kas_investasi' => round($arusKasInvestasi),
                'arus_kas_pendanaan' => round($arusKasPendanaan),
                'perubahan_kas' => round($perubahanKas),
                'saldo_kas' => round($saldoKas),
                'pokok_pinjaman_dibayar' => round($pembayaranPokok),
                'sisa_pinjaman' => round($loanOutstanding),
                'volume_unit' => round($volumePenjualanSaatIni),
                'harga_per_unit' => round($hargaJualSaatIni),
            ];

            $tahunKe = (int) ceil($i / 12);
            if (!isset($yearlyTotals[$tahunKe])) {
                $yearlyTotals[$tahunKe] = [
                    'pendapatan' => 0,
                    'cogs' => 0,
                    'gross_profit' => 0,
                    'biaya_tetap' => 0,
                    'depresiasi' => 0,
                    'ebitda' => 0,
                    'laba_bersih' => 0,
                    'cfo' => 0,
                    'cfi' => 0,
                    'cff' => 0,
                    'biaya_bunga' => 0,
                    'ending_cash' => 0,
                ];
            }

            $yearlyTotals[$tahunKe]['pendapatan'] += $pendapatan;
            $yearlyTotals[$tahunKe]['cogs'] += $biayaVariabel;
            $yearlyTotals[$tahunKe]['gross_profit'] += $keuntunganKotor;
            $yearlyTotals[$tahunKe]['biaya_tetap'] += $biayaTetapSaatIni;
            $yearlyTotals[$tahunKe]['depresiasi'] += $biayaDepresiasi;
            $yearlyTotals[$tahunKe]['ebitda'] += $ebitda;
            $yearlyTotals[$tahunKe]['laba_bersih'] += $keuntunganBersihSetelahPajak;
            $yearlyTotals[$tahunKe]['cfo'] += $arusKasOperasi;
            $yearlyTotals[$tahunKe]['cfi'] += $arusKasInvestasi;
            $yearlyTotals[$tahunKe]['cff'] += $arusKasPendanaan;
            $yearlyTotals[$tahunKe]['biaya_bunga'] += $biayaBunga;
            $yearlyTotals[$tahunKe]['ending_cash'] = $saldoKas;

            $volumePenjualanSaatIni *= (1 + $pertumbuhanBulanan);
            $cogsSaatIni *= (1 + $inflasiCogsBulanan);
            $hargaJualSaatIni *= (1 + $inflasiHargaBulanan);
            $biayaTetapSaatIni *= (1 + $inflasiBiayaTetapBulanan);
        }

        // --- 5. SIAPKAN HASIL AKHIR ---
        if ($paybackMonth !== null) {
            $tahunPayback = intdiv($paybackMonth - 1, 12) + 1;
            $bulanPayback = (($paybackMonth - 1) % 12) + 1;
            $waktuBalikModal = "Bulan $paybackMonth (Tahun $tahunPayback Bulan $bulanPayback)";
        } else {
            $waktuBalikModal = "Belum tercapai selama $durasiTahun tahun proyeksi";
        }

        $marginKontribusi = $hargaJual - $cogs;
        $titikImpasUnit = $marginKontribusi > 0 ? ceil($biayaTetap / $marginKontribusi) : 0;


        $proyeksiTahunan = [];
        foreach ($yearlyTotals as $tahunKe => $data) {
            $proyeksiTahunan[] = [
                'tahun' => "Tahun $tahunKe",
                'pendapatan' => round($data['pendapatan']),
                'cogs' => round($data['cogs']),
                'gross_profit' => round($data['gross_profit']),
                'ebitda' => round($data['ebitda']),
                'laba_bersih' => round($data['laba_bersih']),
                'arus_kas_operasi' => round($data['cfo']),
                'arus_kas_investasi' => round($data['cfi']),
                'arus_kas_pendanaan' => round($data['cff']),
                'saldo_kas_akhir' => round($data['ending_cash']),
            ];
        }

        $tahunPertama = $yearlyTotals[1] ?? null;
        $grossProfitMargin = $tahunPertama && $tahunPertama['pendapatan'] > 0
            ? ($tahunPertama['gross_profit'] / $tahunPertama['pendapatan']) * 100
            : 0;
        $netProfitMargin = $tahunPertama && $tahunPertama['pendapatan'] > 0
            ? ($tahunPertama['laba_bersih'] / $tahunPertama['pendapatan']) * 100
            : 0;
        $cashFlowBreakdown = [
            'operasi' => round($tahunPertama['cfo'] ?? 0),
            'investasi' => round($tahunPertama['cfi'] ?? 0),
            'pendanaan' => round($tahunPertama['cff'] ?? 0),
        ];
        $debtToEquity = $modalDisetorPemilik > 0
            ? round($jumlahPinjaman / $modalDisetorPemilik, 2)
            : null;
        $interestCoverage = ($tahunPertama['biaya_bunga'] ?? 0) > 0
            ? round(($tahunPertama['ebitda'] ?? 0) / $tahunPertama['biaya_bunga'], 2)
            : null;

        $labaBersihTahunPertama = round($tahunPertama['laba_bersih'] ?? 0);

        $results = [
            'summary' => [
                'keuntungan_bersih_proyeksi' => $labaBersihTahunPertama,
                'margin_kontribusi_per_unit' => round($marginKontribusi),
                'titik_impas_unit' => (int) $titikImpasUnit,
                'waktu_balik_modal' => $waktuBalikModal,
                'diperbarui_pada' => Carbon::now()->isoFormat('D MMMM Y, H:mm') . " WITA",
                'saldo_kas_akhir' => round($saldoKas),
                'cash_warning_message' => $kasNegatifMessage,
                'ebitda_tahun_pertama' => round($tahunPertama['ebitda'] ?? 0),
                'gross_profit_margin' => round($grossProfitMargin, 2),
                'net_profit_margin' => round($netProfitMargin, 2),
                'cash_flow_breakdown_year1' => $cashFlowBreakdown,
                'debt_to_equity' => $debtToEquity,
                'interest_coverage_ratio' => $interestCoverage,
                'has_debt' => $jumlahPinjaman > 0,
                'durasi_proyeksi_tahun' => $durasiTahun,
                'total_keuntungan_bersih_proyeksi' => round($totalKeuntunganAfterTax),
            ],
            'proyeksi_bulanan' => array_slice($proyeksiLengkap, 0, min(12, count($proyeksiLengkap))),
            'proyeksi_tahunan' => $proyeksiTahunan,
            'skenario_perbandingan' => [
                'skenario_a' => [
                    'nama' => 'Skenario Dasar (Input Anda)',
                    'harga_jual' => round($hargaJual),
                    'keuntungan_bersih_tahunan' => $labaBersihTahunPertama,
                    'saldo_kas_akhir' => round($saldoKas),
                ],
                'skenario_b' => null
            ]
        ];

        // --- 6. KIRIM HASIL KE FRONTEND ---
        return response()->json([
            'status' => 'success',
            'message' => 'Simulasi berhasil dihitung.',
            'data' => $results,
        ]);
    }
    
    // --- METODE BARU: MENYIMPAN SKENARIO (MEMBUTUHKAN AUTH) ---
    public function save(Request $request)
    {
        $userId = Auth::id(); // Ambil ID user yang sedang login

        $validated = $request->validate([
            'nama_skenario' => 'required|string|max:255',
            'harga_jual' => 'required|numeric|min:1',
            'volume_penjualan' => 'required|numeric|min:1',
            'capex' => 'required|numeric|min:0',
            'modal_kerja' => 'required|numeric|min:0',
            'modal_disetor_pemilik' => 'required|numeric|min:0',
            'jumlah_pinjaman' => 'required|numeric|min:0',
            'bunga_pinjaman_tahunan' => 'required|numeric|min:0|max:100',
            'tenor_pinjaman_bulan' => 'required|integer|min:0|max:600',
            'masa_manfaat_aset_tahun' => 'required|numeric|min:1|max:30',
            'cogs' => 'required|numeric|min:0',
            'biaya_tetap' => 'required|numeric|min:0',
            'tingkat_pertumbuhan' => 'required|numeric|min:0|max:50',
            'kenaikan_harga_jual_tahunan' => 'required|numeric|min:0|max:50',
            'tarif_pajak' => 'required|numeric|min:0|max:100',
            'inflasi_cogs_tahunan' => 'required|numeric|min:0|max:50',
            'inflasi_biaya_tetap_tahunan' => 'required|numeric|min:0|max:50',
            'durasi_proyeksi_tahun' => 'required|integer|min:1|max:5',
            'inflasi_biaya' => 'nullable|numeric|min:0|max:50',
        ]);

        $validated['inflasi_biaya'] = $validated['inflasi_cogs_tahunan'];
        $validated['user_id'] = $userId;

        try {
            $simulation = Simulation::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Skenario berhasil disimpan.',
                'id' => $simulation->id,
                'nama' => $simulation->nama_skenario,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan skenario: ' . $e->getMessage(),
            ], 500);
        }
    }

    // --- METODE BARU: MEMUAT SKENARIO (MEMBUTUHKAN AUTH) ---
    public function load($id)
    {
        $userId = Auth::id(); 

        try {
            // Hanya ambil simulasi milik user yang sedang login
            $simulation = Simulation::where('user_id', $userId)
                                    ->findOrFail($id);

            $data = $simulation->only([
                'harga_jual', 'volume_penjualan', 'capex', 'modal_kerja', 'modal_disetor_pemilik',
                'jumlah_pinjaman', 'bunga_pinjaman_tahunan', 'tenor_pinjaman_bulan', 'masa_manfaat_aset_tahun',
                'cogs', 'biaya_tetap', 'tingkat_pertumbuhan', 'kenaikan_harga_jual_tahunan',
                'tarif_pajak', 'inflasi_cogs_tahunan', 'inflasi_biaya_tetap_tahunan', 'durasi_proyeksi_tahun', 'inflasi_biaya'
            ]);

            if (($data['inflasi_cogs_tahunan'] ?? 0) == 0 && ($data['inflasi_biaya'] ?? 0) > 0) {
                $data['inflasi_cogs_tahunan'] = $data['inflasi_biaya'];
            }
            if (($data['inflasi_biaya_tetap_tahunan'] ?? 0) == 0 && ($data['inflasi_biaya'] ?? 0) > 0) {
                $data['inflasi_biaya_tetap_tahunan'] = $data['inflasi_biaya'];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Skenario berhasil dimuat.',
                'data' => $data,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Skenario tidak ditemukan atau Anda tidak memiliki akses.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat skenario: ' . $e->getMessage(),
            ], 500);
        }
    }

    // --- METODE BARU: DAFTAR SKENARIO TERSIMPAN (MEMBUTUHKAN AUTH) ---
    public function listSavedSimulations()
    {
        $userId = Auth::id();
        
        // Hanya tampilkan simulasi milik user yang sedang login
        $simulations = Simulation::where('user_id', $userId)
                                 ->select('id', 'nama_skenario', 'created_at')
                                 ->orderBy('created_at', 'desc')
                                 ->limit(10)
                                 ->get();

        return response()->json([
            'status' => 'success',
            'data' => $simulations,
        ]);
    }
}