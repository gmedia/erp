# User Guide: Bank Reconciliations

## Gambaran Umum

Modul Bank Reconciliations digunakan untuk mencocokkan saldo rekening bank pada laporan bank (bank statement) dengan saldo buku kas/bank di sistem ERP. Proses rekonsiliasi penting untuk memastikan akurasi data keuangan, mendeteksi transaksi yang belum tercatat, dan memenuhi kebutuhan audit trail.

Setiap rekonsiliasi bank memiliki komponen utama:
- **Bank Account**: Rekening bank yang direkonsiliasi
- **Fiscal Year**: Tahun buku aktif (otomatis terpilih berdasarkan filter `status=open`)
- **Statement Date**: Tanggal akhir laporan bank
- **Statement Ending Balance**: Saldo akhir sesuai laporan bank
- **Internal Book Balance**: Saldo buku di sistem ERP
- **Difference**: Selisih antara saldo bank dan saldo buku (harus nol untuk rekonsilasi lengkap)

Status rekonsiliasi:
- **In Progress**: Proses pencocokan masih berlangsung
- **Reconciled**: Rekonsilasi selesai dan saldo seimbang

## Menu & Navigasi

| Menu | Path | Deskripsi |
|------|------|-----------|
| Bank Reconciliations | `/bank-reconciliations` | Daftar semua rekonsiliasi bank |
| Add New | Tombol "Add" di toolbar | Buat rekonsiliasi baru |
| View | Tombol "View" di kolom Actions | Lihat detail rekonsiliasi |
| Edit | Tombol "Edit" di kolom Actions | Ubah data rekonsiliasi |
| Delete | Tombol "Delete" di kolom Actions | Hapus rekonsiliasi (hanya status In Progress) |
| Export | Tombol "Export" di toolbar | Ekspor daftar ke Excel |

## 1. Membuat Rekonsiliasi Bank Baru

### Langkah-langkah:

1. Buka halaman Bank Reconciliations di `/bank-reconciliations`
2. Klik tombol **Add** di pojok kanan atas toolbar
3. Isi form rekonsiliasi:
   - **Bank Account**: Pilih rekening bank dari dropdown
   - **Fiscal Year**: Otomatis terpilih berdasarkan tahun buku dengan status `open`. Jika perlu, Anda dapat mengubah pilihan.
   - **Statement Date**: Masukkan tanggal akhir laporan bank
   - **Statement Ending Balance**: Masukkan saldo akhir sesuai bank statement
4. Klik **Save** untuk menyimpan

[Screenshot: Form pembuatan rekonsiliasi bank baru dengan field Bank Account, Fiscal Year, Statement Date, dan Statement Ending Balance]

Sistem akan otomatis menghitung:
- **Internal Book Balance**: Saldo buku berdasarkan transaksi kas/bank yang tercatat
- **Difference**: Selisih antara Statement Ending Balance dan Internal Book Balance

## 2. Import Bank Statement

### Langkah-langkah:

1. Buka detail rekonsiliasi yang berstatus **In Progress**
2. Klik tombol **Import Statement** di toolbar
3. Pilih file CSV atau Excel dari komputer Anda
4. Pastikan format file sesuai dengan template yang disediakan:
   - Kolom wajib: Date, Description, Amount
   - Format tanggal: YYYY-MM-DD atau DD/MM/YYYY
   - Amount: Angka positif untuk penerimaan, negatif untuk pengeluaran
5. Klik **Import** untuk memproses

[Screenshot: Dialog import bank statement dengan preview data]

Setelah import berhasil, sistem akan menampilkan daftar transaksi dari bank statement yang siap dicocokkan.

## 3. Pencocokan Transaksi (Matching)

### Auto-Matching

Sistem otomatis mencocokkan transaksi berdasarkan kriteria:
- **Amount**: Nilai transaksi sama persis
- **Date**: Tanggal transaksi dalam rentang toleransi (±3 hari)

Transaksi yang berhasil dicocokkan secara otomatis akan ditandai dengan status **Matched**.

### Manual Matching

Untuk transaksi yang tidak terdeteksi otomatis:

1. Pilih transaksi dari bank statement (panel kiri)
2. Pilih transaksi internal yang sesuai (panel kanan)
3. Klik tombol **Match** untuk menghubungkan keduanya
4. Jika tidak ada transaksi yang cocok, Anda dapat membuat jurnal penyesuaian langsung

[Screenshot: Tampilan pencocokan manual dengan dua panel: bank statement items dan internal transactions]

### Unmatched Items

Transaksi yang belum dicocokkan akan muncul di bagian **Unmatched Items**:

- **Unmatched Bank Items**: Transaksi di bank statement yang tidak ada di buku
- **Unmatched Book Items**: Transaksi di buku yang tidak ada di bank statement

Anda perlu menyelesaikan semua unmatched items sebelum menyelesaikan rekonsiliasi.

## 4. Menyelesaikan Rekonsiliasi

### Prasyarat:

- Semua transaksi sudah dicocokkan, atau
- Selisih (Difference) sudah bernilai nol

