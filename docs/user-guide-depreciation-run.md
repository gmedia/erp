# User Guide: Depreciation Run

## Gambaran Umum

Depreciation Run adalah fitur non-CRUD untuk menghitung dan memposting depresiasi aset ke general ledger. Fitur ini memastikan nilai aset berkurang secara akurat sepanjang useful life sesuai dengan metode depresiasi yang ditetapkan (default: straight-line method). Depreciation Run merupakan bagian kritikal dari proses month-end closing karena mengupdate nilai buku aset dan menghasilkan journal entries otomatis.

Untuk mengakses Depreciation Run, pengguna memerlukan permission `asset_depreciation_run`. Proses di-backing oleh action classes `CalculateDepreciationAction` dan `PostDepreciationToJournalAction`. Depreciation Run biasanya dijalankan setiap bulan setelah semua transaksi aset (acquisition, disposal, maintenance, movement) sudah direcord.

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Depreciation Runs | /asset-depreciation-runs | Halaman utama untuk menghitung, review, dan memposting depresiasi aset |

## 1. Mengakses Halaman Depreciation Runs

### Langkah-langkah:

1. Login ke aplikasi dengan akun yang memiliki permission `asset_depreciation_run`.
2. Klik menu **Asset Management** di sidebar, kemudian pilih submenu **Depreciation Runs** atau akses langsung melalui URL `/asset-depreciation-runs`.
3. Halaman akan menampilkan daftar depreciation run yang sudah dilakukan.

[Screenshot: Halaman Depreciation Runs dengan tabel history dan tombol Run Calculation]

### Komponen Halaman:

- **Header**: Judul "Depreciation Runs" dengan breadcrumb navigasi
- **Tombol Run Calculation**: Untuk memulai kalkulasi depresiasi baru
- **Tabel History**: Daftar run yang sudah dilakukan dengan kolom:
  - Fiscal Year
  - Period (Period Start to Period End)
  - Lines (jumlah aset yang didepresiasi)
  - Status (calculated, posted, void)
  - Journal (nomor journal entry jika sudah diposting)
  - Created By
  - Actions (View Lines, Post)

## 2. Memahami Status Depreciation Run

Setiap depreciation run memiliki status yang menunjukkan tahapan proses:

| Status | Warna Badge | Deskripsi | Action Available |
|--------|-------------|-----------|------------------|
| calculated | Default (biru) | Kalkulasi sudah selesai, menunggu review dan posting | View Lines, Post |
| posted | Secondary (abu-abu) | Sudah diposting ke journal, nilai buku aset sudah diupdate | View Lines |
| void | Destructive (merah) | Run sudah dibatalkan (tidak digunakan dalam proses normal) | View Lines |

[Screenshot: Tabel dengan berbagai status badge dan action buttons]

### Interpretasi Status:

- **Calculated**: Run baru selesai dihitung. Review detail lines sebelum posting. Pastikan nilai depresiasi sudah sesuai expected.
- **Posted**: Run sudah diposting ke general ledger. Journal entry sudah terbuat dan nilai buku aset sudah terupdate. Status ini final dan tidak bisa di-reverse tanpa proses khusus.
- **Void**: Run dibatalkan karena error atau double calculation. Tidak mempengaruhi nilai aset atau journal.

## 3. Menjalankan Kalkulasi Depresiasi Baru

### Langkah-langkah:

1. Klik tombol **Run Calculation** di bagian atas halaman.
2. Dialog "Calculate Depreciation" akan muncul dengan form input.
3. Isi field yang diperlukan:
   - **Fiscal Year**: Pilih fiscal year dari dropdown (auto-select preferred fiscal year)
   - **Period Start**: Pilih tanggal mulai periode (picker date)
   - **Period End**: Pilih tanggal akhir periode (picker date)
4. Klik tombol **Calculate** untuk memulai proses.

[Screenshot: Dialog Calculate Depreciation dengan form fiscal year dan period dates]

### Validasi Input:

- Fiscal Year harus dipilih sebelum calculate.
- Period Start dan Period End harus diisi.
- Period End harus setelah Period Start.
- Sistem memvalidasi tidak ada run existing untuk period yang sama dengan status draft, calculated, atau posted.

