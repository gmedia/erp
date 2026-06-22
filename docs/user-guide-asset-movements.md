# User Guide: Asset Movements

## Gambaran Umum

Modul Asset Movements mencatat **riwayat perpindahan aset** — setiap kali aset dipindahkan, ditugaskan ke karyawan, atau dikembalikan ke lokasi semula. Setiap movement mencatat aset yang dipindahkan, tipe perpindahan (Transfer/Assignment/Return), lokasi asal, lokasi tujuan, tanggal perpindahan, catatan/referensi, dan PIC (Person In Charge) yang bertanggung jawab. Modul ini mempertahankan audit trail lengkap tentang di mana setiap aset pernah berada dan siapa yang bertanggung jawab pada setiap periode.

Fitur utama:
- Pencatatan tiga tipe movement: Transfer, Assignment, Return
- Relasi ke Asset, Location (asal dan tujuan), dan Employee (PIC)
- Export data ke Excel
- Dialog view detail setiap movement
- Search dan filter untuk pencarian cepat
- Sorting pada kolom Asset, Type, Date, Ref/Notes, PIC

---

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Asset Movements | `/asset-movements` | Kelola riwayat perpindahan aset |

Modul ini berada dalam grup menu **Asset**.

---

## 1. Menambah Asset Movement Baru

### Langkah-langkah

1. Buka menu **Asset Movements** → klik **Add New**
2. Isi form dengan data perpindahan:

   **Informasi Perpindahan:**
   - **Asset** — Pilih aset yang dipindahkan dari dropdown pencarian (AsyncSelectField, cari berdasarkan kode/nama aset)
   - **Type** — Pilih tipe perpindahan:
     - `Transfer` — Aset dipindahkan dari satu lokasi ke lokasi lain
     - `Assignment` — Aset ditugaskan/diberikan ke karyawan tertentu
     - `Return` — Aset dikembalikan ke lokasi semula
   - **Date** — Tanggal perpindahan terjadi
   - **Origin** — Lokasi asal aset sebelum dipindahkan (pilih dari daftar lokasi)
   - **Destination** — Lokasi tujuan aset setelah dipindahkan (pilih dari daftar lokasi)
   - **Ref/Notes** — Nomor referensi atau catatan tambahan (opsional)
   - **PIC** — Person In Charge, karyawan yang bertanggung jawab atas perpindahan ini (pilih dari daftar karyawan)

3. Klik **Save** — movement tersimpan dan muncul di tabel

[Screenshot: Form Add Asset Movement menampilkan field Asset, Type, Date, Origin, Destination, Ref/Notes, PIC]

### Validasi Form

| Field | Validasi |
|-------|----------|
| Asset | Wajib dipilih |
| Type | Wajib dipilih (Transfer/Assignment/Return) |
| Date | Wajib diisi, format tanggal valid |
| Origin | Wajib dipilih |
| Destination | Wajib dipilih |
| Ref/Notes | Opsional, teks bebas |
| PIC | Wajib dipilih |

---

## 2. Melihat Detail Asset Movement

1. Buka menu **Asset Movements**
2. Cari movement yang ingin dilihat menggunakan search atau filter
3. Klik icon **View** (eye) pada baris movement tersebut
4. Dialog detail akan menampilkan semua informasi movement

[Screenshot: Dialog View Asset Movement menampilkan detail movement]

### Informasi yang Ditampilkan

- Nama/Kode aset yang dipindahkan
- Tipe perpindahan (Transfer/Assignment/Return)
- Tanggal perpindahan
- Lokasi asal (Origin)
- Lokasi tujuan (Destination)
- Referensi atau catatan
- PIC yang bertanggung jawab
- Tanggal data dibuat dan terakhir diupdate

---

## 3. Mengedit Asset Movement

1. Buka menu **Asset Movements**
2. Cari movement yang ingin diedit
3. Klik icon **Edit** (pencil) pada baris movement
4. Form edit akan terbuka dengan data movement yang sudah terisi
5. Ubah field yang diperlukan
6. Klik **Save** untuk menyimpan perubahan

[Screenshot: Form Edit Asset Movement dengan data existing]

---

## 4. Menghapus Asset Movement

1. Buka menu **Asset Movements**
2. Cari movement yang ingin dihapus
3. Klik icon **Delete** (trash) pada baris movement
4. Konfirmasi penghapusan pada dialog yang muncul
5. Movement akan terhapus dari daftar

> **Catatan**: Hapus movement hanya jika data tidak diperlukan untuk historis audit trail aset. Pertimbangkan bahwa modul ini berfungsi sebagai catatan riwayat perpindahan.

---

## 5. Search dan Filter

### Search

Ketik kata kunci pada field **Search movements...** untuk mencari berdasarkan:
- Nama atau kode aset
- Tipe movement (Transfer/Assignment/Return)
- Referensi atau catatan
- Nama PIC

[Screenshot: Search field dengan hasil pencarian]

### Filter Tersedia

