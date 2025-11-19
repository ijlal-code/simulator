<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simulation extends Model
{
    use HasFactory;
    
    protected $table = 'simulations'; 

    protected $fillable = [
        'nama_skenario',
        'user_id', // PENTING: Untuk relasi kepemilikan
        'harga_jual',
        'volume_penjualan',
        'anggaran_marketing',
        'biaya_per_lead',
        'tingkat_konversi',
        'kapasitas_bulanan',
        'capex',
        'modal_kerja',
        'modal_disetor_pemilik',
        'jumlah_pinjaman',
        'bunga_pinjaman_tahunan',
        'tenor_pinjaman_bulan',
        'masa_manfaat_aset_tahun',
        'cogs',
        'biaya_tetap',
        'jumlah_karyawan',
        'gaji_per_karyawan',
        'tingkat_pertumbuhan',
        'kenaikan_harga_jual_tahunan',
        'inflasi_cogs_tahunan',
        'inflasi_biaya_tetap_tahunan',
        'durasi_proyeksi_tahun',
        'tarif_pajak',
        'inflasi_biaya',
        'hari_piutang',
        'hari_utang_usaha',
        'seasonality_factors',
    ];

    protected $casts = [
        'harga_jual' => 'integer',
        'volume_penjualan' => 'integer',
        'anggaran_marketing' => 'integer',
        'biaya_per_lead' => 'integer',
        'tingkat_konversi' => 'float',
        'kapasitas_bulanan' => 'integer',
        'capex' => 'integer',
        'modal_kerja' => 'integer',
        'modal_disetor_pemilik' => 'integer',
        'jumlah_pinjaman' => 'integer',
        'bunga_pinjaman_tahunan' => 'float',
        'tenor_pinjaman_bulan' => 'integer',
        'masa_manfaat_aset_tahun' => 'float',
        'cogs' => 'integer',
        'biaya_tetap' => 'integer',
        'jumlah_karyawan' => 'integer',
        'gaji_per_karyawan' => 'integer',
        'tingkat_pertumbuhan' => 'float',
        'kenaikan_harga_jual_tahunan' => 'float',
        'inflasi_cogs_tahunan' => 'float',
        'inflasi_biaya_tetap_tahunan' => 'float',
        'durasi_proyeksi_tahun' => 'integer',
        'tarif_pajak' => 'float',
        'inflasi_biaya' => 'float',
        'hari_piutang' => 'integer',
        'hari_utang_usaha' => 'integer',
        'seasonality_factors' => 'array',
    ];

    /**
     * Relasi ke model User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}