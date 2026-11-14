### 2. Perbarui `layanan-penerima/README.md` (Layanan Pembukuan & Keuangan)

Ganti isi `layanan-penerima/README.md` agar menjelaskan perannya sebagai *consumer* pembukuan:

```markdown
# Layanan Pembukuan & Keuangan Konsinyasi (`layanan-penerima`)

**Kelompok:** Anti-Normal  
**Mata Kuliah:** Integrasi Cloud (Semester 5)  
**Peran Layanan:** Event Consumer & Accounting Service (Port `8002`)

---

## Deskripsi Layanan
Layanan penerima *event* asinkron dari RabbitMQ (`toko.events` $\rightarrow$ queue `stok-penerima-queue`) yang secara otomatis mencatat buku besar keuangan usaha tahu/tempe setiap kali manifest setoran sore diselesaikan di `stok-tempe`.

### Mekanisme Integrasi
- **Event Consumer:** `App\Jobs\ProcessKonsinyasiEvent` mendengarkan routing key `konsinyasi.*`.
- **Idempotensi:** Setiap pesan diperiksa melalui `message_id` pada tabel `processed_messages` untuk mencegah pencatatan ganda (*duplicate processing*).
- **Pencatatan Jurnal Otomatis (`jurnal_pembukuan`):**
  - `PEMASUKAN_SETORAN` — mencatat total uang setoran dari produk terjual.
  - `BEBAN_WASTE` — mencatat nilai kerugian produk rusak/basi.

---

## Endpoint Laporan Pembukuan

| Method | Endpoint Path | Deskripsi |
| :--- | :--- | :--- |
| `GET` | `/api/v1/pembukuan` | Menampilkan ringkasan total pemasukan, kerugian waste, dan riwayat jurnal |

---

## Cara Menjalankan Worker & Cek Laporan

```bash
# 1. Jalankan container & migrasi database
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate

# 2. Jalankan worker penerima pesan RabbitMQ
./vendor/bin/sail artisan queue:work rabbitmq --queue=stok-penerima-queue

# 3. Cek hasil pembukuan via API
curl http://localhost:8002/api/v1/pembukuan