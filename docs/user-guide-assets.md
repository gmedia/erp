# User Guide: Assets

## Gambaran Umum

Modul Assets adalah modul CRUD Complex terlengkap dalam sistem ERP ini, mengelola siklus hidup aset perusahaan dari akuisisi hingga disposisi. Setiap aset memiliki identifikasi unik (code auto-generated), informasi fisik (lokasi, kondisi), data keuangan (cost, depreciation), dan relasi ke kategori, cabang, departemen, karyawan, serta supplier.

Fitur utama:
- Manajemen data aset lengkap dengan tracking status dan kondisi
- Histori pergerakan aset (acquired, transferred, disposed)
- Jadwal dan histori perawatan (preventive, corrective, calibration, other)
- Stocktake aset dengan variance reporting
- Depresiasi bulanan straight-line dengan posting ke journal entry
- Dashboard visualisasi distribusi aset dan alert warranty/maintenance
- Pipeline state dan approval history tracking per aset
- Import dan export data dalam format Excel

[Screenshot: Asset List Page - menampilkan tabel aset dengan kolom Code, Name, Category, Branch, Status, Cost, Purchase Date, Location, Department, Employee, Supplier]

## Menu & Navigasi

| Menu | Route | Fungsi |
|------|-------|--------|
| Assets | /assets | Daftar aset dengan filter, search, CRUD, import/export |
| Asset Profile | /assets/{ulid} | Detail aset dengan 7 tab: Summary, Movements, Maintenance, Stocktake, Depreciation, Pipeline, Approvals |
| Asset Dashboard | /asset-dashboard | Visualisasi distribusi aset, KPI, alerts warranty/maintenance |
| Asset Register Report | /reports/assets/register | Laporan aset read-only dengan filter dan export |
| Asset Categories | /asset-categories | Kategori aset dengan default useful life |
| Asset Models | /asset-models | Model aset dengan manufacturer dan specs |
| Asset Locations | /asset-locations | Lokasi fisik aset dengan hierarchy |
| Asset Movements | /asset-movements | Histori pergerakan semua aset |
| Asset Maintenances | /asset-maintenances | Jadwal dan histori perawatan |
| Asset Stocktakes | /asset-stocktakes | Stocktake header dengan perform page |
| Asset Depreciation Runs | /asset-depreciation-runs | Run depresiasi dengan calculate/post/void |
| Asset Stocktake Variance Report | /asset-stocktake-variances | Laporan variance stocktake |

## 1. Manajemen Data Aset

### 1.1 Daftar Aset

Halaman `/assets` menampilkan tabel aset dengan fitur:

**Kolom tabel:**
- Code: Kode unik aset (auto-generated)
- Name: Nama aset
- Category: Kategori aset
- Branch: Cabang lokasi
- Status: draft, active, maintenance, disposed, lost
- Cost: Nilai perolehan
- Purchase Date: Tanggal pembelian
- Location: Lokasi fisik
- Department: Departemen pemilik
- Employee: Karyawan yang menggunakan
- Supplier: Supplier asal

**Filter tersedia:**
- Search: filter berdasarkan code, name, serial number, barcode
- Category: dropdown async select
- Branch: dropdown async select
- Location: dropdown async select
- Status: dropdown (draft/active/maintenance/disposed/lost)
- Condition: dropdown (good/needs_repair/damaged)
- Department: dropdown async select
- Employee: dropdown async select

[Screenshot: Asset Filters Panel - menampilkan semua field filter]

**Sorting:** Semua kolom sortable kecuali Description. Klik header kolom untuk sort ascending/descending.

### 1.2 Menambah Aset Baru

Klik tombol "Add" di toolbar untuk membuka form tambah aset.

**Field form:**
- Asset Code: auto-generated, tidak editable
- Name: wajib diisi
- Asset Category: pilih dari dropdown (wajib)
- Asset Model: pilih dari dropdown (opsional)
- Branch: pilih cabang lokasi (wajib)
- Location: pilih lokasi fisik (wajib)
- Department: pilih departemen (opsional)
- Employee: pilih karyawan assignment (opsional)
- Supplier: pilih supplier (opsional)
- Serial Number: nomor serial fisik (opsional)
- Barcode: barcode fisik (opsional)
- Purchase Date: tanggal perolehan (wajib)
- Purchase Cost: nilai perolehan (wajib)
- Currency: mata uang (default IDR)
- Warranty End Date: tanggal akhir warranty (opsional)
- Status: default "active"
- Condition: default "good"
- Depreciation Method: pilih metode (straight-line)
- Useful Life (Months): masa manfaat (default dari category)
- Salvage Value: nilai residu (opsional)
- Depreciation Expense Account: akun beban depresiasi (wajib jika ada depreciation)
- Accumulated Depreciation Account: akun akumulasi depresiasi (wajib jika ada depreciation)
- Notes: catatan tambahan (opsional)

