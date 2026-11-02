<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatStok extends Model
{
    use HasFactory;

    protected $table = 'riwayat_stok';
    protected $fillable = [
        'kode_produk',
        'jenis_penyesuaian',
        'jumlah',
        'stok_sebelum',
        'stok_sesudah',
        'keterangan',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'kode_produk');
    }
}