### Proses yang Terjadi di Backend:

1. Sistem mencari aset eligible untuk depresiasi dengan criteria:
   - Status = active atau maintenance
   - Depreciation method sudah diset
   - Depreciation start date <= period end
   - Purchase cost > salvage value
   - Useful life months > 0
   - Book value masih > salvage value (belum fully depreciated)

2. Untuk setiap aset eligible, sistem menghitung:
   - Monthly depreciation amount = (Purchase Cost - Salvage Value) / Useful Life Months
   - Accumulated depreciation before dan after
   - Book value after depreciation

3. Sistem membuat AssetDepreciationRun record dengan status `calculated`.
4. Sistem membuat AssetDepreciationLine records untuk setiap aset yang didepresiasi.

### Catatan Penting:

- Kalkulasi menggunakan straight-line depreciation method (default).
- Jika monthly amount melebihi remaining depreciable value, sistem akan adjust ke nilai maksimum yang tersisa.
- Aset yang sudah fully depreciated (book value <= salvage value) akan di-skip dari kalkulasi.

## 4. Review Detail Depreciation Lines

### Langkah-langkah:

1. Dari tabel history, klik icon **Eye** (View Lines) pada baris run yang ingin di-review.
2. Dialog "Depreciation Run Lines" akan muncul dengan tabel detail.
3. Review setiap line item untuk memastikan kalkulasi sudah benar.

[Screenshot: Dialog Run Lines dengan tabel detail per aset]

### Informasi per Line:

| Kolom | Deskripsi |
|-------|-----------|
| Asset | Nama dan kode aset yang didepresiasi |
| Amount | Nilai depresiasi untuk periode ini |
| Accumulated Before | Akumulasi depresiasi sebelum run ini |
| Accumulated After | Akumulasi depresiasi setelah run ini |
| Book Value After | Nilai buku aset setelah depresiasi |

### Checklist Review:

- Periksa jumlah aset yang didepresiasi (Lines count) sesuai expected.
- Periksa amount per aset reasonable berdasarkan purchase cost dan useful life.
- Periksa accumulated after tidak melebihi purchase cost minus salvage value.
- Periksa book value after tidak negatif atau di bawah salvage value.
- Identifikasi aset yang tidak muncul jika expected butuh investigasi (possible: status inactive, already fully depreciated, atau missing depreciation method).

### Tips:

- Jika ada anomali dalam lines, jangan post. Investigasi cause terlebih dahulu.
- Bandingkan dengan run sebelumnya untuk trending validation.
- Aset baru yang recently acquired harus muncul jika depreciation start date <= period end.

## 5. Memposting Depresiasi ke Journal

### Langkah-langkah:

1. Pastikan run status = `calculated` dan sudah direview.
2. Klik tombol **Post** pada baris run di tabel.
3. Sistem akan memproses posting dan mengupdate status ke `posted`.
4. Journal entry number akan muncul di kolom Journal setelah posting berhasil.

[Screenshot: Tabel dengan tombol Post dan status transition dari calculated ke posted]

### Proses yang Terjadi di Backend:

1. Sistem memvalidasi status run = calculated dan lines tidak kosong.
2. Untuk setiap line, sistem mengumpulkan:
   - Depreciation Expense Account (debit)
   - Accumulated Depreciation Account (credit)
3. Sistem meng-aggregate lines per account dan branch untuk journal entry.
4. Sistem membuat Journal Entry dengan:
   - Entry Date = Period End
   - Reference = DEPR-{YYYYMM} (format period start)
   - Description = "Depreciation Run for {Month Year}"
   - Lines: Debit ke expense account, Credit ke accumulated account

5. Sistem mengupdate nilai aset:
   - Accumulated Depreciation = Accumulated After
   - Book Value = Book Value After

6. Sistem mengupdate run status ke `posted` dengan timestamp dan posted_by user.

### Journal Entry Structure:

| Account | Branch | Debit | Credit | Memo |
|---------|--------|-------|--------|------|
| Depreciation Expense Account (per asset category) | Asset Branch | Amount | - | Depreciation Expense |
| Accumulated Depreciation Account (per asset category) | Asset Branch | - | Amount | Accumulated Depreciation |

