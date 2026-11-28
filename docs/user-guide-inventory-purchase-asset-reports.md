# Panduan: Laporan Persediaan, Pembelian & Aset

Panduan ini mencakup sembilan laporan pada menu **Laporan**: Nilai Persediaan, Mutasi Stok, Selisih
Stock Opname Persediaan, Status Pesanan Pembelian, Riwayat Pembelian, Register Aset, Penyusutan
Nilai Buku, Biaya Pemeliharaan, dan Selisih Stock Opname Aset. Semua halaman bersifat hanya baca.

## Gambaran Umum

| Laporan | URL | Isi utama |
|---|---|---|
| Laporan Nilai Persediaan | `/reports/inventory-valuation` | Jumlah dan nilai stok per produk dan gudang |
| Laporan Mutasi Stok | `/reports/stock-movement` | Total stok masuk dan keluar per produk |
| Selisih Stock Opname Persediaan | `/reports/inventory-stocktake-variance` | Selisih stok sistem dan hitung fisik |
| Laporan Status Pesanan Pembelian | `/reports/purchase-order-status` | Progres pesanan dari terbit sampai diterima |
| Laporan Riwayat Pembelian | `/reports/purchase-history` | Transaksi pembelian per produk dan pemasok |
| Laporan Register Aset | `/reports/assets/register` | Daftar aset tetap beserta perolehan dan status |
| Laporan Penyusutan Nilai Buku | `/reports/book-value-depreciation` | Harga perolehan, akumulasi, dan nilai buku |
| Laporan Biaya Pemeliharaan | `/reports/maintenance-cost` | Biaya pemeliharaan per aset |
| Selisih Stock Opname Aset | `/reports/asset-stocktake-variances` | Selisih aset tercatat dan hasil hitung fisik |

## Akses & Izin

| Laporan | Permission |
|---|---|
| Laporan Nilai Persediaan | `inventory_valuation_report` |
| Laporan Mutasi Stok | `stock_movement_report` |
| Selisih Stock Opname Persediaan | `inventory_stocktake_variance_report` |
| Laporan Status Pesanan Pembelian | `purchase_order_status_report` |
| Laporan Riwayat Pembelian | `purchase_history_report` |
| Laporan Register Aset | `asset` |
| Laporan Penyusutan Nilai Buku | `asset` |
| Laporan Biaya Pemeliharaan | `asset` |
| Selisih Stock Opname Aset | `asset_stocktake` |

Permission yang sama dipakai untuk melihat laporan dan menekan tombol **Ekspor**. Pengguna tanpa
permission akan melihat pesan akses ditolak.

## Menu & Navigasi

Semua laporan berada di grup **Laporan** pada sidebar:

- **Laporan > Laporan Nilai Persediaan**
- **Laporan > Laporan Mutasi Stok**
- **Laporan > Selisih Stock Opname Persediaan**
- **Laporan > Laporan Status Pesanan Pembelian**
- **Laporan > Laporan Riwayat Pembelian**
- **Laporan > Laporan Register Aset**
- **Laporan > Laporan Penyusutan Nilai Buku**
- **Laporan > Laporan Biaya Pemeliharaan**
- **Laporan > Selisih Stock Opname Aset**

Setiap halaman menampilkan kolom pencarian, panel **Filter**, menu **Kolom**, dan tombol **Ekspor**.
Panel filter memakai tombol **Terapkan filter**, **Atur ulang**, dan **Hapus semua**.

## 1. Laporan Nilai Persediaan

1. Isi **Cari** untuk mencocokkan produk, kategori, gudang, atau cabang.
2. Pilih **Produk**, **Gudang**, **Cabang**, dan **Kategori** bila perlu.
3. Terapkan filter.

Kolom: **Produk**, **Kategori**, **Satuan**, **Gudang**, **Qty Tersedia**, **Rata-rata Biaya**,
**Nilai Stok**, dan **Pergerakan Terakhir**. Nilai stok dihitung dari kuantitas tersedia dikali
rata-rata biaya.

## 2. Laporan Mutasi Stok

1. Isi **Cari**, lalu pilih **Produk**, **Gudang**, **Cabang**, dan **Kategori**.
2. Atur **Tanggal Mulai** dan **Tanggal Akhir**.
3. Terapkan filter.

