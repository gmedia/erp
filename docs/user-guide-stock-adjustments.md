# User Guide: Stock Adjustments

## Gambaran Umum

Modul Stock Adjustments digunakan untuk mencatat penyesuaian stok barang di gudang. Penyesuaian dapat berupa pengurangan atau penambahan stok dengan berbagai alasan seperti kerusakan, kadaluarsa, penyusutan, koreksi, atau hasil stock opname. Setiap penyesuaian akan menghitung selisih kuantitas dan nilai biaya secara otomatis.

Fitur utama:
- Pembuatan penyesuaian stok dengan detail item lengkap
- Tracking kuantitas sebelum, sesudah, dan selisih penyesuaian
- Berbagai tipe penyesuaian: kerusakan, kadaluarsa, penyusutan, koreksi, hasil stock opname, lainnya
- Integrasi dengan jurnal keuangan saat status diubah menjadi approved
- Export data ke Excel untuk pelaporan

## Menu & Navigasi

| Menu | URL | Permission |
|------|-----|------------|
| Inventory > Stock Adjustments | /stock-adjustments | stock_adjustment |

## 1. Mengakses Daftar Stock Adjustments

Untuk mengakses halaman Stock Adjustments:

1. Login ke aplikasi dengan akun yang memiliki permission `stock_adjustment`
2. Buka menu **Inventory** di sidebar
3. Klik submenu **Stock Adjustments**
4. Halaman daftar stock adjustments akan ditampilkan

[Screenshot: Halaman daftar stock adjustments dengan tabel dan filter]

Pada halaman ini terdapat:
- Tabel daftar penyesuaian dengan kolom: Adjustment Number, Warehouse, Adjustment Date, Adjustment Type, Status
- Tombol **Add** di pojok kanan atas untuk membuat penyesuaian baru
- Tombol **Export** untuk mengunduh data ke Excel
- Field pencarian dengan placeholder "Search stock adjustments..."
- Filter berdasarkan Warehouse, Status, dan Adjustment Type

## 2. Mencari dan Memfilter Data

### Pencarian Cepat

Gunakan field pencarian untuk menemukan penyesuaian berdasarkan nomor atau catatan:

1. Klik field pencarian di bagian atas tabel
2. Ketik kata kunci pencarian
3. Tabel akan otomatis memfilter hasil sesuai kata kunci

[Screenshot: Field pencarian stock adjustments]

### Filter Lanjutan

Gunakan filter untuk menyaring data berdasarkan kriteria spesifik:

1. **Filter Warehouse**: Pilih gudang tertentu untuk melihat penyesuaian hanya di gudang tersebut
2. **Filter Status**: Pilih status (draft, approved, cancelled)
3. **Filter Adjustment Type**: Pilih tipe penyesuaian (damage, expired, shrinkage, correction, stocktake_result, other)

[Screenshot: Panel filter stock adjustments dengan dropdown]

## 3. Membuat Stock Adjustment Baru

Untuk membuat penyesuaian stok baru:

1. Klik tombol **Add** di pojok kanan atas tabel
2. Dialog form "Add New Stock Adjustment" akan muncul

[Screenshot: Dialog form pembuatan stock adjustment baru]

### Mengisi Data Utama

Isi field-field berikut:

1. **Warehouse**: Pilih gudang tempat penyesuaian dilakukan (wajib)
2. **Adjustment Date**: Pilih tanggal penyesuaian (wajib, default hari ini)
3. **Adjustment Type**: Pilih tipe penyesuaian:
   - **Damage**: Kerusakan barang
   - **Expired**: Barang kadaluarsa
   - **Shrinkage**: Penyusutan/kehilangan
   - **Correction**: Koreksi data
   - **Stocktake Result**: Hasil stock opname
   - **Other**: Lainnya
4. **Notes**: Tambahkan catatan jika diperlukan (opsional)

[Screenshot: Bagian data utama form stock adjustment]

### Menambahkan Item Penyesuaian

Setelah mengisi data utama, tambahkan item-item yang akan disesuaikan:

