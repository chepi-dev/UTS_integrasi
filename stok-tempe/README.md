# Layanan Gudang & Konsinyasi Tahu-Tempe (`stok-tempe`)

**Kelompok:** Anti-Normal  
**Mata Kuliah:** Integrasi Cloud (Semester 5)  
**Peran Layanan:** Publisher & Core Domain Service (Port `8000`)

---

## Deskripsi Layanan
Layanan REST API untuk mengelola stok produksi harian pabrik tahu/tempe serta siklus distribusi **konsinyasi (titip jual)** kepada pedagang keliling, meliputi **Pengambilan Pagi** dan **Rekonsiliasi Setoran Sore**.

### Standar & Arsitektur
- **Framework:** Laravel 12 & MySQL 8 (Laravel Sail)
- **Kontrak API:** OpenAPI 3.1.0 (`openapi.yaml`, tervalidasi 0 error via Spectral)
- **Format Error:** RFC 9457 Problem Details (`application/problem+json`)
- **Integrasi Asinkron:** Transactional Outbox Pattern (`tabel outbox` + `php artisan outbox:relay`) ke RabbitMQ (`toko.events`)

---

## Ringkasan Endpoint API (`v1`)

| Method | Endpoint Path | Deskripsi | Status Sukses | Status Error |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/produk` | Daftar katalog & stok produk gudang | `200 OK` | — |
| `POST` | `/api/v1/produk` | Tambah master produk tahu/tempe baru | `201 Created` | `422` |
| `POST` | `/api/v1/produk/{kode_produk}/produksi` | Catat hasil produksi masuk gudang | `200 OK` | `404`, `422` |
| `GET` | `/api/v1/pedagang` | Daftar pedagang keliling aktif | `200 OK` | — |
| `POST` | `/api/v1/pedagang` | Registrasi pedagang keliling baru | `201 Created` | `422` |
| `GET` | `/api/v1/konsinyasi` | Daftar manifest konsinyasi (`?status=`) | `200 OK` | — |
| `POST` | `/api/v1/konsinyasi` | Catat pengambilan barang pagi (`BR-01`, `BR-03`, `BR-04`) | `201 Created` | `404`, `409`, `422` |
| `POST` | `/api/v1/konsinyasi/{kode_manifest}/setoran` | Rekonsiliasi setoran sore (`BR-02`, `BR-05`, `BR-06`) | `200 OK` | `404`, `409`, `422` |

---

## Cara Menjalankan & Menguji

```bash
# 1. Jalankan container & migrasi + data awal
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed

# 2. Jalankan Feature Test otomatis
./vendor/bin/sail artisan test

# 3. Terbitkan event Outbox ke RabbitMQ
./vendor/bin/sail artisan outbox:relay