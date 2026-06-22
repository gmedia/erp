# User Guide: Asset Categories

## Gambaran Umum

Asset Categories adalah modul untuk mengklasifikasi aset berdasarkan jenis atau kelompok. Setiap kategori aset memiliki kode unik, nama, dan nilai default useful life (masa manfaat dalam bulan) yang digunakan untuk perhitungan depresiasi otomatis.

Kategori aset menjadi fondasi untuk:
- Pengelompokan aset di Asset Management
- Penentuan default masa manfaat untuk Asset Models
- Perhitungan depresiasi bulanan di Asset Depreciation Runs

## Menu & Navigasi

| Menu | Path | Permission |
|------|------|------------|
| Asset Categories | `/asset-categories` | `asset_category` |

Modul ini berada di grup menu **Asset** dan dapat diakses melalui navigasi sidebar.

[Screenshot: Sidebar menu dengan grup Asset dan submenu Asset Categories]

## 1. Menampilkan Daftar Kategori Aset

Halaman utama Asset Categories menampilkan tabel dengan kolom:

| Kolom | Deskripsi | Sortable |
|-------|-----------|----------|
| Code | Kode unik kategori aset | Ya |
| Name | Nama kategori aset | Ya |
| Default Useful Life (Months) | Masa manfaat default dalam bulan | Ya |
| Created At | Tanggal pembuatan record | Ya |
| Updated At | Tanggal terakhir diubah | Ya |

**Fitur tabel:**
- Sorting: klik header kolom untuk mengurutkan ascending/descending
- Pagination: navigasi halaman dengan kontrol di bagian bawah tabel
- Row selection: checkbox di kolom pertama untuk seleksi batch (tanpa checkbox di header)

[Screenshot: Tabel Asset Categories dengan 5 baris data sample]

### 1.1 Mencari Kategori Aset

Gunakan field search di toolbar atas tabel untuk memfilter data.

[Screenshot: Search field dengan placeholder "Search asset categories..."]

Ketik kata kunci dan tekan Enter. Sistem akan memfilter hasil berdasarkan kode atau nama kategori.

### 1.2 Melihat Detail Kategori Aset

Klik tombol **View** (ikon mata) pada baris kategori untuk membuka dialog detail.

Dialog menampilkan:
- Code
- Name
- Default Useful Life (dengan suffix "months")
- Created At (format lokal)
- Updated At (format lokal)

[Screenshot: View dialog Asset Category dengan detail lengkap]

## 2. Menambah Kategori Aset Baru

Klik tombol **Add New Asset Category** di toolbar atas tabel.

[Screenshot: Toolbar dengan tombol Add New Asset Category]

Form input memiliki 3 field:

| Field | Tipe | Required | Placeholder |
|-------|------|----------|-------------|
| Code | Text | Ya | e.g., KND |
| Name | Text | Ya | e.g., Kendaraan |
| Default Useful Life (Months) | Number | Ya | e.g., 48 |

[Screenshot: Form Add New Asset Category dengan 3 input field]

**Catatan field:**
- **Code**: Kode singkat unik untuk identifikasi kategori. Gunakan format konsisten (misal 3-4 karakter uppercase).
- **Name**: Nama deskriptif kategori aset.
- **Default Useful Life (Months)**: Nilai default masa manfaat dalam bulan. Angka ini akan digunakan sebagai default saat membuat Asset Model atau saat menjalankan depresiasi untuk aset yang tidak memiliki masa manfaat eksplisit.

Setelah isi semua field, klik **Save**. Dialog akan tertutup dan kategori baru muncul di tabel.

## 3. Mengubah Kategori Aset

Klik tombol **Edit** (ikon pensil) pada baris kategori yang ingin diubah.

[Screenshot: Baris tabel dengan ikon Edit aktif]

Dialog Edit akan terbuka dengan nilai existing. Ubah field yang diperlukan dan klik **Save**.

Perubahan pada Default Useful Life tidak akan retroactively mempengaruhi aset yang sudah ada. Aset existing tetap menggunakan masa manfaat yang sudah diset pada saat registrasi.

## 4. Menghapus Kategori Aset

Klik tombol **Delete** (ikon trash) pada baris kategori.

