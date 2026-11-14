<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedagang extends Model
{
    use HasFactory;

    protected $table = 'pedagang';
    protected $primaryKey = 'kode_pedagang';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode_pedagang',
        'nama_pedagang',
        'no_telepon',
        'wilayah_jualan',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function konsinyasi(): HasMany
    {
        return $this->hasMany(Konsinyasi::class, 'kode_pedagang', 'kode_pedagang');
    }
}