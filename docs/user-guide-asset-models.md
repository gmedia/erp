# User Guide: Asset Models

## Gambaran Umum

Asset Models adalah modul yang digunakan untuk mendefinisikan model atau tipe standar dari aset perusahaan. Setiap model aset berisi informasi seperti Model Name, Manufacturer, Category (referensi ke Asset Categories), dan Specs (spesifikasi teknis). Model aset menjadi referensi saat membuat aset baru, karena dapat otomatis mengisi default untuk category, specs, dan useful life.

Modul ini merupakan bagian dari menu group **Asset** dan memiliki permission `asset_model`. Dengan menggunakan model aset, proses input aset baru menjadi lebih cepat dan konsisten karena data standar sudah tersedia.

[Screenshot: Halaman daftar Asset Models dengan tabel dan filter]

---

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Asset Models | `/asset-models` | Kelola model/tipe aset standar (tambah, edit, hapus, lihat detail) |
| Asset Categories | `/asset-categories` | Kelola kategori aset (referensi untuk Asset Models) |
| Assets | `/assets` | Kelola data aset individual (menggunakan Asset Models sebagai referensi) |
| Asset Locations | `/asset-locations` | Kelola lokasi aset |
| Asset Movements | `/asset-movements` | Kelola pergerakan aset |
| Asset Maintenances | `/asset-maintenances` | Kelola maintenance aset |

Untuk mengakses menu Asset Models, user harus memiliki permission `asset_model`.

---

## 1. Melihat Daftar Model Aset

Halaman Asset Models menampilkan tabel semua model aset dengan kolom:

| Kolom | Penjelasan | Sortable |
|-------|------------|----------|
| Model Name | Nama model atau tipe aset | Ya |
| Manufacturer | Produsen atau pembuat model aset | Ya |
| Category | Kategori aset dari Asset Categories | Ya |
| Specs | Spesifikasi teknis model (text field) | Tidak |
| Actions | Tombol View, Edit, Delete | Tidak |

[Screenshot: Tabel Asset Models dengan kolom-kolom di atas]

### Filter dan Pencarian

Gunakan filter di bagian atas tabel untuk menyaring data:

1. **Search** - ketik model name atau manufacturer untuk mencari model aset
2. **Category** - pilih kategori aset tertentu dari dropdown

[Screenshot: Panel filter Asset Models]

---

## 2. Menambah Model Aset Baru

1. Buka halaman **Asset Models** (`/asset-models`)
2. Klik tombol **Add New** di bagian atas tabel
3. Isi form dengan data model aset:

[Screenshot: Form Add New Asset Model]

| Field | Keterangan | Wajib |
|-------|------------|-------|
| Model Name | Nama model atau tipe aset (contoh: Laptop Dell XPS 15) | Ya |
| Manufacturer | Nama produsen atau pembuat (contoh: Dell, HP, Lenovo) | Ya |
| Category | Pilih kategori dari dropdown Asset Categories | Ya |
| Specs | Spesifikasi teknis dalam bentuk text (contoh: Intel i7, 16GB RAM, 512GB SSD) | Tidak |

4. Klik **Save** untuk menyimpan

### Validasi Form

- Model Name harus unique dan tidak boleh kosong
- Category harus dipilih dari dropdown Asset Categories yang sudah ada
- Manufacturer tidak boleh kosong
- Specs bersifat opsional dan dapat diisi dengan text panjang

---

## 3. Melihat Detail Model Aset

1. Klik tombol **View** (ikon mata) pada baris model aset
2. Modal detail akan muncul menampilkan semua informasi:

[Screenshot: Modal View Asset Model Details]

Informasi yang ditampilkan:
- Model Name
- Manufacturer
- Category (dengan link ke detail kategori)
- Specs (jika ada)
- Created At, Updated At

---

## 4. Mengedit Model Aset

1. Klik tombol **Edit** (ikon pensil) pada baris model aset
2. Form edit akan muncul dengan data yang sudah terisi
3. Ubah field yang diperlukan
4. Klik **Save** untuk menyimpan perubahan

[Screenshot: Form Edit Asset Model]

### Catatan Edit

- Semua field bisa diubah kecuali ID
- Jika mengubah Category, pastikan category baru sudah ada di Asset Categories
- Perubahan pada model aset tidak akan mengubah data aset individual yang sudah ada

---

## 5. Menghapus Model Aset

1. Klik tombol **Delete** (ikon trash) pada baris model aset
2. Konfirmasi penghapusan pada dialog yang muncul
3. Klik **Delete** untuk menghapus permanen

[Screenshot: Dialog konfirmasi delete Asset Model]

### Peringatan Penghapusan

Model aset **tidak bisa dihapus** jika:
- Sudah digunakan oleh aset individual di modul Assets
- Terdapat referensi dari modul lain