1. Klik tombol **Add Item** di bagian Items
2. Pilih **Product** dari dropdown
3. Pilih **Unit** satuan produk
4. Sistem akan otomatis mengisi **Qty Before** (kuantitas saat ini di gudang)
5. Isi **Qty Adjusted** dengan jumlah penyesuaian (positif untuk penambahan, negatif untuk pengurangan)
6. **Qty After** akan dihitung otomatis (Qty Before + Qty Adjusted)
7. Isi **Unit Cost** jika diperlukan penyesuaian nilai
8. **Total Cost** akan dihitung otomatis (Qty Adjusted x Unit Cost)
9. Isi **Reason** alasan penyesuaian untuk item ini (opsional)

[Screenshot: Form penambahan item stock adjustment]

Ulangi langkah di atas untuk menambahkan item lainnya.

### Menyimpan Stock Adjustment

1. Pastikan semua data sudah lengkap
2. Klik tombol **Save**
3. Stock adjustment akan dibuat dengan status **draft** dan nomor penyesuaian akan digenerate otomatis dengan prefix "SA"

[Screenshot: Stock adjustment berhasil disimpan]

## 4. Melihat Detail Stock Adjustment

Untuk melihat detail penyesuaian:

1. Cari penyesuaian yang ingin dilihat
2. Klik ikon **View** (mata) pada kolom Actions
3. Dialog detail akan muncul menampilkan informasi lengkap

[Screenshot: Dialog detail stock adjustment]

Informasi yang ditampilkan:
- Adjustment Number: Nomor unik penyesuaian
- Status: Status penyesuaian (draft/approved/cancelled)
- Adjustment Type: Tipe penyesuaian
- Warehouse: Gudang tempat penyesuaian
- Adjustment Date: Tanggal penyesuaian
- Stocktake: Nomor stock opname terkait (jika ada)
- Notes: Catatan penyesuaian

Tabel items menampilkan:
- Product: Nama produk
- Unit: Satuan
- Qty Before: Kuantitas sebelum penyesuaian
- Qty Adjusted: Jumlah penyesuaian
- Qty After: Kuantitas setelah penyesuaian
- Unit Cost: Biaya per unit
- Total Cost: Total biaya penyesuaian
- Reason: Alasan per item

## 5. Mengedit Stock Adjustment

Penyesuaian dengan status **draft** dapat diedit:

1. Cari penyesuaian yang ingin diedit
2. Klik ikon **Edit** (pensil) pada kolom Actions
3. Dialog form edit akan muncul dengan data yang sudah terisi
4. Ubah data sesuai kebutuhan
5. Klik **Save** untuk menyimpan perubahan

[Screenshot: Dialog edit stock adjustment]

Catatan: Penyesuaian dengan status **approved** atau **cancelled** tidak dapat diedit.

## 6. Mengubah Status Stock Adjustment

### Meng-approve Stock Adjustment

Untuk meng-approve penyesuaian stok:

1. Buka penyesuaian yang ingin di-approve dalam mode edit
2. Ubah status menjadi **approved**
3. Klik **Save**
4. Sistem akan otomatis memposting jurnal keuangan terkait penyesuaian

[Screenshot: Perubahan status ke approved]

Setelah di-approve:
- Stok di gudang akan diupdate sesuai penyesuaian
- Jurnal keuangan akan terbuat secara otomatis
- Penyesuaian tidak dapat diedit lagi

### Membatalkan Stock Adjustment

Untuk membatalkan penyesuaian:

1. Cari penyesuaian yang ingin dibatalkan
2. Klik ikon **Delete** (tempat sampah) pada kolom Actions
3. Konfirmasi pembatalan
4. Status penyesuaian akan berubah menjadi **cancelled**

[Screenshot: Konfirmasi pembatalan stock adjustment]

Catatan: Pembatalan adalah soft-cancel. Data tetap tersimpan untuk audit trail.

## 7. Mengurutkan Data

Klik header kolom untuk mengurutkan data:

