<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailKonsinyasi extends Model
{
    protected $table = 'detail_konsinyasi';

    protected $fillable = [
        'kode_manifest',
        'kode_produk',
        'harga_satuan_snapshot',
        'jumlah_dibawa',
        'jumlah_terjual',
        'jumlah_retur_layak',
        'jumlah_rusak',
    ];

    protected $casts = [
        'harga_satuan_snapshot' => 'integer',
        'jumlah_dibawa' => 'integer',
        'jumlah_terjual' => 'integer',
        'jumlah_retur_layak' => 'integer',
        'jumlah_rusak' => 'integer',
    ];

    public function konsinyasi(): BelongsTo
    {
        return $this->belongsTo(Konsinyasi::class, 'kode_manifest', 'kode_manifest');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'kode_produk', 'kode_produk');
    }
}