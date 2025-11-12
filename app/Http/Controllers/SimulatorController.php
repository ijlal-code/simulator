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
        $totalKeuntunganAfterTax = 0;
        
        // Inisialisasi Arus Kas & Payback
        $saldoKas = $modalKerja - $capex;
        $capexSudahTercakup = false;
        $bulanBalikModal = null;
        
        $bulanKasNegatifPertama = null; 
        $kasNegatifMessage = "Arus kas stabil, modal kerja aman selama proyeksi."; 


        // --- 3. JALANKAN BUSINESS LOGIC ENGINE (BLE) ---
        for ($i = 1; $i <= $totalBulanProyeksi; $i++) {
            
            $pendapatan = $volumePenjualanSaatIni * $hargaJual;
            $biayaVariabel = $volumePenjualanSaatIni * $cogsSaatIni; 
            $keuntunganKotor = $pendapatan - $biayaVariabel;
            
            $labaSebelumPajak = $keuntunganKotor - $biayaTetapSaatIni; 
            
            $pajakBulanIni = $labaSebelumPajak > 0 ? $labaSebelumPajak * $tarifPajak : 0;
            $keuntunganBersihSetelahPajak = $labaSebelumPajak - $pajakBulanIni;
            
            $totalKeuntunganAfterTax += $keuntunganBersihSetelahPajak;

            $arusKasOperasi = $keuntunganBersihSetelahPajak;
            $saldoKas += $arusKasOperasi; 
            
            if ($saldoKas < 0 && $bulanKasNegatifPertama === null) {
                 $bulanKasNegatifPertama = $i;
                 $kasNegatifMessage = "Kritis! Saldo Kas mulai negatif pada Bulan $i. Diperlukan penambahan modal!";
            }

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
                'saldo_kas' => round($saldoKas), 
            ];

            $volumePenjualanSaatIni *= (1 + ($pertumbuhan / 12));
            $cogsSaatIni *= (1 + ($inflasiBiaya / 12));
            $biayaTetapSaatIni *= (1 + ($inflasiBiaya / 12));
        }

        // --- 5. SIAPKAN HASIL AKHIR ---
        $waktuBalikModal = $bulanBalikModal !== null 
            ? "$bulanBalikModal Bulan" 
            : "Di atas $totalBulanProyeksi Bulan";

        $marginKontribusi = $hargaJual - $cogs;
        $titikImpasUnit = $marginKontribusi > 0 ? ceil($biayaTetap / $marginKontribusi) : 0;


        $results = [
            'summary' => [
                'keuntungan_bersih_proyeksi' => number_format($totalKeuntunganAfterTax, 0, ',', '.'),
                'margin_kontribusi_per_unit' => number_format($marginKontribusi, 0, ',', '.'),
                'titik_impas_unit' => number_format($titikImpasUnit, 0, ',', '.'),
                'waktu_balik_modal' => $waktuBalikModal, 
                'diperbarui_pada' => Carbon::now()->isoFormat('D MMMM Y, H:mm') . " WITA",
                'saldo_kas_akhir' => number_format($saldoKas, 0, ',', '.'),
                'cash_warning_message' => $kasNegatifMessage,
            ],
            'proyeksi_bulanan' => $proyeksiBulanan,
            'skenario_perbandingan' => [
                'skenario_a' => [
                    'nama' => 'Skenario Dasar (Input Anda)',
                    'harga_jual' => number_format($hargaJual, 0, ',', '.'),
                    'keuntungan_bersih_tahunan' => number_format($totalKeuntunganAfterTax, 0, ',', '.'),
                    'saldo_kas_akhir' => number_format($saldoKas, 0, ',', '.'),
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
            'cogs' => 'required|numeric|min:0',
            'biaya_tetap' => 'required|numeric|min:0',
            'tingkat_pertumbuhan' => 'required|numeric|min:0|max:50',
            'tarif_pajak' => 'required|numeric|min:0|max:100', 
            'inflasi_biaya' => 'required|numeric|min:0|max:50', 
        ]);
        
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
                'harga_jual', 'volume_penjualan', 'capex', 'modal_kerja', 'cogs', 
                'biaya_tetap', 'tingkat_pertumbuhan', 'tarif_pajak', 'inflasi_biaya'
            ]);

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