- **Adjustment Number**: Urutkan berdasarkan nomor penyesuaian
- **Warehouse**: Urutkan berdasarkan nama gudang
- **Adjustment Date**: Urutkan berdasarkan tanggal penyesuaian
- **Adjustment Type**: Urutkan berdasarkan tipe penyesuaian
- **Status**: Urutkan berdasarkan status

Klik sekali untuk urutan ascending (A-Z, lama-baru), klik lagi untuk descending (Z-A, baru-lama).

[Screenshot: Header kolom dengan indikator sorting]

## 8. Export ke Excel

Untuk mengunduh data stock adjustment ke Excel:

1. Setel filter sesuai data yang ingin di-export
2. Klik tombol **Export** di toolbar atas
3. File Excel akan otomatis terunduh

[Screenshot: Tombol Export dan proses download]

File Excel berisi kolom yang sama dengan tabel di aplikasi sesuai filter yang aktif.

## FAQ & Tips

**Q:** Apa perbedaan antara Adjustment Type?

**J:** Setiap tipe memiliki kegunaan berbeda:
- **Damage**: Untuk barang rusak yang tidak bisa dijual/digunakan
- **Expired**: Untuk barang yang sudah melewati tanggal kadaluarsa
- **Shrinkage**: Untuk kehilangan atau penyusutan tanpa sebab jelas
- **Correction**: Untuk koreksi kesalahan input atau data
- **Stocktake Result**: Untuk selisih hasil stock opname
- **Other**: Untuk alasan lain yang tidak termasuk kategori di atas

**Q:** Apakah Qty Adjusted bisa negatif?

**J:** Ya, Qty Adjusted bisa bernilai negatif untuk pengurangan stok. Misalnya, jika Qty Before = 100 dan Anda ingin mengurangi 5 unit, isi Qty Adjusted = -5, maka Qty After = 95.

**Q:** Kapan jurnal keuangan dibuat?

**J:** Jurnal keuangan dibuat secara otomatis saat status penyesuaian diubah dari draft menjadi approved. Jurnal akan mencatat perubahan nilai persediaan dan biaya terkait.

**Q:** Apa yang terjadi jika jurnal gagal diposting?

**J:** Jika terjadi kegagalan posting jurnal (misalnya karena mapping COA belum lengkap), penyesuaian tetap akan berstatus approved. Anda dapat memposting jurnal manual setelah mapping COA dilengkapi.

**Q:** Bisakah penyesuaian yang sudah di-approve diedit?

**J:** Tidak. Penyesuaian dengan status approved tidak dapat diedit. Jika perlu koreksi, buat penyesuaian baru dengan tipe Correction.

**Q:** Bagaimana cara membatalkan penyesuaian yang sudah di-approve?

**J:** Klik ikon Delete pada penyesuaian tersebut. Status akan berubah menjadi cancelled (soft-cancel). Data tetap tersimpan untuk audit trail. Stok yang sudah diubah tidak otomatis dikembalikan; Anda perlu membuat penyesuaian baru jika ingin membalikkan.

**Q:** Apakah setiap item harus memiliki Unit Cost?

**J:** Tidak wajib, namun disarankan diisi untuk tracking nilai persediaan yang akurat. Jika dikosongkan, sistem tidak akan menghitung total cost untuk item tersebut.

**Q:** Bagaimana hubungan dengan Stock Opname?

**J:** Jika penyesuaian dibuat dari hasil stock opname, pilih Adjustment Type "Stocktake Result" dan field Stocktake akan menampilkan referensi ke nomor stock opname terkait. Ini memudahkan tracking dan audit.

**Q:** Apakah penyesuaian stok mempengaruhi average cost produk?

**J:** Ya, jika Unit Cost diisi. Penyesuaian dengan nilai cost akan mempengaruhi perhitungan average cost produk di gudang tersebut.

**Q:** Siapa yang bisa meng-approve penyesuaian stok?

**J:** User dengan permission `stock_adjustment` dapat mengubah status penyesuaian. Pastikan hanya user yang berwenang yang memiliki akses untuk approve agar kontrol internal terjaga.
