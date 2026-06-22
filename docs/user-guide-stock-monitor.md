# User Guide: Stock Monitor

## Gambaran Umum

Stock Monitor adalah dashboard pemantauan stok read-only yang menampilkan snapshot stok terkini per produk per gudang. Dashboard ini dirancang untuk membantu tim inventory dan manajemen dalam memantau level stok, mengidentifikasi produk dengan stok rendah, dan menganalisis distribusi stok berdasarkan gudang, kategori, dan cabang.

Halaman ini tidak mendukung operasi CRUD (tambah, edit, hapus). Untuk melakukan penyesuaian stok, silakan gunakan modul Stock Adjustment atau Inventory Stocktake.

**Fitur utama:**
- 4 kartu ringkasan: Total SKU-Warehouse, Total Quantity, Total Stock Value, Low Stock Items
- 3 kartu distribusi: Stock by Warehouse, Stock by Category, Stock by Branch
- Tabel data stok per produk per gudang dengan 7 kolom
- 6 filter untuk mempersempit tampilan data
- Export data ke Excel

## Menu & Navigasi

| Menu | URL | Permission / Fungsi |
|------|-----|---------------------|
| Inventory > Stock Monitor | `/stock-monitor` | `stock_monitor` - Melihat dashboard stok |
| Export Excel | `POST /api/stock-monitor/export` | `stock_monitor` - Mengunduh data stok |

---

## 1. Kartu Ringkasan (Summary Cards)

Bagian atas halaman menampilkan 4 kartu ringkasan yang memberikan gambaran cepat kondisi stok secara keseluruhan.

### 1.1 Total SKU-Warehouse

Menampilkan jumlah total kombinasi produk-gudang yang memiliki stok dalam sistem. Setiap produk yang ada di beberapa gudang dihitung sebagai entri terpisah.

[Screenshot: Kartu Total SKU-Warehouse dengan angka contoh]

### 1.2 Total Quantity

Menampilkan jumlah total kuantitas seluruh produk di seluruh gudang. Angka ini merupakan agregasi dari kolom Qty On Hand di tabel monitoring.

[Screenshot: Kartu Total Quantity dengan angka contoh]

### 1.3 Total Stock Value

Menampilkan total nilai stok (quantity x average cost) dari seluruh produk di seluruh gudang. Nilai ini dihitung berdasarkan average cost per produk.

[Screenshot: Kartu Total Stock Value dengan angka contoh]

### 1.4 Low Stock Items

Menampilkan jumlah kombinasi produk-gudang yang kuantitasnya berada di bawah threshold stok rendah. Threshold dapat diatur melalui filter Low Stock Threshold.

[Screenshot: Kartu Low Stock Items dengan angka contoh]

---

## 2. Kartu Distribusi Stok

Di bawah kartu ringkasan, terdapat 3 kartu distribusi yang menampilkan 5 besar (top 5) berdasarkan kuantitas.

### 2.1 Stock by Warehouse

Menampilkan 5 gudang dengan total kuantitas stok tertinggi. Membantu mengidentifikasi gudang mana yang menyimpan stok paling banyak.

[Screenshot: Kartu Stock by Warehouse dengan 5 gudang teratas]

### 2.2 Stock by Category

Menampilkan 5 kategori produk dengan total kuantitas stok tertinggi. Membantu mengidentifikasi kategori produk mana yang mendominasi inventory.

[Screenshot: Kartu Stock by Category dengan 5 kategori teratas]

### 2.3 Stock by Branch

Menampilkan 5 cabang dengan total kuantitas stok tertinggi. Membantu mengidentifikasi distribusi stok antar cabang.

[Screenshot: Kartu Stock by Branch dengan 5 cabang teratas]

---

## 3. Tabel Monitoring Stok

Tabel utama menampilkan data stok per produk per gudang dengan 7 kolom.

**Kolom yang ditampilkan:**

