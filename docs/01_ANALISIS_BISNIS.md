# FASE 1: ANALISIS DOMAIN BISNIS & ATURAN VALIDASI
**Layanan Manajemen Stok & Konsinyasi Tahu Tempe**

**Mata Kuliah:** Integrasi Cloud (Semester 5)  
**Kelompok:** Anti-Normal  
**Nama Layanan:** `stok-tempe` (Layanan Gudang & Distribusi Konsinyasi)  
**Tanggal Penyusunan:** 14 November 2026  
**Status Fase:** ✅ Selesai  

---

## 1. LATAR BELAKANG PERUBAHAN KONSEP

Pada rancangan awal (UTS), layanan hanya menyediakan operasi penambahan dan pengurangan stok secara generik (`restok`, `penjualan`, `koreksi`). Pendekatan tersebut belum mencerminkan proses bisnis nyata pada usaha keluarga produsen tahu dan tempe.

Dalam operasional riil pabrik tahu/tempe skala keluarga, distribusi utama dilakukan melalui skema **konsinyasi (titip jual) kepada pedagang keliling atau warung**. Barang yang keluar dari gudang di pagi hari belum berstatus terjual, melainkan masih berstatus titipan hingga pedagang kembali di sore hari untuk melakukan perhitungan fisik (rekonsiliasi) dan penyetoran uang.

Oleh karena itu, layanan dirombak dari sekadar pencatat stok statis menjadi **Sistem Manajemen Stok Gudang & Siklus Konsinyasi Harian**.

---

## 2. AKTOR & BATASAN SISTEM

| Komponen | Peran / Deskripsi | Keterangan Teknis |
| :--- | :--- | :--- |
| **Admin Gudang / Pemilik Usaha** | Aktor utama yang mengoperasikan sistem untuk mencatat hasil produksi masuk, mengeluarkan barang di pagi hari, dan memverifikasi setoran sore hari. | Pengguna langsung REST API. |
| **Pedagang Keliling / Mitra** | Pihak yang mengambil produk di pagi hari untuk dijual kembali dan menyetorkan hasil penjualan beserta sisa barang di sore hari. | Data master entitas (`pedagang`), tidak perlu login ke sistem. |
| **Stok Gudang (`produk`)** | Persediaan tahu dan tempe siap distribusi yang berada di dalam pabrik/gudang. | Berkurang saat diambil pedagang, bertambah saat produksi masuk atau saat ada retur layak jual. |
| **Manifest Konsinyasi** | Surat jalan digital harian yang mengikat satu pedagang dengan daftar produk yang dibawanya pada hari tersebut. | Memiliki siklus status: `BERJALAN` $\rightarrow$ `SELESAI`. |

---

## 3. SIKLUS HIDUP STOK & STATUS MANIFEST

Setiap unit produk tahu dan tempe mengalami perpindahan status yang jelas dari awal produksi hingga rekonsiliasi akhir hari:

1. **Produksi Masuk (`RESTOK_GUDANG`):** Hasil produksi pabrik ditambahkan ke dalam `stok_gudang` (status: *Tersedia di Gudang*).
2. **Pengambilan Pagi (`MANIFEST_KELUAR`):** Pedagang mengambil sejumlah tahu/tempe. `stok_gudang` berkurang dan barang berpindah status menjadi *Dibawa Pedagang*. Manifest berstatus `BERJALAN`.
3. **Setoran & Rekonsiliasi Sore (`SETORAN_SELESAI`):** Pedagang kembali membawa uang dan sisa fisik barang. Jumlah barang yang dibawa di pagi hari wajib terbagi habis ke dalam 3 status akhir:
   * **Terjual (`terjual`):** Barang laku dijual pedagang; dikonversi menjadi tagihan uang tunai yang wajib disetor.
   * **Retur Layak Jual (`retur_layak`):** Barang tidak laku namun kondisi masih segar/utuh; dikembalikan masuk menambah `stok_gudang`.
   * **Rusak / Basi (`rusak`):** Barang hancur di perjalanan atau basi karena sifat produk tahu/tempe; tidak dikembalikan ke stok gudang, melainkan dicatat sebagai kerugian produksi (*waste*).

### Diagram Alur Status (State Diagram)

```mermaid
stateDiagram-v2
    [*] --> StokGudang : Produksi Harian Masuk (+)
    
    state "Manifest Konsinyasi (BERJALAN)" as Berjalan {
        DibawaPedagang : Barang di Lapangan
    }
    
    StokGudang --> DibawaPedagang : Pengambilan Pagi (Stok Gudang -)
    
    state "Rekonsiliasi Setoran Sore (SELESAI)" as Selesai {
        Terjual : Laku (Jadi Tagihan Setoran)
        ReturLayak : Sisa Utuh (Kembali ke Gudang)
        RusakBasi : Hancur/Basi (Dicatat sebagai Waste)
    }
    
    DibawaPedagang --> Terjual
    DibawaPedagang --> ReturLayak
    DibawaPedagang --> RusakBasi
    
    ReturLayak --> StokGudang : Stok Gudang (+)
    Terjual --> [*] : Trigger Event Pemasukan
    RusakBasi --> [*] : Trigger Event Kerugian