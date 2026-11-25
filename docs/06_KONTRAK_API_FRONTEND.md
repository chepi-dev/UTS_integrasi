# FASE 6: PANDUAN INTEGRASI REST API UNTUK FRONTEND
**Layanan Stok Konsinyasi (`stok-tempe`) & Layanan Pembukuan (`layanan-penerima`)**

**Mata Kuliah:** Integrasi Cloud (Semester 5)  
**Kelompok:** Anti-Normal  
**Base URL Layanan Stok:** `http://localhost:8000/api/v1`  
**Base URL Layanan Pembukuan:** `http://localhost:8002/api/v1`  
**Tanggal Penyusunan:** 25 November 2026  
**Status Fase:** ✅ Siap Integrasi Frontend  

---

## 1. KONFIGURASI KONEKSI & AKUN DEMO

Tim *frontend* berkomunikasi dengan **dua layanan backend** yang sudah mengaktifkan kebijakan CORS (`Access-Control-Allow-Origin: *`).

### 1.1 Daftar Base URL Layanan

| Variabel Environment (Saran) | URL Layanan | Fungsi Utama |
| :--- | :--- | :--- |
| `VITE_STOK_API_URL` | `http://localhost:8000/api/v1` | Login, profil user, master produk/pedagang, produksi, dan siklus konsinyasi. |
| `VITE_PEMBUKUAN_API_URL` | `http://localhost:8002/api/v1` | Jurnal pemasukan setoran dan pencatatan kerugian *waste* (khusus `pemilik`). |

### 1.2 Kredensial Akun Pengujian (Hasil Seeder)

| Peran (`role`) | Nama Pengguna | Email | Password | Akses Halaman Utama |
| :--- | :--- | :--- | :--- | :--- |
| **`pemilik`** | Pemilik Pabrik Tahu Tempe | `pemilik@tempe.test` | `password123` | Dashboard Ringkasan, Master Produk, Master Pedagang, Buku Pembukuan |
| **`petugas_gudang`** | Petugas Gudang Pagi-Sore | `gudang@tempe.test` | `password123` | Input Produksi, Pengambilan Pagi, Setoran Sore, Daftar Manifest |

### 1.3 Header Wajib Setiap Request Terproteksi

Kecuali *endpoint* `POST /auth/login`, setiap permintaan HTTP dari *frontend* wajib menyertakan *header* berikut:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <access_token_dari_login>
```

---

## 2. SPESIFIKASI ENDPOINT AUTENTIKASI (`stok-tempe`)

### 2.1 Login Pengguna (`POST /api/v1/auth/login`)

**Request Body:**

```json
{
  "email": "pemilik@tempe.test",
  "password": "password123"
}
```

**Response `200 OK`:**

```json
{
  "access_token": "1|sdQTjIJqJKFPWl517UUKQa0itrMHA5BjZ6oV7KPFa6284ad7",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Pemilik Pabrik Tahu Tempe",
    "email": "pemilik@tempe.test",
    "role": "pemilik"
  }
}
```

> **Catatan Implementasi Frontend:** Simpan `access_token` dan objek `user` (khususnya `user.role`) pada *state management* / `localStorage`. Gunakan `user.role` untuk mengarahkan pengguna setelah login (`pemilik` $\rightarrow$ halaman Dashboard/Pembukuan, `petugas_gudang` $\rightarrow$ halaman Operasional Konsinyasi).

### 2.2 Cek Sesi Aktif (`GET /api/v1/auth/me`)

Digunakan saat aplikasi *frontend* dimuat ulang (*page refresh*) untuk memastikan token yang tersimpan masih berlaku.

**Response `200 OK`:**

```json
{
  "id": 1,
  "name": "Pemilik Pabrik Tahu Tempe",
  "email": "pemilik@tempe.test",
  "role": "pemilik"
}
```

### 2.3 Logout Pengguna (`POST /api/v1/auth/logout`)

Menghapus token aktif di server. Setelah menerima `200 OK`, bersihkan token dari `localStorage` dan arahkan ke halaman `/login`.

**Response `200 OK`:**

```json
{
  "message": "Logout berhasil."
}
```

---

## 3. ENDPOINT TAMBAHAN BUKU TRANSAKSI (`stok-tempe`)

Selain 8 *endpoint* pada Fase 3 (`03_KONTRAK_API.md`), tersedia pembaruan dan *endpoint* detail berikut untuk mendukung halaman **Buku Transaksi**:

### 3.1 Filter Daftar Manifest (`GET /api/v1/konsinyasi`)

Mendukung kombinasi parameter *query* opsional:
* `?status=BERJALAN` atau `?status=SELESAI`
* `?tanggal=YYYY-MM-DD` (contoh: `GET /api/v1/konsinyasi?tanggal=2026-11-25`)

**Response `200 OK`:** Mengembalikan *array* objek manifest (format sama dengan Fase 3).

### 3.2 Detail Satu Manifest (`GET /api/v1/konsinyasi/{kode_manifest}`)

Digunakan saat pengguna mengklik salah satu baris manifest untuk membuka halaman/modal detail rincian produk yang dibawa, terjual, retur layak, dan rusak.

**Contoh Request:** `GET http://localhost:8000/api/v1/konsinyasi/KSY-20261125-001`

