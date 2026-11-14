<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';
    protected $primaryKey = 'kode_produk';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode_produk',
        'nama_produk',
        'jenis_produk',
        'harga_satuan',
        'stok_gudang',
    ];

    protected $casts = [
        'harga_satuan' => 'integer',
        'stok_gudang' => 'integer',
    ];

    public function getTersediaAttribute(): bool
    {
        return $this->stok_gudang > 0;
    }

    public function detailKonsinyasi(): HasMany
    {
        return $this->hasMany(DetailKonsinyasi::class, 'kode_produk', 'kode_produk');
    }
}