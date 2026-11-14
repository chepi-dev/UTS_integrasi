<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Konsinyasi extends Model
{
    protected $table = 'konsinyasi';
    protected $primaryKey = 'kode_manifest';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode_manifest',
        'kode_pedagang',
        'tanggal_konsinyasi',
        'status',
        'total_tagihan_setoran',
        'total_kerugian_waste',
        'waktu_ambil',
        'waktu_setor',
    ];

    protected $casts = [
        'tanggal_konsinyasi' => 'date:Y-m-d',
        'total_tagihan_setoran' => 'integer',
        'total_kerugian_waste' => 'integer',
        'waktu_ambil' => 'datetime',
        'waktu_setor' => 'datetime',
    ];

    public function pedagang(): BelongsTo
    {
        return $this->belongsTo(Pedagang::class, 'kode_pedagang', 'kode_pedagang');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailKonsinyasi::class, 'kode_manifest', 'kode_manifest');
    }
}