Hapus atau ubah semua aset yang menggunakan model tersebut sebelum menghapus.

---

## 6. Export Data Model Aset

1. Klik tombol **Export** di bagian atas tabel
2. File Excel akan diunduh dengan semua data model aset

[Screenshot: Tombol Export Asset Models]

### Kolom dalam Export

File Excel berisi kolom:
- ID, Model Name, Manufacturer
- Category (nama kategori)
- Specs
- Created At, Updated At

Export menggunakan endpoint `/api/asset-models/export` dan mengikuti filter yang aktif di tabel.

---

## 7. Integrasi dengan Modul Assets

Data model aset digunakan saat membuat aset baru di modul **Assets**:

### Penggunaan di Form Asset Baru

1. Field **Asset Model** di form asset memilih dari daftar model aset
2. Setelah memilih model, sistem dapat otomatis mengisi:
   - **Category** - dari category yang terdefinisi di model
   - **Specs** - dari specs yang terdefinisi di model
   - **Useful Life** - default useful life dari category model

### Manfaat Menggunakan Model Aset

- **Konsistensi** - semua aset dengan model yang sama memiliki spesifikasi seragam
- **Efisiensi** - input aset baru lebih cepat karena data default sudah tersedia
- **Akurasi** - mengurangi kesalahan input manual untuk field yang berulang

[Screenshot: Form Add Asset dengan dropdown Asset Model]

---

## FAQ & Tips

### Apakah model aset yang dihapus bisa dikembalikan?

Tidak. Penghapusan model aset bersifat permanen. Pastikan tidak ada aset yang menggunakan model tersebut sebelum menghapus.

### Bagaimana jika model aset sudah tidak diproduksi?

Model aset tetap bisa digunakan untuk mendokumentasikan aset yang sudah ada. Tidak ada status aktif/nonaktif untuk model aset. Jika ingin menyembunyikan dari dropdown, pertimbangkan untuk mengubah nama atau menambah catatan di field Specs.

### Bisakah satu model aset memiliki beberapa kategori?

Tidak. Setiap model aset hanya terhubung ke satu kategori. Jika model yang sama memiliki variasi kategori, buat model terpisah dengan nama yang berbeda (contoh: Laptop Dell XPS 15 - Standard dan Laptop Dell XPS 15 - Premium).

### Apakah Specs wajib diisi?

Tidak. Field Specs bersifat opsional. Namun, mengisi Specs sangat disarankan untuk memudahkan identifikasi aset dan otomatis mengisi data saat membuat aset baru.

### Bagaimana cara mencari model aset dengan nama parsial?

Gunakan field **Search** di filter. Ketik bagian dari Model Name atau Manufacturer. Sistem akan mencari dengan pencocokan parsial (substring match).

### Bisakah import model aset dari file?

Modul Asset Models saat ini belum memiliki fitur import bulk. Model aset harus ditambah satu per satu melalui form Add New.

### Apakah perubahan di model aset akan mempengaruhi aset yang sudah ada?

Tidak. Perubahan pada model aset tidak otomatis mengubah data aset individual yang sudah ada. Model aset hanya memberikan default value saat membuat aset baru.

### Bagaimana hubungan antara Asset Model dan Asset Category?

Asset Model memiliki foreign key ke Asset Category. Setiap model harus memiliki kategori. Kategori menentukan default useful life dan depreciation method untuk aset yang menggunakan model tersebut.

**Q:** Apa perbedaan antara Asset Model dan Asset Category?

**J:** Asset Category adalah pengelompokan umum berdasarkan jenis aset (contoh: Kendaraan, Peralatan Kantor, Komputer). Asset Model adalah tipe spesifik dalam kategori tersebut (contoh: Toyota Avanza 2024 dalam kategori Kendaraan). Category menentukan aturan penyusutan (useful life, depreciation method), sedangkan Model menentukan spesifikasi teknis dan manufacturer. Satu Category bisa memiliki banyak Model, tetapi satu Model hanya terikat pada satu Category.

**Q:** Bagaimana tips penamaan model aset yang baik?

**J:** Gunakan format konsisten: [Manufacturer] [Nama Produk] [Varian/Spesifikasi]. Contoh: "Dell XPS 15 i7-1360P 16GB", "Toyota Avanza 1.5 G MT", "HP LaserJet Pro M404dn". Hindari nama terlalu umum seperti "Laptop" atau "Printer" karena menyulitkan identifikasi. Sertakan informasi pembeda utama (processor, kapasitas, tipe) dalam nama jika varian signifikan.

**Q:** Kapan sebaiknya membuat model baru vs menggunakan model yang sudah ada?

**J:** Buat model baru jika spesifikasi atau manufacturer berbeda secara signifikan. Contoh: laptop dengan processor i5 vs i7 sebaiknya dibuat model terpisah karena mempengaruhi nilai dan useful life. Gunakan model yang sudah ada jika perbedaan hanya minor (warna, garansi). Pertimbangkan: apakah perbedaan ini akan mempengaruhi pencarian, pelaporan, atau penyusutan aset? Jika ya, buat model terpisah.

