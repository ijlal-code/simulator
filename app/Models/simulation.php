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
        'capex', 
        'modal_kerja', 
        'cogs', 
        'biaya_tetap', 
        'tingkat_pertumbuhan', 
        'tarif_pajak', 
        'inflasi_biaya', 
    ];

    protected $casts = [
        'harga_jual' => 'integer',
        'volume_penjualan' => 'integer',
        'capex' => 'integer',
        'modal_kerja' => 'integer',
        'cogs' => 'integer',
        'biaya_tetap' => 'integer',
        'tingkat_pertumbuhan' => 'float',
        'tarif_pajak' => 'float',
        'inflasi_biaya' => 'float',
    ];

    /**
     * Relasi ke model User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}