<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';
    protected $primaryKey = 'kode_produk';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode_produk',
        'kategori_id',
        'nama_produk',
        'satuan',
        'harga_satuan',
        'jumlah_stok',
        'stok_minimum',
        'status',
        'terakhir_diperbarui',
    ];

    protected $casts = [
        'harga_satuan' => 'integer',
        'jumlah_stok' => 'integer',
        'stok_minimum' => 'integer',
        'terakhir_diperbarui' => 'datetime',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriProduk::class, 'kategori_id');
    }

    public function batches()
    {
        return $this->hasMany(BatchStok::class, 'kode_produk');
    }

    public function riwayat()
    {
        return $this->hasMany(RiwayatStok::class, 'kode_produk');
    }

    public function getStatusStokAttribute(): string
    {
        if ($this->jumlah_stok == 0) return 'habis';
        if ($this->jumlah_stok <= $this->stok_minimum) return 'kritis';
        return 'aman';
    }
}