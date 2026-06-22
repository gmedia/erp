# User Guide: Inventory Stocktakes

## Gambaran Umum

Modul Inventory Stocktakes mengelola **siklus stock opname persediaan** — dari perencanaan penghitungan fisik hingga rekonsiliasi dan penyesuaian stok. Modul ini memungkinkan tim gudang melakukan penghitungan stok secara terstruktur, mencatat selisih antara stok sistem dan stok fisik, serta memposting penyesuaian persediaan secara otomatis. Terintegrasi dengan modul Products, Warehouses, dan Stock Adjustments.

---

## Menu & Navigasi

| Menu | URL | Permission |
|------|-----|------------|
| Inventory Stocktakes | `/inventory-stocktakes` | `inventory_stocktake` |
| Inventory Stocktake Variance Report | `/reports/inventory-stocktake-variance` | `inventory_stocktake_variance_report` |

---

## 1. Membuat Stock Opname Baru (Planning)

Stock opname dimulai dengan membuat dokumen perencanaan yang mendefinisikan ruang lingkup penghitungan.

### Langkah-langkah

1. Buka menu **Inventory Stocktakes** → klik **Add New**
2. Isi form header:

   | Field | Keterangan | Contoh |
   |-------|------------|--------|
   | **Stocktake Number** | Nomor otomatis (format: ST-YYYY-NNNNNN) | ST-2026-000001 |
   | **Warehouse** | Pilih gudang yang akan di-opname | Gudang Pusat |
   | **Product Category** | Filter kategori produk (opsional, kosongkan untuk semua kategori) | Electronics |
   | **Stocktake Date** | Tanggal pelaksanaan stock opname | 2026-01-15 |
   | **Notes** | Catatan atau instruksi (opsional) | Opname akhir tahun |

3. Klik **Save**

Stocktake dibuat dengan status **Draft**. Pada tahap ini, item belum di-populate ke dalam dokumen.

[Screenshot: Form pembuatan stock opname baru dengan field warehouse, product category, dan stocktake date]

---

## 2. Memulai Penghitungan (Start Opname)

Setelah dokumen stock opname dibuat, langkah selanjutnya adalah memulai proses penghitungan fisik.

### Langkah-langkah

1. Buka **Inventory Stocktakes** dan cari stocktake yang ingin dimulai
2. Klik **View** untuk membuka detail stocktake
3. Klik tombol **Start Opname**

Saat tombol diklik:
- Status berubah dari **Draft** menjadi **In Progress**
- Sistem otomatis mengambil snapshot stok sistem dari `product_stocks` berdasarkan warehouse dan product category yang dipilih
- Setiap item memiliki: Product, System Quantity (stok di sistem), dan Counted Quantity (kosong, menunggu input)

### Validasi

- Hanya stocktake dengan status **Draft** yang bisa di-start
- User memerlukan permission `inventory-stocktakes.manage`

[Screenshot: Detail stock opname dengan tombol Start Opname dan daftar item yang sudah di-populate]

---

## 3. Melakukan Penghitungan Fisik (Counting)

Setelah status **In Progress**, tim gudang dapat mulai menghitung stok fisik dan memasukkan hasilnya ke sistem.

### Langkah-langkah

1. Buka stocktake yang sudah di-start (status: In Progress)
2. Tabel menampilkan daftar item dengan kolom:
   - **Product** — Nama produk dan kode
   - **Category** — Kategori produk
   - **Unit** — Satuan produk
   - **System Qty** — Jumlah stok di sistem (snapshot saat start)
   - **Counted Qty** — Jumlah stok hasil hitung fisik (input manual)
   - **Variance** — Selisih (Counted Qty - System Qty)
   - **Notes** — Catatan per item (opsional)

3. Untuk setiap item, isi **Counted Qty** sesuai hasil penghitungan fisik
4. Jika ada ketidaksesuaian atau catatan khusus, isi kolom **Notes**
5. Klik **Save** untuk menyimpan progress (penghitungan bisa dilakukan bertahap)

### Tips Penghitungan

- Gunakan filter untuk fokus pada kategori produk tertentu
- Variance akan dihitung otomatis saat Counted Qty diisi
- Negative variance = stok fisik lebih sedikit dari sistem (deficit)
- Positive variance = stok fisik lebih banyak dari sistem (surplus)

