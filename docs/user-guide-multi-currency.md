# User Guide: Multi-Currency

## Gambaran Umum

Multi-Currency adalah fitur lintas modul yang mengatur mata uang yang digunakan dalam transaksi keuangan di sistem ERP gmedia. Saat ini, sistem hanya mendukung satu mata uang yaitu **Indonesian Rupiah (IDR)**. Fitur ini bukan modul CRUD mandiri, melainkan constraint sistem yang diterapkan di seluruh modul transaksi: Purchase Order, Supplier Bill, Customer Invoice, AP Payment, AR Receipt, dan Asset.

Setiap transaksi yang melibatkan nilai uang secara otomatis menggunakan IDR. Sistem akan menolak input dengan mata uang selain IDR, baik melalui form manual maupun import Excel. Pengaturan mata uang dapat dilihat di halaman Admin Settings, namun saat ini hanya IDR yang tersedia sebagai pilihan.

Dukungan multi-currency penuh (multiple currency) direncanakan pada roadmap mendatang (H3-Wave2), yang akan memungkinkan transaksi dalam berbagai mata uang asing dengan mekanisme foreign exchange (FX).

## Menu & Navigasi

Fitur Multi-Currency tidak memiliki menu tersendiri di sidebar. Pengaturan mata uang dapat diakses melalui halaman Admin Settings.

| Menu | URL | Permission / Function |
|------|-----|-----------------------|
| Admin Settings | `/admin-settings` | `admin_setting` — mengelola pengaturan regional termasuk mata uang |

Modul-modul transaksi yang menggunakan mata uang:

| Modul | URL | Mata Uang Tersedia |
|-------|-----|---------------------|
| Purchase Order | `/purchase-orders` | IDR |
| Supplier Bill | `/supplier-bills` | IDR |
| Customer Invoice | `/customer-invoices` | IDR |
| AP Payment | `/ap-payments` | IDR |
| AR Receipt | `/ar-receipts` | IDR |
| Asset | `/assets` | IDR |

## 1. Melihat Pengaturan Mata Uang

1. Buka halaman **Admin Settings** dari menu sidebar atau navigasi ke `/admin-settings`.

[Screenshot: Halaman Admin Settings dengan tab Regional terbuka]

2. Pada tab **Regional**, temukan field **Currency**.

[Screenshot: Field Currency pada tab Regional Admin Settings]

3. Nilai saat ini adalah **IDR** (Indonesian Rupiah). Field ini bersifat read-only dan hanya menerima IDR sebagai nilai yang valid.

4. Klik **Save** untuk menyimpan pengaturan. Jika Anda mencoba mengganti ke mata uang lain (misalnya USD), sistem akan menolak dengan pesan validasi.

## 2. Mata Uang pada Form Transaksi

Saat membuat atau mengedit transaksi, field mata uang secara otomatis diisi dengan **IDR** dan disembunyikan dari tampilan form. Anda tidak perlu memilih mata uang secara manual.

[Screenshot: Form tambah Purchase Order — field Currency disembunyikan, otomatis IDR]

Berikut daftar form transaksi yang menerapkan aturan ini:

- Form Purchase Order
- Form Supplier Bill
- Form Customer Invoice
- Form AP Payment
- Form AR Receipt
- Form Asset

Semua form menggunakan validasi yang sama: mata uang harus IDR. Jika ada percobaan mengirim data dengan mata uang selain IDR (misalnya melalui API), sistem akan mengembalikan error 422 (Unprocessable Entity).

## 3. Melihat Mata Uang pada Detail Transaksi

Meskipun field mata uang disembunyikan di form, Anda tetap dapat melihat informasi mata uang pada halaman detail atau modal View setiap transaksi.

[Screenshot: Modal detail Purchase Order menampilkan Currency: IDR]

1. Buka halaman daftar transaksi (misalnya `/purchase-orders`).

2. Klik ikon **View** (mata) pada salah satu baris transaksi.