### Langkah-langkah:

1. Pastikan semua transaksi sudah direkonsiliasi
2. Verifikasi nilai **Difference** = 0
3. Klik tombol **Complete Reconciliation**
4. Sistem akan mengubah status menjadi **Reconciled**
5. Rekonsiliasi tidak dapat diedit setelah berstatus Reconciled

[Screenshot: Halaman detail rekonsiliasi dengan summary matched/unmatched items dan tombol Complete Reconciliation]

## 5. Laporan Rekonsiliasi

### Reconciliation Report

Setelah rekonsiliasi selesai, Anda dapat melihat laporan yang berisi:

- **Matched Items**: Daftar transaksi yang berhasil dicocokkan
- **Unmatched Items**: Daftar transaksi yang belum tercatat di kedua sisi
- **Summary**: Perbandingan saldo bank vs saldo buku
- **Adjustment Entries**: Jurnal penyesuaian yang dibuat selama proses

### Export ke Excel

1. Klik tombol **Export** di toolbar halaman daftar atau detail
2. File Excel akan diunduh otomatis
3. File berisi semua data rekonsiliasi sesuai filter yang aktif

Endpoint export: `/api/bank-reconciliations/export`

## 6. Mengelola Rekonsiliasi Existing

### Melihat Detail

1. Klik tombol **View** (ikon mata) pada baris rekonsiliasi
2. Dialog detail akan menampilkan semua informasi rekonsiliasi
3. Untuk rekonsiliasi berstatus In Progress, Anda dapat melanjutkan proses pencocokan

### Mengedit Rekonsiliasi

1. Klik tombol **Edit** (ikon pensil) pada baris rekonsiliasi
2. Hanya rekonsiliasi berstatus **In Progress** yang dapat diedit
3. Ubah data yang diperlukan dan klik **Save**

### Menghapus Rekonsiliasi

1. Klik tombol **Delete** (ikon tempat sampah) pada baris rekonsiliasi
2. Konfirmasi penghapusan pada dialog yang muncul
3. Hanya rekonsiliasi berstatus **In Progress** yang dapat dihapus
4. Penghapusan tidak dapat dibatalkan

[Screenshot: Dialog konfirmasi penghapusan rekonsiliasi]

## FAQ & Tips

### Apa yang harus dilakukan jika Difference tidak nol?

Periksa hal berikut:

1. **Transaksi bank yang belum tercatat**: Buat jurnal penyesuaian untuk mencatat transaksi yang ada di bank statement tapi tidak ada di buku
2. **Transaksi buku yang belum clearing**: Tandai sebagai outstanding jika memang belum muncul di bank statement
3. **Kesalahan input**: Periksa kembali nilai Statement Ending Balance dan pastikan sesuai dengan bank statement

### Bagaimana cara menangani outstanding checks?

Outstanding checks (cek yang sudah dikeluarkan tapi belum dicairkan) tidak perlu dicocokkan. Biarkan sebagai unmatched book items. Item ini akan muncul di rekonsiliasi periode berikutnya saat cek sudah dicairkan.

### Apakah rekonsiliasi bisa dibatalkan setelah selesai?

Tidak. Setelah rekonsiliasi berstatus **Reconciled**, data tidak dapat diubah. Jika terjadi kesalahan, Anda perlu membuat rekonsiliasi baru dengan adjustment yang sesuai.

### Format file import apa yang didukung?

Sistem mendukung format:
- CSV (Comma Separated Values)
- Excel (.xlsx, .xls)

Pastikan file memiliki header yang sesuai dengan template.

### Mengapa Fiscal Year otomatis terpilih?

Sistem otomatis memilih tahun buku dengan status `open` yang paling relevan. Fitur ini memastikan rekonsiliasi tercatat di periode yang benar. Anda tetap dapat mengubah pilihan jika diperlukan.

### Apa yang dimaksud dengan Auto-Matching?

Auto-Matching adalah fitur yang secara otomatis mencocokkan transaksi bank statement dengan transaksi internal berdasarkan kesamaan nilai (amount) dan kedekatan tanggal (dalam rentang ±3 hari). Fitur ini mempercepat proses rekonsiliasi untuk transaksi yang sudah sesuai.

### Bagaimana cara menambah rekening bank baru?

Rekening bank dikelola di modul Accounts. Pastikan akun bank sudah dikonfigurasi dengan benar sebelum membuat rekonsiliasi. Hubungi administrator jika perlu menambah rekening bank baru.

### Tips untuk rekonsiliasi efisien

1. **Lakukan rekonsiliasi secara rutin** minimal setiap akhir bulan
2. **Import bank statement** daripada input manual untuk mengurangi kesalahan
3. **Periksa unmatched items** dengan teliti sebelum menyelesaikan rekonsiliasi
4. **Dokumentasikan penyesuaian** yang dibuat untuk referensi audit
5. **Simpan bank statement** asli sebagai bukti pendukung

---

## FAQ

**Q: Apa itu rekonsiliasi bank dan mengapa penting?**

