<?php

namespace Tests\Feature;

use App\Jobs\ProcessStokDisesuaikanEvent;
use App\Models\CatatanStokMasuk;
use App\Models\ProcessedMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumerTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumer_berhasil_memproses_event(): void
    {
        $messageId = 'msg-12345';
        $payload = [
            'kode_produk' => 'TAH-001',
            'nama_produk' => 'Tahu Sumedang',
            'jenis_penyesuaian' => 'masuk_produksi',
            'jumlah' => 50,
            'terjadi_pada' => now()->toDateTimeString(),
        ];

        $job = new ProcessStokDisesuaikanEvent($payload, $messageId);
        $job->handle();

        $this->assertDatabaseHas('catatan_stok_masuk', [
            'kode_produk' => 'TAH-001',
            'jumlah' => 50,
        ]);

        $this->assertDatabaseHas('processed_messages', [
            'message_id' => $messageId,
        ]);
    }

    public function test_handler_idempoten_mencegah_duplikasi(): void
    {
        $messageId = 'msg-12345';
        $payload = [
            'kode_produk' => 'TAH-001',
            'nama_produk' => 'Tahu Sumedang',
            'jenis_penyesuaian' => 'masuk_produksi',
            'jumlah' => 50,
        ];

        // Eksekusi pertama
        $job = new ProcessStokDisesuaikanEvent($payload, $messageId);
        $job->handle();

        // Eksekusi ulang dengan messageId yang SAMA
        $jobRepeat = new ProcessStokDisesuaikanEvent($payload, $messageId);
        $jobRepeat->handle();

        // Pastikan record tidak terduplikasi (tetap 1)
        $this->assertEquals(1, CatatanStokMasuk::where('kode_produk', 'TAH-001')->count());
        $this->assertEquals(1, ProcessedMessage::where('message_id', $messageId)->count());
    }
}