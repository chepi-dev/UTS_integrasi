<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurnalPembukuan extends Model
{
    protected $table = 'jurnal_pembukuan';

    protected $guarded = [];

    protected $casts = [
        'tanggal_transaksi' => 'date:Y-m-d',
        'nominal' => 'integer',
    ];
}