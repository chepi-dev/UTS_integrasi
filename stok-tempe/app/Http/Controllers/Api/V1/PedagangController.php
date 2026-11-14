<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePedagangRequest;
use App\Http\Resources\PedagangResource;
use App\Models\Pedagang;

class PedagangController extends Controller
{
    public function index()
    {
        $data = Pedagang::orderBy('kode_pedagang')->get();
        return response()->json(PedagangResource::collection($data));
    }

    public function store(StorePedagangRequest $request)
    {
        $valid = $request->validated();

        $kode = 'PDG-' . str_pad(Pedagang::count() + 1, 3, '0', STR_PAD_LEFT);

        $pedagang = Pedagang::create([
            'kode_pedagang' => $kode,
            'nama_pedagang' => $valid['nama_pedagang'],
            'no_telepon' => $valid['no_telepon'],
            'wilayah_jualan' => $valid['wilayah_jualan'] ?? null,
            'aktif' => true,
        ]);

        return response()->json(new PedagangResource($pedagang), 201);
    }
}