3. Pada modal atau halaman detail, cari label **Currency** yang menampilkan nilai **IDR**.

Informasi ini disediakan untuk transparansi dan keperluan audit.

## 4. Mata Uang pada DataTable dan Kolom

Pada setiap modul transaksi, kolom **Currency** tersedia di DataTable dan dapat digunakan untuk sorting serta filtering.

[Screenshot: DataTable Purchase Order dengan kolom Currency]

1. Buka halaman daftar transaksi.

2. Kolom **Currency** ditampilkan di tabel. Anda dapat mengklik header kolom untuk mengurutkan berdasarkan mata uang.

3. Saat ini semua transaksi akan menampilkan **IDR** pada kolom ini.

## 5. Mata Uang pada Export Excel

Saat melakukan export data ke Excel, kolom mata uang akan selalu disertakan dalam file hasil export.

[Screenshot: File Excel hasil export Purchase Order dengan kolom Currency]

1. Buka halaman daftar transaksi yang ingin di-export.

2. Klik tombol **Export** pada toolbar.

3. File Excel yang dihasilkan akan memiliki kolom **Currency** dengan nilai **IDR** untuk setiap baris transaksi.

Kolom ini berguna untuk keperluan audit dan pelaporan.

## 6. Import Excel dan Validasi Mata Uang

Saat mengimpor data melalui Excel (untuk modul yang mendukung import), sistem akan memeriksa kolom mata uang pada setiap baris data.

[Screenshot: File Excel template import dengan kolom Currency berisi IDR]

1. Siapkan file Excel dengan format yang sesuai. Pastikan kolom **Currency** (jika ada) diisi dengan **IDR**.

2. Buka halaman daftar transaksi dan klik tombol **Import**.

[Screenshot: Dialog import dengan preview data]

3. Upload file Excel. Sistem akan memvalidasi setiap baris.

4. Jika terdapat baris dengan mata uang selain IDR, baris tersebut akan **ditolak** dan sistem akan menampilkan pesan error yang menjelaskan baris mana yang bermasalah.

5. Hanya baris dengan mata uang IDR yang akan diproses dan dimasukkan ke database.

## 7. Permissions

Fitur Multi-Currency menggunakan permission yang sudah ada di masing-masing modul transaksi. Tidak ada permission khusus untuk mengelola mata uang secara terpisah.

| Permission | Fungsi |
|------------|--------|
| `admin_setting` | Mengakses halaman Admin Settings untuk melihat pengaturan mata uang |
| `purchase_order_create` | Membuat Purchase Order (otomatis IDR) |
| `purchase_order_update` | Mengedit Purchase Order (otomatis IDR) |
| `supplier_bill_create` | Membuat Supplier Bill (otomatis IDR) |
| `supplier_bill_update` | Mengedit Supplier Bill (otomatis IDR) |
| `customer_invoice_create` | Membuat Customer Invoice (otomatis IDR) |
| `customer_invoice_update` | Mengedit Customer Invoice (otomatis IDR) |
| `ap_payment_create` | Membuat AP Payment (otomatis IDR) |
| `ap_payment_update` | Mengedit AP Payment (otomatis IDR) |
| `ar_receipt_create` | Membuat AR Receipt (otomatis IDR) |
| `ar_receipt_update` | Mengedit AR Receipt (otomatis IDR) |
| `asset_create` | Membuat Asset (otomatis IDR) |
| `asset_update` | Mengedit Asset (otomatis IDR) |
| Export permissions | Export Excel — kolom mata uang selalu disertakan |

## FAQ & Tips

**Q:** Mengapa sistem hanya mendukung mata uang IDR?

**J:** Saat ini sistem gmedia masih dalam tahap pengembangan awal untuk fitur multi-currency. Dukungan penuh untuk berbagai mata uang asing beserta mekanisme foreign exchange (FX) direncanakan pada roadmap H3-Wave2. Sementara itu, seluruh transaksi menggunakan IDR sebagai mata uang tunggal.

