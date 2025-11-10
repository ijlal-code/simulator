<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SimulatorController extends Controller
{
    // ... (Fungsi index() tetap sama)
 public function index()
    {
        // View ini akan kita buat di langkah berikutnya: resources/views/simulator/index.blade.php
        return view('simulator.index');
    }
    /**
     * Memproses semua data input dari form dan menjalankan Business Logic Engine (BLE)
     * untuk menghasilkan Proyeksi Keuangan (Mode Batch/Tumpukan) yang detail.
     */
    public function calculate(Request $request)
    {
        // --- 1. VALIDASI INPUT (Contoh sederhana) ---
        $request->validate([
            'harga_jual' => 'required|numeric|min:1',
            'volume_penjualan' => 'required|numeric|min:1',
            'capex' => 'required|numeric|min:0',
            'cogs' => 'required|numeric|min:0',
            'biaya_tetap' => 'required|numeric|min:0',
            'tingkat_pertumbuhan' => 'required|numeric|min:0|max:50',
        ]);

        // --- 2. AMBIL DATA INPUT ---
        $hargaJual = $request->input('harga_jual');
        $volumeAwal = $request->input('volume_penjualan');
        $capex = $request->input('capex');
        $cogs = $request->input('cogs');
        $biayaTetap = $request->input('biaya_tetap');
        $pertumbuhan = $request->input('tingkat_pertumbuhan') / 100; // Ubah ke desimal

        // --- 3. JALANKAN BUSINESS LOGIC ENGINE (BLE) YANG LEBIH DETAIL ---
        $proyeksiBulanan = [];
        $totalBulanProyeksi = 12; // Proyeksi selama 1 tahun
        $volumePenjualanSaatIni = $volumeAwal;
        $totalKeuntungan = 0;
        
        for ($i = 1; $i <= $totalBulanProyeksi; $i++) {
            // Hitung setiap bulan
            $pendapatan = $volumePenjualanSaatIni * $hargaJual;
            $biayaVariabel = $volumePenjualanSaatIni * $cogs;
            $keuntunganKotor = $pendapatan - $biayaVariabel;
            $keuntunganBersih = $keuntunganKotor - $biayaTetap;
            
            $totalKeuntungan += $keuntunganBersih;

            $proyeksiBulanan[] = [
                'bulan' => "Bulan $i",
                'pendapatan' => $pendapatan,
                'keuntungan_bersih' => $keuntunganBersih,
                'volume_unit' => round($volumePenjualanSaatIni),
            ];

            // Terapkan pertumbuhan bulanan (asumsi pertumbuhan tahunan dibagi 12)
            $volumePenjualanSaatIni *= (1 + ($pertumbuhan / 12));
        }

        // --- 4. DATA PERBANDINGAN SKENARIO (Contoh: Skenario Jual Lebih Murah) ---
        $skenarioB = [
            'nama' => 'Skenario Jual Lebih Murah',
            'harga_jual' => $hargaJual * 0.9, // 10% lebih murah
            'keuntungan_bersih_tahunan' => $totalKeuntungan * 0.8, // Asumsi untung tahunan 20% lebih rendah
        ];

        // --- 5. SIAPKAN HASIL AKHIR ---
        $results = [
            'summary' => [
                'keuntungan_bersih_proyeksi' => number_format($totalKeuntungan, 0, ',', '.'),
                // Hitung BEP (Titik Impas)
                'margin_kontribusi_per_unit' => $hargaJual - $cogs,
                'titik_impas_unit' => ($hargaJual - $cogs) > 0 ? ceil($biayaTetap / ($hargaJual - $cogs)) : 0,
                'waktu_balik_modal' => ceil($capex / $totalKeuntungan * 12) . " Bulan", // Estimasi
                'diperbarui_pada' => now()->format('d F Y, H:i WITA'),
            ],
            'proyeksi_bulanan' => $proyeksiBulanan,
            'skenario_perbandingan' => [
                'skenario_a' => [
                    'nama' => 'Skenario Dasar (Input Anda)',
                    'harga_jual' => number_format($hargaJual, 0),
                    'keuntungan_bersih_tahunan' => number_format($totalKeuntungan, 0),
                ],
                'skenario_b' => [
                    'nama' => $skenarioB['nama'],
                    'harga_jual' => number_format($skenarioB['harga_jual'], 0),
                    'keuntungan_bersih_tahunan' => number_format($skenarioB['keuntungan_bersih_tahunan'], 0),
                ],
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