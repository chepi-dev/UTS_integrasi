# FASE 2: PERANCANGAN BASIS DATA RELASIONAL (ERD & KAMUS DATA)
**Layanan Manajemen Stok & Konsinyasi Tahu Tempe**

**Mata Kuliah:** Integrasi Cloud (Semester 5)  
**Kelompok:** Anti-Normal  
**Nama Layanan:** `stok-tempe` (Database: `UTS_stok`)  
**Tanggal Penyusunan:** 14 November 2026  
**Status Fase:** ✅ Selesai  

---

## 1. ARSITEKTUR SKEMA BASIS DATA

Untuk mendukung siklus konsinyasi harian (pengambilan pagi $\rightarrow$ rekonsiliasi sore), struktur tabel tunggal (`produk`) pada rancangan UTS dirombak menjadi **4 tabel relasional** yang saling terhubung:

1. **`produk`**: Menyimpan data master tahu/tempe beserta saldo stok fisik yang tersedia di dalam gudang (`stok_gudang`).
2. **`pedagang`**: Menyimpan data mitra pedagang keliling atau warung yang mengambil barang secara konsinyasi.
3. **`konsinyasi`**: Menyimpan header manifest surat jalan harian untuk setiap pengambilan barang oleh pedagang, lengkap dengan status siklus (`BERJALAN` / `SELESAI`) serta ringkasan nilai tagihan setoran dan nilai kerugian (*waste*).
4. **`detail_konsinyasi`**: Menyimpan rincian item produk per manifest, mengunci harga satuan saat transaksi (**BR-04**), dan mencatat pemecahan jumlah barang saat setoran sore (`terjual`, `retur_layak`, `rusak`).

---

## 2. ENTITY RELATIONSHIP DIAGRAM (ERD)

```mermaid
erDiagram
    PRODUK ||--o{ DETAIL_KONSINYASI : "dicatat dalam"
    PEDAGANG ||--o{ KONSINYASI : "memiliki manifest"
    KONSINYASI ||--|{ DETAIL_KONSINYASI : "memuat rincian"

    PRODUK {
        string kode_produk PK "Contoh: TAHU-001"
        string nama_produk
        enum jenis_produk "tahu | tempe"
        int harga_satuan "Harga setor dasar (Rp)"
        int stok_gudang "Stok siap ambil di pabrik"
        timestamp created_at
        timestamp updated_at
    }

    PEDAGANG {
        string kode_pedagang PK "Contoh: PDG-001"
        string nama_pedagang
        string no_telepon
        string wilayah_jualan
        boolean aktif "Default: true"
        timestamp created_at
        timestamp updated_at
    }

    KONSINYASI {
        string kode_manifest PK "Contoh: KSY-20261115-001"
        string kode_pedagang FK
        date tanggal_konsinyasi
        enum status "BERJALAN | SELESAI"
        int total_tagihan_setoran "Total Rp barang terjual"
        int total_kerugian_waste "Total Rp barang rusak"
        timestamp waktu_ambil
        timestamp waktu_setor "Nullable"
        timestamp created_at
        timestamp updated_at
    }

    DETAIL_KONSINYASI {
        bigint id PK "Auto Increment"
        string kode_manifest FK
        string kode_produk FK
        int harga_satuan_snapshot "Kunci harga saat ambil (BR-04)"
        int jumlah_dibawa "Diisi saat pagi"
        int jumlah_terjual "Diisi saat sore"
        int jumlah_retur_layak "Kembali ke stok_gudang"
        int jumlah_rusak "Dicatat sebagai waste"
        timestamp created_at
        timestamp updated_at
    }