[Screenshot: Add Asset Form - menampilkan semua field form]

**Catatan:** Saat aset dibuat, sistem otomatis membuat movement record dengan type "acquired" yang mencatat lokasi awal aset.

### 1.3 Melihat Detail Aset

Klik icon "View" atau klik baris tabel untuk navigasi ke halaman profile `/assets/{ulid}`.

Profile page memiliki 7 tab:

**Tab Summary:**
Menampilkan informasi lengkap aset: identifikasi, lokasi, status, kondisi, data keuangan, data depresiasi, relasi.

[Screenshot: Asset Profile Summary Tab]

**Tab Movements:**
Histori pergerakan aset dari acquired hingga disposed. Setiap movement record mencatat:
- Movement Type: acquired, transferred, disposed
- From Branch/Location/Department/Employee
- To Branch/Location/Department/Employee
- Movement Date
- Notes

[Screenshot: Asset Profile Movements Tab]

**Tab Maintenance:**
Histori perawatan aset dengan detail:
- Maintenance Type: preventive, corrective, calibration, other
- Status: scheduled, in_progress, completed, cancelled
- Scheduled At, Performed At
- Cost
- Supplier/Vendor
- Notes

[Screenshot: Asset Profile Maintenance Tab]

**Tab Stocktake:**
Histori pengecekan fisik aset dalam stocktake:
- Stocktake Reference
- Stocktake Date
- Expected Location vs Found Location
- Result: matched, surplus, deficit
- Condition Found
- Notes
- Checked By, Checked At

[Screenshot: Asset Profile Stocktake Tab]

**Tab Depreciation:**
Histori depresiasi per aset dari semua depreciation run:
- Run Period
- Depreciation Amount
- Accumulated Before/After
- Book Value After
- Run Status

[Screenshot: Asset Profile Depreciation Tab]

**Tab Pipeline:**
Timeline state aset dalam pipeline (jika aset terdaftar dalam pipeline):
- Current State
- State History
- Available Transitions

[Screenshot: Asset Profile Pipeline Tab]

**Tab Approvals:**
Histori approval request terkait aset:
- Approval Request Date
- Requester
- Step Approver
- Action (approved/rejected)
- Comment
- Timestamp

[Screenshot: Asset Profile Approvals Tab]

### 1.4 Mengedit Aset

Klik icon "Edit" di toolbar atau pada profile page untuk membuka form edit. Field sama seperti form tambah, dengan nilai existing sudah terisi.

**Catatan:** Saat edit lokasi/branch/department/employee, sistem otomatis membuat movement record dengan type "transferred" jika ada perubahan.

### 1.5 Menghapus Aset

Klik icon "Delete" untuk menghapus aset. Sistem menggunakan soft delete, sehingga aset masih bisa di-restore.

**Warning:** Aset dengan status "active" atau yang memiliki maintenance/stocktake history tidak dapat langsung dihapus. Harus di-dispose atau di-maintenance terlebih dahulu.

### 1.6 Import Aset

Klik tombol "Import" di toolbar untuk import batch aset dari Excel.

**Format template import:**

| Kolom | Wajib | Contoh |
|-------|-------|--------|
| asset_code | No | AUTO |
| name | Yes | Laptop Dell XPS |
| asset_category | Yes | IT Equipment |
| asset_model | No | Dell XPS 15 |
| branch | Yes | HQ Jakarta |
| location | Yes | IT Room |
| department | No | IT Department |
| employee | No | John Doe |
| supplier | No | Dell Indonesia |
| serial_number | No | SN123456 |
| barcode | No | BC001 |
| purchase_date | Yes | 2024-01-15 |
| purchase_cost | Yes | 15000000 |
| currency | No | IDR |
| warranty_end_date | No | 2027-01-15 |
| status | No | active |
| condition | No | good |
| notes | No | Unit baru |

[Screenshot: Import Dialog - menampilkan upload file dan preview template]

