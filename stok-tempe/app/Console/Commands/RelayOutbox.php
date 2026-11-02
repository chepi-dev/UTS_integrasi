<?php

namespace App\Console\Commands;

use App\Messaging\RabbitPublisher;
use App\Models\Outbox;
use Illuminate\Console\Command;
use Throwable;

class RelayOutbox extends Command
{
    protected $signature = 'outbox:relay {--terus : Ulangi terus sampai dihentikan (Ctrl+C)} {--jeda=2 : Detik antarputaran}';

    protected $description = 'Terbitkan pesan outbox yang belum terkirim ke RabbitMQ';

    public function handle(RabbitPublisher $publisher): int
    {
        do {
            $terkirim = $this->satuPutaran($publisher);
            if ($terkirim > 0) {
                $this->info("{$terkirim} pesan terkirim.");
            }
            if ($this->option('terus')) {
                sleep((int) $this->option('jeda'));
            }
        } while ($this->option('terus'));

        return self::SUCCESS;
    }

    private function satuPutaran(RabbitPublisher $publisher): int
    {
        $terkirim = 0;

        $tertunda = Outbox::whereNull('dikirim_pada')->orderBy('id')->limit(100)->get();

        foreach ($tertunda as $pesan) {
            try {
                $publisher->publish($pesan->routing_key, $pesan->payload, $pesan->message_id, $pesan->jenis);
            } catch (Throwable $e) {
                $this->warn("Gagal menerbitkan {$pesan->message_id}: {$e->getMessage()}");
                break;
            }

            $pesan->update(['dikirim_pada' => now()]);
            $terkirim++;
        }

        return $terkirim;
    }
}