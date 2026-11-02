# Laporan Praktikum 2 & Panduan Eksekusi Microservices (RabbitMQ)

Dokumentasi ini berisi instruksi lengkap untuk menjalankan, menguji, dan memverifikasi integrasi asinkron antara **`stok-tempe`** (Publisher) dan **`layanan-penerima`** (Consumer) menggunakan Laravel Sail dan RabbitMQ.

---

## 📋 1. Kontrak Event

| Field | Tipe Data | Deskripsi | Contoh Value |
| :--- | :--- | :--- | :--- |
| `kode_produk` | `string` | Kode unik item/produk | `"TEM-001"` |
| `nama_produk` | `string` | Nama barang | `"Tempe Mendoan"` |
| `jenis_penyesuaian` | `string (enum)` | Kategori transaksi | `"masuk_produksi"` |
| `jumlah` | `integer` | Jumlah kuantitas stok | `100` |
| `terjadi_pada` | `timestamp` | Waktu transaksi | `"2026-11-02 01:40:00"` |
| `message_id` | `string` | UUID/ID Unik untuk idempotency | `"msg-uts-001"` |

> **Alasan Asinkron:**  
> Proses pencatatan pada `layanan-penerima` dilakukan secara asinkron (*decoupled*) agar transaksi pada `stok-tempe` tidak terhambat (*non-blocking*). Jika `layanan-penerima` mengalami downtime, pesan tetap aman tersimpan di dalam antrean (*queue*) RabbitMQ secara durable.

---

## 🛠️ 2. Panduan Eksekusi (Full Commands)

### A. Persiapan & Migrasi Database

#### 1. Proyek `stok-tempe` (Publisher)
```bash
cd ~/UTS/stok-tempe

# 1. Clear cache konfigurasi agar membaca QUEUE_CONNECTION=rabbitmq
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear

# 2. Pastikan Sail dan RabbitMQ berjalan
./vendor/bin/sail up -d
```

#### 2. Proyek `layanan-penerima` (Consumer)
```bash
cd ~/UTS/layanan-penerima

# 1. Jalankan database migration bersih di environment utama
./vendor/bin/sail artisan migrate:fresh

# 2. Berikan izin akses database testing untuk automated test
./vendor/bin/sail exec mysql mysql -u root -p'password' -e "CREATE DATABASE IF NOT EXISTS testing; GRANT ALL PRIVILEGES ON testing.* TO 'sail'@'%'; FLUSH PRIVILEGES;"

# 3. Jalankan pengujian otomatis (Unit & Feature Test)
./vendor/bin/sail artisan test
```

---

### B. Simulasi Pengiriman & Pemrosesan Pesan Real-Time

#### Langkah 1: Jalankan Queue Worker (Terminal Consumer)
Di terminal **`layanan-penerima`**, jalankan worker:
```bash
cd ~/UTS/layanan-penerima
./vendor/bin/sail artisan queue:work rabbitmq --queue=stok-penerima-queue
```

#### Langkah 2: Terbitkan Event / Job (Terminal Publisher)
Buka terminal baru di folder **`stok-tempe`**, lalu masuk ke Tinker:
```bash
cd ~/UTS/stok-tempe
./vendor/bin/sail artisan tinker
```
Jalankan kode PHP berikut di dalam prompt Tinker (`>`):
```php
dispatch(new \App\Jobs\ProcessStokDisesuaikanEvent([
    'kode_produk' => 'TEM-001',
    'nama_produk' => 'Tempe Mendoan',
    'jenis_penyesuaian' => 'masuk_produksi',
    'jumlah' => 100,
    'terjadi_pada' => now()->toDateTimeString(),
], 'msg-uts-001'))->onQueue('stok-penerima-queue');
```

#### Langkah 3: Verifikasi Isi Database Pembukuan
Buka terminal baru di **`layanan-penerima`** dan jalankan perintah verifikasi:
```bash
./vendor/bin/sail artisan tinker --execute="dump(App\Models\CatatanStokMasuk::all()); dump(App\Models\ProcessedMessage::all());"
```

---

## 🧪 3. Tabel Hasil Skenario Uji Praktikum

| Skenario Uji | Kondisi Pengujian | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- |
| **Point-to-Point (Durable)** | Worker `layanan-penerima` dimatikan, event diterbitkan dari `stok-tempe`. | Pesan tersimpan aman di RabbitMQ Queue dan diproses saat worker dinyalakan. | **PASS** |
| **Idempotency Handler** | Event dengan `message_id` sama (`msg-uts-001`) dikirim 2 kali. | Event kedua diabaikan, tabel `catatan_stok_masuk` tidak terduplikasi. | **PASS** |
| **Dead Letter Queue (DLQ)** | Payload dikirim tanpa `kode_produk` (corrupt/invalid). | Pesan gagal dialihkan ke DLQ, tidak hilang atau terbuang diam-diam. | **PASS** |
| **Automated Testing** | Menjalankan `./vendor/bin/sail artisan test`. | Seluruh pengujian `ConsumerTest` menunjukkan status **PASS (Hijau)**. | **PASS** |

---

## 📌 4. Daftar Periksa Kelulusan UTS

- [x] RabbitMQ Management UI aktif dan dapat diakses di `http://localhost:15672`
- [x] Microservice `stok-tempe` menerbitkan event persistent lengkap dengan `message_id`
- [x] Consumer `layanan-penerima` mengimplementasikan fitur Idempotency
- [x] Automated unit & feature test seluruhnya lulus (**PASS**)
- [x] Verifikasi data tersimpan di MySQL database `UTS_pembukuan`