**Proses import:**
1. Download template Excel
2. Isi data sesuai kolom
3. Upload file via dialog
4. Sistem validate dan import data
5. Notifikasi sukses atau error detail per baris

### 1.7 Export Aset

Klik tombol "Export" di toolbar untuk download data aset dalam format Excel.

**Fitur export:**
- Export semua data atau hasil filter
- Kolom export: Code, Name, Category, Branch, Location, Department, Employee, Supplier, Status, Condition, Purchase Date, Cost, Serial Number, Barcode, Warranty End Date, Depreciation Method, Useful Life, Salvage Value, Accumulated Depreciation, Book Value
- Format: XLSX dengan auto column width

## 2. Asset Categories

### 2.1 Daftar Kategori

Halaman `/asset-categories` menampilkan daftar kategori aset. Setiap kategori memiliki:
- Code: kode kategori (mis. IT-EQP, VEH)
- Name: nama kategori
- Default Useful Life (Months): masa manfaat default untuk aset dalam kategori ini

[Screenshot: Asset Categories List]

### 2.2 Menambah Kategori

Field form:
- Code: kode unik kategori
- Name: nama kategori
- Default Useful Life (Months): default masa manfaat (akan auto-fill saat buat aset baru)

### 2.3 Kategori dengan Model

Kategori bisa memiliki beberapa Asset Model. Misalnya kategori "IT Equipment" bisa memiliki model "Dell XPS 15", "MacBook Pro", dll.

## 3. Asset Models

### 3.1 Daftar Model

Halaman `/asset-models` menampilkan daftar model aset. Setiap model memiliki:
- Model Name: nama model
- Manufacturer: produsen
- Category: kategori aset
- Specs: spesifikasi teknis (JSON format)

[Screenshot: Asset Models List]

### 3.2 Manfaat Model

Model membantu standardisasi aset dengan specs yang sama. Saat pilih model di form aset, sistem tidak auto-fill field lain, tapi model bisa dipakai untuk reporting dan filtering.

## 4. Asset Locations

### 4.1 Daftar Lokasi

Halaman `/asset-locations` menampilkan daftar lokasi fisik aset. Setiap lokasi memiliki:
- Code: kode lokasi
- Name: nama lokasi
- Branch: cabang tempat lokasi berada
- Parent Location: lokasi induk (untuk hierarchy)

[Screenshot: Asset Locations List]

### 4.2 Hierarchy Lokasi

Lokasi bisa memiliki sub-lokasi untuk representasi hierarchy. Misalnya:
- Building A (parent)
  - Floor 1
    - Room 101
    - Room 102
  - Floor 2

[Screenshot: Location Hierarchy Tree]

### 4.3 Filter Aset per Lokasi

Di halaman Assets, filter by Location untuk melihat semua aset di lokasi tertentu termasuk sub-lokasinya.

## 5. Asset Movements

### 5.1 Konsep Movement

Setiap pergerakan aset tercatat dalam movement record. Movement type:
- **Acquired**: aset baru masuk (auto-created saat tambah aset)
- **Transferred**: aset pindah lokasi/cabang/departemen/karyawan (auto-created saat edit aset)
- **Disposed**: aset di-dispose (manual creation)

### 5.2 Daftar Movement

Halaman `/asset-movements` menampilkan histori semua pergerakan aset.

**Kolom tabel:**
- Asset Code dan Name
- Movement Type
- From/To Branch, Location, Department, Employee
- Movement Date
- Notes

[Screenshot: Asset Movements List]

### 5.3 Menambah Movement Manual

Biasanya movement auto-created saat edit aset. Namun bisa juga tambah manual via menu Asset Movements untuk kasus khusus seperti disposal.

## 6. Asset Maintenances

### 6.1 Konsep Maintenance

Maintenance tracking untuk perawatan aset, baik terjadwal maupun tidak.

**Maintenance Type:**
- Preventive: perawatan rutin terjadwal
- Corrective: perbaikan kerusakan
- Calibration: kalibrasi alat
- Other: lain-lain

**Status:**
- Scheduled: terjadwal, belum dilaksanakan
- In Progress: sedang dikerjakan
- Completed: selesai
- Cancelled: dibatalkan

### 6.2 Daftar Maintenance

Halaman `/asset-maintenances` menampilkan semua maintenance record.

**Kolom tabel:**
- Asset Code dan Name
- Maintenance Type
- Status
- Scheduled At
- Performed At
- Supplier/Vendor
- Cost
- Notes

