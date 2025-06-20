<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';
    protected $primaryKey = 'barang_id';

    protected $fillable = [
        'nama_barang',
        'kategori_id',
        'satuan',
        'stok',
        'umkm_id',
        'batas_minimum'
    ];

    // Ensure timestamps are enabled
    public $timestamps = true;

    // Cast timestamps to datetime
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'stok' => 'integer',
        'batas_minimum' => 'integer',
    ];

    /**
     * Relationship to UMKM
     */
    public function umkm()
    {
        return $this->belongsTo(Umkm::class, 'umkm_id', 'id');
    }

    /**
     * Relationship to Kategori
     */
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id', 'kategori_id');
    }

    /**
     * Relationship to Barang Masuk
     */
    public function barangMasuk()
    {
        return $this->hasMany(BarangMasuk::class, 'barang_id', 'barang_id');
    }

    /**
     * Relationship to Barang Keluar
     */
    public function barangKeluar()
    {
        return $this->hasMany(BarangKeluar::class, 'barang_id', 'barang_id');
    }

    /**
     * Relationship to Notifikasi Stok
     */
    public function notifikasiStok()
    {
        return $this->hasMany(NotifikasiStok::class, 'barang_id', 'barang_id');
    }
}
