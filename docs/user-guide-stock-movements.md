# Panduan: Mutasi Stok

## Gambaran Umum

Halaman **Mutasi Stok** (`/stock-movements`) adalah kartu stok digital yang menampilkan seluruh
pergerakan produk per gudang. Setiap baris mencatat jenis pergerakan, jumlah masuk dan keluar,
saldo setelah pergerakan, harga satuan, serta rata-rata biaya setelah pergerakan. Data berasal dari
dokumen sumber yang sudah diproses, sehingga halaman ini dipakai untuk menelusuri asal-usul
perubahan stok tanpa membuka satu per satu dokumen.

Halaman bersifat **hanya-baca**. Tidak ada tombol tambah, ubah, atau hapus karena mutasi stok
dibentuk otomatis oleh dokumen sumber seperti penerimaan barang, retur, transfer, penyesuaian,
stock opname, produksi, dan penjualan.

## Akses & Izin

- Permission **`stock_movement`** diperlukan untuk membuka halaman dan mengekspor data. Tombol
  **Ekspor** memakai permission yang sama.
- Halaman **Laporan Mutasi Stok** di grup Laporan adalah halaman terpisah dan memakai permission
  **`stock_movement_report`**. Panduan ini membahas halaman Mutasi Stok, bukan laporan tersebut.
- Pengguna tanpa permission `stock_movement` akan melihat pesan akses ditolak.

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Persediaan > Mutasi Stok | `/stock-movements` | Kartu stok digital per produk dan gudang |

Breadcrumb halaman menampilkan **Persediaan > Mutasi Stok**. Judul halaman adalah **Mutasi Stok**
dengan keterangan "Kartu stok digital — pergerakan produk per gudang, jenis, dan periode."

## Membaca tabel

Kolom yang tersedia:

| Kolom | Isi |
|-------|-----|
| **Waktu Pergerakan** | Tanggal dan jam pergerakan tercatat |
| **Produk** | Nama produk dengan kode produk sebagai keterangan |
| **Gudang** | Nama gudang beserta cabangnya (disembunyikan secara default) |
| **Jenis Pergerakan** | Jenis mutasi, misalnya Penerimaan Barang atau Penyesuaian Masuk |
| **Referensi** | Nomor dokumen sumber |
| **Qty Masuk** | Jumlah stok yang bertambah |
| **Qty Keluar** | Jumlah stok yang berkurang |
| **Saldo** | Saldo stok setelah pergerakan tersebut |
| **Harga Satuan** | Harga satuan yang dipakai pada pergerakan |
| **Rata-rata Biaya** | Rata-rata biaya setelah pergerakan (disembunyikan secara default) |
| **Dibuat Oleh** | Pengguna yang mencatat pergerakan (disembunyikan secara default) |

Kolom **Gudang**, **Rata-rata Biaya**, dan **Dibuat Oleh** tidak tampil pada awalnya. Gunakan tombol
**Kolom** di toolbar untuk menampilkan atau menyembunyikan kolom sesuai kebutuhan.

### Jenis pergerakan

| Nilai | Label tampilan |
|-------|----------------|
| `goods_receipt` | Penerimaan Barang |
| `supplier_return` | Retur Pemasok |
| `transfer_out` | Transfer Keluar |
| `transfer_in` | Transfer Masuk |
| `adjustment_in` | Penyesuaian Masuk |
| `adjustment_out` | Penyesuaian Keluar |
| `production_consume` | Konsumsi Produksi |
| `production_output` | Hasil Produksi |
| `sales` | Penjualan |
| `sales_return` | Retur Penjualan |

### Menelusuri dokumen sumber

Nomor **Referensi** yang diawali kode tertentu dapat diklik untuk membuka halaman dokumen sumber:

| Awalan referensi | Halaman tujuan |
|------------------|----------------|
| `ST-` | Transfer Stok |
| `SA-` | Penyesuaian Stok |
| `SO-` | Stock Opname Persediaan |
| `SR-` | Retur Pemasok |

Referensi dengan awalan lain tetap ditampilkan sebagai teks biasa.

## Memfilter dan mencari

Gunakan tombol **Filter** di toolbar untuk mempersempit data:

