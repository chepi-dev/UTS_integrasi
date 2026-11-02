<?php

namespace App\Jobs;

use App\Models\CatatanStokMasuk;
use App\Models\ProcessedMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessStokDisesuaikanEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public array $payload,
        public string $messageId
    ) {}

    public function handle(): void
    {
        // 1. Pengecekan Idempotency (Gunakan message_id unik)
        $alreadyProcessed = ProcessedMessage::where('message_id', $this->messageId)->exists();

        if ($alreadyProcessed) {
            Log::info(sprintf('Pesan dengan ID %s sudah pernah diproses. Melewati.', $this->messageId));
            return;
        }

        // 2. Validasi Payload (Format corrupt dialihkan ke Exception agar masuk DLQ)
        if (!isset($this->payload['kode_produk']) || !isset($this->payload['jumlah'])) {
            Log::error('Payload pesan tidak valid! Memindahkan ke Dead Letter Queue.');
            throw new Exception('Payload pesan corrupt atau tidak valid.');
        }

        // 3. Simpan Ke Database Pembukuan
        DB::transaction(function () {
            CatatanStokMasuk::create([
                'kode_produk' => $this->payload['kode_produk'],
                'nama_produk' => $this->payload['nama_produk'] ?? 'Tanpa Nama',
                'jenis_penyesuaian' => $this->payload['jenis_penyesuaian'],
                'jumlah' => $this->payload['jumlah'],
                'terjadi_pada' => $this->payload['terjadi_pada'] ?? now(),
            ]);

            ProcessedMessage::create([
                'message_id' => $this->messageId,
                'event_name' => 'StokDisesuaikan',
                'processed_at' => now(),
            ]);
        });

        Log::info(sprintf('Event StokDisesuaikan produk %s berhasil dicatat.', $this->payload['kode_produk']));
    }
}