# User Guide: Report Configurations

## Gambaran Umum

Report Configurations adalah modul untuk mendefinisikan template dan layout laporan yang dapat digunakan ulang. Setiap konfigurasi memiliki Code (kode unik), Name (nama konfigurasi), dan pengaturan layout laporan seperti pemilihan akun, pengelompokan, subtotal, dan format tampilan.

Modul ini memungkinkan akuntan membuat struktur laporan kustom tanpa perubahan kode program. Laporan seperti Balance Sheet, Income Statement, dan Trial Balance dapat mereferensikan konfigurasi ini untuk menampilkan data sesuai kebutuhan bisnis.

[Screenshot: Halaman daftar Report Configurations dengan tabel data]

---

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Report Configurations | `/report-configurations` | Kelola template dan layout laporan (tambah, edit, hapus, lihat detail) |

Untuk mengakses modul ini, pengguna memerlukan permission `report_configuration`. Modul ini berada di bawah grup menu Accounting.

---

## 1. Melihat Daftar Report Configurations

Halaman Report Configurations menampilkan tabel semua konfigurasi dengan kolom:

| Kolom | Penjelasan | Sortable |
|-------|------------|----------|
| Code | Kode unik konfigurasi | Ya |
| Name | Nama konfigurasi | Ya |
| Actions | Tombol View, Edit, Delete | Tidak |

[Screenshot: Tabel Report Configurations dengan kolom Code, Name, Actions]

### Fitur pada Tabel

| Fitur | Deskripsi |
|-------|-----------|
| Search | Filter berdasarkan code atau name |
| Sort | Klik header kolom Code atau Name untuk sorting |
| Pagination | Navigasi halaman untuk data yang banyak |
| Export | Download data ke file Excel |

---

## 2. Menambah Konfigurasi Baru

1. Buka halaman **Report Configurations** (`/report-configurations`)
2. Klik tombol **Add New** di bagian atas tabel
3. Isi form dengan data konfigurasi:

[Screenshot: Form Add New Report Configuration]

| Field | Keterangan | Wajib |
|-------|------------|-------|
| Code | Kode unik untuk konfigurasi (contoh: `BS-STD`, `PL-DETAIL`) | Ya |
| Name | Nama deskriptif konfigurasi (contoh: "Balance Sheet Standard", "P&L Detailed") | Ya |

4. Klik **Save** untuk menyimpan

### Validasi Form

- Code tidak boleh kosong dan harus unique
- Name tidak boleh kosong
- Code hanya boleh berisi huruf, angka, dan tanda hubung/underscore

---

## 3. Melihat Detail Konfigurasi

1. Klik tombol **View** (ikon mata) pada baris konfigurasi
2. Modal detail akan muncul menampilkan informasi:

[Screenshot: Modal View Report Configuration Details]

Informasi yang ditampilkan:
- Code
- Name
- Created At
- Updated At

---

## 4. Mengedit Konfigurasi

1. Klik tombol **Edit** (ikon pensil) pada baris konfigurasi
2. Form edit akan muncul dengan data yang sudah terisi
3. Ubah field yang diperlukan
4. Klik **Save** untuk menyimpan perubahan

[Screenshot: Form Edit Report Configuration]

### Catatan Edit

- Code dapat diubah selama masih unique
- Perubahan konfigurasi akan mempengaruhi laporan yang menggunakan konfigurasi tersebut

---

## 5. Menghapus Konfigurasi

1. Klik tombol **Delete** (ikon trash) pada baris konfigurasi
2. Konfirmasi penghapusan pada dialog yang muncul
3. Klik **Delete** untuk menghapus permanen

[Screenshot: Dialog konfirmasi delete Report Configuration]

### Peringatan Penghapusan

Konfigurasi **tidak bisa dihapus** jika:
- Sudah direferensikan oleh laporan keuangan aktif
- Memiliki riwayat penggunaan dalam periode yang sudah di-close

---

## 6. Export Data Konfigurasi

1. Klik tombol **Export** di bagian atas tabel
2. File Excel akan diunduh dengan semua data konfigurasi

[Screenshot: Tombol Export dan hasil file Excel]

### Kolom dalam Export

File Excel berisi kolom:
- ID
- Code
- Name
- Created At
- Updated At

Export menggunakan endpoint `/api/report-configurations/export` dan mengikuti filter yang aktif di tabel.

---

## 7. Penggunaan Konfigurasi dalam Laporan

Konfigurasi report dapat direferensikan oleh berbagai laporan keuangan:

### Balance Sheet

Laporan neraca dapat menggunakan konfigurasi untuk:
- Menentukan akun-akun yang termasuk dalam Assets, Liabilities, dan Equity
- Mengatur pengelompokan akun (Current Assets, Fixed Assets, dll)
- Menentukan posisi subtotal dan total

### Income Statement