- **Cari** — mencari pada nomor referensi, catatan, nama/kode produk, nama/kode gudang, serta
  nama/email pembuat.
- **Produk** — pilih satu produk.
- **Gudang** — pilih satu gudang.
- **Jenis Pergerakan** — pilih salah satu jenis pergerakan.
- **Tanggal Mulai** dan **Tanggal Akhir** — rentang berdasarkan **Waktu Pergerakan**.

Tekan **Terapkan filter** untuk menjalankan filter, **Atur ulang** untuk mengembalikan isian filter,
dan **Hapus semua** untuk membersihkan seluruh isian. Tombol **Reset Filter** pada toolbar
mengembalikan daftar ke kondisi tanpa filter.

Klik judul kolom untuk mengurutkan data. Kolom yang dapat diurutkan: Waktu Pergerakan, Jenis
Pergerakan, Qty Masuk, Qty Keluar, Saldo, Harga Satuan, Rata-rata Biaya, Referensi, Produk, Gudang,
dan Dibuat Oleh.

## Ekspor

Tombol **Ekspor** mengunduh data sesuai filter aktif dalam format Excel (`.xlsx`) atau CSV. Kolom
yang diekspor:

1. Moved At
2. Product Code
3. Product Name
4. Warehouse Code
5. Warehouse Name
6. Branch
7. Movement Type
8. Reference
9. Qty In
10. Qty Out
11. Balance After
12. Unit Cost
13. Average Cost After
14. Notes
15. Created By

Nilai kosong pada kolom teks ditulis sebagai `-`, sedangkan nilai kosong pada kolom angka ditulis
sebagai `0`. File ekspor mengikuti filter dan urutan yang sedang aktif.

## Aturan & validasi penting

- Halaman **hanya-baca**; koreksi stok dilakukan melalui dokumen sumber, bukan dari halaman ini.
- **Saldo** adalah saldo setelah pergerakan pada baris tersebut, bukan total stok terkini. Untuk
  melihat stok saat ini, gunakan halaman **Pemantauan Stok**.
- Rentang **Tanggal Akhir** tidak boleh lebih awal dari **Tanggal Mulai**.
- Jumlah data per halaman dibatasi maksimal **100** baris.
- Nilai jumlah dan nominal mengikuti pengaturan regional perusahaan (pemisah ribuan dan desimal).

## Tanya jawab singkat

**Q:** Mengapa saldo pada baris terakhir berbeda dengan stok di halaman Pemantauan Stok?

**J:** Saldo pada halaman ini adalah saldo **setelah** pergerakan pada baris tersebut, bukan total
stok terkini. Bila filter produk, gudang, atau tanggal dipersempit, baris terakhir hanya mewakili
kondisi pada rentang yang dipilih. Gunakan halaman Pemantauan Stok untuk melihat stok saat ini.

**Q:** Mengapa kolom Gudang, Rata-rata Biaya, dan Dibuat Oleh tidak terlihat?

**J:** Ketiga kolom disembunyikan secara default agar tabel lebih ringkas. Klik tombol **Kolom** di
toolbar lalu aktifkan kolom yang ingin ditampilkan.

**Q:** Bagaimana cara mengetahui dokumen yang menyebabkan suatu mutasi?

**J:** Lihat kolom **Referensi**. Bila nomor berawalan `ST-`, `SA-`, `SO-`, atau `SR-`, klik nomor
tersebut untuk membuka halaman dokumen sumbernya. Referensi dengan awalan lain ditampilkan sebagai
teks biasa.

**Q:** Apakah saya bisa mengubah atau menghapus baris mutasi stok?

**J:** Tidak. Halaman Mutasi Stok hanya untuk melihat. Perubahan stok harus dilakukan melalui
dokumen sumber yang sesuai agar jejak audit tetap utuh.

**Q:** Apa perbedaan Qty Masuk dan Qty Keluar?

**J:** **Qty Masuk** menambah stok (misalnya penerimaan barang, transfer masuk, penyesuaian masuk,
hasil produksi, retur penjualan). **Qty Keluar** mengurangi stok (misalnya retur pemasok, transfer
keluar, penyesuaian keluar, konsumsi produksi, penjualan).
