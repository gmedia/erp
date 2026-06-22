# User Guide: Supplier

## Gambaran Umum

Modul Supplier digunakan untuk mengelola data pemasok (supplier) yang bermitra dengan perusahaan. Setiap supplier memiliki informasi kontak, lokasi cabang, kategori, dan status aktif/nonaktif. Data supplier ini menjadi referensi utama untuk modul Accounts Payable (AP), khususnya pada proses pembuatan supplier bills dan pembayaran ke supplier.

Fitur utama:
- CRUD supplier (create, read, update, delete)
- Filter dan pencarian supplier
- Export data supplier ke Excel
- Import data supplier dari file Excel
- Relasi dengan Branch (cabang) dan Supplier Category (kategori supplier)

[Screenshot: Halaman utama modul Supplier dengan tabel data dan toolbar]

## Menu & Navigasi

| Menu | Path | Deskripsi |
|------|------|-----------|
| Suppliers | `/suppliers` | Halaman utama daftar supplier |
| Supplier Bills | `/supplier-bills` | Tagihan dari supplier (membutuhkan data supplier) |
| AP Payments | `/ap-payments` | Pembayaran ke supplier |
| Supplier Categories | `/supplier-categories` | Kategori supplier (referensi) |
| Branches | `/branches` | Cabang perusahaan (referensi) |

Akses modul Supplier membutuhkan permission `supplier`.

## 1. Melihat Daftar Supplier

Halaman `/suppliers` menampilkan tabel semua supplier dengan kolom:

| Kolom | Keterangan | Sortable |
|-------|------------|----------|
| Name | Nama supplier | Ya |
| Email | Alamat email supplier | Ya |
| Phone | Nomor telepon | Ya |
| Branch | Cabang yang terkait | Ya |
| Category | Kategori supplier | Ya |
| Status | Status aktif/nonaktif | Ya |
| Created At | Tanggal dibuat | Ya |
| Updated At | Tanggal diupdate | Ya |
| Actions | Tombol View, Edit, Delete | - |

[Screenshot: Tabel supplier dengan kolom-kolom yang tersedia]

### 1.1 Mencari Supplier

Gunakan field search di toolbar untuk mencari supplier berdasarkan:
- Nama supplier
- Email
- Phone
- Nama cabang
- Nama kategori

Ketik kata kunci dan hasil akan otomatis terfilter.

[Screenshot: Field pencarian supplier dengan hasil filter]

### 1.2 Filter berdasarkan Status

Filter dropdown Status memungkinkan filter supplier berdasarkan:
- **Active**: Supplier yang masih aktif bermitra
- **Inactive**: Supplier yang sudah tidak aktif

[Screenshot: Dropdown filter status supplier]

### 1.3 Sorting Kolom

Klik header kolom sortable (Name, Email, Phone, Branch, Category, Status, Created At, Updated At) untuk mengurutkan data. Klik sekali untuk ascending, klik lagi untuk descending.

[Screenshot: Header kolom dengan indikator sort direction]

## 2. Menambah Supplier Baru

Klik tombol **Add** di toolbar untuk membuka form tambah supplier.

[Screenshot: Tombol Add di toolbar halaman Supplier]

Form tambah supplier memiliki field:

| Field | Type | Required | Keterangan |
|-------|------|----------|------------|
| Name | Text | Ya | Nama supplier |
| Email | Email | Tidak | Alamat email valid |
| Phone | Text | Tidak | Nomor telepon |
| Branch | Select | Ya | Pilih cabang dari dropdown |
| Category | Select | Ya | Pilih kategori supplier dari dropdown |
| Status | Select | Ya | Active atau Inactive |

[Screenshot: Form tambah supplier dengan semua field]

### 2.1 Memilih Branch dan Category

Field Branch dan Category menggunakan dropdown yang data diambil dari modul referensi:
- Branch: dari `/api/branches`
- Category: dari `/api/supplier-categories`

Pilih nilai dari dropdown. Field ini wajib diisi.

[Screenshot: Dropdown Branch dan Category dalam form]

### 2.2 Menyimpan Supplier

Klik **Save** untuk menyimpan. Jika validasi gagal, error message akan tampil di field yang bermasalah.

[Screenshot: Form dengan error validation message]

Setelah sukses, dialog tertutup dan supplier baru muncul di tabel.

## 3. Melihat Detail Supplier

Klik icon **View** (eye) di kolom Actions untuk membuka dialog detail supplier.

[Screenshot: Icon View di kolom Actions]

Dialog detail menampilkan semua informasi supplier:
- Name
- Email
- Phone
- Branch (nama cabang)
- Category (nama kategori)
- Status
- Created At
- Updated At

[Screenshot: Dialog detail supplier]

## 4. Mengubah Data Supplier

Klik icon **Edit** (pencil) di kolom Actions untuk membuka form edit.

[Screenshot: Icon Edit di kolom Actions]

Form edit memiliki field sama dengan form tambah. Data supplier yang existing sudah terisi. Ubah field yang diperlukan dan klik **Save**.

[Screenshot: Form edit supplier dengan data existing]

Setelah sukses, dialog tertutup dan data di tabel terupdate.

## 5. Menghapus Supplier

Klik icon **Delete** (trash) di kolom Actions untuk menghapus supplier.

[Screenshot: Icon Delete di kolom Actions]

Konfirmasi dialog akan muncul dengan pesan: "Are you sure you want to delete supplier [nama]?"

[Screenshot: Konfirmasi dialog delete supplier]

Klik **Delete** untuk konfirmasi. Supplier terhapus dari tabel.