| Filter | Penjelasan |
|--------|------------|
| **Type** | Filter berdasarkan tipe movement: Transfer, Assignment, atau Return |
| **Date Range** | Filter berdasarkan rentang tanggal perpindahan |

[Screenshot: Panel filter Asset Movement dengan dropdown Type dan date range]

### Menggunakan Filter

1. Klik field filter yang ingin digunakan
2. Pilih nilai dari dropdown (misal: Type = "Transfer")
3. Tabel akan otomatis menampilkan hasil yang sesuai
4. Kombinasi filter bisa digunakan untuk pencarian lebih spesifik (contoh: Type = Assignment dan date range bulan ini)

---

## 6. Sorting Kolom

Klik header kolom untuk mengurutkan data. Kolom yang bisa di-sort:

| Kolom | Penjelasan |
|-------|------------|
| **Asset** | Urutkan berdasarkan nama/kode aset |
| **Type** | Urutkan berdasarkan tipe movement |
| **Date** | Urutkan berdasarkan tanggal perpindahan |
| **Ref/Notes** | Urutkan berdasarkan referensi atau catatan |
| **PIC** | Urutkan berdasarkan nama PIC |

Kolom **Origin** dan **Destination** tidak mendukung sorting.

Klik kolom sekali untuk ascending (A-Z, 0-9), klik lagi untuk descending (Z-A, 9-0).

---

## 7. Export ke Excel

1. Buka menu **Asset Movements**
2. Terapkan filter jika ingin export data tertentu
3. Klik tombol **Export** pada toolbar
4. File Excel (.xlsx) akan diunduh dengan semua kolom data

[Screenshot: Tombol Export pada toolbar Asset Movement]

### Kolom dalam File Export

| Kolom | Penjelasan |
|-------|------------|
| ID | ID internal sistem |
| Asset | Nama/kode aset |
| Type | Tipe movement (Transfer/Assignment/Return) |
| Date | Tanggal perpindahan |
| Origin | Lokasi asal |
| Destination | Lokasi tujuan |
| Ref/Notes | Referensi atau catatan |
| PIC | Nama karyawan yang bertanggung jawab |
| Created At | Tanggal data dibuat |
| Updated At | Tanggal data terakhir diupdate |

---

## 8. Permissions

| Permission | Akses |
|-----------|-------|
| `asset_movement` | View & list asset movements |
| `asset_movement.create` | Membuat asset movement baru |
| `asset_movement.edit` | Mengedit asset movement |
| `asset_movement.delete` | Menghapus asset movement |

> **Catatan**: Pastikan user memiliki permission yang sesuai untuk menjalankan fungsi yang diperlukan. Permission `asset_movement` minimal diperlukan untuk melihat daftar movement.

---

## FAQ & Tips

### Apa perbedaan tipe Transfer, Assignment, dan Return?

- **Transfer**: Aset dipindahkan dari satu lokasi fisik ke lokasi fisik lain (contoh: dari Gudang A ke Gudang B). Tidak melibatkan penugasan ke karyawan.
- **Assignment**: Aset ditugaskan/diberikan ke karyawan tertentu sebagai penanggung jawab. Biasanya disertai lokasi tujuan tempat karyawan tersebut berada.
- **Return**: Aset dikembalikan dari karyawan ke lokasi semula atau ke lokasi penyimpanan.

### Apakah Origin dan Destination bisa sama?

Tidak. Lokasi asal dan tujuan harus berbeda untuk setiap movement. Sistem akan memvalidasi bahwa kedua lokasi tidak sama.

### Bagaimana cara melihat riwayat perpindahan satu aset tertentu?

Gunakan search field dan ketik nama atau kode aset. Semua movement yang melibatkan aset tersebut akan ditampilkan. Bisa dikombinasikan dengan filter Type atau date range untuk mempersempit hasil.

### Apakah movement otomatis mengubah lokasi aset saat ini?

Modul Asset Movements berfungsi sebagai catatan historis perpindahan. Perubahan lokasi aset pada data master Asset mungkin memerlukan proses terpisah. Movement ini menyediakan audit trail untuk melacak riwayat.

### Siapa yang bisa menjadi PIC?

PIC dipilih dari daftar karyawan (Employees) yang sudah terdaftar di sistem. Pastikan karyawan sudah dibuat di modul Employee sebelum mencatat movement.

### Bisakah saya export data movement untuk periode tertentu?

Ya. Terapkan filter date range terlebih dahulu, lalu klik Export. File Excel hanya akan berisi data sesuai filter yang diterapkan.

### Bagaimana jika lokasi asal atau tujuan belum ada di sistem?

Lokasi (Asset Locations) harus dibuat terlebih dahulu di modul Asset Locations sebelum bisa digunakan di Asset Movements. Buka menu Asset Locations untuk menambah lokasi baru.

### Apakah data movement yang sudah dihapus bisa dikembalikan?

Tidak. Penghapusan bersifat permanen. Export data sebelum menghapus jika diperlukan untuk keperluan audit.