[Screenshot: Asset Maintenances List]

### 6.3 Menambah Maintenance

Field form:
- Asset: pilih dari dropdown
- Maintenance Type: preventive/corrective/calibration/other
- Status: scheduled/in_progress/completed/cancelled
- Scheduled At: tanggal rencana maintenance
- Performed At: tanggal aktual (jika sudah selesai)
- Supplier: vendor pelaksana (opsional)
- Cost: biaya maintenance
- Notes: catatan

### 6.4 Update Status Maintenance

Setelah maintenance selesai:
1. Edit maintenance record
2. Update Status ke "Completed"
3. Isi Performed At dan Cost aktual

## 7. Asset Stocktakes

### 7.1 Konsep Stocktake

Stocktake adalah proses pengecekan fisik aset untuk memastikan data sistem sesuai dengan kondisi aktual.

**Status Stocktake:**
- Draft: baru dibuat, belum dimulai
- In Progress: pengecekan sedang berlangsung
- Completed: pengecekan selesai
- Cancelled: dibatalkan

### 7.2 Daftar Stocktake

Halaman `/asset-stocktakes` menampilkan daftar stocktake.

**Kolom tabel:**
- Reference: nomor referensi stocktake
- Branch: cabang yang di-stocktake
- Planned Date: tanggal rencana
- Performed Date: tanggal aktual
- Status
- Created By

[Screenshot: Asset Stocktakes List]

### 7.3 Membuat Stocktake Baru

Field form:
- Reference: auto-generated
- Branch: cabang untuk stocktake
- Planned Date: tanggal rencana pengecekan
- Notes: catatan

### 7.4 Melaksanakan Stocktake

1. Buka stocktake dari list
2. Klik tombol "Perform" untuk navigasi ke halaman perform
3. Untuk setiap aset, catat:
   - Found Location: lokasi aktual ditemukan
   - Condition: kondisi aktual
   - Notes: catatan jika ada variance
4. Submit hasil stocktake

[Screenshot: Stocktake Perform Page]

### 7.5 Variance Report

Setelah stocktake completed, variance report tersedia di `/asset-stocktake-variances` menampilkan:
- Aset yang tidak ditemukan di lokasi expected
- Aset yang ditemukan di lokasi berbeda
- Perbedaan kondisi

## 8. Asset Depreciation Runs

### 8.1 Konsep Depresiasi

Sistem menghitung depresiasi bulanan dengan metode straight-line. Setiap run mencatat depresiasi untuk satu periode.

**Formula:**
```
Monthly Depreciation = (Purchase Cost - Salvage Value) / Useful Life (Months)
```

### 8.2 Daftar Depreciation Run

Halaman `/asset-depreciation-runs` menampilkan daftar run depresiasi.

**Kolom tabel:**
- Fiscal Year
- Period Start - Period End
- Status: draft, calculated, posted, void
- Journal Entry (jika sudah posted)
- Created By, Posted By

[Screenshot: Asset Depreciation Runs List]

### 8.3 Membuat Run Baru

1. Klik "Add" untuk membuat run baru
2. Pilih Fiscal Year dan Period
3. Status awal = "draft"

### 8.4 Calculate Depreciation

1. Buka run dari list
2. Klik "Calculate"
3. Sistem akan:
   - Cek apakah sudah ada run untuk periode yang sama
   - Ambil semua aset eligible (status active/maintenance, punya depreciation method, start_date <= period_end, purchase_cost > salvage_value)
   - Hitung depresiasi per aset
   - Status berubah ke "calculated"

**Eligibility aset:**
- Status: active atau maintenance
- Depreciation Method: harus diisi
- Useful Life: > 0
- Purchase Cost > Salvage Value
- Depreciation Start Date <= Period End

### 8.5 Posting ke Journal

Setelah calculated, klik "Post" untuk membuat journal entry:

1. Sistem validasi:
   - Status harus "calculated"
   - Ada depreciation lines
   - Setiap aset punya Depreciation Expense Account dan Accumulated Depreciation Account

2. Journal entry dibuat dengan:
   - Debit: Depreciation Expense Account (per aset)
   - Credit: Accumulated Depreciation Account (per aset)
   - Grouped by expense account + branch

3. Status berubah ke "posted"

### 8.6 Void Run

Run yang sudah posted bisa di-void. Ini akan:
- Reverse journal entry
- Update status ke "void"

