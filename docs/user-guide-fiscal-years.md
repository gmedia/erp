# User Guide: Fiscal Years

## Gambaran Umum

Fiscal Years adalah modul yang digunakan untuk mendefinisikan periode akuntansi dalam sistem. Tahun fiskal merupakan fondasi untuk semua modul finansial seperti jurnal entries, laporan keuangan, period closings, dan transaksi AP/AR. Setiap tahun fiskal memiliki Name (contoh: "FY 2026"), Start Date, End Date, dan Status (open/closed).

Hanya satu tahun fiskal yang dapat berstatus `open` dalam satu waktu. Ketika sebuah tahun fiskal ditutup, tidak ada journal entry baru yang dapat dibuat untuk periode tersebut. Status lifecycle mengikuti pola: Open → Closed.

Untuk mengakses modul ini, pengguna memerlukan permission `fiscal_year`. Sistem menggunakan GetPreferredFiscalYearAction untuk otomatis memilih tahun fiskal yang paling relevan pada form dan report: prioritas pertama adalah FY dengan posted journal entry terbaru, fallback ke FY pertama dengan status open.

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Fiscal Years | /fiscal-years | Mengelola periode akuntansi (tahun fiskal) termasuk create, edit, close, dan export data |

## 1. Mengakses Daftar Fiscal Years

### Langkah-langkah:

1. Login ke aplikasi dengan akun yang memiliki permission `fiscal_year`.
2. Klik menu **Fiscal Years** di sidebar atau akses langsung melalui URL `/fiscal-years`.
3. Halaman akan menampilkan tabel daftar tahun fiskal dengan kolom: Name, Start Date, End Date, Status, Created At.

[Screenshot: Halaman daftar Fiscal Years dengan tabel data]

### Fitur pada Tabel:

| Fitur | Deskripsi |
|-------|-----------|
| Search | Filter berdasarkan nama tahun fiskal |
| Sort | Klik header kolom Name, Start Date, End Date, Status, atau Created At untuk sorting |
| Pagination | Navigasi halaman untuk data yang banyak |
| Export | Download data ke file Excel via tombol Export |

## 2. Membuat Tahun Fiskal Baru

### Langkah-langkah:

1. Klik tombol **Add New** di bagian atas tabel.
2. Dialog form akan muncul dengan field yang harus diisi.
3. Isi field yang diperlukan:

| Field | Deskripsi | Contoh |
|-------|-----------|--------|
| Name | Nama tahun fiskal | "FY 2026" |
| Start Date | Tanggal mulai periode | "2026-01-01" |
| End Date | Tanggal akhir periode | "2026-12-31" |

4. Klik tombol **Save** untuk menyimpan.
5. Tahun fiskal baru akan muncul di tabel dengan status `open`.

[Screenshot: Dialog form Add New Fiscal Year]

### Validasi:

- Name tidak boleh kosong dan harus unique.
- Start Date harus lebih awal dari End Date.
- Tidak boleh overlap dengan tahun fiskal yang sudah ada.
- Jika sudah ada tahun fiskal dengan status `open`, tahun fiskal baru akan dibuat dengan status `open` dan tahun fiskal sebelumnya otomatis berubah menjadi `closed`.

## 3. Melihat Detail Tahun Fiskal

### Langkah-langkah:

1. Temukan tahun fiskal yang ingin dilihat di tabel.
2. Klik icon **View** (ikon mata) pada baris tersebut.
3. Dialog detail akan muncul menampilkan informasi lengkap.

[Screenshot: Dialog detail Fiscal Year]

### Informasi yang Ditampilkan:

- Name
- Start Date
- End Date
- Status
- Created At
- Updated At

## 4. Mengedit Tahun Fiskal

### Langkah-langkah:

1. Temukan tahun fiskal yang ingin diedit di tabel.
2. Klik icon **Edit** (ikon pensil) pada baris tersebut.
3. Dialog form akan muncul dengan data yang sudah ada.
4. Ubah field yang diperlukan.
5. Klik tombol **Save** untuk menyimpan perubahan.

[Screenshot: Dialog form Edit Fiscal Year]

### Catatan:

- Start Date dan End Date hanya bisa diubah jika status masih `open`.
- Perubahan tanggal tidak boleh menyebabkan overlap dengan tahun fiskal lain.

## 5. Menutup Tahun Fiskal