**Q:** Apa yang terjadi jika saya mencoba menginput mata uang selain IDR melalui API?

**J:** Sistem akan menolak permintaan tersebut dengan response HTTP 422 (Unprocessable Entity). Pesan error akan menjelaskan bahwa mata uang yang dimasukkan tidak didukung. Validasi ini diterapkan di level backend pada setiap form request transaksi.

**Q:** Bagaimana cara melihat mata uang pada transaksi yang sudah ada?

**J:** Buka halaman daftar transaksi, klik ikon View pada transaksi yang ingin dilihat. Pada modal detail, akan tampil label **Currency: IDR**. Anda juga dapat melihat kolom Currency pada DataTable utama atau pada file hasil export Excel.

**Q:** Apakah multi-currency akan didukung di masa mendatang?

**J:** Ya, dukungan multi-currency penuh (multiple currency dengan foreign exchange) ada dalam roadmap pengembangan sistem gmedia. Fitur ini akan memungkinkan transaksi dalam berbagai mata uang asing dengan konversi otomatis berdasarkan kurs yang berlaku.

**Q:** Mengapa field mata uang tidak muncul di form transaksi?

**J:** Karena saat ini hanya IDR yang didukung, field mata uang disembunyikan (hidden) dari tampilan form untuk mengurangi kebingungan pengguna. Nilai IDR otomatis dikirim bersama data form. Field ini akan muncul kembali sebagai dropdown pilihan mata uang saat fitur multi-currency penuh sudah tersedia.

**Q:** Bagaimana cara import Excel menangani kolom mata uang?

**J:** Sistem memiliki mekanisme CurrencyGuard yang memeriksa setiap baris data dalam file Excel. Baris dengan mata uang selain IDR akan ditolak dan tidak dimasukkan ke database. Pastikan kolom Currency pada file Excel Anda diisi dengan IDR. Jika kolom Currency tidak ada, sistem akan menggunakan IDR sebagai default.

**Q:** Apa perbedaan antara base_currency dan display currency?

**J:** Base currency (IDR) adalah mata uang dasar yang digunakan untuk menyimpan nilai di database dan melakukan kalkulasi internal. Display currency adalah mata uang yang ditampilkan ke pengguna. Saat ini keduanya sama (IDR). Ketika fitur multi-currency penuh tersedia, base currency tetap IDR sementara display currency bisa berbeda sesuai pilihan pengguna, dengan konversi otomatis berdasarkan kurs.

**Q:** Apakah data transaksi saya aman jika mata uang berubah di masa depan?

**J:** Ya, setiap transaksi menyimpan nilai mata uangnya sendiri di kolom `currency` pada tabel database. Ketika fitur multi-currency diaktifkan di masa depan, transaksi yang sudah ada dengan mata uang IDR tidak akan terpengaruh. Data historis tetap utuh dan dapat diaudit.

**Q:** Apakah saya bisa mengubah pengaturan mata uang di Admin Settings?

**J:** Saat ini field Currency di Admin Settings hanya menerima nilai IDR. Jika Anda mencoba menyimpan dengan nilai lain, sistem akan menolak dengan pesan validasi. Field ini disediakan sebagai persiapan untuk fitur multi-currency mendatang.

**Q:** Modul apa saja yang terpengaruh oleh pengaturan mata uang?

**J:** Enam modul transaksi utama: Purchase Order, Supplier Bill, Customer Invoice, AP Payment, AR Receipt, dan Asset. Setiap modul ini memiliki kolom `currency` di database, validasi IDR di form, dan tampilan mata uang di DataTable serta View modal.

**Q:** Apakah laporan keuangan (Balance Sheet, Income Statement, dll) terpengaruh oleh mata uang?

**J:** Seluruh laporan keuangan menggunakan IDR sebagai mata uang pelaporan. Karena semua transaksi saat ini dalam IDR, tidak ada konversi mata uang yang diperlukan dalam perhitungan laporan. Ketika fitur multi-currency tersedia, laporan akan mendukung konversi otomatis ke base currency.