### Prasyarat Posting:

- Setiap aset harus memiliki depreciation expense account dan accumulated depreciation account yang sudah diset di Asset Category atau Asset Profile.
- Jika ada aset yang missing account configuration, posting akan gagal dengan error message.
- COA Version untuk fiscal year harus active dan memiliki account mapping yang valid.

### Catatan Penting:

- Posting adalah proses irreversible. Setelah posted, tidak bisa di-unpost tanpa manual journal reversal.
- Journal entry yang dibuat status default = draft (harus di-post journal entry untuk affect GL).
- Nilai buku aset akan terupdate langsung di database setelah posting depreciation run.

## 6. Workflow Depreciation Run

```
Login dengan permission asset_depreciation_run
    |
    v
Buka Depreciation Runs (/asset-depreciation-runs)
    |
    v
Review Existing Runs (optional)
    |
    v
Klik Run Calculation
    |
    v
Select Fiscal Year + Period Dates
    |
    v
Submit Calculate
    |
    v
Sistem Mencari Eligible Assets
    |
    v
Sistem Menghitung Depreciation Amount
    |
    v
Run Status = calculated
    |
    v
Review Depreciation Lines (View Lines)
    |
    v
Validasi Lines Correct?
    |        |
    | YES    | NO
    v        v
Klik Post   Investigasi Issue
    |        |
    v        v
Sistem Aggregate per Account/Branch
    |        (Fix asset config, re-run)
    v
Create Journal Entry
    |
    v
Update Asset Accumulated Depreciation & Book Value
    |
    v
Run Status = posted
    |
    v
Journal Entry Visible di Kolom Journal
    |
    v
Post Journal Entry (separate process via Journal Entries module)
```

## 7. Skenario Penggunaan Umum

### Skenario 1: Monthly Depreciation Run (End of Month)

1. Pastikan semua transaksi aset bulan ini sudah direcord (acquisition, disposal, maintenance, movement).
2. Buka Depreciation Runs halaman.
3. Klik Run Calculation.
4. Pilih Fiscal Year current.
5. Set Period Start = awal bulan (e.g., 2025-01-01).
6. Set Period End = akhir bulan (e.g., 2025-01-31).
7. Submit dan tunggu calculation complete.
8. Review lines untuk validasi.
9. Post ke journal jika valid.
10. Lanjutkan ke Journal Entries module untuk post journal entry yang dibuat.

### Skenario 2: Catch-up Depreciation (Missed Previous Periods)

1. Identifikasi period yang missed (misalnya 3 bulan lalu tidak run).
2. Run Calculation untuk period missed.
3. Period Start dan Period End = period yang missed.
4. Review lines (asset book value akan adjust secara cumulative).
5. Post ke journal.
6. Note: Journal entry date akan = period end (bukan current date), sesuai accounting convention.

### Skenario 3: First Depreciation Run After Asset Acquisition

1. Acquisition asset sudah direcord dengan depreciation start date set.
2. Tunggu sampai period end >= depreciation start date.
3. Run Calculation untuk period yang mencakup depreciation start date.
4. Review lines - asset baru harus muncul dengan depreciation amount.
5. Post jika valid.
6. Asset book value akan berkurang sesuai monthly depreciation.

### Skenario 4: Handling Asset with Missing Depreciation Accounts

1. Run Calculation gagal saat posting dengan error "Asset XXX is missing depreciation accounts."
2. Investigasi asset yang error via Asset Profile.
3. Check Asset Category atau Asset Profile untuk:
   - Depreciation Expense Account ID
   - Accumulated Depreciation Account ID
4. Set missing accounts di Asset Category (apply ke semua assets di category) atau Asset Profile (specific asset).
5. Retry posting depreciation run.
6. Alternatif: Void run yang error dan re-run dengan fixed configuration.

### Skenario 5: Monthly Monitoring via Asset Dashboard

1. Sebelum run depreciation, buka Asset Dashboard.
2. Review Book Value dan Accum. Depreciation summary cards untuk baseline.
3. Run Depreciation untuk period.
4. Post ke journal.
5. Refresh Asset Dashboard.
6. Compare Book Value (berkurang) dan Accum. Depreciation (bertambah) untuk verification.

