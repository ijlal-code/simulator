<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Carbon\Carbon; // Pastikan Carbon sudah diimpor

class SimulatorController extends Controller
{
    /**
     * Tampilkan view simulator.
     */
    public function index()
    {
        return view('simulator.index');
    }

    /**
     * Memproses semua data input dari form dan menjalankan Business Logic Engine (BLE)
     * untuk menghasilkan Proyeksi Keuangan yang lebih realistis.
     */
    public function calculate(Request $request)
    {
        // --- 1. VALIDASI INPUT (DIREVISI untuk input baru) ---
        $request->validate([
            'harga_jual' => 'required|numeric|min:1',
            'volume_penjualan' => 'required|numeric|min:1',
            'capex' => 'required|numeric|min:0',
            'modal_kerja' => 'required|numeric|min:0', 
            'cogs' => 'required|numeric|min:0',
            'biaya_tetap' => 'required|numeric|min:0',
            'tingkat_pertumbuhan' => 'required|numeric|min:0|max:50',
            'tarif_pajak' => 'required|numeric|min:0|max:100', 
            'inflasi_biaya' => 'required|numeric|min:0|max:50', 
        ]);

        // --- 2. AMBIL DATA INPUT ---
        $hargaJual = $request->input('harga_jual');
        $volumeAwal = $request->input('volume_penjualan');
        $capex = $request->input('capex');
        $modalKerja = $request->input('modal_kerja'); 
        $cogs = $request->input('cogs');
        $biayaTetap = $request->input('biaya_tetap');
        $pertumbuhan = $request->input('tingkat_pertumbuhan') / 100;
        $tarifPajak = $request->input('tarif_pajak') / 100;
        $inflasiBiaya = $request->input('inflasi_biaya') / 100;

        // Inisialisasi variabel iterasi bulanan
        $proyeksiBulanan = [];
        $totalBulanProyeksi = 12; 
        $volumePenjualanSaatIni = $volumeAwal;
        $cogsSaatIni = $cogs;
        $biayaTetapSaatIni = $biayaTetap;
        $totalKeuntunganSetelahPajak = 0;
        
        // 1. Implementasi Arus Kas
        // Saldo Kas Awal: Modal Kerja dikurangi pengeluaran CAPEX
        $saldoKas = $modalKerja - $capex;
        $capexSudahTercakup = false;
        $bulanBalikModal = null;


        // --- 3. JALANKAN BUSINESS LOGIC ENGINE (BLE) YANG LEBIH DETAIL ---
        for ($i = 1; $i <= $totalBulanProyeksi; $i++) {
            // Hitung setiap bulan dengan nilai yang sudah terinflasi
            $pendapatan = $volumePenjualanSaatIni * $hargaJual;
            $biayaVariabel = $volumePenjualanSaatIni * $cogsSaatIni; // COGS terinflasi
            $keuntunganKotor = $pendapatan - $biayaVariabel;
            
            // Keuntungan Sebelum Pajak (EBIT)
            $labaSebelumPajak = $keuntunganKotor - $biayaTetapSaatIni; // Biaya Tetap terinflasi
            
            // 2. Perhitungan Pajak
            // Pajak hanya dibayar jika ada laba (EBIT > 0)
            $pajakBulanIni = $labaSebelumPajak > 0 ? $labaSebelumPajak * $tarifPajak : 0;
            $keuntunganBersihSetelahPajak = $labaSebelumPajak - $pajakBulanIni;
            
            $totalKeuntunganSetelahPajak += $keuntunganBersihSetelahPajak;

            // Arus Kas Bulanan (Asumsi sederhana: Laba Bersih = Arus Kas Operasi)
            $arusKasOperasi = $keuntunganBersihSetelahPajak;
            $saldoKas += $arusKasOperasi; // Tambahkan arus kas operasi ke saldo kas

            // 4. Perhitungan Payback Period yang Lebih Akurat
            // Payback tercapai saat saldo kas bulanan kembali ke atau melebihi Modal Kerja Awal
            if (!$capexSudahTercakup && $saldoKas >= $modalKerja) {
                 $bulanBalikModal = $i;
                 $capexSudahTercakup = true;
            }

            $proyeksiBulanan[] = [
                'bulan' => "Bulan $i",
                'pendapatan' => round($pendapatan),
                'biaya_variabel' => round($biayaVariabel),
                'biaya_tetap' => round($biayaTetapSaatIni),
                'laba_sebelum_pajak' => round($labaSebelumPajak),
                'pajak' => round($pajakBulanIni),
                'keuntungan_bersih' => round($keuntunganBersihSetelahPajak),
                'volume_unit' => round($volumePenjualanSaatIni),
                'saldo_kas' => round($saldoKas), // Saldo Kas bulanan
            ];

            // Terapkan pertumbuhan bulanan
            $volumePenjualanSaatIni *= (1 + ($pertumbuhan / 12));
            
            // 3. Terapkan Inflasi Biaya untuk bulan berikutnya
            $cogsSaatIni *= (1 + ($inflasiBiaya / 12));
            $biayaTetapSaatIni *= (1 + ($inflasiBiaya / 12));
        }

        // --- 5. SIAPKAN HASIL AKHIR ---
        // Finalisasi Waktu Balik Modal
        $waktuBalikModal = $bulanBalikModal !== null 
            ? "$bulanBalikModal Bulan" 
            : "Di atas $totalBulanProyeksi Bulan";

        // Hitung BEP (Titik Impas) - Menggunakan biaya Tetap BULAN 1 (tanpa inflasi)
        $marginKontribusi = $hargaJual - $cogs;
        $titikImpasUnit = $marginKontribusi > 0 ? ceil($biayaTetap / $marginKontribusi) : 0;


        $results = [
            'summary' => [
                'keuntungan_bersih_proyeksi' => number_format($totalKeuntunganSetelahPajak, 0, ',', '.'),
                'margin_kontribusi_per_unit' => number_format($marginKontribusi, 0, ',', '.'),
                'titik_impas_unit' => number_format($titikImpasUnit, 0, ',', '.'),
                'waktu_balik_modal' => $waktuBalikModal, 
                'diperbarui_pada' => Carbon::now()->isoFormat('D MMMM Y, H:mm') . " WITA",
                'saldo_kas_akhir' => number_format($saldoKas, 0, ',', '.'), 
            ],
            'proyeksi_bulanan' => $proyeksiBulanan,
            'skenario_perbandingan' => [
                'skenario_a' => [
                    'nama' => 'Skenario Dasar (Input Anda)',
                    'harga_jual' => number_format($hargaJual, 0, ',', '.'),
                    'keuntungan_bersih_tahunan' => number_format($totalKeuntunganSetelahPajak, 0, ',', '.'),
                    'saldo_kas_akhir' => number_format($saldoKas, 0, ',', '.'),
                ],
                // Skenario B dikosongkan untuk diisi secara dinamis di frontend
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
}