[Screenshot: Konfirmasi dialog delete dengan warning message]

Sistem akan menampilkan konfirmasi:

> "This action cannot be undone. This will permanently delete [nama kategori]'s asset category record."

Klik **Delete** untuk konfirmasi atau **Cancel** untuk membatalkan.

**Penting:** Kategori yang sudah digunakan oleh Asset atau Asset Model tidak dapat dihapus. Sistem akan memblokir penghapusan dengan pesan error jika ada referensi.

## 5. Export Data Kategori Aset

Klik tombol **Export** di toolbar untuk mengunduh data dalam format Excel (.xlsx).

[Screenshot: Toolbar dengan tombol Export]

File export berisi kolom:
- ID
- Code
- Name
- Default Useful Life (Months)
- Created At
- Updated At

Export endpoint: `/api/asset-categories/export`

## FAQ & Tips

### Apakah Default Useful Life bisa berbeda per aset?

Ya. Nilai di kategori hanya default. Saat registrasi aset individual, user dapat mengisi masa manfaat yang berbeda dari default kategori.

### Kategori aset apa yang umum digunakan?

Contoh kategori dengan masa manfaat tipikal:
- Kendaraan (KND): 48-60 bulan
- Peralatan Office (PRK): 36 bulan
- Komputer & IT Equipment (IT): 24-36 bulan
- Bangunan (BGN): 240-360 bulan
- Tanah (TNH): tidak ada depresiasi (useful life = 0 atau tidak diisi)

### Bagaimana jika kategori sudah digunakan oleh banyak aset?

Kategori yang sudah memiliki aset terkait tidak bisa dihapus. Solusi:
1. Nonaktifkan kategori dengan mengubah nama atau code (opsional)
2. Buat kategori baru untuk kebutuhan klasifikasi berbeda
3. Kosongkan useful life default jika kategori tidak relevan untuk depresiasi

### Bisakah satu aset memiliki beberapa kategori?

Tidak. Setiap aset hanya memiliki satu kategori. Untuk klasifikasi tambahan, gunakan field lain seperti Department, Branch, atau custom attributes.

### Apa hubungan kategori dengan Asset Model?

Asset Model dapat mengacu pada kategori untuk inheriting default useful life. Saat membuat Asset Model baru, pilih kategori dan sistem akan pre-fill masa manfaat default dari kategori tersebut.

### Apakah kategori mempengaruhi perhitungan depresiasi?

Ya. Asset Depreciation Runs menggunakan useful life untuk menghitung depresiasi bulanan:
- Formula: `(Cost - Salvage Value) / Useful Life Months`
- Aset tanpa useful life eksplisit akan menggunakan default dari kategori

[Screenshot: Dialog form dengan highlight pada field Default Useful Life]

**Q:** Bagaimana cara membuat kategori aset baru dengan benar?

**J:** Klik tombol **Add New Asset Category** di toolbar. Isi tiga field wajib: Code (kode unik, disarankan 3-4 karakter uppercase seperti "KND" untuk Kendaraan), Name (nama deskriptif seperti "Kendaraan Operasional"), dan Default Useful Life dalam bulan (misal 48 untuk 4 tahun). Klik **Save**. Kategori akan langsung muncul di tabel. Pastikan Code tidak duplikat dengan kategori yang sudah ada karena sistem akan menolak kode yang sama.

**Q:** Apa pengaruh Default Useful Life terhadap perhitungan penyusutan aset?

**J:** Default Useful Life (masa manfaat) adalah nilai default dalam satuan bulan yang digunakan saat menghitung depresiasi bulanan aset. Rumusnya: `(Harga Perolehan - Nilai Residu) / Masa Manfaat (bulan)`. Nilai ini hanya default -- saat registrasi aset individual, user dapat mengganti masa manfaat dengan nilai yang berbeda. Jika aset tidak memiliki masa manfaat eksplisit, sistem akan mengambil nilai dari kategori aset yang terkait.

**Q:** Bagaimana cara mencari dan memfilter kategori aset dengan cepat?

