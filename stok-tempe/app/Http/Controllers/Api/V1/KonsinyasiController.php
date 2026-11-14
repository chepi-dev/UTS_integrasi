<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\ApiProblem;
use App\Http\Controllers\Controller;
use App\Http\Requests\AmbilKonsinyasiRequest;
use App\Http\Requests\SetoranKonsinyasiRequest;
use App\Http\Resources\KonsinyasiResource;
use App\Models\DetailKonsinyasi;
use App\Models\Konsinyasi;
use App\Models\Pedagang;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KonsinyasiController extends Controller
{
    public function index(Request $request)
    {
        $query = Konsinyasi::with(['pedagang', 'details.produk'])->latest('waktu_ambil');

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->query('status')));
        }

        return response()->json(KonsinyasiResource::collection($query->get()));
    }

    /**
     * Pengambilan Pagi (BR-01, BR-03, BR-04)
     */
    public function store(AmbilKonsinyasiRequest $request)
    {
        $valid = $request->validated();
        $pedagang = Pedagang::find($valid['kode_pedagang']);

        if (!$pedagang) {
            return ApiProblem::respond(
                404,
                'Pedagang tidak ditemukan',
                sprintf('Pedagang dengan kode %s tidak terdaftar.', $valid['kode_pedagang'])
            );
        }

        // BR-03: Satu manifest aktif per pedagang
        $punyaManifestAktif = Konsinyasi::where('kode_pedagang', $pedagang->kode_pedagang)
            ->where('status', 'BERJALAN')
            ->exists();

        if ($punyaManifestAktif) {
            return ApiProblem::respond(
                409,
                'Pedagang masih memiliki tanggungan manifest aktif',
                sprintf('Pedagang %s (%s) harus menyelesaikan setoran manifest sebelumnya terlebih dahulu.', $pedagang->nama_pedagang, $pedagang->kode_pedagang),
                'https://localhost:8000/api/v1/problems/manifest-aktif'
            );
        }

        try {
            $manifest = DB::transaction(function () use ($pedagang, $valid) {
                $tanggal = now()->toDateString();
                $urutan = Konsinyasi::whereDate('tanggal_konsinyasi', $tanggal)->count() + 1;
                $kodeManifest = sprintf('KSY-%s-%03d', now()->format('Ymd'), $urutan);

                $konsinyasi = Konsinyasi::create([
                    'kode_manifest' => $kodeManifest,
                    'kode_pedagang' => $pedagang->kode_pedagang,
                    'tanggal_konsinyasi' => $tanggal,
                    'status' => 'BERJALAN',
                    'total_tagihan_setoran' => 0,
                    'total_kerugian_waste' => 0,
                    'waktu_ambil' => now(),
                ]);

                foreach ($valid['items'] as $item) {
                    $produk = Produk::where('kode_produk', $item['kode_produk'])
                        ->lockForUpdate()
                        ->first();

                    if (!$produk) {
                        throw new RuntimeException("NOT_FOUND:Produk dengan kode {$item['kode_produk']} tidak ditemukan.");
                    }

                    // BR-01: Cek ketersediaan stok gudang
                    if ($produk->stok_gudang < $item['jumlah_dibawa']) {
                        throw new RuntimeException(
                            sprintf('CONFLICT:Stok %s di gudang tersisa %d, diminta %d.', $produk->nama_produk, $produk->stok_gudang, $item['jumlah_dibawa'])
                        );
                    }

                    $produk->stok_gudang -= $item['jumlah_dibawa'];
                    $produk->save();

                    // BR-04: Kunci harga satuan saat pengambilan pagi
                    DetailKonsinyasi::create([
                        'kode_manifest' => $konsinyasi->kode_manifest,
                        'kode_produk' => $produk->kode_produk,
                        'harga_satuan_snapshot' => $produk->harga_satuan,
                        'jumlah_dibawa' => $item['jumlah_dibawa'],
                    ]);
                }

                return $konsinyasi->load(['pedagang', 'details.produk']);
            });
        } catch (RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'NOT_FOUND:')) {
                return ApiProblem::respond(404, 'Produk tidak ditemukan', substr($e->getMessage(), 10));
            }
            if (str_starts_with($e->getMessage(), 'CONFLICT:')) {
                return ApiProblem::respond(409, 'Stok gudang tidak mencukupi', substr($e->getMessage(), 9));
            }
            throw $e;
        }

        return response()
            ->json(new KonsinyasiResource($manifest), 201)
            ->header('Location', url('/api/v1/konsinyasi/' . $manifest->kode_manifest));
    }

    /**
     * Setoran & Rekonsiliasi Sore (BR-02, BR-05, BR-06)
     */
    public function setoran(SetoranKonsinyasiRequest $request, string $kode_manifest)
    {
        $konsinyasi = Konsinyasi::with('details.produk')->find($kode_manifest);

        if (!$konsinyasi) {
            return ApiProblem::respond(
                404,
                'Manifest tidak ditemukan',
                sprintf('Manifest dengan kode %s tidak tersedia.', $kode_manifest)
            );
        }

        // BR-05: Manifest selesai terkunci permanen
        if ($konsinyasi->status === 'SELESAI') {
            return ApiProblem::respond(
                409,
                'Manifest sudah diselesaikan sebelumnya',
                sprintf('Manifest %s sudah disetor pada %s.', $kode_manifest, $konsinyasi->waktu_setor?->toISOString())
            );
        }

        $valid = $request->validated();
        $inputItems = collect($valid['items'])->keyBy('kode_produk');
        $detailDb = $konsinyasi->details->keyBy('kode_produk');

        // Validasi kelengkapan & kecocokan daftar produk
        if ($inputItems->count() !== $detailDb->count() || $inputItems->keys()->diff($detailDb->keys())->isNotEmpty()) {
            return ApiProblem::respond(
                422,
                'Daftar item rekonsiliasi tidak cocok',
                'Daftar produk pada setoran sore harus sama persis dengan item yang diambil pada manifest pagi.',
                'https://localhost:8000/api/v1/problems/rekonsiliasi-item-tidak-cocok'
            );
        }

        // BR-02: Zero-Discrepancy (terjual + retur_layak + rusak == dibawa)
        foreach ($detailDb as $kodeProduk => $detail) {
            $row = $inputItems[$kodeProduk];
            $totalHitung = $row['jumlah_terjual'] + $row['jumlah_retur_layak'] + $row['jumlah_rusak'];

            if ($totalHitung !== $detail->jumlah_dibawa) {
                return ApiProblem::respond(
                    422,
                    'Rekonsiliasi item tidak seimbang',
                    sprintf(
                        'Produk %s dibawa sebanyak %d, namun total hitungan setor adalah %d (terjual: %d, retur: %d, rusak: %d).',
                        $kodeProduk,
                        $detail->jumlah_dibawa,
                        $totalHitung,
                        $row['jumlah_terjual'],
                        $row['jumlah_retur_layak'],
                        $row['jumlah_rusak']
                    ),
                    'https://localhost:8000/api/v1/problems/rekonsiliasi-tidak-seimbang'
                );
            }
        }

        $updated = DB::transaction(function () use ($konsinyasi, $detailDb, $inputItems) {
            $totalTagihan = 0;
            $totalWaste = 0;

            foreach ($detailDb as $kodeProduk => $detail) {
                $row = $inputItems[$kodeProduk];

                $detail->update([
                    'jumlah_terjual' => $row['jumlah_terjual'],
                    'jumlah_retur_layak' => $row['jumlah_retur_layak'],
                    'jumlah_rusak' => $row['jumlah_rusak'],
                ]);

                // Kembalikan retur layak jual ke stok_gudang
                if ($row['jumlah_retur_layak'] > 0) {
                    $produk = Produk::where('kode_produk', $kodeProduk)->lockForUpdate()->first();
                    $produk->stok_gudang += $row['jumlah_retur_layak'];
                    $produk->save();
                }

                // BR-04 & BR-06: Hitung keuangan pakai harga snapshot
                $totalTagihan += $row['jumlah_terjual'] * $detail->harga_satuan_snapshot;
                $totalWaste += $row['jumlah_rusak'] * $detail->harga_satuan_snapshot;
            }

            $konsinyasi->update([
                'status' => 'SELESAI',
                'total_tagihan_setoran' => $totalTagihan,
                'total_kerugian_waste' => $totalWaste,
                'waktu_setor' => now(),
            ]);

            return $konsinyasi->fresh(['pedagang', 'details.produk']);
        });

        return response()->json(new KonsinyasiResource($updated));
    }
}