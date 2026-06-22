# User Guide: Period Closings

## Gambaran Umum

Modul Period Closings (Penutupan Periode) mengelola proses penutupan akhir bulan dan akhir tahun dalam siklus akuntansi. Proses ini penting untuk memastikan akurasi laporan keuangan karena semua laporan keuangan bergantung pada periode yang ditutup dengan benar.

Setiap record penutupan periode mencatat:
- **Fiscal Year**: Tahun buku terkait (otomatis memilih yang paling relevan)
- **Period Month**: Bulan periode yang ditutup
- **Period Year**: Tahun periode
- **Closing Type**: Tipe penutupan (monthly/yearly)
- **Status**: Status penutupan (Open, In Progress, Closed)
- **Net Income**: Laba bersih yang dihitung otomatis
- **Closed At**: Timestamp saat penutupan selesai

Proses penutupan mencakup penutupan akun pendapatan dan beban ke income summary, perhitungan laba bersih, dan posting jurnal penutupan.

**Lokasi**: `/period-closings`

**Permission**: `period_closing`

## Menu & Navigasi

| Menu | Path | Deskripsi |
|------|------|-----------|
| Period Closings | Accounting → Period Closings | Daftar semua penutupan periode |
| Add New | Tombol "+ Add" di toolbar | Membuat record penutupan baru |
| View | Klik baris atau tombol View | Melihat detail penutupan |
| Edit | Tombol Edit pada baris | Mengubah data penutupan |
| Delete | Tombol Delete pada baris | Menghapus record penutupan |
| Export | Tombol "Export" di toolbar | Mengunduh data ke Excel |

## 1. Melihat Daftar Period Closings

Halaman utama menampilkan tabel semua record penutupan periode dengan kolom-kolom yang dapat diurutkan:

- **Fiscal Year**: Tahun buku terkait
- **Period Month**: Bulan periode (1-12)
- **Period Year**: Tahun periode
- **Closing Type**: Monthly atau Yearly
- **Status**: Open, In Progress, atau Closed
- **Net Income**: Laba bersih periode tersebut
- **Closed At**: Waktu penutupan selesai

[Screenshot: Tabel Period Closings dengan filter dan toolbar]

### Mengurutkan Data

Klik header kolom untuk mengurutkan data ascending/descending. Kolom yang dapat diurutkan: Fiscal Year, Period Month, Period Year, Closing Type, Status, Net Income, Closed At.

### Mencari dan Memfilter

Gunakan field pencarian dan filter untuk mempersempit data yang ditampilkan.

[Screenshot: Panel filter Period Closings]

## 2. Membuat Period Closing Baru

Klik tombol "+ Add" untuk membuat record penutupan periode baru.

### Field pada Form

| Field | Tipe | Wajib | Deskripsi |
|-------|------|-------|-----------|
| Fiscal Year | Dropdown | Ya | Pilih tahun buku. Sistem otomatis menampilkan tahun buku yang paling relevan berdasarkan aktivitas jurnal |
| Period Month | Dropdown | Ya | Pilih bulan (1-12) |
| Period Year | Number | Ya | Tahun periode |
| Closing Type | Dropdown | Ya | Monthly untuk penutupan bulanan, Yearly untuk penutupan tahunan |
| Status | Dropdown | Ya | Status penutupan: Open, In Progress, Closed |

[Screenshot: Form tambah Period Closing baru]

### Auto-Select Fiscal Year

Field Fiscal Year menggunakan fitur auto-select. Saat membuka form, sistem otomatis memilih tahun buku yang paling relevan:
1. Tahun buku terbaru yang memiliki jurnal posted
2. Jika tidak ada, tahun buku pertama dengan status open
3. Jika tidak ada, tahun buku pertama dalam daftar

Fitur ini mempercepat input dan mengurangi kesalahan pemilihan tahun buku.

### Langkah Pembuatan

1. Klik tombol "+ Add" di toolbar
2. Sistem otomatis mengisi Fiscal Year yang direkomendasikan
3. Pilih Period Month dan isi Period Year
4. Pilih Closing Type (Monthly/Yearly)
5. Pilih Status awal (biasanya Open)
6. Klik "Save" untuk menyimpan

## 3. Melihat Detail Period Closing

Klik baris pada tabel atau tombol View untuk melihat detail penutupan periode.

Modal detail menampilkan informasi lengkap:
- Data dasar penutupan
- Net Income yang dihitung sistem
- Timestamp penutupan (Closed At)
- Informasi audit (Created At, Updated At)

[Screenshot: Modal detail Period Closing]

## 4. Mengubah Period Closing

Klik tombol Edit pada baris untuk mengubah data penutupan.

### Field yang Dapat Diubah

Semua field dapat diubah selama status masih Open. Untuk status In Progress atau Closed, perubahan mungkin dibatasi sesuai aturan bisnis.

### Alur Perubahan Status

Status penutupan mengikuti lifecycle:
1. **Open**: Penutupan baru dibuat, belum diproses
2. **In Progress**: Proses penutupan sedang berjalan
3. **Closed**: Penutupan selesai, jurnal penutupan sudah diposting

[Screenshot: Form edit Period Closing]

## 5. Menghapus Period Closing

