<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SimulatorController;


// PENTING: Sertakan route Auth yang dihasilkan oleh Laravel Breeze/Jetstream
require __DIR__.'/auth.php'; 


// --- ROUTE UMUM ---

// Route Halaman Utama (Mengarahkan ke Simulator Index)
Route::get('/', function () {
    return redirect()->route('simulator.index');
});

// Route Simulator (Akses Publik)
Route::get('/simulator', [SimulatorController::class, 'index'])->name('simulator.index');
Route::post('/simulator/calculate', [SimulatorController::class, 'calculate'])->name('simulator.calculate');


// --- ROUTE YANG DILINDUNGI (MEMBUTUHKAN AUTH h) ---
Route::middleware(['auth'])->group(function () {
    
    // Route Dashboard standar dari Breeze (Pengguna TIDAK akan diarahkan ke sini setelah login, karena RouteServiceProvider diubah)
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // --- ROUTE PROFIL PENGGUNA (EDIT/UPDATE) ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // --- ROUTE FITUR SIMPAN & MUAT SKENARIO ---
    Route::post('/simulator/save', [SimulatorController::class, 'save'])->name('simulator.save');
    Route::get('/simulator/load/{id}', [SimulatorController::class, 'load'])->name('simulator.load');
    Route::get('/simulator/list', [SimulatorController::class, 'listSavedSimulations'])->name('simulator.list');
});