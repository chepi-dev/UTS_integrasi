# FASE 3: PERANCANGAN KONTRAK REST API (DESIGN-FIRST)

**Layanan Manajemen Stok & Konsinyasi Tahu Tempe**

**Mata Kuliah:** Integrasi Cloud (Semester 5)  
**Kelompok:** Anti-Normal  
**Base URL:** `http://localhost:8000/api/v1`  
**Standar Spesifikasi:** OpenAPI 3.1.0 & RFC 9457 Problem Details (`application/problem+json`)  
**Tanggal Penyusunan:** 14 November 2026  
**Status Fase:** ✅ Selesai  

---

## Daftar Isi

1. [Daftar Endpoint REST API](#1-daftar-endpoint-rest-api)
2. [Spesifikasi Payload (Request & Response)](#2-spesifikasi-payload-request--response)
   - [2.1 Tambah Hasil Produksi Masuk](#21-tambah-hasil-produksi-masuk)
   - [2.2 Pengambilan Konsinyasi Pagi](#22-pengambilan-konsinyasi-pagi)
   - [2.3 Setoran Konsinyasi Sore](#23-setoran-konsinyasi-sore)
3. [Spesifikasi Respons Error (RFC 9457)](#3-spesifikasi-respons-error-rfc-9457)
4. [Referensi Aturan Bisnis (BR)](#4-referensi-aturan-bisnis-br)
5. [Kesimpulan](#5-kesimpulan)

---

## 1. DAFTAR ENDPOINT REST API (`/api/v1`)

Seluruh operasi bisnis dari Fase 1 dan skema database Fase 2 dipetakan ke dalam **8 endpoint** yang terbagi ke dalam 3 kelompok *resource*:

### A. Resource Produk & Stok Gudang (`/produk`)

| No | Method | Path | Deskripsi Operasi | Status Sukses | Status Galat |
|:---|:---|:---|:---|:---|:---|
| 1 | `GET` | `/api/v1/produk` | Menampilkan daftar produk tahu/tempe beserta saldo `stok_gudang`. | `200 OK` | — |
| 2 | `POST` | `/api/v1/produk` | Menambahkan jenis produk tahu/tempe baru ke master katalog. | `201 Created` | `422` |
| 3 | `POST` | `/api/v1/produk/{kode_produk}/produksi` | Mencatat penambahan `stok_gudang` dari hasil produksi harian pabrik. | `200 OK` | `404`, `422` |

### B. Resource Mitra Pedagang (`/pedagang`)

| No | Method | Path | Deskripsi Operasi | Status Sukses | Status Galat |
|:---|:---|:---|:---|:---|:---|
| 4 | `GET` | `/api/v1/pedagang` | Menampilkan daftar mitra pedagang keliling/warung. | `200 OK` | — |
| 5 | `POST` | `/api/v1/pedagang` | Mendaftarkan mitra pedagang baru ke sistem. | `201 Created` | `422` |

### C. Resource Siklus Konsinyasi (`/konsinyasi`)

| No | Method | Path | Deskripsi Operasi | Status Sukses | Status Galat |
|:---|:---|:---|:---|:---|:---|
| 6 | `GET` | `/api/v1/konsinyasi` | Monitoring daftar manifest konsinyasi (mendukung filter `?status=BERJALAN` atau `SELESAI`). | `200 OK` | — |
| 7 | `POST` | `/api/v1/konsinyasi` | **Pengambilan Pagi:** Membuat manifest baru, memotong `stok_gudang`, dan mengunci harga (**BR-01, BR-03, BR-04**). | `201 Created` | `404`, `409`, `422` |
| 8 | `POST` | `/api/v1/konsinyasi/{kode_manifest}/setoran` | **Setoran Sore:** Rekonsiliasi barang terjual, retur layak, dan rusak (**BR-02, BR-05, BR-06**). | `200 OK` | `404`, `409`, `422` |

---

## 2. SPESIFIKASI PAYLOAD (REQUEST & RESPONSE)

Bagian ini mendetailkan body request dan response untuk setiap endpoint yang membutuhkan body.

### 2.1 Tambah Hasil Produksi Masuk (`POST /api/v1/produk/{kode_produk}/produksi`)

Menggantikan endpoint generik lama agar spesifik untuk pencatatan hasil produksi tahu/tempe masuk ke gudang.

**Request Body (`application/json`):**

```json
{
  "jumlah_produksi": 100,
  "catatan": "Produksi kloter subuh 15 Nov 2026"
}
```

**Response 200 OK:**

```json
{
  "kode_produk": "TAHU-001",
  "nama_produk": "Tahu Putih",
  "jenis_produk": "tahu",
  "harga_satuan": 4000,
  "stok_gudang": 175,
  "tersedia": true
}
```

### 2.2 Pengambilan Konsinyasi Pagi (`POST /api/v1/konsinyasi`)

Membuat manifest konsinyasi baru, memotong `stok_gudang`, dan mengunci harga satuan sesuai katalog saat pengambilan (**BR-01, BR-03, BR-04**).

**Request Body (`application/json`):**

```json
{
  "kode_pedagang": "PDG-001",
  "items": [
    {
      "kode_produk": "TAHU-001",
      "jumlah_dibawa": 30
    },
    {
      "kode_produk": "TEMP-001",
      "jumlah_dibawa": 20
    }
  ]
}
```

**Response 201 Created** (disertai header `Location: /api/v1/konsinyasi/KSY-20261115-001`):

```json
{
  "kode_manifest": "KSY-20261115-001",
  "pedagang": {
    "kode_pedagang": "PDG-001",
    "nama_pedagang": "Pak Budi"
  },
  "tanggal_konsinyasi": "2026-11-15",
  "status": "BERJALAN",
  "waktu_ambil": "2026-11-15T06:00:00Z",
  "waktu_setor": null,
  "total_tagihan_setoran": 0,
  "total_kerugian_waste": 0,
  "items": [
    {
      "kode_produk": "TAHU-001",
      "nama_produk": "Tahu Putih",
      "harga_satuan_snapshot": 4000,
      "jumlah_dibawa": 30,
      "jumlah_terjual": 0,
      "jumlah_retur_layak": 0,
      "jumlah_rusak": 0
    },
    {
      "kode_produk": "TEMP-001",
      "nama_produk": "Tempe Papan",
      "harga_satuan_snapshot": 5000,
      "jumlah_dibawa": 20,
      "jumlah_terjual": 0,
      "jumlah_retur_layak": 0,
      "jumlah_rusak": 0
    }
  ]
}
```

### 2.3 Setoran Konsinyasi Sore (`POST /api/v1/konsinyasi/{kode_manifest}/setoran`)

Rekonsiliasi barang terjual, retur layak, dan barang rusak pada akhir hari (**BR-02, BR-05, BR-06**).

**Request Body (`application/json`):**

```json
{
  "items": [
    {
      "kode_produk": "TAHU-001",
      "jumlah_terjual": 22,
      "jumlah_retur_layak": 5,
      "jumlah_rusak": 3
    },
    {
      "kode_produk": "TEMP-001",
      "jumlah_terjual": 18,
      "jumlah_retur_layak": 2,
      "jumlah_rusak": 0
    }
  ]
}
```

**Response 200 OK:**

```json
{
  "kode_manifest": "KSY-20261115-001",
  "pedagang": {
    "kode_pedagang": "PDG-001",
    "nama_pedagang": "Pak Budi"
  },
  "tanggal_konsinyasi": "2026-11-15",
  "status": "SELESAI",
  "waktu_ambil": "2026-11-15T06:00:00Z",
  "waktu_setor": "2026-11-15T16:00:00Z",
  "total_tagihan_setoran": 178000,
  "total_kerugian_waste": 12000,
  "items": [
    {
      "kode_produk": "TAHU-001",
      "nama_produk": "Tahu Putih",
      "harga_satuan_snapshot": 4000,
      "jumlah_dibawa": 30,
      "jumlah_terjual": 22,
      "jumlah_retur_layak": 5,
      "jumlah_rusak": 3
    },
    {
      "kode_produk": "TEMP-001",
      "nama_produk": "Tempe Papan",
      "harga_satuan_snapshot": 5000,
      "jumlah_dibawa": 20,
      "jumlah_terjual": 18,
      "jumlah_retur_layak": 2,
      "jumlah_rusak": 0
    }
  ]
}
```

---

## 3. SPESIFIKASI RESPONS ERROR (RFC 9457)

Seluruh respons error mengikuti standar RFC 9457 Problem Details dengan tipe konten `application/problem+json`.

**Contoh Respons Error 422 (Rekonsiliasi Tidak Seimbang):**

```json
{
  "type": "https://localhost:8000/api/v1/problems/rekonsiliasi-tidak-seimbang",
  "title": "Rekonsiliasi item tidak seimbang",
  "status": 422,
  "detail": "Produk TAHU-001 dibawa sebanyak 30, namun total hitungan setor adalah 29 (terjual: 22, retur: 5, rusak: 2)."
}
```

---

## 4. REFERENSI ATURAN BISNIS (BR)

| Kode | Aturan Bisnis | Terkait Endpoint |
|:---|:---|:---|
| BR-01 | Harga satuan dikunci pada saat pengambilan konsinyasi pagi berdasarkan snapshot katalog. | `POST /konsinyasi` |
| BR-02 | Total setoran sore harus seimbang: terjual + retur layak + rusak = jumlah dibawa per item. | `POST /konsinyasi/{id}/setoran` |
| BR-03 | Stok gudang dikurangi sejumlah barang yang dibawa pedagang pada waktu pengambilan. | `POST /konsinyasi` |
| BR-04 | Produk yang stoknya habis tidak dapat diambil dalam konsinyasi. | `POST /konsinyasi` |
| BR-05 | Retur layak dikembalikan ke stok gudang; barang rusak tidak dikembalikan. | `POST /konsinyasi/{id}/setoran` |
| BR-06 | Tagihan setoran dihitung dari jumlah terjual × harga satuan snapshot, dikurangi kerugian waste. | `POST /konsinyasi/{id}/setoran` |

---

## 5. KESIMPULAN

Kontrak REST API di atas mencakup seluruh alur bisnis manajemen stok dan konsinyasi tahu tempe, dari pencatatan produksi masuk, pengambilan barang oleh mitra pedagang, hingga rekonsiliasi setoran sore hari. Semua endpoint menggunakan standar OpenAPI 3.1.0 untuk definisi dan RFC 9457 untuk penanganan error, sehingga siap diimplementasikan ke dalam layanan integrasi cloud.

---
