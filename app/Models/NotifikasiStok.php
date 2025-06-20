<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifikasiStok extends Model
{
    use HasFactory;

    protected $table = 'notifikasi_stok';
    protected $primaryKey = 'notifikasi_id';

    protected $fillable = [
        'pesan',
        'status',
        'tanggal',
        'barang_id'
    ];

    // Ensure timestamps are enabled
    public $timestamps = true;

    // Cast timestamps to datetime
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'tanggal' => 'datetime',
    ];

    /**
     * Relationship to Barang
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'barang_id');
    }
}
