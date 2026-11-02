<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchStok extends Model
{
    use HasFactory;

    protected $table = 'batch_stok';
    protected $fillable = [
        'kode_produk',
        'nomor_batch',
        'tanggal_produksi',
        'tanggal_kadaluarsa',
        'jumlah_stok',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'kode_produk');
    }
}