| Kolom | Keterangan |
|-------|------------|
| Product | Nama produk dan kode produk (subtitle) |
| Category | Kategori produk |
| Warehouse | Nama gudang, kode gudang, dan cabang (subtitle) |
| Qty On Hand | Kuantitas stok tersedia di gudang tersebut |
| Avg Cost | Rata-rata harga pokok per unit |
| Stock Value | Nilai total stok (Qty On Hand x Avg Cost) |
| Last Movement | Tanggal dan waktu pergerakan stok terakhir |

**Fitur tabel:**
- **Sorting**: Klik header kolom untuk mengurutkan data. Kolom yang dapat diurutkan: Product, Category, Warehouse, Qty On Hand, Avg Cost, Stock Value, Last Movement.
- **Pagination**: Gunakan navigasi halaman di bagian bawah tabel untuk berpindah halaman. Ukuran halaman dapat disesuaikan.
- **Search**: Gunakan field pencarian di toolbar untuk mencari produk, kategori, gudang, atau cabang.

[Screenshot: Tabel monitoring stok dengan data contoh]

---

## 4. Filter Data

Dashboard menyediakan 6 filter untuk mempersempit data yang ditampilkan.

### 4.1 Search

**Fungsi:** Mencari berdasarkan nama produk, kode produk, nama kategori, nama gudang, atau nama cabang.

**Cara menggunakan:**
1. Ketik kata kunci di field Search pada toolbar
2. Data akan otomatis diperbarui berdasarkan hasil pencarian

[Screenshot: Field Search dengan kata kunci contoh]

### 4.2 Filter Product

**Fungsi:** Memfilter data berdasarkan produk tertentu.

**Cara menggunakan:**
1. Klik dropdown "Product" di toolbar
2. Pilih produk yang diinginkan atau biarkan "All products"
3. Data akan otomatis diperbarui

[Screenshot: Dropdown filter Product]

### 4.3 Filter Warehouse

**Fungsi:** Memfilter data berdasarkan gudang tertentu.

**Cara menggunakan:**
1. Klik dropdown "Warehouse" di toolbar
2. Pilih gudang yang diinginkan atau biarkan "All warehouses"
3. Data akan otomatis diperbarui

[Screenshot: Dropdown filter Warehouse]

### 4.4 Filter Branch

**Fungsi:** Memfilter data berdasarkan cabang tertentu.

**Cara menggunakan:**
1. Klik dropdown "Branch" di toolbar
2. Pilih cabang yang diinginkan atau biarkan "All branches"
3. Data akan otomatis diperbarui

[Screenshot: Dropdown filter Branch]

### 4.5 Filter Category

**Fungsi:** Memfilter data berdasarkan kategori produk tertentu.

**Cara menggunakan:**
1. Klik dropdown "Category" di toolbar
2. Pilih kategori yang diinginkan atau biarkan "All categories"
3. Data akan otomatis diperbarui

[Screenshot: Dropdown filter Category]

### 4.6 Filter Low Stock Threshold

**Fungsi:** Memfilter produk dengan kuantitas di bawah threshold tertentu. Berguna untuk mengidentifikasi produk yang perlu segera di-restock.

**Cara menggunakan:**
1. Ketik angka threshold di field "Low Stock Threshold" pada toolbar (contoh: 10)
2. Data akan menampilkan hanya produk dengan Qty On Hand di bawah threshold
3. Kartu Low Stock Items akan menampilkan jumlah item yang memenuhi kriteria

**Default:** Kosong (tidak ada filter threshold)

**Use case:**
- Memonitor produk yang hampir habis
- Menyusun daftar pembelian (purchase order)
- Mengidentifikasi produk yang perlu transfer antar gudang

[Screenshot: Filter Low Stock Threshold dengan nilai 10]

---

## 5. Export Data ke Excel

Data stok dapat diekspor ke file Excel untuk analisis lebih lanjut atau pelaporan.