Klik tombol Delete pada baris untuk menghapus record penutupan.

Peringatan akan muncul untuk konfirmasi penghapusan. Data yang sudah Closed biasanya tidak dapat dihapus untuk menjaga integritas laporan keuangan.

[Screenshot: Dialog konfirmasi hapus Period Closing]

## 6. Export Data Period Closings

Klik tombol "Export" di toolbar untuk mengunduh data ke file Excel.

### Kolom dalam Export

File Excel berisi semua kolom yang ditampilkan di tabel ditambah kolom tambahan jika ada. Format file disesuaikan untuk kemudahan analisis.

### Langkah Export

1. Terapkan filter jika diperlukan
2. Klik tombol "Export"
3. File Excel akan diunduh otomatis

[Screenshot: Hasil export Period Closings di Excel]

## 7. Proses Penutupan Periode

### Penutupan Bulanan (Monthly Closing)

Penutupan bulanan dilakukan di akhir setiap bulan untuk:
- Memastikan semua transaksi bulan tersebut sudah tercatat
- Memvalidasi saldo akun sebelum lanjut ke bulan berikutnya
- Menyiapkan data untuk laporan keuangan bulanan

### Penutupan Tahunan (Yearly Closing)

Penutupan tahunan dilakukan di akhir tahun buku untuk:
- Menutup semua akun pendapatan dan beban ke retained earnings
- Menghitung laba rugi tahun berjalan
- Memfinalisasi neraca akhir tahun

### Urutan Proses

1. Pastikan semua jurnal periode tersebut sudah posted
2. Buat atau buka record Period Closing
3. Ubah status ke In Progress
4. Sistem menghitung Net Income
5. Posting jurnal penutupan
6. Ubah status ke Closed
7. Catat timestamp Closed At

## 8. Dampak pada Laporan Keuangan

Penutupan periode yang benar sangat penting untuk:

### Trial Balance

Trial balance membutuhkan periode yang ditutup untuk menampilkan saldo akurat.

### Income Statement

Laporan laba rugi bergantung pada penutupan pendapatan dan beban yang benar.

### Balance Sheet

Neraca memerlukan penutupan tahunan untuk memindahkan laba rugi ke retained earnings.

### Comparative Reports

Laporan komparatif antar periode membutuhkan penutupan yang konsisten.

## FAQ

**Q: Apakah periode yang sudah Closed bisa dibuka kembali?**

Pembukaan kembali periode Closed memerlukan proses reversal yang dapat mempengaruhi laporan keuangan. Koordinasikan dengan tim finance atau administrator sistem sebelum melakukan reversal.

**Q: Bagaimana jika ada transaksi yang terlewat setelah periode ditutup?**

Transaksi yang terlewat harus diproses di periode berikutnya. Koordinasikan dengan tim accounting untuk treatment yang tepat.

**Q: Fiscal Year yang muncul di dropdown tidak sesuai harapan. Mengapa?**

Dropdown Fiscal Year menampilkan tahun buku dengan status open. Jika tahun buku yang dicari tidak muncul, periksa status tahun buku tersebut di modul Fiscal Years.

**Q: Bisakah membuat penutupan periode untuk bulan yang sama berkali-kali?**

Tidak. Setiap kombinasi Fiscal Year, Period Month, dan Period Year hanya boleh memiliki satu record Period Closing.

**Q: Kapan sebaiknya melakukan penutupan bulanan?**

Lakukan penutupan bulanan setelah semua invoice, bill, dan transaksi bulan tersebut sudah diinput, rekonsiliasi bank sudah selesai, adjustment entries sudah diposting, dan review dengan supervisor sudah dilakukan.

**Q: Apa yang terjadi pada Net Income setelah penutupan?**

Net Income dihitung otomatis dari selisih total pendapatan dan total beban periode tersebut. Untuk penutupan tahunan, net income dipindahkan ke retained earnings.

**Q: Bagaimana cara memastikan semua jurnal sudah posted sebelum penutupan?**

Jalankan laporan Journal Entries dengan filter status dan periode untuk memverifikasi tidak ada jurnal draft atau pending.

**Q: Apakah export Period Closings bisa difilter?**

Ya. Terapkan filter pada tabel sebelum klik Export. Hanya data yang tampil di tabel yang akan di-export.

**Q: Apa perbedaan Closing Type Monthly dan Yearly?**

Monthly closing menutup satu bulan dalam fiscal year — pendapatan dan beban dihitung untuk bulan tersebut. Yearly closing menutup seluruh fiscal year — semua pendapatan dan beban ditutup, dan net income dipindahkan ke retained earnings (ekuitas).

**Q: Apakah penutupan periode mempengaruhi modul lain?**

Ya. Setelah periode ditutup, tidak bisa membuat transaksi baru (journal entry, invoice, bill, payment, receipt) dengan tanggal dalam periode tersebut. Ini memastikan integritas data laporan keuangan.

**Q: Bagaimana urutan penutupan periode yang benar?**

1. Selesaikan semua transaksi bulan berjalan
2. Lakukan rekonsiliasi bank
3. Posting semua adjusting entries
4. Review trial balance
5. Lakukan monthly closing
6. Setelah semua bulan ditutup, lakukan yearly closing