**J:** Gunakan kolom pencarian (search field) di bagian atas toolbar tabel. Ketik kode atau nama kategori lalu tekan Enter. Sistem akan memfilter hasil yang mengandung kata kunci tersebut. Untuk pengurutan, klik header kolom (Code, Name, Default Useful Life, Created At, Updated At) untuk mengurutkan ascending atau descending. Kombinasikan search dan sorting untuk menemukan kategori yang diinginkan dengan cepat.

**Q:** Apakah kategori aset bisa dihapus jika sudah digunakan oleh aset atau asset model?

**J:** Tidak bisa. Sistem akan memblokir penghapusan dan menampilkan pesan error jika kategori sudah memiliki referensi dari tabel Assets atau Asset Models. Ini adalah mekanisme keamanan untuk menjaga integritas data. Jika kategori sudah tidak relevan, Anda dapat mengubah nama atau kodenya sebagai penanda, atau membuat kategori baru untuk kebutuhan klasifikasi yang berbeda.

**Q:** Bagaimana cara mengekspor data kategori aset ke Excel?

**J:** Klik tombol **Export** di toolbar atas tabel. Sistem akan mengunduh file Excel (.xlsx) yang berisi seluruh data kategori aset sesuai filter dan pencarian yang sedang aktif. Kolom yang diekspor meliputi: ID, Code, Name, Default Useful Life (Months), Created At, dan Updated At. File dapat langsung dibuka di Microsoft Excel, Google Sheets, atau aplikasi spreadsheet lainnya.

**Q:** Apa hubungan antara kategori aset dengan modul Assets dan Asset Models?

**J:** Kategori aset adalah data master yang menjadi acuan untuk dua modul lain: (1) **Assets** -- setiap aset wajib memiliki satu kategori yang menentukan kelompok aset tersebut, (2) **Asset Models** -- saat membuat model aset baru, pilih kategori dan sistem akan otomatis mengisi default useful life dari kategori tersebut. Kategori juga mempengaruhi laporan dan perhitungan depresiasi karena sistem mengelompokkan aset berdasarkan kategori.

**Q:** Tips penamaan kode dan nama kategori yang baik?

**J:** Untuk kode (Code): gunakan 3-4 karakter uppercase yang singkat dan mudah diingat. Contoh: "KND" untuk Kendaraan, "BGN" untuk Bangunan, "IT" untuk Peralatan IT, "PRK" untuk Peralatan Kantor. Hindari spasi dan karakter khusus. Untuk nama (Name): gunakan kata benda deskriptif yang jelas membedakan satu kategori dengan lainnya. Contoh: "Kendaraan Operasional" lebih baik daripada hanya "Kendaraan". Konsistensi penamaan memudahkan pencarian dan pelaporan.

**Q:** Apa yang terjadi jika Default Useful Life diubah setelah ada aset yang terdaftar dalam kategori tersebut?

**J:** Perubahan Default Useful Life pada kategori TIDAK akan mempengaruhi aset yang sudah terdaftar sebelumnya (tidak retroaktif). Aset yang sudah ada tetap menggunakan masa manfaat yang diset pada saat registrasi. Nilai baru hanya akan digunakan sebagai default untuk aset atau asset model yang dibuat setelah perubahan. Jika ingin mengubah masa manfaat aset existing, Anda harus mengedit data aset tersebut satu per satu melalui modul Assets.

**Q:** Bagaimana jika saya tidak ingin suatu kategori aset disusutkan (misalnya Tanah)?

**J:** Isi Default Useful Life dengan angka 0 (nol) atau kosongkan field tersebut. Aset dengan masa manfaat 0 tidak akan diproses dalam perhitungan depresiasi bulanan. Kategori seperti Tanah umumnya tidak disusutkan karena tidak mengalami penurunan nilai seiring waktu. Pastikan Anda memahami kebijakan akuntansi perusahaan sebelum menonaktifkan depresiasi untuk suatu kategori.

**Q:** Berapa jumlah maksimal kategori aset yang bisa dibuat?

**J:** Tidak ada batasan jumlah kategori aset yang dapat dibuat dalam sistem. Namun disarankan untuk menjaga jumlah kategori tetap terkelola dan tidak terlalu banyak agar klasifikasi tetap jelas. Idealnya satu kategori mewakili satu kelompok aset yang memiliki karakteristik dan masa manfaat serupa. Terlalu banyak kategori dapat mempersulit pelaporan dan analisis aset.