**Cara melakukan export:**
1. Atur filter sesuai kebutuhan (search, product, warehouse, branch, category, low stock threshold)
2. Klik tombol Export di toolbar
3. Sistem akan memproses dan menghasilkan file Excel
4. Unduh file dari link yang disediakan

**Catatan:** Export akan mengikuti filter yang sedang aktif. Pastikan filter sudah sesuai sebelum menekan tombol Export.

[Screenshot: Tombol Export di toolbar dan dialog download]

---

## 6. Persyaratan & Akses

### 6.1 Permission

Untuk mengakses Stock Monitor, user memerlukan permission `stock_monitor`.

**Cara cek permission:**
- Hubungi administrator sistem
- Pastikan role user memiliki permission ini

### 6.2 Menu Navigation

Stock Monitor berada di bawah menu group Inventory.

**Lokasi menu:**
1. Buka sidebar/menu utama
2. Pilih menu group "Inventory"
3. Klik "Stock Monitor"

[Screenshot: Sidebar menu Inventory dengan Stock Monitor]

---

## 7. Alur Kerja yang Disarankan

### 7.1 Monitoring Rutin Harian

1. Buka Stock Monitor
2. Periksa kartu Low Stock Items -- jika ada angka di atas 0, lanjutkan ke langkah 3
3. Gunakan filter Low Stock Threshold untuk melihat produk spesifik yang perlu perhatian
4. Catat produk yang perlu di-restock atau di-transfer
5. Tindak lanjuti dengan membuat Purchase Order atau Stock Transfer

### 7.2 Analisis Distribusi Stok Mingguan

1. Buka Stock Monitor
2. Periksa kartu Stock by Warehouse -- identifikasi ketidakseimbangan
3. Periksa kartu Stock by Category -- identifikasi kategori dengan stok berlebih atau kurang
4. Periksa kartu Stock by Branch -- pastikan distribusi antar cabang optimal
5. Gunakan filter Branch atau Warehouse untuk analisis lebih detail
6. Export data ke Excel jika diperlukan untuk presentasi

### 7.3 Investigasi Stok Tidak Bergerak

1. Buka Stock Monitor
2. Urutkan tabel berdasarkan kolom Last Movement (klik header)
3. Identifikasi produk dengan Last Movement terlama
4. Gunakan filter Category atau Warehouse untuk mempersempit analisis
5. Tindak lanjuti dengan stock opname atau penghapusan stok usang

---

## FAQ

**Q: Apa perbedaan antara Stock Monitor dan Stock Movements?**
A: Stock Monitor menampilkan snapshot stok terkini (posisi stok saat ini). Stock Movements menampilkan riwayat pergerakan stok (kartu stok) dari waktu ke waktu. Gunakan Stock Monitor untuk melihat kondisi saat ini, dan Stock Movements untuk melacak riwayat transaksi.

**Q: Apakah data dapat diedit dari halaman ini?**
A: Tidak. Stock Monitor adalah halaman read-only. Untuk melakukan penyesuaian stok, gunakan modul Stock Adjustment. Untuk stock opname, gunakan modul Inventory Stocktake.

**Q: Bagaimana cara menghitung Stock Value?**
A: Stock Value dihitung dengan rumus: Qty On Hand x Average Cost. Average cost adalah rata-rata tertimbang dari harga pokok produk yang dihitung otomatis oleh sistem berdasarkan transaksi pembelian dan penerimaan barang.

**Q: Mengapa Qty On Hand di Stock Monitor berbeda dengan laporan lain?**
A: Pastikan filter yang digunakan sama (warehouse, branch, category). Perbedaan bisa terjadi jika ada transaksi yang belum diposting atau jika laporan menggunakan tanggal cutoff yang berbeda. Stock Monitor selalu menampilkan data real-time.

**Q: Bagaimana cara mengatur threshold stok rendah?**
A: Threshold stok rendah diatur secara manual melalui filter Low Stock Threshold. Masukkan angka minimum kuantitas, dan sistem akan menampilkan semua produk dengan Qty On Hand di bawah angka tersebut. Tidak ada threshold default -- setiap pengguna dapat mengatur threshold sesuai kebutuhan.