### Kolom mana yang tidak bisa di-sort?

Kolom Origin dan Destination tidak mendukung sorting. Gunakan search atau filter untuk menemukan movement berdasarkan lokasi tertentu.

---

> **Butuh bantuan?** Hubungi administrator sistem jika mengalami kendala permission atau error saat menggunakan modul Asset Movements.

## FAQ & Tips

**Q:** Bagaimana cara membatalkan atau mengoreksi movement yang salah input?

**J:** Saat ini sistem tidak menyediakan fitur "cancel" atau "void" untuk movement. Jika terjadi kesalahan input, Anda dapat menghapus movement yang salah (klik icon Delete) lalu membuat movement baru dengan data yang benar. Pastikan Anda memiliki permission `asset_movement.delete`. Sebelum menghapus, catat atau export data movement tersebut sebagai arsip.

**Q:** Apakah bisa mencatat movement untuk banyak aset sekaligus?

**J:** Tidak. Setiap movement hanya mencatat perpindahan untuk satu aset. Jika Anda perlu memindahkan banyak aset sekaligus (misalnya pemindahan satu ruangan penuh), Anda harus membuat movement terpisah untuk setiap aset. Gunakan Ref/Notes dengan kode batch yang sama untuk mengelompokkan movement terkait.

**Q:** Bagaimana cara menemukan aset yang sedang berada di lokasi tertentu saat ini?

**J:** Modul Asset Movements mencatat riwayat perpindahan, bukan lokasi terkini aset. Untuk melihat lokasi terkini aset, buka modul Assets dan gunakan filter berdasarkan Location. Anda juga bisa menggunakan modul Asset Locations untuk melihat daftar aset yang tercatat di suatu lokasi.

**Q:** Apa yang harus diisi pada field Ref/Notes?

**J:** Field Ref/Notes bersifat opsional dan dapat diisi dengan nomor dokumen pendukung (seperti nomor BAST, surat perintah, atau nomor tiket internal), alasan perpindahan, atau catatan tambahan lainnya. Isi field ini secara konsisten untuk memudahkan pelacakan dan audit di kemudian hari.

**Q:** Apakah movement dengan tipe Assignment akan otomatis muncul di profil karyawan?

**J:** Modul Asset Movements mencatat PIC (Person In Charge) sebagai referensi. Untuk melihat aset yang ditugaskan ke seorang karyawan, gunakan search dengan nama karyawan tersebut pada field search movements, atau buka modul Assets dan filter berdasarkan Employee. Data movement tidak otomatis muncul di modul Employees.

**Q:** Bagaimana cara terbaik men-track pergerakan aset bernilai tinggi?

**J:** Untuk aset bernilai tinggi, terapkan praktik berikut: (1) selalu isi Ref/Notes dengan nomor dokumen pendukung, (2) gunakan filter date range dan search secara berkala untuk memantau frekuensi perpindahan, (3) export data movement ke Excel setiap bulan sebagai arsip eksternal, (4) pastikan PIC yang ditunjuk adalah karyawan yang benar-benar bertanggung jawab, dan (5) koordinasikan dengan modul Asset Maintenances jika aset memerlukan pengecekan setelah perpindahan.

**Q:** Bagaimana cara memastikan data movement sudah sesuai sebelum disimpan?

**J:** Periksa kembali field berikut sebelum klik Save: (1) Asset yang dipilih sudah benar, (2) Type movement sesuai dengan konteks perpindahan, (3) Date sesuai dengan tanggal aktual perpindahan, (4) Origin dan Destination berbeda dan sesuai fakta, (5) PIC adalah karyawan yang tepat. Gunakan fitur View setelah menyimpan untuk verifikasi ulang data yang sudah masuk.

**Q:** Apakah movement bisa mempengaruhi laporan atau modul lain di sistem?

**J:** Modul Asset Movements berdiri sebagai catatan historis independen. Saat ini, data movement tidak secara otomatis mempengaruhi laporan keuangan, laporan aset, atau modul lainnya. Namun, data ini penting untuk audit internal dan dapat digunakan sebagai referensi silang saat melakukan Asset Stocktake atau pemeriksaan fisik aset.

**Q:** Berapa lama data movement disimpan di sistem?

**J:** Data movement disimpan secara permanen selama tidak dihapus secara manual. Tidak ada mekanisme auto-purge atau archive otomatis. Sistem dirancang untuk mempertahankan audit trail lengkap seluruh riwayat perpindahan aset sejak pertama kali dicatat.

**Q:** Bagaimana cara membedakan movement yang baru dibuat dengan yang sudah lama?

**J:** Gunakan sorting pada kolom Date untuk mengurutkan dari yang terbaru (klik header Date dua kali untuk descending) atau dari yang terlama (sekali klik untuk ascending). Anda juga bisa menggunakan filter date range untuk menampilkan movement dalam periode tertentu, misalnya bulan ini atau tahun ini saja.