**Response `200 OK`:**

```json
{
  "kode_manifest": "KSY-20261125-001",
  "pedagang": {
    "kode_pedagang": "PDG-001",
    "nama_pedagang": "Pak Budi"
  },
  "tanggal_konsinyasi": "2026-11-25",
  "status": "SELESAI",
  "waktu_ambil": "2026-11-25T06:00:00Z",
  "waktu_setor": "2026-11-25T16:00:00Z",
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
    }
  ]
}
```

**Response `404 Not Found` (jika kode manifest tidak ditemukan):**

```json
{
  "type": "https://localhost:8000/api/v1/problems/404",
  "title": "Manifest tidak ditemukan",
  "status": 404,
  "detail": "Manifest dengan kode KSY-TIDAK-ADA tidak tersedia."
}
```

---

## 4. SPESIFIKASI ENDPOINT BUKU PEMBUKUAN (`layanan-penerima`)

### 4.1 Laporan Ringkasan & Jurnal Pembukuan (`GET http://localhost:8002/api/v1/pembukuan`)

* **Hak Akses:** Khusus token dengan *role* **`pemilik`**.
* **Query Parameter Opsional:** `?tanggal=YYYY-MM-DD` (filter berdasarkan `tanggal_transaksi`).

**Contoh Request:**
`GET http://localhost:8002/api/v1/pembukuan?tanggal=2026-11-25`

**Response `200 OK`:**

```json
{
  "ringkasan": {
    "total_pemasukan": 178000,
    "total_kerugian_waste": 12000
  },
  "transaksi": [
    {
      "id": 2,
      "kode_manifest": "KSY-20261125-001",
      "kode_pedagang": "PDG-001",
      "nama_pedagang": "Pak Budi",
      "tanggal_transaksi": "2026-11-25",
      "jenis_jurnal": "BEBAN_WASTE",
      "nominal": 12000,
      "keterangan": "Kerugian barang rusak/basi manifest KSY-20261125-001",
      "created_at": "2026-11-25T09:00:05.000000Z",
      "updated_at": "2026-11-25T09:00:05.000000Z"
    },
    {
      "id": 1,
      "kode_manifest": "KSY-20261125-001",
      "kode_pedagang": "PDG-001",
      "nama_pedagang": "Pak Budi",
      "tanggal_transaksi": "2026-11-25",
      "jenis_jurnal": "PEMASUKAN_SETORAN",
      "nominal": 178000,
      "keterangan": "Setoran hasil penjualan konsinyasi KSY-20261125-001",
      "created_at": "2026-11-25T09:00:05.000000Z",
      "updated_at": "2026-11-25T09:00:05.000000Z"
    }
  ]
}
```

> **Catatan Parsing UI:** Gunakan nilai `jenis_jurnal` untuk membedakan lencana (*badge*) pada tabel jurnal:
> * `PEMASUKAN_SETORAN` $\rightarrow$ warna hijau (Kas Masuk / Setoran).
> * `BEBAN_WASTE` $\rightarrow$ warna merah/oranye (Kerugian Rusak/Basi).

---

## 5. PANDUAN PENANGANAN ERROR DI FRONTEND (INTERCEPTOR)

Semua respons error dari kedua layanan selalu memuat field `title`, `status`, dan `detail` sesuai standar **RFC 9457**. Disarankan memasang *HTTP Interceptor* global (misalnya pada Axios atau `fetch` wrapper) dengan aturan berikut:

| Kode HTTP | Penyebab Umum | Tindakan yang Disarankan di UI |
| :--- | :--- | :--- |
| **`401`** | Token kosong, salah, atau sudah logout. | Hapus token dari penyimpanan lokal dan *redirect* paksa ke halaman `/login`. |
| **`403`** | User login mencoba mengakses aksi/halaman milik *role* lain. | Tampilkan pesan peringatan menggunakan nilai `detail` dari JSON. |
| **`404`** | Kode produk, pedagang, atau manifest tidak ditemukan. | Tampilkan status data tidak ditemukan (*empty/not found state*). |
| **`409`** | Pelanggaran aturan bisnis (stok gudang kurang, pedagang masih punya manifest `BERJALAN`, atau manifest sudah `SELESAI`). | Tampilkan notifikasi galat bisnis (`title` & `detail`) tanpa me-reset isian form. |
| **`422`** | Validasi input gagal atau hitungan rekonsiliasi sore tidak sama dengan `jumlah_dibawa` (**BR-02**). | Tampilkan pesan `detail` di dekat form input agar petugas dapat memperbaiki angka setoran. |