Laporan laba rugi dapat menggunakan konfigurasi untuk:
- Menentukan akun-akun Revenue dan Expense
- Mengatur pengelompokan (Operating Revenue, Other Income, dll)
- Menghitung Gross Profit, Operating Income, Net Income

### Trial Balance

Laporan daftar saldo dapat menggunakan konfigurasi untuk:
- Memilih akun-akun yang ditampilkan
- Mengatur format tampilan (dengan atau tanpa saldo awal)

[Screenshot: Dropdown pemilihan konfigurasi di halaman laporan keuangan]

---

## 8. Best Practice Penamaan Konfigurasi

### Penamaan Code

Gunakan format yang konsisten dan mudah dipahami:

| Tipe Laporan | Contoh Code | Penjelasan |
|--------------|-------------|------------|
| Balance Sheet | `BS-STD`, `BS-DETAIL` | Standard vs detailed version |
| Income Statement | `IS-STD`, `IS-MONTHLY` | Standard vs monthly comparison |
| Trial Balance | `TB-STD`, `TB-WITH-OPENING` | Standard vs with opening balance |

### Penamaan Name

Gunakan nama yang deskriptif:

- "Balance Sheet - Standard Format"
- "Income Statement - Monthly Comparison"
- "Trial Balance - With Opening Balance"

---

## FAQ

**Q: Apakah konfigurasi yang dihapus bisa dikembalikan?**

Tidak. Penghapusan konfigurasi bersifat permanen. Pastikan konfigurasi tidak digunakan oleh laporan aktif sebelum menghapus.

**Q: Bisakah satu konfigurasi digunakan untuk beberapa laporan?**

Ya. Satu konfigurasi dapat direferensikan oleh beberapa laporan dengan tipe yang sama. Misalnya, konfigurasi Balance Sheet Standard dapat digunakan untuk laporan bulanan maupun tahunan.

**Q: Bagaimana cara membuat format laporan kustom untuk audit?**

Buat konfigurasi baru dengan Code seperti `BS-AUDIT-2025` atau `IS-AUDIT-Q4`. Sesuaikan pengelompokan akun sesuai kebutuhan auditor. Simpan konfigurasi tersebut untuk referensi di masa depan.

**Q: Apakah ada batasan jumlah konfigurasi?**

Tidak ada batasan teknis. Namun, disarankan untuk membuat konfigurasi yang benar-benar dibutuhkan dan menghapus konfigurasi yang tidak digunakan untuk menjaga kerapian daftar.

**Q: Bagaimana jika konfigurasi yang digunakan sudah diubah?**

Perubahan pada konfigurasi akan langsung berlaku untuk laporan yang menggunakannya. Jika laporan historical memerlukan format lama, buat konfigurasi baru dengan versi berbeda daripada mengubah konfigurasi existing.

**Q: Bisakah konfigurasi dicopy atau diduplikasi?**

Saat ini belum ada fitur duplicate. Untuk membuat variasi konfigurasi, buat konfigurasi baru dengan code dan name yang berbeda, lalu sesuaikan pengaturannya.

**Q: Apakah konfigurasi yang digunakan di laporan bisa diedit?**

Ya, tapi hati-hati. Perubahan akan langsung berdampak pada tampilan laporan. Disarankan untuk membuat konfigurasi baru daripada mengubah konfigurasi yang sudah digunakan di laporan aktif.

**Q: Bagaimana cara memilih konfigurasi yang tepat untuk laporan saya?**

1. Tentukan tipe laporan (Balance Sheet, Income Statement, Trial Balance).
2. Pilih konfigurasi yang sesuai dengan standar akuntansi yang digunakan.
3. Lihat preview layout di detail konfigurasi.
4. Jika tidak ada yang cocok, buat konfigurasi baru dengan pengaturan kustom.

**Q: Apakah format grouping bisa diubah setelah konfigurasi dibuat?**

Ya. Edit konfigurasi dan ubah pengaturan grouping (kode akun, sub-total, header section). Simpan perubahan dan format baru langsung berlaku di laporan.

**Q: Bisakah menggunakan COA version berbeda untuk konfigurasi yang sama?**

Konfigurasi tidak terikat ke COA version tertentu. Konfigurasi menggunakan kode akun — pastikan kode akun yang direferensikan ada di COA version yang digunakan laporan.

---

## Troubleshooting

### Konfigurasi tidak muncul di dropdown laporan

Pastikan konfigurasi sudah tersimpan dengan benar. Refresh halaman laporan dan periksa kembali dropdown pemilihan konfigurasi.

### Error "Code already exists" saat save

Code yang dimasukkan sudah digunakan oleh konfigurasi lain. Gunakan code yang berbeda atau edit konfigurasi existing.

### Laporan tidak sesuai dengan konfigurasi

Periksa apakah laporan menggunakan konfigurasi yang benar. Buka detail konfigurasi untuk memverifikasi pengaturan layout.

### Export tidak menghasilkan file

Periksa koneksi internet dan pastikan browser tidak memblokir download. Coba gunakan browser lain atau clear cache browser.