**Q:** Apa yang terjadi jika Asset Category yang digunakan model dihapus?

**J:** Sistem mencegah penghapusan Category jika masih digunakan oleh Model atau Aset. Anda harus memindahkan semua Model yang menggunakan Category tersebut ke Category lain terlebih dahulu, atau menghapus Model tersebut. Setelah tidak ada Model yang mereferensi Category, barulah Category bisa dihapus.

**Q:** Bagaimana cara memanfaatkan model aset untuk mempercepat input aset massal?

**J:** Siapkan model aset selengkap mungkin (isi Specs dengan detail) sebelum input aset massal. Saat menambah aset baru di modul Assets, pilih model dari dropdown, dan sistem akan otomatis mengisi Category, Specs, dan default useful life dari model tersebut. Ini mengurangi input manual dan memastikan konsistensi data antar aset dengan model yang sama. Untuk efisiensi maksimal, buat semua model yang dibutuhkan terlebih dahulu sebelum mulai input aset.

**Q:** Apakah field Manufacturer wajib diisi? Apa dampaknya jika dikosongkan?

**J:** Ya, Manufacturer wajib diisi dan tidak boleh kosong. Jika produsen tidak diketahui (misal untuk aset lama atau custom-built), isi dengan "Unknown", "Generic", atau "Custom". Mengosongkan Manufacturer akan menyebabkan validasi gagal saat save. Manufacturer digunakan sebagai kriteria pencarian dan filter di tabel, sehingga penting untuk konsistensi data.

**Q:** Bagaimana cara terbaik mengisi field Specs agar bermanfaat?

**J:** Isi Specs dengan format terstruktur: spesifikasi kunci dipisahkan koma atau baris baru. Contoh: "Processor: Intel i7-1360P, RAM: 16GB DDR5, Storage: 512GB NVMe SSD, Display: 15.6 inch FHD". Untuk kendaraan: "Engine: 1500cc, Transmission: Manual 5-speed, Fuel: Petrol, Seats: 7". Specs akan otomatis terisi di form Asset saat memilih model, jadi semakin lengkap Specs, semakin cepat input aset. Specs juga muncul di modal View dan file Export.

**Q:** Bisakah saya mengubah Category model yang sudah digunakan oleh banyak aset?

**J:** Bisa. Mengubah Category pada model tidak akan mengubah Category aset individual yang sudah ada. Aset yang sudah dibuat tetap mempertahankan Category saat aset tersebut dibuat. Perubahan Category model hanya akan mempengaruhi aset baru yang dibuat setelah perubahan. Jika Anda ingin mengubah Category aset existing, lakukan satu per satu di modul Assets.

**Q:** Bagaimana cara ekspor data model aset dengan filter tertentu?

**J:** Terapkan filter yang diinginkan di tabel terlebih dahulu (Search berdasarkan nama/manufacturer, atau pilih Category spesifik), lalu klik tombol Export. File Excel yang diunduh hanya akan berisi data model aset yang sesuai dengan filter aktif. Jika tidak ada filter aktif, semua data model aset akan diekspor. Kolom export mencakup: ID, Model Name, Manufacturer, Category, Specs, Created At, Updated At.

**Q:** Apakah ada batasan jumlah model aset yang bisa dibuat?

**J:** Tidak ada batasan jumlah. Anda bisa membuat model aset sebanyak yang dibutuhkan. Namun, untuk menjaga kerapihan data, hindari duplikasi model dengan nama yang mirip. Gunakan konsistensi penamaan dan pastikan setiap model benar-benar merepresentasikan tipe aset yang berbeda. Model yang sudah tidak relevan bisa dihapus jika tidak digunakan oleh aset manapun.

---

## Troubleshooting

### Model aset tidak muncul di dropdown asset form

Pastikan model aset sudah dibuat dengan benara di halaman Asset Models. Refresh halaman form asset jika baru saja menambah model baru.

### Error "Category not found" saat save

Category yang dipilih sudah dihapus atau tidak valid. Refresh halaman dan pilih category yang tersedia di dropdown.

### Tidak bisa menghapus model aset

Model aset tidak bisa dihapus jika sudah digunakan oleh aset individual. Periksa aset yang menggunakan model tersebut di modul Assets, ubah ke model lain atau hapus aset tersebut terlebih dahulu.

### Export tidak menghasilkan file

Periksa koneksi internet dan pastikan browser tidak memblokir download. Coba gunakan browser lain atau clear cache browser.

### Specs tidak muncul di export

Pastikan field Specs terisi saat membuat atau mengedit model aset. Jika Specs kosong, kolom akan tetap ada di export dengan nilai kosong.
