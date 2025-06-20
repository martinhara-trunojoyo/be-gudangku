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
        Schema::create('barang', function (Blueprint $table) {
            $table->id('barang_id');
            $table->string('nama_barang', 100);
            $table->unsignedBigInteger('kategori_id');
            $table->string('satuan', 45);
            $table->integer('stok');
            $table->unsignedBigInteger('umkm_id');
            $table->integer('batas_minimum')->default(0);
            $table->timestamps();
            
            $table->foreign('kategori_id')
                  ->references('kategori_id')
                  ->on('kategori')
                  ->onDelete('restrict');
                  
            $table->foreign('umkm_id')
                  ->references('id')
                  ->on('umkm')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
