<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangKeluar extends Model
{
    use HasFactory;

    protected $table = 'barang_keluar';
    protected $primaryKey = 'keluar_id';

    protected $fillable = [
        'jumlah_keluar',
        'tanggal_keluar',
        'tujuan',
        'barang_id',
        'user_id'
    ];

    // Ensure timestamps are enabled
    public $timestamps = true;

    // Cast timestamps to datetime
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'tanggal_keluar' => 'datetime',
        'jumlah_keluar' => 'integer',
    ];

    /**
     * Relationship to Barang
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'barang_id');
    }

    /**
     * Relationship to User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