## 9. Asset Dashboard

### 9.1 Akses Dashboard

Halaman `/asset-dashboard` menampilkan overview aset perusahaan.

[Screenshot: Asset Dashboard]

### 9.2 KPI Cards

4 KPI utama:
- Total Assets: jumlah aset
- Total Purchase Cost: total nilai perolehan
- Total Book Value: nilai buku saat ini
- Total Accumulated Depreciation: total akumulasi depresiasi

### 9.3 Charts

**Status Distribution:**
Pie chart menampilkan distribusi aset per status (draft, active, maintenance, disposed, lost).

**Category Distribution:**
Pie chart menampilkan distribusi aset per kategori.

**Condition Distribution:**
Pie chart menampilkan distribusi kondisi (good, needs_repair, damaged).

### 9.4 Alerts

**Upcoming Maintenances:**
List maintenance yang terjadwal dalam waktu dekat.

**Warranty Expiry Alerts:**
List aset yang warranty-nya akan segera berakhir (dalam 30 hari).

## 10. Asset Register Report

### 10.1 Akses Report

Halaman `/reports/assets/register` menampilkan laporan aset read-only dengan filter.

[Screenshot: Asset Register Report]

### 10.2 Filter Report

- Category, Branch, Location, Department
- Status, Condition
- Purchase Date range

### 10.3 Export Report

Klik "Export" untuk download laporan dalam format Excel.

## 11. Permissions

Modul Assets menggunakan permission berikut:

| Permission | Akses |
|------------|-------|
| asset | Lihat daftar aset, detail, dashboard, report |
| asset.create | Tambah aset baru, import |
| asset.edit | Edit aset, update maintenance/stocktake |
| asset.delete | Hapus aset |

## FAQ & Tips

### Q: Bagaimana cara memindahkan aset ke lokasi lain?

A: Edit aset dan ubah field Location, Branch, Department, atau Employee. Sistem akan otomatis membuat movement record dengan type "transferred".

### Q: Bagaimana cara mendispose aset?

A: Ada dua cara:
1. Edit aset dan ubah status ke "disposed"
2. Buat movement record manual dengan type "disposed"

### Q: Mengapa aset tidak masuk dalam depreciation run?

A: Periksa:
1. Status aset harus "active" atau "maintenance"
2. Depreciation Method harus diisi
3. Useful Life > 0
4. Purchase Cost > Salvage Value
5. Depreciation Expense Account dan Accumulated Depreciation Account harus diisi

### Q: Bagaimana cara melihat histori lengkap aset?

A: Buka halaman profile aset di `/assets/{ulid}`. Semua histori tersedia dalam tab masing-masing: Movements, Maintenance, Stocktake, Depreciation, Pipeline, Approvals.

### Q: Apakah bisa import aset dalam jumlah besar?

A: Ya, gunakan fitur Import di toolbar halaman Assets. Download template, isi data, upload file. Sistem akan validate dan import semua data yang valid.

### Q: Bagaimana cara melihat variance stocktake?

A: Setelah stocktake completed, buka halaman `/asset-stocktake-variances` untuk melihat laporan variance semua stocktake.

### Q: Bisakah depreciation run di-edit setelah posted?

A: Tidak. Run yang sudah posted tidak bisa di-edit. Jika ada kesalahan, void run tersebut dan buat run baru.

### Q: Bagaimana cara melihat book value aset saat ini?

A: Buka profile aset, tab Summary menampilkan Book Value. Atau lihat di Asset Dashboard untuk overview semua aset.

### Tips: Maintenance Scheduling

Untuk maintenance rutin (preventive), buat maintenance record dengan status "Scheduled" dan tanggal di masa depan. Sistem akan menampilkannya di Asset Dashboard sebagai upcoming maintenance.

### Tips: Serial Number dan Barcode

Isi Serial Number dan Barcode untuk aset yang memiliki label fisik. Ini memudahkan pencarian dan stocktake menggunakan scanner.

### Tips: Warranty Tracking

Isi Warranty End Date untuk tracking warranty. Asset Dashboard akan menampilkan alert untuk aset yang warranty-nya segera berakhir.

**Q: Apa perbedaan antara Asset Categories dan Asset Models?**

