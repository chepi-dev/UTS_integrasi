<?php

namespace App\Jobs;

use App\Models\JurnalPembukuan;
use App\Models\ProcessedMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessKonsinyasiEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public array $payload,
        public string $messageId,
        public string $routingKey = ''
    ) {}

    public function handle(): void
    {
        // 1. Pengecekan Idempotensi
        if (ProcessedMessage::where('message_id', $this->messageId)->exists()) {
            Log::info("Pesan {$this->messageId} sudah pernah diproses. Melewati.");
            return;
        }

        // 2. Validasi struktur dasar payload
        if (!isset($this->payload['kode_manifest']) || !isset($this->payload['pedagang'])) {
            throw new Exception('Payload konsinyasi tidak valid.');
        }

        DB::transaction(function () {
            $kodeManifest = $this->payload['kode_manifest'];
            $pedagang = $this->payload['pedagang'];
            $tanggal = $this->payload['tanggal_konsinyasi'] ?? now()->toDateString();

            // Hanya buat jurnal keuangan saat setoran sore selesai (konsinyasi.disetor)
            if ($this->routingKey === 'konsinyasi.disetor' && isset($this->payload['ringkasan_keuangan'])) {
                $keuangan = $this->payload['ringkasan_keuangan'];
                $pemasukan = (int) ($keuangan['total_tagihan_setoran'] ?? 0);
                $kerugian = (int) ($keuangan['total_kerugian_waste'] ?? 0);

                if ($pemasukan > 0) {
                    JurnalPembukuan::create([
                        'kode_manifest' => $kodeManifest,
                        'kode_pedagang' => $pedagang['kode_pedagang'],
                        'nama_pedagang' => $pedagang['nama_pedagang'],
                        'tanggal_transaksi' => $tanggal,
                        'jenis_jurnal' => 'PEMASUKAN_SETORAN',
                        'nominal' => $pemasukan,
                        'keterangan' => "Setoran penjualan konsinyasi {$kodeManifest} ({$pedagang['nama_pedagang']})",
                    ]);
                }

                if ($kerugian > 0) {
                    JurnalPembukuan::create([
                        'kode_manifest' => $kodeManifest,
                        'kode_pedagang' => $pedagang['kode_pedagang'],
                        'nama_pedagang' => $pedagang['nama_pedagang'],
                        'tanggal_transaksi' => $tanggal,
                        'jenis_jurnal' => 'BEBAN_WASTE',
                        'nominal' => $kerugian,
                        'keterangan' => "Kerugian tahu/tempe rusak/basi pada {$kodeManifest} ({$pedagang['nama_pedagang']})",
                    ]);
                }
            }

            ProcessedMessage::create([
                'message_id' => $this->messageId,
                'event_name' => $this->routingKey ?: 'konsinyasi.event',
                'processed_at' => now(),
            ]);
        });

        Log::info("Event {$this->routingKey} untuk manifest {$this->payload['kode_manifest']} berhasil dibukukan.");
    }
}