### Langkah-langkah:

1. Pastikan semua journal entry untuk tahun fiskal tersebut sudah posted.
2. Jalankan Period Closing untuk periode-periode dalam tahun fiskal.
3. Buka tahun fiskal yang ingin ditutup.
4. Edit status dari `open` menjadi `closed`.
5. Simpan perubahan.

[Screenshot: Form edit status Fiscal Year dari open ke closed]

### Dampak Penutupan:

- Tidak ada journal entry baru yang bisa dibuat untuk tahun fiskal yang sudah closed.
- Tidak ada modifikasi pada transaksi yang sudah ada.
- Laporan keuangan masih bisa diakses untuk tahun fiskal yang closed.

### Warning:

Penutupan tahun fiskal adalah operasi irreversible. Pastikan semua data sudah benar sebelum menutup tahun fiskal.

## 6. Menghapus Tahun Fiskal

### Langkah-langkah:

1. Temukan tahun fiskal yang ingin dihapus di tabel.
2. Klik icon **Delete** (ikon trash) pada baris tersebut.
3. Konfirmasi penghapusan pada dialog yang muncul.
4. Tahun fiskal akan dihapus dari sistem.

[Screenshot: Dialog konfirmasi delete Fiscal Year]

### Catatan:

- Tahun fiskal yang sudah memiliki journal entry tidak bisa dihapus.
- Tahun fiskal dengan status `closed` tidak bisa dihapus.
- Hanya tahun fiskal kosong dengan status `open` yang bisa dihapus.

## 7. Export Data Fiscal Years

### Langkah-langkah:

1. Buka halaman Fiscal Years.
2. Klik tombol **Export** di bagian atas tabel.
3. File Excel akan otomatis di-download.

[Screenshot: Tombol Export dan hasil file Excel]

### Kolom dalam File Export:

| Kolom | Deskripsi |
|-------|-----------|
| ID | ID tahun fiskal |
| Name | Nama tahun fiskal |
| Start Date | Tanggal mulai periode |
| End Date | Tanggal akhir periode |
| Status | Status tahun fiskal (open/closed) |
| Created At | Tanggal pembuatan |
| Updated At | Tanggal update terakhir |

## 8. Auto-Select Tahun Fiskal Preferred

Sistem menggunakan GetPreferredFiscalYearAction untuk otomatis memilih tahun fiskal yang paling relevan saat user membuka form atau report yang berkaitan dengan periode akuntansi.

### Prioritas Pemilihan:

1. Tahun fiskal dengan posted journal entry terbaru.
2. Fallback: Tahun fiskal pertama dengan status `open`.
3. Fallback: Tahun fiskal pertama dalam koleksi.

[Screenshot: Form transaksi dengan tahun fiskal auto-selected]

### Modul yang Menggunakan Auto-Select:

| Modul | Filter Endpoint |
|-------|-----------------|
| AP Payments | /api/fiscal-years (tanpa filter) |
| AR Receipts | /api/fiscal-years (tanpa filter) |
| Period Closings | /api/fiscal-years?status=open |
| Bank Reconciliations | /api/fiscal-years?status=open |
| Financial Reports | InteractsWithFinancialReportRequest trait |

### Tips:

Jika form tidak menampilkan tahun fiskal secara otomatis, pastikan endpoint `/api/fiscal-years` mengembalikan `meta.preferred_fiscal_year_id` dan form menggunakan `preferredMetaKey`.

## 9. Workflow Penggunaan Fiscal Years

```
Planning Tahun Fiskal
    |
    v
Create New Fiscal Year
    |
    v
Gunakan dalam Transaksi
(Journal Entries, AP/AR, etc.)
    |
    v
Period Closing per Bulan
    |
    v
Review & Verify Data
    |
    v
Close Fiscal Year
    |
    v
Archive & Reporting
```

## 10. Skenario Penggunaan Umum

### Skenario 1: Setup Tahun Fiskal Awal

1. Buat tahun fiskal pertama dengan nama "FY 2025".
2. Set Start Date = 2025-01-01, End Date = 2025-12-31.
3. Status akan otomatis `open` karena ini tahun fiskal pertama.
4. Gunakan tahun fiskal ini untuk semua transaksi tahun 2025.

### Skenario 2: Transisi ke Tahun Fiskal Baru

