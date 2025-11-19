<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulations', function (Blueprint $table) {
            $table->bigInteger('anggaran_marketing')->default(0)->after('volume_penjualan');
            $table->bigInteger('biaya_per_lead')->default(0)->after('anggaran_marketing');
            $table->float('tingkat_konversi')->default(0)->after('biaya_per_lead');
            $table->bigInteger('kapasitas_bulanan')->default(0)->after('tingkat_konversi');
            $table->integer('jumlah_karyawan')->default(0)->after('cogs');
            $table->bigInteger('gaji_per_karyawan')->default(0)->after('jumlah_karyawan');
            $table->integer('hari_piutang')->default(0)->after('durasi_proyeksi_tahun');
            $table->integer('hari_utang_usaha')->default(0)->after('hari_piutang');
            $table->json('seasonality_factors')->nullable()->after('hari_utang_usaha');
        });
    }

    public function down(): void
    {
        Schema::table('simulations', function (Blueprint $table) {
            $table->dropColumn([
                'anggaran_marketing',
                'biaya_per_lead',
                'tingkat_konversi',
                'kapasitas_bulanan',
                'jumlah_karyawan',
                'gaji_per_karyawan',
                'hari_piutang',
                'hari_utang_usaha',
                'seasonality_factors',
            ]);
        });
    }
};
