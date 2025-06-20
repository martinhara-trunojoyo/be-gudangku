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
        Schema::create('supplier', function (Blueprint $table) {
            $table->id('supplier_id');
            $table->string('nama_supplier', 100);
            $table->text('alamat_supplier');
            $table->string('kontak_supplier', 50);
            $table->timestamps();
            $table->unsignedBigInteger('umkm_id');
            
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
        Schema::dropIfExists('supplier');
    }
};