**Q: Apakah data di kartu distribusi (top 5) terpengaruh oleh filter?**
A: Ya. Kartu ringkasan (Total SKU-Warehouse, Total Quantity, Total Stock Value, Low Stock Items) dan kartu distribusi (Stock by Warehouse, Stock by Category, Stock by Branch) akan mengikuti filter yang sedang aktif. Gunakan filter untuk mendapatkan insight yang lebih spesifik.

**Q: Bagaimana cara mengecek stok produk tertentu di semua gudang?**
A: Gunakan filter Product untuk memilih produk yang diinginkan. Tabel akan menampilkan stok produk tersebut di setiap gudang. Kartu Stock by Warehouse juga akan menampilkan distribusi produk tersebut per gudang.

**Q: Apakah export Excel menyertakan data kartu ringkasan?**
A: Export Excel hanya menyertakan data tabel (7 kolom) sesuai filter yang aktif. Data kartu ringkasan dan distribusi tidak termasuk dalam export. Untuk dokumentasi kartu ringkasan, gunakan screenshot.

**Q: Apa yang harus dilakukan jika ada produk dengan stok negatif?**
A: Stok negatif biasanya terjadi karena kesalahan pencatatan atau transaksi yang belum disesuaikan. Segera lakukan investigasi melalui Stock Movements untuk melacak penyebabnya, lalu lakukan penyesuaian melalui Stock Adjustment.

**Q: Bagaimana cara memantau stok secara rutin setiap hari?**
A: Buka Stock Monitor setiap pagi untuk memantau level stok dan mengidentifikasi produk yang perlu di-restock sebelum memulai operasional. Gunakan filter Low Stock Threshold untuk fokus pada produk yang hampir habis.

**Q: Bagaimana cara mendapatkan insight yang lebih tajam dari filter?**
A: Gunakan kombinasi filter (misalnya Branch + Category + Low Stock Threshold) untuk analisis yang lebih spesifik. Contoh: produk kategori "Bahan Baku" di cabang Jakarta dengan stok di bawah 50 unit.

**Q: Bagaimana cara menyiapkan data untuk rapat inventory?**
A: Export data ke Excel sebelum rapat inventory untuk digunakan sebagai bahan diskusi. Filter data sesuai agenda rapat (per gudang, per kategori, atau hanya produk stok rendah).

**Q: Bagaimana cara membandingkan stok dengan periode sebelumnya?**
A: Meskipun Stock Monitor hanya menampilkan data terkini, Anda dapat membandingkan dengan export sebelumnya untuk melihat tren perubahan stok dari waktu ke waktu.

**Q: Bagaimana cara mengidentifikasi produk prioritas untuk restock?**
A: Urutkan kolom Qty On Hand secara ascending (dari kecil ke besar) untuk melihat produk dengan stok paling sedikit. Urutkan kolom Stock Value secara descending untuk melihat produk dengan nilai inventori tertinggi.

**Q: Bagaimana cara berkoordinasi dengan tim purchasing?**
A: Bagikan data Low Stock Items kepada tim purchasing sebagai dasar pembuatan Purchase Order atau Purchase Request.

---

## Terkait

- [User Guide: Stock Movements](user-guide-stock-movements.md)
- [User Guide: Stock Adjustment](user-guide-stock-adjustments.md)
- [User Guide: Inventory Stocktake](user-guide-inventory-stocktakes.md)
- [User Guide: Inventory Valuation Report](user-guide-inventory-valuation-reports.md)
- [User Guide: Stock Movement Report](user-guide-stock-movement-report.md)
- [User Guide: Stock Adjustment Report](user-guide-stock-adjustment-report.md)
- [User Guide: Warehouse](user-guide-warehouses.md)
- [User Guide: Product](user-guide-products.md)