[Screenshot: Tabel item stock opname dengan kolom System Qty, Counted Qty, dan Variance yang sudah terisi sebagian]

---

## 4. Menyelesaikan Stock Opname (Complete)

Setelah seluruh item selesai dihitung, stock opname dapat diselesaikan.

### Langkah-langkah

1. Pastikan semua item sudah memiliki **Counted Qty** yang terisi
2. Buka stocktake yang ingin diselesaikan
3. Klik tombol **Complete**

### Validasi

- Semua item **wajib** memiliki Counted Qty yang terisi
- User memerlukan permission `inventory-stocktakes.manage`

### Proses Saat Complete

- Status berubah dari **In Progress** menjadi **Completed**
- Sistem menghitung total variance per item
- Data siap untuk diproses lebih lanjut (posting adjustment)

[Screenshot: Detail stock opname dengan status Completed dan ringkasan variance]

---

## 5. Membatalkan Stock Opname (Cancel)

Stock opname dapat dibatalkan jika tidak jadi dilaksanakan atau terdapat kesalahan.

### Membatalkan dari Draft

1. Buka stocktake dengan status **Draft**
2. Klik **Edit** → ubah status ke **Cancelled**
3. Klik **Save**

### Membatalkan dari In Progress

1. Buka stocktake dengan status **In Progress**
2. Klik **Cancel** (soft-cancel)
3. Status berubah menjadi **Cancelled**

### Catatan Penting

- Stock opname yang sudah **Completed** tidak bisa dibatalkan
- Pembatalan hanya mengubah status, tidak menghapus data
- Data tetap tersimpan untuk keperluan audit

[Screenshot: Dialog konfirmasi pembatalan stock opname]

---

## 6. Melihat Detail Stock Opname

Halaman detail menampilkan informasi lengkap stock opname dan daftar item.

### Informasi Header

- **Stocktake Number** — Nomor dokumen unik
- **Warehouse** — Lokasi gudang
- **Product Category** — Kategori produk (jika difilter)
- **Stocktake Date** — Tanggal pelaksanaan
- **Status** — Draft / In Progress / Completed / Cancelled
- **Notes** — Catatan umum

### Tabel Item

| Kolom | Keterangan |
|-------|------------|
| Product | Nama dan kode produk |
| Category | Kategori produk |
| Unit | Satuan |
| System Qty | Jumlah stok di sistem (snapshot) |
| Counted Qty | Jumlah stok hasil hitung fisik |
| Variance | Selisih (Counted - System) |
| Notes | Catatan per item |

### Warna Variance

- **Hijau** — Variance positif (surplus)
- **Merah** — Variance negatif (deficit)
- **Abu-abu** — Variance nol (sesuai)

[Screenshot: Modal detail stock opname dengan tabel item dan ringkasan variance]

---

## 7. Mengedit Stock Opname

Perubahan hanya bisa dilakukan pada stock opname dengan status **Draft**.

### Langkah-langkah

1. Buka **Inventory Stocktakes**
2. Cari stocktake yang ingin diedit
3. Klik **Edit** pada baris yang sesuai
4. Ubah field yang diperlukan (Warehouse, Product Category, Stocktake Date, Notes)
5. Klik **Save**

### Batasan

- Tidak bisa mengedit stock opname dengan status **In Progress** atau **Completed**
- Stocktake Number tidak bisa diubah (auto-generated)

[Screenshot: Form edit stock opname]

---

## 8. Pencarian dan Filter

Gunakan fitur pencarian dan filter untuk menemukan stock opname dengan cepat.

### Pencarian

Ketik kata kunci di kolom search:

```
Search inventory stocktakes...
```

Pencarian mencakup:
- Stocktake Number
- Notes

### Filter Tersedia

| Filter | Keterangan |
|--------|------------|
| **Warehouse** | Filter berdasarkan gudang |
| **Product Category** | Filter berdasarkan kategori produk |
| **Stocktake Date** | Filter berdasarkan tanggal pelaksanaan |
| **Status** | Draft / In Progress / Completed / Cancelled |

### Cara Menggunakan Filter

1. Klik dropdown filter yang diinginkan
2. Pilih nilai atau rentang tanggal
3. Tabel otomatis menyaring data sesuai filter

[Screenshot: Halaman daftar stock opname dengan filter warehouse dan status aktif]

---

## 9. Pengurutan Kolom (Sorting)

