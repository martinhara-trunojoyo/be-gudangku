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
        Schema::create('notifikasi_stok', function (Blueprint $table) {
            $table->id('notifikasi_id');
            $table->text('pesan');
            $table->enum('status', ['read', 'unread']);
            $table->dateTime('tanggal');
            $table->unsignedBigInteger('barang_id');
            $table->timestamps();

            $table->foreign('barang_id')
                  ->references('barang_id')
                  ->on('barang')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi_stok');
    }
};
