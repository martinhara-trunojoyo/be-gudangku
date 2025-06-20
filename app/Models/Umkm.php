<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Umkm extends Model
{
    use HasFactory;

    protected $table = 'umkm';

    protected $fillable = [
        'nama_umkm',
        'pemilik',
        'alamat',
        'kontak'
    ];

    // Ensure timestamps are enabled
    public $timestamps = true;

    // Cast timestamps to datetime
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'umkm_id', 'id');
    }

    public function categories()
    {
        return $this->hasMany(Kategori::class, 'umkm_id', 'id');
    }

    public function barang()
    {
        return $this->hasMany(Barang::class, 'umkm_id', 'id');
    }

    public function barangMasuk()
    {
        return $this->hasManyThrough(BarangMasuk::class, Barang::class, 'umkm_id', 'barang_id', 'id', 'barang_id');
    }

    public function barangKeluar()
    {
        return $this->hasManyThrough(BarangKeluar::class, Barang::class, 'umkm_id', 'barang_id', 'id', 'barang_id');
    }

    public function notifikasiStok()
    {
        return $this->hasManyThrough(NotifikasiStok::class, Barang::class, 'umkm_id', 'barang_id', 'id', 'barang_id');
    }
}