A: Asset Categories mengelompokkan aset berdasarkan jenis penggunaan dan masa manfaat, misalnya IT Equipment dengan default useful life 36 bulan. Asset Models lebih spesifik, mendefinisikan model konkret seperti Dell XPS 15 atau MacBook Pro dengan spesifikasi teknis. Saat membuat aset, Category wajib dipilih karena menentukan default useful life dan pengelompokan laporan, sedangkan Model bersifat opsional untuk detail lebih rinci.

**Q: Bagaimana cara assign aset ke karyawan tertentu?**

A: Edit aset dan pilih karyawan di field Employee. Simpan perubahan. Sistem akan otomatis membuat movement record dengan type "transferred" yang mencatat perubahan penugasan. Untuk melihat semua aset yang dipakai karyawan tertentu, gunakan filter Employee di halaman Assets.

**Q: Apa yang dimaksud dengan Asset Profile Page?**

A: Asset Profile Page adalah halaman detail aset yang diakses dari `/assets/{ulid}`. Halaman ini menampilkan 7 tab: Summary (informasi lengkap), Movements (histori perpindahan), Maintenance (histori perawatan), Stocktake (histori pengecekan), Depreciation (histori depresiasi), Pipeline (state workflow), dan Approvals (histori persetujuan). Gunakan halaman ini untuk tracking lengkap siklus hidup aset.

**Q: Bagaimana cara mengubah kondisi aset dari "good" ke "needs_repair"?**

A: Edit aset dan ubah field Condition. Untuk kondisi "needs_repair" atau "damaged", sebaiknya buat juga maintenance record dengan type "corrective" untuk mencatat proses perbaikan. Setelah perbaikan selesai, update kembali condition ke "good" melalui edit aset atau dari maintenance record.

**Q: Apa perbedaan status "active" dan "maintenance"?**

A: Status "active" berarti aset sedang digunakan normal dan eligible untuk depreciation run. Status "maintenance" berarti aset sedang dalam proses perawatan, tidak digunakan sementara, namun tetap eligible untuk depreciation run. Gunakan status maintenance ketika aset dikirim ke bengkel atau dalam proses perbaikan yang memerlukan waktu.

**Q: Bagaimana cara tracking depresiasi aset per bulan?**

A: Depresiasi dihitung melalui Asset Depreciation Runs di menu `/asset-depreciation-runs`. Buat run baru untuk periode tertentu, klik Calculate untuk menghitung depresiasi semua aset eligible, lalu klik Post untuk membuat journal entry. Setelah posted, setiap aset akan memiliki record depresiasi yang bisa dilihat di tab Depreciation pada Asset Profile Page.

**Q: Bagaimana cara filter aset berdasarkan cabang dan lokasi sekaligus?**

A: Gunakan kombinasi filter Branch dan Location di panel filter. Pilih Branch terlebih dahulu, kemudian pilih Location. Filter Location akan menampilkan aset di lokasi tersebut termasuk sub-lokasi. Untuk melihat semua aset di cabang tertentu, cukup gunakan filter Branch tanpa memilih Location spesifik.

**Q: Apa yang harus dilakukan jika aset hilang?**

A: Ubah status aset ke "lost" melalui edit aset. Sistem tidak akan menghapus data aset, hanya menandai statusnya. Untuk audit trail, buat movement record manual dengan type "disposed" dan catat keterangan kehilangan di field Notes. Aset dengan status lost tidak akan masuk dalam depreciation run.

**Q: Bagaimana cara export daftar aset dengan filter tertentu?**

A: Terapkan filter yang diinginkan di panel filter (misalnya Branch, Category, Status), kemudian klik tombol "Export" di toolbar. File Excel yang dihasilkan hanya akan berisi data aset yang sesuai dengan filter aktif. Pastikan filter sudah benar sebelum klik export.

**Q: Apakah bisa assign satu aset ke beberapa lokasi sekaligus?**

A: Tidak. Satu aset hanya bisa memiliki satu lokasi pada satu waktu. Jika aset perlu dipindahkan antar lokasi, edit aset dan ubah field Location. Sistem akan otomatis mencatat movement record dengan lokasi sebelumnya dan lokasi baru.

**Q: Bagaimana cara mengetahui aset yang warranty-nya sudah kedaluwarsa?**

A: Buka Asset Dashboard di `/asset-dashboard`. Section "Warranty Expiry Alerts" menampilkan aset yang warranty-nya akan berakhir dalam 30 hari kedepan. Untuk melihat semua aset tanpa warranty aktif, gunakan filter di halaman Assets dan periksa field Warranty End Date secara manual.
