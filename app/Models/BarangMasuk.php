<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangMasuk extends Model
{
    use HasFactory;

    protected $table = 'barang_masuk';
    protected $primaryKey = 'masuk_id';

    protected $fillable = [
        'jumlah_masuk',
        'tanggal_masuk',
        'supplier_id',
        'barang_id',
        'user_id'
    ];

    // Ensure timestamps are enabled
    public $timestamps = true;

    // Cast timestamps to datetime
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'tanggal_masuk' => 'datetime',
        'jumlah_masuk' => 'integer',
    ];

    /**
     * Relationship to Supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

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