Kolom: **Produk**, **Kategori**, **Gudang**, **Total Masuk**, **Total Keluar**, **Saldo Akhir**,
dan **Pergerakan Terakhir**. Mutasi mencakup penerimaan, retur pemasok, transfer, penyesuaian,
konsumsi produksi, hasil produksi, penjualan, dan retur penjualan.

## 3. Selisih Stock Opname Persediaan

1. Isi **Cari** untuk mencocokkan nomor opname, produk, kategori, atau gudang.
2. Pilih **Stok opname**, **Produk**, **Gudang**, **Cabang**, dan **Kategori**.
3. Pilih **Hasil** untuk menyaring **Kelebihan** atau **Kekurangan**.
4. Atur **Tanggal Mulai** dan **Tanggal Akhir**, lalu terapkan filter.

Kolom: **No. Opname**, **Tanggal Opname**, **Produk**, **Kategori**, **Gudang**, **Qty Sistem**,
**Qty Hitung**, **Selisih**, **Hasil**, dan **Waktu Hitung**. Selisih dihitung dari kuantitas
hitung dikurangi kuantitas sistem.

## 4. Laporan Status Pesanan Pembelian

1. Isi **Cari** untuk mencocokkan nomor PO, pemasok, gudang, atau produk.
2. Pilih **Pemasok**, **Gudang**, dan **Produk**.
3. Pilih **Kategori Status** dan **Status PO**.
4. Atur **Tanggal Mulai** dan **Tanggal Akhir**, lalu terapkan filter.

Kolom: **No. PO**, **Pemasok**, **Gudang**, **Status**, **Kategori Status**, **Qty Dipesan**,
**Qty Diterima**, **Qty Belum Diterima**, **Progres Penerimaan (%)**, **Total Akhir**, dan
**Perkiraan Pengiriman**.

Kategori status: **Belum Lunas**, **Diterima Sebagian**, dan **Ditutup**. Status PO: **Draf**,
**Menunggu Persetujuan**, **Dikonfirmasi**, **Ditolak**, **Diterima Sebagian**, **Diterima Penuh**,
**Dibatalkan**, dan **Ditutup**.

## 5. Laporan Riwayat Pembelian

1. Isi **Cari**, lalu pilih **Pemasok**, **Gudang**, dan **Produk**.
2. Pilih **Status PO** bila perlu.
3. Atur **Tanggal Mulai** dan **Tanggal Akhir**, lalu terapkan filter.

Kolom: **No. PO**, **Pemasok**, **Produk**, **Gudang**, **Status**, **Qty Dipesan**, **Qty
Diterima**, **Qty Belum Diterima**, **Jumlah Penerimaan**, **Penerimaan Terakhir**, **Total
Nilai**, dan **Perkiraan Pengiriman**. Ringkasan di bawah tabel menampilkan total qty dipesan,
qty diterima, qty belum diterima, dan total nilai.

## 6. Laporan Register Aset

1. Isi **Cari** untuk mencocokkan aset.
2. Pilih **Kategori**, **Lokasi**, **Departemen**, **Karyawan**, **Pemasok**, dan **Cabang**.
3. Pilih **Status** dan **Kondisi**, lalu terapkan filter.

Kolom: **Kode**, **Nama**, **Kategori**, **Cabang**, **Lokasi**, **Departemen**, **Karyawan**,
**Pemasok**, **Status**, **Harga Perolehan**, dan **Tanggal Perolehan**. Ringkasan menampilkan
total harga perolehan. Status aset: **Draf**, **Aktif**, **Perawatan**, **Dihapus Buku**, dan
**Hilang**. Kondisi: **Baik**, **Perlu Perbaikan**, dan **Rusak**.

Tombol **Ekspor** tersedia di toolbar. Untuk daftar aset yang lebih lengkap, gunakan modul
**Aset > Data Aset**.

## 7. Laporan Penyusutan Nilai Buku

1. Isi **Cari** untuk mencocokkan kode atau nama aset.
2. Pilih **Kategori** dan **Cabang** bila perlu.
3. Terapkan filter.