Perhatian: Supplier yang sudah memiliki transaksi (supplier bills, AP payments) tidak dapat dihapus. Sistem akan menampilkan error jika ada constraint relasi.

[Screenshot: Error constraint saat delete supplier dengan transaksi]

## 6. Export Data Supplier ke Excel

Klik tombol **Export** di toolbar untuk mengunduh semua data supplier dalam format Excel.

[Screenshot: Tombol Export di toolbar]

File Excel berisi kolom:
- ID
- Name
- Email
- Phone
- Branch
- Category
- Status
- Created At
- Updated At

[Screenshot: Preview file Excel export supplier]

Export menggunakan endpoint `/api/suppliers/export` dan mengikuti filter yang aktif (search, status). Jika filter aktif, hanya data yang terfilter akan diexport.

## 7. Import Data Supplier dari Excel

Klik tombol **Import** di toolbar untuk membuka dialog import.

[Screenshot: Tombol Import di toolbar]

Dialog import memungkinkan upload file Excel dengan format kolom yang sesuai. Download template untuk memastikan format benar.

[Screenshot: Dialog import supplier dengan file upload]

### 7.1 Format File Import

File Excel harus memiliki header kolom:
- Name (required)
- Email
- Phone
- Branch (nama cabang, harus match dengan data existing)
- Category (nama kategori, harus match dengan data existing)
- Status (Active/Inactive)

[Screenshot: Template file Excel import supplier]

### 7.2 Proses Import

1. Download template jika diperlukan
2. Isi data supplier di file Excel
3. Upload file via dialog import
4. Sistem memvalidasi data
5. Jika sukses, notifikasi berhasil muncul dengan jumlah record imported
6. Jika ada error, notifikasi error muncul dengan detail baris yang gagal

[Screenshot: Notifikasi berhasil import supplier]

[Screenshot: Notifikasi error import dengan detail]

## FAQ & Tips

### FAQ

**Q: Apakah field Email dan Phone wajib diisi?**
A: Tidak wajib. Namun untuk keperluan komunikasi dan invoice, sangat disarankan diisi.

**Q: Supplier yang sudah ada di supplier bills bisa dihapus?**
A: Tidak. Sistem mencegah delete supplier yang memiliki relasi dengan transaksi AP.

**Q: Bagaimana cara mengubah status supplier dari Active ke Inactive?**
A: Edit supplier via form edit, ubah field Status ke Inactive, dan save.

**Q: Apakah import bisa menambah dan mengupdate data sekaligus?**
A: Import saat ini hanya untuk menambah data baru. Untuk update, gunakan form edit manual.

**Q: Branch dan Category dropdown tidak menampilkan data?**
A: Pastikan modul Branches dan Supplier Categories sudah memiliki data. Hubungi admin jika kosong.

**Q: Export tidak menghasilkan file?**
A: Periksa koneksi internet dan popup blocker. Download file via browser popup.

**Q: Bagaimana cara mencari supplier berdasarkan branch?**

Gunakan filter Branch di toolbar. Pilih branch spesifik dari dropdown untuk menampilkan supplier hanya di branch tersebut. Bisa dikombinasikan dengan search field untuk hasil lebih spesifik.

**Q: Apakah supplier yang Inactive masih muncul di transaksi?**

Ya. Supplier yang sudah Inactive tetap muncul di data historis dan transaksi existing (supplier bills, purchase orders). Status Inactive hanya mencegah supplier dipilih di form transaksi baru.

**Q: Bagaimana format Excel yang benar untuk import?**

Download template dari dialog import. Template berisi header: Name, Email, Phone, Address, Branch, Category, Status. Isi data supplier sesuai kolom. Pastikan Branch dan Category match persis dengan data di sistem.

**Q: Bisakah menghapus banyak supplier sekaligus (batch delete)?**

Tidak. Delete hanya tersedia satu per satu via ikon Trash di Action column. Ini untuk mencegah penghapusan massal yang tidak disengaja.

**Q: Apa yang terjadi jika import gagal di sebagian baris?**

Sistem menampilkan notifikasi error dengan detail baris yang gagal. Baris yang sukses tetap di-import. Perbaiki baris yang gagal dan upload ulang file yang sudah dikoreksi.

### Tips

1. **Konsistensi Kategori**: Assign kategori supplier yang relevan (misalnya "Material", "Service", "Equipment") untuk memudahkan analisis dan reporting.

2. **Branch Mapping**: Pastikan supplier di-map ke cabang yang benar. Supplier bills akan mengikuti branch supplier untuk routing approval dan laporan per cabang.

3. **Status Management**: Nonaktifkan supplier yang tidak lagi bermitra daripada menghapus. Ini menjaga history transaksi tetap terhubung.

4. **Import Batch**: Gunakan import untuk menambah banyak supplier sekaligus. Download template dulu untuk format yang benar.

5. **Filter before Export**: Terapkan filter (status, search) sebelum export untuk mendapatkan subset data yang spesifik.

6. **Search Multi-Keyword**: Search field mendukung pencarian partial match. Ketik bagian nama atau email untuk hasil yang relevan.

7. **Validation Check**: Sebelum import, pastikan nama Branch dan Category di Excel match exactly dengan data di sistem. Case-sensitive.

8. **Backup before Batch Delete**: Jika ada rencana delete banyak supplier, export dulu sebagai backup. Supplier dengan transaksi akan gagal delete anyway.

9. **Phone Format**: Gunakan format nomor telepon yang konsisten (misalnya +62 atau 021) untuk memudahkan komunikasi.

10. **Email Validation**: Field email validasi format. Pastikan email valid untuk menghindari error saat save.