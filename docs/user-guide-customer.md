# User Guide: Customer

## Gambaran Umum

Modul Customer mengelola **data pelanggan** yang digunakan dalam transaksi Accounts Receivable (AR). Setiap pelanggan memiliki informasi kontak, alamat, cabang, kategori, dan status aktif. Data pelanggan menjadi referensi saat membuat Customer Invoice, AR Receipt, dan Credit Note.

[Screenshot: Halaman daftar Customer dengan tabel dan filter]

---

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Customers | `/customers` | Kelola data pelanggan (tambah, edit, hapus, lihat detail) |
| Customer Categories | `/customer-categories` | Kelola kategori pelanggan |
| Customer Invoices | `/customer-invoices` | Faktur pelanggan |
| AR Receipts | `/ar-receipts` | Penerimaan pembayaran dari pelanggan |
| Credit Notes | `/credit-notes` | Nota kredit untuk pelanggan |

Untuk mengakses menu Customers, user harus memiliki permission `customer`.

---

## 1. Melihat Daftar Pelanggan

Halaman Customers menampilkan tabel semua pelanggan dengan kolom:

| Kolom | Penjelasan | Sortable |
|-------|------------|----------|
| Name | Nama pelanggan | Ya |
| Email | Alamat email pelanggan | Ya |
| Phone | Nomor telepon (opsional) | Ya |
| Branch | Cabang tempat pelanggan terdaftar | Ya |
| Category | Kategori pelanggan | Ya |
| Status | Active / Inactive | Ya |
| Actions | Tombol View, Edit, Delete | Tidak |

[Screenshot: Tabel Customer dengan kolom-kolom di atas]

### Filter dan Pencarian

Gunakan filter di bagian atas tabel untuk menyaring data:

1. **Search** — ketik nama, email, atau phone untuk mencari pelanggan
2. **Branch** — pilih cabang tertentu
3. **Category** — pilih kategori pelanggan
4. **Status** — pilih Active atau Inactive

[Screenshot: Panel filter Customer]

---

## 2. Menambah Pelanggan Baru

1. Buka halaman **Customers** (`/customers`)
2. Klik tombol **Add New** di bagian atas tabel
3. Isi form dengan data pelanggan:

[Screenshot: Form Add New Customer]

| Field | Keterangan | Wajib |
|-------|------------|-------|
| Name | Nama pelanggan | Ya |
| Email | Alamat email pelanggan | Ya |
| Phone | Nomor telepon (format: +62 812 3456 7890) | Tidak |
| Address | Alamat lengkap pelanggan | Ya |
| Branch | Pilih cabang dari dropdown | Ya |
| Category | Pilih kategori dari dropdown | Ya |
| Status | Active / Inactive (default: Active) | Ya |
| Notes | Catatan tambahan (opsional) | Tidak |

4. Klik **Save** untuk menyimpan

### Validasi Form

- Email harus format valid (contoh: `customer@example.com`)
- Branch dan Category harus dipilih dari dropdown (tidak bisa diisi manual)
- Status default adalah `Active` jika tidak diubah

---

## 3. Melihat Detail Pelanggan

1. Klik tombol **View** (ikon mata) pada baris pelanggan
2. Modal detail akan muncul menampilkan semua informasi:

[Screenshot: Modal View Customer Details]

Informasi yang ditampilkan:
- Name, Email, Phone
- Address
- Branch dan Category
- Status (dengan badge Active/Inactive)
- Notes (jika ada)

---

## 4. Mengedit Pelanggan

1. Klik tombol **Edit** (ikon pensil) pada baris pelanggan
2. Form edit akan muncul dengan data yang sudah terisi
3. Ubah field yang diperlukan
4. Klik **Save** untuk menyimpan perubahan

[Screenshot: Form Edit Customer]

### Catatan Edit

- Semua field bisa diubah kecuali ID
- Status bisa diubah dari Active ke Inactive untuk menonaktifkan pelanggan
- Pelanggan yang Inactive masih bisa dipilih di transaksi AR, namun dengan peringatan

---

## 5. Menghapus Pelanggan