1. Pastikan tahun fiskal lama sudah di-close.
2. Buat tahun fiskal baru untuk tahun berikutnya.
3. Status tahun fiskal baru akan otomatis `open`.
4. Semua transaksi baru akan menggunakan tahun fiskal baru.

### Skenario 3: Review Sebelum Closing

1. Buka laporan Trial Balance untuk tahun fiskal.
2. Buka laporan Income Statement untuk memastikan profit/loss sudah benar.
3. Jalankan Period Closing untuk semua periode dalam tahun fiskal.
4. Setelah semua periode closed, close tahun fiskal.

### Skenario 4: Audit Tahun Fiskal Closed

1. Buka General Ledger Report dengan filter tahun fiskal closed.
2. Export data untuk dokumentasi audit.
3. Bandingkan dengan data fisik atau backup.
4. Simpan file export untuk arsip.

## FAQ & Tips

### Apakah bisa memiliki lebih dari satu tahun fiskal dengan status open?

Tidak. Sistem hanya mengizinkan satu tahun fiskal dengan status `open` dalam satu waktu. Ketika membuat tahun fiskal baru, tahun fiskal sebelumnya dengan status `open` akan otomatis berubah menjadi `closed`.

### Bagaimana jika ada transaksi yang belum selesai saat ingin close fiscal year?

Pastikan semua journal entry sudah posted sebelum menutup tahun fiskal. Journal entry dengan status draft tidak akan mempengaruhi laporan keuangan, namun sebaiknya review dan resolve sebelum closing.

### Bisakah tahun fiskal yang sudah closed dibuka kembali?

Tidak ada fitur reopen tahun fiskal yang sudah closed. Ini untuk menjaga integritas data historis. Jika ada kesalahan, buat adjusting entry di tahun fiskal aktif.

### Mengapa tahun fiskal tidak bisa dihapus?

Tahun fiskal tidak bisa dihapus jika sudah memiliki journal entry, sudah closed, atau memiliki dependensi dengan modul lain seperti Period Closing atau COA Version. Hapus semua dependensi terlebih dahulu jika memungkinkan.

### Bagaimana sistem memilih tahun fiskal secara otomatis di form?

Sistem menggunakan GetPreferredFiscalYearAction yang memprioritaskan tahun fiskal dengan posted journal entry terbaru. Jika tidak ada, fallback ke tahun fiskal pertama dengan status `open`. Ini memastikan form selalu memilih periode yang paling aktif.

### Apakah tanggal tahun fiskal harus mengikuti kalender standar?

Tidak. Anda bisa membuat tahun fiskal dengan periode kustom, misalnya dari April ke March untuk bisnis dengan fiscal year non-calendar. Pastikan periode tidak overlap dengan tahun fiskal lain.

### Bagaimana cara melihat journal entry dalam tahun fiskal tertentu?

Gunakan filter `fiscal_year_id` di modul Journal Entries atau buka General Ledger Report dengan filter tahun fiskal yang diinginkan.

### Tips Manajemen Fiscal Years

1. Plan ahead: Buat tahun fiskal baru beberapa bulan sebelum periode berakhir.
2. Review sebelum close: Jalankan Trial Balance dan Income Statement untuk memastikan data benar.
3. Period closing bertahap: Close periode per bulan, bukan langsung close tahun fiskal.
4. Backup data: Export data tahun fiskal sebelum closing untuk arsip.
5. Koordinasi dengan tim accounting: Pastikan semua tim sudah aware saat closing periode.
6. Verifikasi auto-select: Test form transaksi untuk memastikan tahun fiskal preferred muncul otomatis.

**Q: Apa perbedaan antara Fiscal Year dan Period Month dalam sistem?**

Fiscal Year adalah periode akuntansi tahunan yang mencakup rentang tanggal dari Start Date hingga End Date. Period Month adalah pembagian lebih granular dalam periode closing bulanan di dalam fiscal year. Satu fiscal year terdiri dari 12 period month. Period closing dilakukan per bulan untuk kontrol yang lebih detail, sedangkan fiscal year closing dilakukan setelah semua period month dalam tahun tersebut sudah closed.

**Q: Kapan waktu yang tepat untuk menutup fiscal year?**