Semua kolom utama dapat diurutkan untuk memudahkan analisis.

### Kolom yang Bisa Diurutkan

| Kolom | Keterangan |
|-------|------------|
| **Stocktake Number** | Urutkan berdasarkan nomor dokumen |
| **Warehouse** | Urutkan berdasarkan nama gudang |
| **Product Category** | Urutkan berdasarkan kategori produk |
| **Stocktake Date** | Urutkan berdasarkan tanggal pelaksanaan |
| **Status** | Urutkan berdasarkan status |

### Cara Mengurutkan

1. Klik header kolom yang ingin diurutkan
2. Klik sekali untuk urutan ascending (A-Z, terlama-terbaru)
3. Klik lagi untuk urutan descending (Z-A, terbaru-terlama)
4. Icon panah menunjukkan arah pengurutan

[Screenshot: Header kolom dengan icon sorting aktif]

---

## 10. Export ke Excel

Data stock opname dapat diekspor ke file Excel untuk pelaporan atau analisis lebih lanjut.

### Langkah-langkah

1. Buka **Inventory Stocktakes**
2. Terapkan filter jika diperlukan (filter mempengaruhi data yang di-export)
3. Klik tombol **Export** di toolbar
4. File Excel (.xlsx) akan otomatis terunduh

### Kolom dalam Export

- Stocktake Number
- Warehouse
- Product Category
- Stocktake Date
- Status
- Created At
- Updated At

### Export Item Detail

Untuk export detail item stock opname, gunakan **Inventory Stocktake Variance Report** yang menyediakan breakdown per item.

[Screenshot: Tombol Export di toolbar halaman stock opname]

---

## 11. Status Lifecycle

Stock opname mengikuti siklus status yang terstruktur melalui pipeline engine.

### Diagram Status

```
Draft → In Progress → Completed
   ↓          ↓
   Cancelled  Cancelled
```

### Penjelasan Status

| Status | Keterangan | Aksi Tersedia |
|--------|------------|---------------|
| **Draft** | Dokumen baru dibuat, item belum di-populate | Edit, Start Opname, Cancel |
| **In Progress** | Penghitungan sedang berlangsung, item sudah di-populate | Save Count, Complete, Cancel |
| **Completed** | Penghitungan selesai, variance sudah dihitung | View, Export |
| **Cancelled** | Dibatalkan, tidak bisa dilanjutkan | View, Export |

### Transisi Status

| Transisi | Dari | Ke | Permission | Kondisi |
|----------|------|-----|------------|---------|
| Start Opname | Draft | In Progress | `inventory-stocktakes.manage` | - |
| Complete | In Progress | Completed | `inventory-stocktakes.manage` | Semua item sudah counted |
| Cancel | Draft | Cancelled | `inventory-stocktakes.cancel` | - |
| Cancel | In Progress | Cancelled | `inventory-stocktakes.cancel` | - |

[Screenshot: Diagram lifecycle status stock opname]

---

## 12. Laporan Variance

Setelah stock opname selesai, laporan variance dapat diakses untuk analisis lebih lanjut.

### Inventory Stocktake Variance Report

Buka menu **Reports → Inventory Stocktake Variance** untuk melihat:
- Daftar semua item dengan variance
- Breakdown per stocktake, produk, dan gudang
- System Qty vs Counted Qty vs Variance
- Hasil (Surplus / Deficit / Sesuai)

### Filter Laporan

- Stocktake Number
- Product
- Category
- Warehouse
- Date Range

### Export Laporan

Laporan variance dapat di-export ke Excel untuk dokumentasi dan tanda tangan.

[Screenshot: Halaman laporan variance dengan filter dan tabel detail]

---

## 13. Permissions

Akses ke modul Inventory Stocktakes dikendalikan melalui permission berikut.

| Permission | Akses |
|------------|-------|
| `inventory_stocktake` | View & list inventory stocktakes |
| `inventory_stocktake.create` | Membuat stock opname baru |
| `inventory_stocktake.edit` | Mengedit stock opname (hanya status Draft) |
| `inventory_stocktake.delete` | Menghapus stock opname (hanya status Draft) |
| `inventory-stocktakes.manage` | Start Opname, Save Count, Complete |
| `inventory-stocktakes.cancel` | Membatalkan stock opname |
| `inventory_stocktake_variance_report` | Akses laporan variance |