## 8. Integrasi dengan Modul Lain

### Asset Module:

- Depreciation run mengupdate field `accumulated_depreciation` dan `book_value` di Asset table.
- Aset dengan status `active` atau `maintenance` eligible untuk depreciation.
- Aset dengan status `draft`, `disposed`, atau `lost` tidak didepresiasi.
- Depreciation method, useful life months, depreciation start date, salvage value di-set di Asset Profile.

### Asset Category Module:

- Asset Category menyediakan default depreciation accounts:
  - Depreciation Expense Account (untuk debit side journal)
  - Accumulated Depreciation Account (untuk credit side journal)
- Useful life months default bisa di-set per category.

### Fiscal Year Module:

- Fiscal Year harus dipilih saat calculate.
- Period harus within Fiscal Year range.
- COA Version untuk fiscal year harus active untuk posting.

### Journal Entries Module:

- Journal entry dibuat otomatis saat post depreciation run.
- Journal entry status = draft, harus di-post via Journal Entries module.
- Journal entry reference format: DEPR-{YYYYMM}.
- Journal entry bisa di-view dari link di kolom Journal.

### Chart of Accounts Module:

- Depreciation Expense Account = Expense account type (debit).
- Accumulated Depreciation Account = Contra-asset account type (credit).
- Account harus exist dalam COA Version yang active.

### Period Closing Module:

- Depreciation run biasanya dilakukan sebelum period closing.
- Period closing meng-lock period untuk prevent further transactions.
- Run depreciation sebelum closing period untuk ensure accurate financial statements.

## 9. Perhitungan Depresiasi (Straight-Line Method)

### Formula:

```
Monthly Depreciation Amount = (Purchase Cost - Salvage Value) / Useful Life Months

Accumulated After = Accumulated Before + Monthly Depreciation Amount

Book Value After = Purchase Cost - Accumulated After
```

### Contoh Kalkulasi:

| Asset | Purchase Cost | Salvage Value | Useful Life (months) | Monthly Amount |
|-------|---------------|---------------|----------------------|----------------|
| Laptop A | Rp 12,000,000 | Rp 1,000,000 | 36 | Rp 305,556 |
| Vehicle B | Rp 200,000,000 | Rp 20,000,000 | 60 | Rp 3,000,000 |
| Printer C | Rp 3,000,000 | Rp 0 | 24 | Rp 125,000 |

### Adjustment untuk Fully Depreciated Asset:

Jika `Book Value Before - Salvage Value < Monthly Amount`:

```
Amount = Book Value Before - Salvage Value (last depreciation)
Book Value After = Salvage Value (fully depreciated)
```

### Timeline Depresiasi Laptop A:

| Period | Accumulated Before | Monthly Amount | Accumulated After | Book Value After |
|--------|--------------------|----------------|-------------------|------------------|
| Month 1 | 0 | 305,556 | 305,556 | 11,694,444 |
| Month 2 | 305,556 | 305,556 | 611,112 | 11,388,888 |
| ... | ... | ... | ... | ... |
| Month 36 | 11,000,000 | 305,556 | 11,305,556 | 694,444 |
| Month 37 | 11,305,556 | 305,556 | 11,611,112 | 388,888 |
| Month 38 | 11,611,112 | 305,556 | 11,916,668 | 83,332 |
| Month 39 | 11,916,668 | 83,332 (adjusted) | 12,000,000 | 0 (fully depreciated) |

## FAQ & Tips

### Apakah depreciation run bisa dijalankan lebih dari once untuk period yang sama?

Tidak. Sistem memvalidasi jika sudah ada run untuk period dengan status draft, calculated, atau posted. Jika perlu re-run, void run existing terlebih dahulu (proses manual via database atau void endpoint).

### Mengapa beberapa aset tidak muncul di depreciation lines?

Aset tidak muncul jika:
- Status = draft, disposed, atau lost (tidak eligible)
- Depreciation method = null (tidak diset)
- Depreciation start date > period end (aset belum start depreciation)
- Purchase cost <= salvage value (tidak ada depreciable value)
- Useful life months = 0 atau null
- Book value sudah <= salvage value (fully depreciated)

Fix: Update Asset Profile dengan data depreciation yang valid.