Rekonsiliasi bank adalah proses mencocokkan saldo akhir pada laporan bank (bank statement) dengan saldo buku kas/bank di sistem ERP. Proses ini penting untuk memastikan akurasi data keuangan, mendeteksi transaksi yang belum tercatat atau salah catat, mengidentifikasi outstanding checks, dan memenuhi kebutuhan audit trail. Idealnya dilakukan rutin setiap akhir bulan.

**Q: Bagaimana cara membuat rekonsiliasi bank baru?**

Buka halaman Bank Reconciliations di `/bank-reconciliations`, klik tombol **Add** di toolbar, lalu isi form: pilih Bank Account, Fiscal Year (terisi otomatis), masukkan Statement Date dan Statement Ending Balance sesuai laporan bank. Klik **Save**. Sistem akan otomatis menghitung Internal Book Balance dan Difference.

**Q: Bagaimana cara mengimpor bank statement?**

Buka detail rekonsiliasi berstatus **In Progress**, klik tombol **Import Statement**, lalu pilih file CSV atau Excel. Pastikan file memiliki kolom wajib Date, Description, dan Amount dengan format tanggal YYYY-MM-DD atau DD/MM/YYYY, serta nilai Amount positif untuk penerimaan dan negatif untuk pengeluaran. Klik **Import** untuk memproses. Setelah berhasil, transaksi siap dicocokkan.

**Q: Bagaimana cara kerja pencocokan (matching) transaksi?**

Ada dua mode. Auto-Matching mencocokkan transaksi secara otomatis berdasarkan nilai (amount) yang sama persis dan tanggal dalam rentang toleransi ±3 hari; transaksi yang cocok ditandai status **Matched**. Manual Matching dilakukan dengan memilih transaksi bank statement di panel kiri dan transaksi internal yang sesuai di panel kanan, lalu klik **Match**. Jika tidak ada transaksi yang cocok, Anda dapat membuat jurnal penyesuaian langsung.

**Q: Apa perbedaan status In Progress dan Reconciled?**

Status **In Progress** berarti proses pencocokan masih berlangsung dan data masih dapat diedit atau dihapus. Status **Reconciled** berarti rekonsiliasi sudah selesai dan saldo seimbang; data tidak dapat diubah lagi setelah berstatus Reconciled.

**Q: Apa arti nilai Difference pada rekonsiliasi?**

Difference adalah selisih antara Statement Ending Balance (saldo laporan bank) dan Internal Book Balance (saldo buku di sistem). Nilai Difference harus nol agar rekonsiliasi dianggap lengkap dan dapat diselesaikan. Jika Difference tidak nol, periksa transaksi bank yang belum tercatat, transaksi buku yang belum clearing, atau kesalahan input saldo.

**Q: Mengapa hanya rekonsiliasi In Progress yang bisa dihapus?**

Pembatasan ini menjaga integritas audit trail. Rekonsiliasi berstatus **Reconciled** sudah final dan menjadi catatan resmi keuangan, sehingga tidak boleh dihapus atau diubah. Hanya rekonsiliasi yang masih dalam proses (In Progress) yang dapat dihapus karena belum dianggap sebagai catatan final. Penghapusan tidak dapat dibatalkan.

**Q: Bagaimana cara mengekspor data rekonsiliasi?**

Klik tombol **Export** di toolbar halaman daftar atau detail. File Excel akan diunduh otomatis dan berisi semua data rekonsiliasi sesuai filter yang sedang aktif. Endpoint export yang digunakan adalah `/api/bank-reconciliations/export`. Pastikan filter sudah sesuai sebelum klik export.

**Q: Mengapa Fiscal Year terpilih otomatis saat membuat rekonsiliasi?**

Sistem otomatis memilih tahun buku dengan status `open` yang paling relevan agar rekonsiliasi tercatat di periode yang benar dan mengurangi kesalahan pemilihan periode. Anda tetap dapat mengubah pilihan Fiscal Year secara manual jika diperlukan sebelum menyimpan.

**Q: Bagaimana menangani outstanding checks dan transaksi yang tidak cocok?**

Outstanding checks (cek yang sudah dikeluarkan tapi belum dicairkan) tidak perlu dicocokkan; biarkan sebagai unmatched book items dan akan muncul kembali di periode berikutnya saat cek dicairkan. Untuk transaksi bank yang belum tercatat di buku, buat jurnal penyesuaian. Selesaikan semua unmatched items dengan teliti sebelum menyelesaikan rekonsiliasi.

**Q: Apa yang harus dilakukan jika terjadi kesalahan setelah rekonsiliasi selesai?**

Rekonsiliasi berstatus **Reconciled** tidak dapat diedit atau dibatalkan. Jika ditemukan kesalahan, buat rekonsiliasi baru dengan adjustment yang sesuai untuk mengoreksi data. Karena itu, periksa kembali semua matched dan unmatched items serta pastikan nilai Difference benar-benar nol sebelum menekan tombol **Complete Reconciliation**.