Tutup fiscal year setelah semua kondisi terpenuhi: semua journal entry sudah posted, semua period month sudah di-close melalui modul Period Closings, laporan keuangan sudah direview dan disetujui, dan tidak ada koreksi yang perlu dilakukan. Idealnya closing dilakukan dalam waktu 1-2 bulan setelah tahun fiskal berakhir untuk memberi waktu review.

**Q: Bagaimana hubungan fiscal year dengan COA Versions?**

Setiap COA Version terhubung ke satu fiscal year melalui field fiscal_year_id. Ini memungkinkan sistem memiliki struktur akun berbeda untuk tahun fiskal berbeda. Saat membuat tahun fiskal baru, Anda bisa membuat COA Version baru dengan struktur yang disesuaikan jika ada perubahan kebijakan akuntansi. COA Version dengan status active untuk fiscal year tertentu akan digunakan dalam laporan keuangan tahun tersebut.

**Q: Apa dampak menutup fiscal year terhadap journal entries yang sudah ada?**

Journal entries yang sudah posted dalam fiscal year yang di-close tetap tersimpan dan tidak bisa diubah atau dihapus. Tidak ada journal entry baru yang bisa dibuat dengan tanggal dalam rentang fiscal year yang sudah closed. Jika ditemukan kesalahan setelah closing, solusinya adalah membuat adjusting entry di fiscal year aktif, bukan membuka kembali fiscal year yang sudah closed.

**Q: Bagaimana cara membuat fiscal year dengan periode non-kalender standar?**

Saat membuat fiscal year baru, isi Start Date dan End Date sesuai kebutuhan bisnis. Contoh: untuk fiscal year April-March, gunakan Start Date 2025-04-01 dan End Date 2026-03-31. Sistem tidak membatasi harus mengikuti kalender Januari-Desember. Yang penting adalah periode tidak overlap dengan fiscal year lain dan total durasi mencakup periode bisnis yang diinginkan.

**Q: Bisakah saya membuat fiscal year baru sebelum fiscal year lama di-close?**

Ya, Anda bisa membuat fiscal year baru kapan saja. Namun, karena sistem hanya mengizinkan satu fiscal year dengan status open, fiscal year sebelumnya akan otomatis berubah menjadi closed saat fiscal year baru dibuat. Pastikan fiscal year lama sudah siap di-close sebelum membuat yang baru untuk menghindari penutupan yang tidak disengaja.

**Q: Bagaimana cara mencari dan memfilter fiscal year di daftar?**

Gunakan kolom search di bagian atas tabel untuk mencari berdasarkan nama fiscal year. Klik header kolom untuk sorting berdasarkan Name, Start Date, End Date, Status, atau Created At. Untuk filter lebih spesifik, gunakan fitur export ke Excel lalu filter di spreadsheet. Filter juga bisa dilakukan via API dengan parameter query string untuk integrasi.

**Q: Apa yang terjadi pada transaksi AP/AR jika fiscal year ditutup?**

Transaksi AP Payments, AR Receipts, Supplier Bills, dan Customer Invoices yang sudah ada tetap tersimpan. Namun, transaksi baru dengan tanggal dalam fiscal year yang closed tidak bisa dibuat. Form transaksi akan otomatis memilih fiscal year aktif melalui fitur auto-select. Pastikan semua transaksi yang perlu dicatat dalam fiscal year sudah dibuat sebelum closing.

**Q: Bagaimana memastikan auto-select fiscal year bekerja dengan benar di form transaksi?**

Verifikasi dengan membuka form transaksi seperti AP Payment atau AR Receipt. Tahun fiskal harus otomatis terpilih tanpa perlu manual select. Jika tidak, periksa apakah endpoint /api/fiscal-years mengembalikan meta.preferred_fiscal_year_id dan form menggunakan prop preferredMetaKey. Jalankan GetPreferredFiscalYearAction untuk memastikan logic memilih tahun fiskal yang tepat berdasarkan journal entry terbaru atau fallback ke status open.

**Q: Bisakah ada gap antara End Date fiscal year lama dan Start Date fiscal year baru?**

Secara teknis sistem mengizinkan gap antara periode fiscal year. Namun, ini tidak disarankan karena transaksi dengan tanggal dalam gap tersebut tidak akan tercatat dalam fiscal year manapun. Best practice adalah membuat fiscal year berurutan tanpa gap untuk memastikan semua transaksi tercakup dalam periode akuntansi yang jelas.