### Bagaimana cara men-set depreciation accounts untuk aset?

Depreciation accounts bisa di-set di dua level:
1. **Asset Category**: Set Depreciation Expense Account dan Accumulated Depreciation Account di Asset Category. Semua aset dalam category akan inherit accounts ini.
2. **Asset Profile**: Override di individual asset jika butuh specific accounts berbeda dari category default.

Pastikan accounts exist dalam COA Version yang active untuk fiscal year.

### Apakah journal entry langsung posted ke GL saat post depreciation run?

Tidak. Journal entry dibuat dengan status = draft. Harus di-post manual via Journal Entries module untuk affect GL balance. Ini untuk allow review journal entry sebelum final posting.

### Bagaimana jika depreciation run gagal karena missing accounts?

Jika posting gagal dengan error "Asset XXX is missing depreciation accounts":
1. Run status tetap = calculated (tidak berubah ke posted).
2. Investigasi asset yang missing accounts.
3. Set accounts di Asset Category atau Asset Profile.
4. Retry posting dengan klik tombol Post lagi.
5. Alternatif: Void run dan re-run dengan fixed configuration.

### Apakah bisa undo depreciation run yang sudah posted?

Tidak ada fitur unpost otomatis. Untuk undo:
1. Manual reversal journal entry via Journal Entries module (create reversing entry).
2. Manual update asset accumulated depreciation dan book value via database atau custom script.
3. Void run (jika ada void endpoint).

Proses reversal harus coordinate dengan finance team dan auditor untuk compliance.

### Kapan timing ideal untuk run depreciation?

Timing ideal:
- **End of month**: Setelah semua transaksi aset bulan ini direcord.
- **Before period closing**: Depreciation harus di-run sebelum period closing meng-lock period.
- **Consistent schedule**: Run setiap bulan pada tanggal yang sama untuk consistency.

Avoid run di tengah bulan karena bisa cause confusion dalam reporting period alignment.

### Bagaimana cara verify depreciation calculation sudah benar?

Check:
1. Monthly amount = (Purchase Cost - Salvage Value) / Useful Life (validate formula).
2. Accumulated After = Accumulated Before + Amount.
3. Book Value After = Purchase Cost - Accumulated After.
4. Book Value After >= Salvage Value (tidak negatif atau below salvage).
5. Compare dengan run sebelumnya untuk trending consistency.
6. Test sample asset dengan manual calculation untuk cross-check.

### Apakah depreciation run affect Asset Dashboard?

Ya. Setelah posting:
- Book Value di Summary Cards berkurang.
- Accum. Depreciation di Summary Cards bertambah.
- Status Distribution tidak berubah (kecuali ada asset status change terpisah).
- Condition Overview tidak berubah (condition field separate dari depreciation).

Refresh Asset Dashboard setelah depreciation run untuk update values.

### Bagaimana cara handling asset yang acquired mid-month?

Aset acquired mid-month:
- Depreciation start date = acquisition date atau custom date.
- First depreciation akan muncul saat period end >= depreciation start date.
- Amount bisa partial jika run period tidak full month from start date (tidak ada prorata logic di current implementation, full monthly amount di-apply).

Future enhancement bisa include prorata calculation untuk first month.

### Tips Optimasi Depreciation Run Process

1. **Schedule consistency**: Run pada tanggal yang sama setiap bulan (e.g., last day of month).
2. **Pre-run checklist**: Pastikan semua asset transactions bulan ini sudah direcord.
3. **Review lines thoroughly**: Investigasi anomaly sebelum post.
4. **Account configuration**: Ensure depreciation accounts diset di Asset Category untuk avoid per-asset manual setup.
5. **Integration check**: After post, check Journal Entries module untuk verify journal created correctly.
6. **Dashboard monitoring**: Use Asset Dashboard untuk quick verification of book value changes.
7. **Documentation**: Keep log of run dates, periods, dan any issues untuk audit trail.
8. **Backup before post**: Consider database backup sebelum post jika concern about irreversible changes.
9. **Coordinate with period closing**: Run depreciation before period closing untuk ensure accurate month-end financials.
10. **Training users**: Ensure team understands the irreversible nature of posting dan proper review workflow.

