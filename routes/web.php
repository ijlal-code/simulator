<?php

use Illuminate\Support\Facades\Route;
// 1. Import Controller yang baru Anda buat
use App\Http\Controllers\SimulatorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rute Default: Arahkan ke halaman utama simulator (index)
Route::get('/', [SimulatorController::class, 'index'])->name('simulator.index');

// Rute untuk memproses perhitungan simulasi (Mode Batch/Tumpukan)
// Ini akan dipicu ketika pengguna menekan tombol "Jalankan Simulasi"
Route::post('/calculate', [SimulatorController::class, 'calculate'])->name('simulator.calculate');