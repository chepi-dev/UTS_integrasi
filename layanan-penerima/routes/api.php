<?php

use App\Models\JurnalPembukuan;
use Illuminate\Support\Facades\Route;

Route::get('/v1/pembukuan', function () {
    $jurnal = JurnalPembukuan::latest()->get();

    return response()->json([
        'ringkasan' => [
            'total_pemasukan' => $jurnal->where('jenis_jurnal', 'PEMASUKAN_SETORAN')->sum('nominal'),
            'total_kerugian_waste' => $jurnal->where('jenis_jurnal', 'BEBAN_WASTE')->sum('nominal'),
        ],
        'transaksi' => $jurnal,
    ]);
});