1. Klik tombol **Delete** (ikon trash) pada baris pelanggan
2. Konfirmasi penghapusan pada dialog yang muncul
3. Klik **Delete** untuk menghapus permanen

[Screenshot: Dialog konfirmasi delete Customer]

### Peringatan Penghapusan

Pelanggan **tidak bisa dihapus** jika:
- Sudah memiliki Customer Invoice
- Sudah memiliki AR Receipt
- Sudah memiliki Credit Note

Hapus atau batalkan semua transaksi terkait sebelum menghapus pelanggan.

---

## 6. Export Data Pelanggan

1. Klik tombol **Export** di bagian atas tabel
2. File Excel akan diunduh dengan semua data pelanggan

[Screenshot: Tombol Export Customer]

### Kolom dalam Export

File Excel berisi kolom:
- ID, Name, Email, Phone
- Address, Branch, Category
- Status, Notes
- Created At, Updated At

Export menggunakan endpoint `/api/customers/export` dan mengikuti filter yang aktif di tabel.

---

## 7. Integrasi dengan Modul AR

Data pelanggan digunakan di tiga modul Accounts Receivable:

### Customer Invoices

- Field **Customer** di form invoice memilih dari daftar pelanggan
- Hanya pelanggan dengan status `Active` yang recommended
- Pelanggan `Inactive` masih bisa dipilih dengan peringatan

### AR Receipts

- Field **Customer** di form receipt memilih pelanggan yang membayar
- Daftar invoice yang dilunasi difilter berdasarkan pelanggan yang dipilih

### Credit Notes

- Field **Customer** di form credit note memilih pelanggan yang menerima nota kredit

---

## FAQ & Tips

### Apakah pelanggan yang dihapus bisa dikembalikan?

Tidak. Penghapusan pelanggan bersifat permanen. Pastikan tidak ada transaksi terkait sebelum menghapus.

### Bagaimana jika pelanggan sudah tidak aktif?

Ubah status pelanggan ke `Inactive`. Pelanggan tetap ada di database dan riwayat transaksi tetap terjaga, namun tidak akan muncul di filter default saat membuat invoice baru.

### Bisakah satu pelanggan terdaftar di beberapa cabang?

Tidak. Setiap pelanggan terdaftar di satu cabang. Jika pelanggan beroperasi di beberapa cabang, buat record pelanggan terpisah untuk setiap cabang.

### Apakah kategori pelanggan wajib?

Ya. Setiap pelanggan harus memiliki kategori. Buat kategori di menu **Customer Categories** (`/customer-categories`) sebelum menambah pelanggan baru.

### Bagaimana cara mencari pelanggan dengan nama parsial?

Gunakan field **Search** di filter. Ketik bagian dari nama, email, atau phone. Sistem akan mencari dengan pencocokan parsial (substring match).

### Bisakah import pelanggan dari file?

Modul Customer saat ini belum memiliki fitur import bulk. Pelanggan harus ditambah satu per satu melalui form Add New.

---

## FAQ & Tips Tambahan

**Q: Bagaimana cara menambah customer baru dengan cepat?**

Pastikan data Customer Categories dan Branch sudah tersedia sebelum menambah customer. Buka menu Customers, klik Add New, isi field wajib (Name, Email, Address, Branch, Category), lalu Save. Jika sering menambah customer dengan data serupa, gunakan fitur Duplicate dengan mengedit customer existing dan mengubah nama serta data yang berbeda.

**Q: Apa perbedaan status Active dan Inactive pada customer?**

Customer dengan status Active muncul di dropdown saat membuat Customer Invoice, AR Receipt, atau Credit Note. Customer Inactive tetap tersimpan di sistem dan riwayat transaksi tetap bisa diakses, namun tidak direkomendasikan untuk transaksi baru. Gunakan status Inactive untuk customer yang sudah tidak beroperasi atau tidak lagi bekerja sama, tanpa menghapus data historis.

**Q: Mengapa harus memilih Branch dan Category saat menambah customer?**

