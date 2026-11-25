<?php

namespace App\Http\Controllers;

use App\Models\JurnalPembukuan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JurnalPembukuanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JurnalPembukuan::latest('id');

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_transaksi', $request->query('tanggal'));
        }

        $jurnal = $query->get();

        return response()->json([
            'ringkasan' => [
                'total_pemasukan' => $jurnal->where('jenis_jurnal', 'PEMASUKAN_SETORAN')->sum('nominal'),
                'total_kerugian_waste' => $jurnal->where('jenis_jurnal', 'BEBAN_WASTE')->sum('nominal'),
            ],
            'transaksi' => $jurnal,
        ]);
    }
}