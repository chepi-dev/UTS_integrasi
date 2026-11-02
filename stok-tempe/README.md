# 🍞 Layanan Manajemen Stok & Inventori Tahu-Tempe

**Mata Kuliah:** Integrasi Cloud (Semester 5)  
**Tugas:** Proyek Akhir Kelompok (UTS — Rest API & Integrasi Cloud)  
**Kelompok:** Anti-Normal  
**Tema Studi Kasus:** Sistem Terintegrasi Penjualan Tahu Tempe (Usaha Keluarga)  
**Platform Repository:** [GitHub - chepi-dev/UTS_Integrasi](https://github.com/chepi-dev/UTS_Integrasi)  

---

## 📋 Deskripsi Layanan

Layanan ini merupakan microservice REST API untuk pengelolaan inventori dan stok harian pada usaha produksi tahu & tempe. Sistem ini mendukung pengelolaan kategori produk basah, manajemen daya tahan produk, pencatatan batch produksi, penyesuaian stok (*masuk produksi, penjualan, rusak/basi, koreksi opname*), dan log riwayat transaksi stok.

---

## 🛠️ Stack Teknologi

- **Framework:** Laravel 13
- **Environment:** Laravel Sail (Docker Containerization)
- **Database:** MySQL 8.4
- **API Standard:** OpenAPI Specification (OAS) 3.1.0
- **Error Standard:** RFC 9457 Problem Details (`application/problem+json`)
- **Testing:** Pest / PHPUnit Feature Testing
- **Linter:** Stoplight Spectral

---

## 🚀 Panduan Menjalankan Layanan (Quick Start)

Ikuti langkah-langkah di bawah ini untuk mengunduh, mengonfigurasi, dan menjalankan layanan pada environment lokal Anda.

### 1. Cloning Repositori & Masuk Folder Proyek

```bash
git clone https.github.com/chepi-dev/UTS_Integrasi.git
cd UTS_Integrasi
```

### 2. Salin Konfigurasi Lingkungan (.env)

```bash
cp .env.example .env
```

Pastikan variabel database pada file `.env` terkonfigurasi sebagai berikut:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=UTS_stok
DB_USERNAME=sail
DB_PASSWORD=password
```

### 3. Menjalankan Docker Container (Laravel Sail)

Nyalakan container Docker menggunakan Sail:

```bash
./vendor/bin/sail up -d
```

### 4. Menyiapkan Basis Data & Generasi App Key

Jalankan pembuat database, penerbitan App Key, dan eksekusi migrasi beserta data awal (*seeder*):

```bash
# Generasi App Key Laravel
./vendor/bin/sail artisan key:generate

# Buat database jika belum ada & beri hak akses
./vendor/bin/sail exec mysql mysql -u root -ppassword -e "CREATE DATABASE IF NOT EXISTS UTS_stok;"
./vendor/bin/sail exec mysql mysql -u root -ppassword -e "GRANT ALL PRIVILEGES ON UTS_stok.* TO 'sail'@'%'; FLUSH PRIVILEGES;"

# Jalankan Migration & Seeder Data Kategori
./vendor/bin/sail artisan migrate:fresh --seed
```

---

## 🧪 Pengujian Otomatis (Testing)

Setiap fitur dilengkapi dengan pengujian *Feature Test* untuk memastikan kepatuhan terhadap kontrak API dan aturan bisnis.

Jalankan perintah berikut untuk mengeksekusi pengujian:

```bash
./vendor/bin/sail artisan test
```

*Catatan: Seluruh pengujian harus berstatus **PASS (Hijau)**.*

---

## 📄 Pengujian Kontrak API & Swagger UI

### 1. Validasi Linting Kontrak OpenAPI (Spectral)

Untuk memverifikasi bahwa `openapi.yaml` mematuhi aturan OpenAPI 3.1.0 tanpa error:

```bash
npx @stoplight/spectral-cli lint openapi.yaml
```

### 2. Membuka Swagger UI

Anda dapat membaca dan mencoba seluruh endpoint API secara interaktif melalui Swagger UI:

1. Jalankan extension Swagger UI lokal atau buka [Swagger Editor Browser](https://editor.swagger.io/).
2. Impor berkas `openapi.yaml` yang terdapat pada root folder proyek ini.
3. Alamat Base URL API: `http://localhost:8000/api/v1`

---

## 📌 Ringkasan Endpoint API (v1)

| Method | Endpoint Path | Deskripsi | Status Berhasil | Status Galat |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/produk` | Menampilkan seluruh daftar stok produk | `200 OK` | — |
| `POST` | `/api/v1/produk` | Menambahkan produk baru | `201 Created` | `422 Unprocessable` |
| `GET` | `/api/v1/produk/{kode_produk}` | Detail informasi produk & status stok | `200 OK` | `404 Not Found` |
| `POST` | `/api/v1/produk/{kode_produk}/penyesuaian-stok` | Penyesuaian stok (produksi, penjualan, rusak/basi) | `200 OK` | `404 Not Found`, `409 Conflict`, `422 Unprocessable` |

---

## 🧪 Contoh Perintah Uji Manual (cURL)

### 1. Menampilkan Semua Produk
```bash
curl -i http://localhost:8000/api/v1/produk
```

### 2. Menambah Produk Baru
```bash
curl -i -X POST http://localhost:8000/api/v1/produk \
  -H "Content-Type: application/json" \
  -d '{
    "kategori_id": 1,
    "nama_produk": "Tahu Sumedang Super",
    "satuan": "papan",
    "harga_satuan": 12000,
    "stok_awal": 40,
    "stok_minimum": 5
  }'
```

### 3. Penyesuaian Stok (Rusak / Basi)
```bash
curl -i -X POST http://localhost:8000/api/v1/produk/TAH-001/penyesuaian-stok \
  -H "Content-Type: application/json" \
  -d '{
    "jenis_penyesuaian": "rusak_basi",
    "jumlah": 5,
    "keterangan": "Basi karena penyimpanan suhu lembab"
  }'
```

---
**Kelompok Anti-Normal — UTS Integrasi Cloud 2026**