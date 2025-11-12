<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('simulations', function (Blueprint $table) {
            $table->id();
            $table->string('nama_skenario');
            
            // PENTING: FOREIGN KEY untuk menghubungkan ke tabel users
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            
            // Input Bisnis
            $table->bigInteger('harga_jual');
            $table->bigInteger('volume_penjualan');
            $table->bigInteger('capex');
            $table->bigInteger('modal_kerja');
            $table->bigInteger('cogs');
            $table->bigInteger('biaya_tetap');
            $table->float('tingkat_pertumbuhan');
            $table->float('tarif_pajak');
            $table->float('inflasi_biaya');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulations');
    }
};