### Penugasan Permission

Hubungi administrator sistem untuk mendapatkan permission yang sesuai dengan peran Anda.

---

## 14. Integrasi dengan Modul Lain

### Products

- Item stock opname diambil dari master data produk
- Stok sistem diambil dari `product_stocks` per warehouse
- Perubahan stok setelah adjustment akan mengupdate `product_stocks`

### Warehouses

- Stock opname dilakukan per gudang
- Setiap gudang memiliki stok terpisah
- Multi-warehouse mendukung opname paralel

### Stock Adjustments

- Variance yang signifikan dapat diproses melalui Stock Adjustments
- Dokumen adjustment dibuat terpisah untuk audit trail
- Posting adjustment akan mengupdate stok dan akun persediaan

---

## FAQ & Tips

**Q:** Apa perbedaan System Qty dan Counted Qty?

**J:** System Qty adalah jumlah stok yang tercatat di sistem saat tombol Start Opname diklik (snapshot). Counted Qty adalah jumlah aktual hasil penghitungan fisik yang dilakukan oleh tim gudang.

**Q:** Mengapa tombol Start Opname tidak muncul?

**J:** Pastikan stock opname masih dalam status Draft dan Anda memiliki permission `inventory-stocktakes.manage`. Tombol hanya muncul untuk status Draft.

**Q:** Apakah bisa menghitung item secara bertahap?

**J:** Ya. Setelah Start Opname, Anda bisa mengisi Counted Qty sebagian, klik Save, dan melanjutkan di waktu lain. Pastikan sebelum klik Complete, semua item sudah terisi.

**Q:** Bagaimana jika ada item yang terlewat saat Start Opname?

**J:** Item di-populate berdasarkan stok yang ada di gudang terpilih sesuai filter Product Category. Jika ada produk baru setelah Start Opname, item tersebut tidak akan masuk ke dokumen. Buat stock opname baru untuk mencakup produk tersebut.

**Q:** Bisakah mengedit stock opname yang sudah In Progress?

**J:** Tidak bisa mengedit header (Warehouse, Date). Namun Anda bisa mengedit Counted Qty dan Notes per item sebelum Complete. Setelah Complete, tidak ada yang bisa diubah.

**Q:** Apa yang terjadi setelah stock opname Completed?

**J:** Status berubah menjadi Completed dan variance sudah dihitung. Namun, penyesuaian stok tidak otomatis terjadi. Anda perlu membuat Stock Adjustment terpisah berdasarkan variance jika ingin mengupdate stok sistem.

**Q:** Bagaimana cara melihat riwayat stock opname per produk?

**J:** Gunakan Inventory Stocktake Variance Report dengan filter Product untuk melihat semua stock opname yang melibatkan produk tersebut beserta variance yang terjadi.

**Q:** Apakah stock opname bisa dibatalkan setelah Completed?

**J:** Tidak. Status Completed adalah final. Pastikan semua data benar sebelum menyelesaikan stock opname. Jika ada kesalahan, dokumentasikan dan buat adjustment terpisah.

**Q:** Bagaimana format nomor Stocktake Number?

**J:** Format: ST-YYYY-NNNNNN (contoh: ST-2026-000001). Nomor dibuat otomatis dan berurutan per tahun.

**Q:** Apakah bisa melakukan stock opname untuk semua gudang sekaligus?

**J:** Tidak. Setiap stock opname terikat ke satu warehouse. Jika ingin opname multiple gudang, buat dokumen stock opname terpisah untuk masing-masing gudang.

**Q:** Bagaimana cara mencetak hasil stock opname?

**J:** Gunakan fitur Export ke Excel. File Excel bisa dicetak atau dibagikan ke tim gudang untuk dokumentasi dan tanda tangan.

**Q:** Apakah ada batas waktu untuk menyelesaikan stock opname?

**J:** Tidak ada batas waktu otomatis di sistem. Namun, disarankan menyelesaikan stock opname dalam waktu singkat untuk menghindari perbedaan akibat pergerakan stok yang terjadi di antara waktu.

**Q:** Mengapa variance saya negatif padahal stok fisik lebih banyak?

**J:** Variance = Counted Qty - System Qty. Jika Counted Qty lebih besar dari System Qty, variance akan positif (surplus). Jika lebih kecil, variance negatif (deficit). Periksa kembali input Counted Qty.
