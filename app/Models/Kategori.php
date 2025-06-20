<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';
    protected $primaryKey = 'kategori_id';

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
        'umkm_id'
    ];

    // Ensure timestamps are enabled
    public $timestamps = true;

    // Cast timestamps to datetime
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to UMKM
     */
    public function umkm()
    {
        return $this->belongsTo(Umkm::class, 'umkm_id', 'id');
    }

    /**
     * Relationship to Barang
     */
    public function barang()
    {
        return $this->hasMany(barang::class, 'kategori_id', 'kategori_id');
    }
}
