<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller; // Gunakan ini jika Anda menggunakan Laravel 11/Composer 

class SimulatorController extends Controller
{
    /**
     * Menampilkan form input step-by-step (Langkah 1, 2, 3)
     */
    public function index()
    {
        // View ini akan kita buat di langkah berikutnya: resources/views/simulator/index.blade.php
        return view('simulator.index');
    }

    /**
     * Memproses semua data input dari form dan menjalankan Business Logic Engine (BLE)
     * untuk menghasilkan Proyeksi Keuangan (Mode Batch/Tumpukan).
     */
    public function calculate(Request $request)
    {
        // --- 1. VALIDASI INPUT ---
        // Lakukan validasi input di sini (misalnya, memastikan angka positif, tidak kosong)

        // --- 2. AMBIL DATA INPUT ---
        // Ambil semua data input dari $request:
        // $profilUsaha = $request->input('langkah_1');
        // $biayaModal = $request->input('langkah_2');
        // $biayaOperasional = $request->input('langkah_3');

        // --- 3. JALANKAN BUSINESS LOGIC ENGINE (BLE) ---
        // Di sinilah semua perhitungan Titik Impas (BEP), Keuntungan Bersih,
        // Waktu Balik Modal (Payback Period), dan Analisis Skenario dilakukan.
        
        $results = [
            'keuntungan_bersih_proyeksi' => 'Rp. 15.000.000', 
            'titik_impas_bep' => '500 Unit / Rp. 100.000.000',
            'waktu_balik_modal' => '12 Bulan',
            // Informasi bahwa hasil adalah *batch update*
            'diperbarui_pada' => now()->format('d F Y, H:i WITA'), 
        ];

        // --- 4. KIRIM HASIL KE FRONTEND ---
        // Mengembalikan data JSON ke JavaScript frontend untuk memperbarui Panel Proyeksi
        return response()->json([
            'status' => 'success',
            'message' => 'Simulasi berhasil dihitung.',
            'data' => $results,
        ]);
    }
}