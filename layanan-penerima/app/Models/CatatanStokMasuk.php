<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatatanStokMasuk extends Model
{
    protected $table = 'catatan_stok_masuk';
    protected $fillable = ['kode_produk', 'nama_produk', 'jenis_penyesuaian', 'jumlah', 'terjadi_pada'];
}