Kolom: **Kode Aset**, **Nama Aset**, **Kategori**, **Cabang**, **Tanggal Perolehan**, **Harga
Perolehan**, **Nilai Sisa**, **Umur Manfaat (Bln)**, **Akum. Penyusutan**, dan **Nilai Buku**.
Nilai buku dihitung dari harga perolehan dikurangi akumulasi penyusutan.

## 8. Laporan Biaya Pemeliharaan

1. Isi **Cari** untuk mencocokkan kode, nama, atau catatan.
2. Pilih **Kategori**, **Cabang**, **Vendor**, **Jenis**, dan **Status**.
3. Atur **Tanggal Mulai** dan **Tanggal Akhir**, lalu terapkan filter.

Kolom: **Kode Aset**, **Nama Aset**, **Kategori**, **Cabang**, **Jenis**, **Status**,
**Dijadwalkan Pada**, **Dilaksanakan Pada**, **Vendor**, dan **Harga Pokok**. Ringkasan
menampilkan total biaya.

Jenis pemeliharaan: **Preventif**, **Korektif**, **Kalibrasi**, dan **Lainnya**. Status:
**Dijadwalkan**, **Sedang Berjalan**, **Selesai**, dan **Dibatalkan**.

## 9. Selisih Stock Opname Aset

1. Isi **Cari** untuk mencocokkan kode, nama, atau catatan.
2. Pilih **Stok opname**, **Cabang**, dan **Hasil**.
3. Terapkan filter.

Kolom: **Ref. Stock Opname**, **Kode Aset**, **Nama Aset**, **Cabang Seharusnya**, **Lokasi
Seharusnya**, **Cabang Ditemukan**, **Lokasi Ditemukan**, **Hasil**, **Catatan**, **Diperiksa
Pada**, dan **Diperiksa Oleh**. Hasil yang dapat difilter: **Rusak**, **Hilang**, dan
**Dipindahkan**.

## Ekspor

Tombol **Ekspor** mengunduh data sesuai filter aktif dalam berkas Excel (`.xlsx`). Terapkan filter
dan atur kolom yang terlihat sebelum mengekspor agar berkas mudah dibaca.

## Aturan & validasi penting

- Kolom **Cari** menerima teks maksimal 255 karakter.
- Produk, gudang, cabang, kategori, pemasok, aset, dan stok opname yang dipilih harus terdaftar.
- Tanggal akhir tidak boleh lebih awal dari tanggal mulai.
- Hasil Selisih Stock Opname Persediaan: `surplus` dan `deficit`.
- Hasil Selisih Stock Opname Aset: `damaged`, `missing`, dan `moved`.
- Status PO: `draft`, `pending_approval`, `confirmed`, `rejected`, `partially_received`,
  `fully_received`, `cancelled`, dan `closed`.
- Kategori status PO: `not_started`, `outstanding`, `partially_received`, `closed`, dan
  `cancelled`.
- Jenis pemeliharaan: `preventive`, `corrective`, `calibration`, dan `other`.
- Semua laporan hanya membaca data yang sudah tercatat; tidak ada tambah, ubah, atau hapus dari
  halaman laporan.

## Tanya jawab singkat

**Mengapa produk tidak muncul di Laporan Nilai Persediaan?**
Pastikan produk memiliki saldo stok pada gudang dan cabang yang dipilih. Produk tanpa pergerakan
stok tidak ditampilkan.

**Apa arti Qty Belum Diterima?**
Jumlah yang masih harus diterima dari pesanan pembelian, yaitu qty dipesan dikurangi qty diterima.

**Mengapa Progres Penerimaan belum 100%?**
Masih ada qty yang belum diterima atau penerimaan belum dikonfirmasi. Periksa modul **Penerimaan
Barang**.

**Mengapa Nilai Buku berbeda dari Harga Perolehan?**
Nilai buku sudah dikurangi akumulasi penyusutan. Untuk aset baru yang belum disusutkan, keduanya
sama.

**Mengapa aset tidak muncul di Selisih Stock Opname Aset?**
Laporan hanya menampilkan aset yang hasil hitung fisiknya berbeda dari data tercatat. Aset yang
sesuai tidak masuk daftar selisih.
