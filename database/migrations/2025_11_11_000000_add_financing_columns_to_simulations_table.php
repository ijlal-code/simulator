<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulations', function (Blueprint $table) {
            $table->bigInteger('modal_disetor_pemilik')->default(0)->after('modal_kerja');
            $table->bigInteger('jumlah_pinjaman')->default(0)->after('modal_disetor_pemilik');
            $table->float('bunga_pinjaman_tahunan')->default(0)->after('jumlah_pinjaman');
            $table->integer('tenor_pinjaman_bulan')->default(0)->after('bunga_pinjaman_tahunan');
            $table->float('masa_manfaat_aset_tahun')->default(1)->after('tenor_pinjaman_bulan');
            $table->float('kenaikan_harga_jual_tahunan')->default(0)->after('tingkat_pertumbuhan');
            $table->float('inflasi_cogs_tahunan')->default(0)->after('kenaikan_harga_jual_tahunan');
            $table->float('inflasi_biaya_tetap_tahunan')->default(0)->after('inflasi_cogs_tahunan');
            $table->integer('durasi_proyeksi_tahun')->default(1)->after('inflasi_biaya_tetap_tahunan');
        });
    }

    public function down(): void
    {
        Schema::table('simulations', function (Blueprint $table) {
            $table->dropColumn([
                'modal_disetor_pemilik',
                'jumlah_pinjaman',
                'bunga_pinjaman_tahunan',
                'tenor_pinjaman_bulan',
                'masa_manfaat_aset_tahun',
                'kenaikan_harga_jual_tahunan',
                'inflasi_cogs_tahunan',
                'inflasi_biaya_tetap_tahunan',
                'durasi_proyeksi_tahun',
            ]);
        });
    }
};
