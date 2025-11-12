<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    // UBAH TUJUAN REDIRECT SETELAH LOGIN
    // Dari '/dashboard' atau '/home' menjadi '/simulator'
    public const HOME = '/simulator'; 

    // ... (sisa kode)
}