**Q: Bagaimana cara menjalankan depresiasi untuk aset baru yang baru saja diakuisisi?**

Pastikan acquisition sudah direcord dengan depreciation start date yang valid. Tunggu sampai period end >= depreciation start date. Saat run calculation, aset baru akan otomatis muncul di lines jika memenuhi semua kriteria eligibility. Review lines untuk memastikan aset baru muncul dengan amount yang benar, lalu post jika valid.

**Q: Apakah bisa menjalankan depreciation run untuk beberapa bulan sekaligus?**

Tidak disarankan. Setiap depreciation run hanya untuk satu period (satu bulan). Jika ada period yang terlewat, jalankan run untuk setiap period secara terpisah dan berurutan. Ini memastikan journal entry memiliki date yang akurat per period dan accumulated depreciation tercatat dengan benar per bulan.

**Q: Bagaimana cara membatalkan depreciation run yang sudah dijalankan tapi belum di-post?**

Run dengan status calculated bisa di-void melalui endpoint atau proses manual. Void akan menghapus AssetDepreciationLine records tanpa mempengaruhi nilai aset. Setelah void, bisa run calculation ulang untuk period yang sama jika ada koreksi konfigurasi aset yang diperlukan.

**Q: Apa perbedaan antara straight-line method dan double-declining balance method?**

Straight-line method membagi depreciable value secara merata sepanjang useful life. Monthly amount = (Cost - Salvage) / Useful Life Months. Double-declining balance menghitung depresiasi berdasarkan book value dengan rate dua kali lipat straight-line rate, menghasilkan depresiasi lebih besar di awal useful life. Current implementation menggunakan straight-line method sebagai default.

**Q: Bagaimana cara melihat riwayat depreciation run yang sudah dilakukan?**

Halaman Depreciation Runs menampilkan tabel history semua run. Filter berdasarkan Fiscal Year untuk melihat run di tahun tertentu. Setiap baris menunjukkan period, jumlah lines, status, dan journal entry number jika sudah posted. Klik View Lines untuk detail per aset dari setiap run.

**Q: Mengapa journal entry yang dibuat dari depreciation run masih berstatus draft?**

Journal entry dibuat dengan status draft untuk memberikan kesempatan review sebelum mempengaruhi general ledger. Ini memungkinkan finance team memverifikasi akun-akun yang terlibat dan memastikan mapping sudah benar. Post journal entry secara terpisah melalui Journal Entries module untuk finalisasi.

**Q: Bagaimana hubungan depreciation run dengan period closing?**

Depreciation run sebaiknya dilakukan sebelum period closing. Period closing meng-lock period untuk mencegah transaksi tambahan. Jika period sudah closed, depreciation run tidak bisa dijalankan untuk period tersebut. Pastikan depreciation sudah di-run dan journal entry sudah di-post sebelum closing period untuk memastikan financial statements akurat.

**Q: Apa yang harus dilakukan jika hasil depreciation calculation tidak sesuai expected?**

Jangan post. Review lines untuk identify anomali. Check: aset yang tidak muncul (mungkin tidak eligible), amount yang berbeda dari expected (check formula dan useful life), atau accumulated depreciation yang tidak sesuai. Investigasi root cause di Asset Profile atau Asset Category. Void run jika perlu, fix konfigurasi, lalu re-run calculation.

**Q: Bagaimana cara memastikan depreciation accounts sudah diset dengan benar untuk semua aset?**

Check Asset Category terlebih dahulu karena menyediakan default depreciation accounts. Pastikan setiap category memiliki Depreciation Expense Account dan Accumulated Depreciation Account. Jika ada aset individual yang memerlukan accounts berbeda, set di Asset Profile. Gunakan Asset Dashboard atau report untuk identify aset dengan missing accounts sebelum run depreciation.

**Q: Bisakah depreciation run dijalankan untuk fiscal year yang sudah closed?**

Tidak. Fiscal year yang sudah closed tidak bisa menerima transaksi baru. Pastikan semua depreciation run untuk fiscal year dilakukan sebelum year-end closing. Jika ada period yang terlewat di fiscal year yang sudah closed, perlu koordinasi dengan finance team dan auditor untuk reversal dan adjustment procedures.