Branch menentukan cabang mana yang melayani customer tersebut, berguna untuk pelaporan per cabang dan manajemen piutang. Category membantu mengelompokkan customer berdasarkan tipe bisnis, prioritas, atau segmentasi lain. Kedua field ini wajib karena mempengaruhi filter laporan dan workflow approval di modul AR.

**Q: Bagaimana cara mencari customer yang sudah lama tidak transaksi?**

Gunakan fitur Export untuk mengunduh seluruh data customer ke Excel. Dari file Excel, filter atau sortir berdasarkan tanggal Created At atau hubungkan dengan data Customer Invoice untuk melihat tanggal transaksi terakhir. Alternatif lain, gunakan filter di halaman Customers untuk menyaring berdasarkan Branch atau Category, lalu periksa satu per satu.

**Q: Bagaimana cara melihat piutang customer tertentu?**

Buka menu Customer Invoices, gunakan filter Customer untuk memilih customer yang diinginkan, lalu lihat daftar invoice beserta kolom Amount Due. Untuk ringkasan total piutang per customer, gunakan laporan Aging Dashboard yang menampilkan total receivables dan breakdown per bucket umur piutang.

**Q: Apakah bisa mengubah Branch atau Category setelah customer dibuat?**

Ya, semua field kecuali ID bisa diubah. Klik tombol Edit pada baris customer, pilih Branch atau Category baru, lalu Save. Perubahan ini bersifat forward-looking, artinya transaksi sebelumnya tetap tercatat dengan Branch atau Category lama saat transaksi dibuat.

**Q: Bagaimana cara ekspor data customer berdasarkan filter tertentu?**

Terapkan filter yang diinginkan di halaman Customers (misalnya pilih Branch tertentu, Category tertentu, atau Status Active). Setelah filter aktif, klik tombol Export. File Excel yang diunduh hanya berisi data customer yang sesuai dengan filter aktif.

**Q: Apa bedanya customer dengan supplier?**

Customer adalah pihak yang membeli dari perusahaan, tercatat di modul AR (Accounts Receivable), dan memiliki piutang. Supplier adalah pihak yang menjual ke perusahaan, tercatat di modul AP (Accounts Payable), dan memiliki hutang. Keduanya terpisah di sistem karena workflow, laporan, dan proses bisnisnya berbeda. Satu entitas bisa terdaftar sebagai customer dan supplier sekaligus jika memiliki hubungan bisnis dua arah.

**Q: Field mana saja yang wajib diisi saat menambah customer?**

Field wajib adalah Name, Email, Address, Branch, Category, dan Status. Phone dan Notes bersifat opsional. Pastikan email menggunakan format valid karena email digunakan untuk komunikasi dan identifikasi customer.

**Q: Bagaimana jika customer memiliki beberapa alamat pengiriman?**

Saat ini setiap customer record hanya menyimpan satu alamat. Jika customer memiliki beberapa alamat pengiriman, catat alamat utama di field Address. Alamat pengiriman alternatif bisa dicatat di field Notes atau buat record customer terpisah dengan suffix nama untuk setiap lokasi.

**Q: Bisakah menghapus customer yang sudah ada transaksinya?**

Tidak. Sistem memblokir penghapusan customer yang sudah memiliki Customer Invoice, AR Receipt, atau Credit Note. Jika customer sudah tidak digunakan, ubah status ke Inactive. Jika benar-benar perlu menghapus, batalkan atau hapus semua transaksi terkait terlebih dahulu.

---

## Troubleshooting

### Pelanggan tidak muncul di dropdown invoice

Pastikan pelanggan memiliki status `Active`. Jika pelanggan Inactive, filter dropdown invoice biasanya menampilkan semua pelanggan, namun dengan indikator status.

### Error "Branch not found" saat save

Branch yang dipilih sudah dihapus atau tidak valid. Refresh halaman dan pilih branch yang tersedia di dropdown.

### Error "Category not found" saat save

Kategori pelanggan sudah dihapus. Buat kategori baru di menu Customer Categories atau pilih kategori lain yang masih ada.

### Export tidak menghasilkan file

Periksa koneksi internet dan pastikan browser tidak memblokir download. Coba gunakan browser lain atau clear cache browser.