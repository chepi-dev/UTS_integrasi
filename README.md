# Sistem Manajemen Stok & Konsinyasi Tahu Tempe (Integrasi Cloud)

**Kelompok:** Anti-Normal  
**Mata Kuliah:** Integrasi Cloud (Semester 5)  
**Arsitektur:** Monorepo Microservices (Laravel 12, MySQL 8, RabbitMQ, OpenAPI 3.1.0, RFC 9457)

---

## 1. Arsitektur Layanan

Repositori ini terdiri atas dua layanan independen yang berkomunikasi secara asinkron menggunakan **Transactional Outbox Pattern** dan **RabbitMQ (`topic` exchange `toko.events`)**:

1. **`stok-tempe` (Port `8000`) — Layanan Gudang & Konsinyasi (Publisher)**
   - Mengelola katalog produk tahu/tempe dan pencatatan hasil produksi masuk gudang.
   - Mengelola siklus distribusi harian: **Pengambilan Pagi** (`POST /api/v1/konsinyasi`) dan **Rekonsiliasi Setoran Sore** (`POST /api/v1/konsinyasi/{kode_manifest}/setoran`).
   - Menerapkan aturan bisnis ketat (`BR-01` s.d. `BR-06`), *pessimistic locking* (`lockForUpdate`), format error standar **RFC 9457 (`application/problem+json`)**, serta penulisan *event* ke tabel `outbox`.

2. **`layanan-penerima` (Port `8002`) — Layanan Pembukuan & Keuangan (Consumer)**
   - Mendengarkan *event* `konsinyasi.*` dari queue `stok-penerima-queue`.
   - Menjamin **Idempotensi** melalui pencatatan `message_id` pada tabel `processed_messages`.
   - Otomatis membuat entri buku besar pada tabel `jurnal_pembukuan` (`PEMASUKAN_SETORAN` dan `BEBAN_WASTE`) yang dapat dipantau melalui `GET /api/v1/pembukuan`.

---

## 2. Struktur Dokumentasi Desain (`docs/`)

Seluruh tahapan rekayasa perangkat lunak didokumentasikan secara berurutan:
- `docs/01_ANALISIS_BISNIS.md` — Analisis domain pabrik tahu/tempe & 6 aturan bisnis (`BR-01`–`BR-06`).
- `docs/02_DESAIN_DATABASE.md` — ERD relasional 4 tabel (`produk`, `pedagang`, `konsinyasi`, `detail_konsinyasi`).
- `docs/03_KONTRAK_API.md` — Spesifikasi 8 endpoint REST API & skema error RFC 9457.
- `docs/04_KONTRAK_EVENT.md` — Topologi RabbitMQ, Outbox Pattern, & kontrak payload JSON.
- `stok-tempe/openapi.yaml` — Spesifikasi OpenAPI 3.1.0 (tervalidasi 0 error oleh Spectral).

---

## 3. Panduan Menjalankan & Demo End-to-End

### A. Menjalankan Kedua Layanan
```bash
# 1. Jalankan Layanan Gudang & RabbitMQ
cd stok-tempe && ./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed

# 2. Jalankan Layanan Pembukuan
cd ../layanan-penerima && ./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate