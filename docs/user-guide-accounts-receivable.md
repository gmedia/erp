# Panduan: Piutang Usaha

## Gambaran Umum

Modul Accounts Receivable mengelola **siklus piutang lengkap** — dari penerbitan faktur pelanggan (Customer Invoices), penerimaan pembayaran (AR Receipts), hingga pengurangan piutang melalui nota kredit (Credit Notes). Modul ini terintegrasi dengan Pipeline dan Chart of Accounts.

## Akses & Izin

- **`customer_invoice`** — membuka dan mengekspor daftar Faktur Pelanggan.
- **`customer_invoice.create`** — membuat faktur baru.
- **`customer_invoice.edit`** — mengubah faktur, termasuk mengirim dan membatalkan.
- **`customer_invoice.delete`** — menghapus faktur berstatus Draf.
- **`ar_receipt`** — membuka dan mengekspor daftar Penerimaan Piutang.
- **`ar_receipt.create`** — membuat penerimaan baru.
- **`ar_receipt.edit`** — mengubah penerimaan, termasuk mengonfirmasi dan membatalkan.
- **`ar_receipt.delete`** — menghapus penerimaan berstatus Draf.
- **`credit_note`** — membuka dan mengekspor daftar Nota Kredit.
- **`credit_note.create`** — membuat nota kredit baru.
- **`credit_note.edit`** — mengubah, menerapkan (**Apply**), dan membatalkan nota kredit.
- **`credit_note.delete`** — menghapus nota kredit berstatus Draf.
- **`ar_aging_report`**, **`ar_outstanding_report`**, **`customer_statement_report`** — membuka
  laporan terkait.

Tombol **Ekspor** pada daftar Faktur Pelanggan, Penerimaan Piutang, dan Nota Kredit memakai
permission tampilan masing-masing, bukan permission terpisah.

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Faktur Pelanggan | `/customer-invoices` | Kelola faktur pelanggan |
| Penerimaan Piutang | `/ar-receipts` | Kelola penerimaan pembayaran |
| Nota Kredit | `/credit-notes` | Kelola nota kredit |
| Laporan Umur Piutang | `/reports/ar-aging` | Laporan umur piutang |
| Laporan Piutang Belum Lunas | `/reports/ar-outstanding` | Daftar faktur belum lunas |
| Laporan Mutasi Pelanggan | `/reports/customer-statement` | Laporan transaksi per pelanggan |

---

## 1. Customer Invoices (Faktur Pelanggan)

### Membuat Invoice

1. Buka menu **Faktur Pelanggan** → klik **Add New**
2. Isi header:
   - **Customer** — pelanggan yang ditagih
   - **Branch** — cabang penerbit
   - **Fiscal Year** — tahun fiskal aktif
   - **Invoice Date** — tanggal penerbitan
   - **Due Date** — tanggal jatuh tempo
   - **Payment Terms** — syarat pembayaran (opsional, mis. "Net 30", "COD")
   - **Currency** — mata uang (default: IDR)
3. Tambahkan item:
   - Klik **Add Item** untuk membuka dialog
   - Isi: Product (opsional), Account (akun revenue), Unit (opsional), Description, Quantity, Unit Price, Discount %, Tax %
   - Line total dihitung otomatis
4. Klik **Save** — invoice dibuat dengan status `Draft` dan nomor otomatis (INV-2026-000001)

### Status Lifecycle

```
Draft → Sent → Partially Paid → Paid
  ↓                  ↓
Cancelled         Overdue (jika melewati due date)
                     ↓
                Void (jurnal reversal)
```

| Status | Penjelasan |
|--------|------------|
| **Draf** | Baru dibuat, masih bisa diedit |
| **Terkirim** | Dikirim ke pelanggan, piutang tercatat |
| **Dibayar Sebagian** | Sebagian sudah diterima pembayarannya |
| **Dibayar** | Lunas (amount_due = 0) |
| **Jatuh Tempo** | Melewati jatuh tempo dan belum lunas |
| **Dibatalkan** | Dibatalkan sebelum sent |
| **Batal** | Dibatalkan setelah sent |

> **Catatan**: Status `Partially Paid` dan `Paid` diupdate **otomatis** saat pembayaran diterima atau credit note diterapkan.

### Mengubah Status

- **Send**: Edit invoice → ubah status ke `Sent` → Save (otomatis set `sent_by` dan `sent_at`)
- **Cancel**: Edit invoice (status Draf) → ubah ke `Cancelled` → Save
- **Batal**: Edit invoice (status Terkirim) → ubah ke `Void` → Save

### Aksi Posting & Pembatalan

- **Kirim memposting jurnal.** Saat status diubah menjadi **Terkirim**, sistem otomatis memposting
  jurnal piutang, lalu mengisi `sent_by` dan `sent_at`. Pengiriman dapat dilakukan saat membuat
  faktur baru atau saat mengubah faktur berstatus Draf.
- **Pembatalan dokumen terposting memakai jurnal pembalik.** Untuk faktur berstatus **Terkirim**,
  gunakan aksi baris **Cancel Invoice** atau ubah status menjadi **Dibatalkan**/**Batal**. Sistem
  membalik jurnal, mengubah status menjadi final, dan menyimpan dokumen sebagai jejak audit.
- **Pembatalan ditolak bila masih ada turunan aktif.** Faktur tidak dapat dibatalkan selama masih
  memiliki alokasi penerimaan berstatus **Dikonfirmasi**/**Direkonsiliasi**, atau nota kredit
  berstatus **Diterapkan**. Lepaskan atau batalkan dokumen tersebut terlebih dahulu.
- **Hapus hanya untuk Draf.** Faktur yang sudah dikirim atau memiliki jurnal tidak dapat dihapus;
  gunakan pembatalan agar jurnal pembalik tercatat.

### Ekspor Faktur Pelanggan

Tombol **Ekspor** mengunduh data sesuai filter aktif dalam format Excel (`.xlsx`) atau CSV. Kolom
yang diekspor: ID, Invoice Number, Customer, Branch, Department, Fiscal Year, Invoice Date, Due Date,
Payment Terms, Currency, Status, Subtotal, Tax Amount, Discount Amount, Grand Total, Amount
Received, Credit Note Amount, Amount Due, Notes, dan Created At.

---

## 2. AR Receipts (Penerimaan Pembayaran)

### Membuat Penerimaan

1. Buka menu **Penerimaan Piutang** → klik **Add New**
2. Isi header:
   - **Customer** — pelanggan yang membayar
   - **Branch** — cabang penerima
   - **Fiscal Year** — tahun fiskal
   - **Receipt Date** — tanggal penerimaan
   - **Payment Method** — Bank Transfer / Cash / Check / Giro / Credit Card / Other
   - **Bank Account** — akun kas/bank yang menerima
   - **Total Amount** — nominal yang diterima
   - **Reference** — nomor referensi transfer (opsional)
3. Tambahkan alokasi:
   - Klik **Add Allocation** untuk membuka dialog
   - Pilih **Customer Invoice** yang dilunasi (hanya invoice Sent/Partially Paid/Overdue)
   - Isi **Allocated Amount** (tidak boleh melebihi sisa piutang)
   - Isi **Discount Given** jika ada diskon pembayaran dini (opsional)
4. Klik **Save** — receipt dibuat dengan nomor otomatis (RCV-2026-000001)

> **Catatan departemen:** AR Receipt tidak memiliki departemen. Jurnal penerimaan hanya menyentuh akun kas/bank dan piutang, sehingga tidak masuk ke laporan Laba Rugi per departemen. Departemen dicatat pada Customer Invoice (baris pendapatan).

### Multi-Invoice Receipt

Satu penerimaan bisa melunasi **beberapa invoice sekaligus**:
- Tambahkan beberapa alokasi ke invoice yang berbeda
- Total alokasi tidak boleh melebihi Total Amount penerimaan

### Partial Receipt

Satu invoice bisa dibayar **bertahap**:
- Receipt 1: alokasi Rp 5.000.000 ke Invoice A
- Receipt 2: alokasi Rp 3.000.000 ke Invoice A
- Invoice A otomatis berubah ke `Partially Paid`, lalu `Paid` saat lunas

### Validasi Otomatis

| Validasi | Penjelasan |
|----------|------------|
| Over-allocation | Tidak bisa mengalokasikan lebih dari sisa piutang |
| Invoice status | Hanya invoice `Sent`/`Partially Paid`/`Overdue` yang bisa dialokasi |
| Void/Cancelled guard | Tidak bisa mengalokasikan ke invoice yang sudah void/cancelled |
| Amount sync | `amount_received` dan `amount_due` di invoice diupdate otomatis |

### Menghapus Penerimaan

Saat receipt dihapus:
- Semua alokasi di-revert
- `amount_received` di setiap invoice yang terkait dikurangi
- Status invoice otomatis disesuaikan (bisa kembali ke `Sent`)

### Aksi Posting & Pembatalan

- **Konfirmasi memposting jurnal.** Saat status diubah menjadi **Dikonfirmasi**, sistem otomatis
  memposting jurnal penerimaan (mendebit akun bank/kas dan mengkredit akun piutang), lalu mengisi
  `confirmed_by` dan `confirmed_at`.
- **Pembatalan memakai jurnal pembalik.** Untuk penerimaan berstatus **Dikonfirmasi**, gunakan aksi
  baris **Cancel Receipt** atau ubah status menjadi **Dibatalkan**. Sistem membalik jurnal,
  melepaskan seluruh alokasi, menghitung ulang `amount_received`/`amount_due` invoice, dan
  menyimpan dokumen sebagai jejak audit.
- **Hapus hanya untuk Draf.** Penerimaan yang sudah diposting tidak dapat dihapus; gunakan
  pembatalan agar jurnal pembalik tercatat.

### Ekspor Penerimaan Piutang

Tombol **Ekspor** mengunduh data sesuai filter aktif. Kolom yang diekspor: ID, Receipt Number,
Customer, Branch, Fiscal Year, Receipt Date, Payment Method, Bank Account, Currency, Status, Total
Amount, Total Allocated, Total Unallocated, Reference, Notes, dan Created At.

---

## 3. Credit Notes (Nota Kredit)

### Membuat Credit Note

1. Buka menu **Nota Kredit** → klik **Add New**
2. Isi header:
   - **Customer** — pelanggan penerima CN
   - **Customer Invoice** — invoice yang dikurangi (opsional)
   - **Branch** — cabang penerbit
   - **Fiscal Year** — tahun fiskal
   - **Credit Note Date** — tanggal penerbitan
   - **Reason** — alasan: Return / Discount / Correction / Bad Debt / Other
3. Tambahkan item:
   - Klik **Add Item** untuk membuka dialog
   - Isi: Product (opsional), Account (akun retur/koreksi), Description, Quantity, Unit Price, Tax %
4. Klik **Save** — CN dibuat dengan status `Draft` dan nomor otomatis (CN-2026-000001)

### Status Lifecycle

```
Draft → Confirmed → Applied
  ↓
Cancelled
  ↓ (setelah dikonfirmasi)
Void
```

| Status | Penjelasan |
|--------|------------|
| **Draf** | Baru dibuat, masih bisa diedit |
| **Dikonfirmasi** | Dikonfirmasi, siap diterapkan |
| **Applied** | Sudah diterapkan ke invoice, piutang berkurang |
| **Dibatalkan** | Dibatalkan sebelum dikonfirmasi |
| **Batal** | Dibatalkan setelah dikonfirmasi |

### Menerapkan Credit Note (Apply)

Setelah CN berstatus `Confirmed`, CN bisa diterapkan ke invoice:

1. Buka detail Credit Note yang sudah `Confirmed`
2. Klik **Apply** (atau `POST /api/credit-notes/{id}/apply`)
3. Sistem akan:
   - Memvalidasi CN amount ≤ invoice amount_due
   - Mengupdate `invoice.credit_note_amount`
   - Mengurangi `invoice.amount_due`
   - Mengubah CN status ke `Applied`
   - Mengupdate invoice status otomatis (bisa jadi `Paid` jika lunas)

### Validasi Apply

| Validasi | Penjelasan |
|----------|------------|
| Status check | Hanya CN `Confirmed` yang bisa di-apply |
| Invoice link | CN harus terhubung ke invoice (`customer_invoice_id` wajib) |
| Amount check | CN grand_total tidak boleh melebihi invoice amount_due |

### Aksi Posting & Pembatalan

- **Konfirmasi belum memposting jurnal.** Mengubah status menjadi **Dikonfirmasi** hanya mengisi
  `confirmed_by` dan `confirmed_at`. Jurnal nota kredit baru dibuat saat CN **diterapkan**.
- **Apply memposting jurnal dan mengurangi piutang.** Saat CN diterapkan, sistem memposting jurnal
  nota kredit, mengubah status menjadi **Diterapkan**, menambah `credit_note_amount`, mengurangi
  `amount_due` invoice, lalu memperbarui status pembayaran invoice.
- **Pembatalan memakai jurnal pembalik.** Untuk CN berstatus **Dikonfirmasi** atau **Diterapkan**,
  gunakan aksi baris **Cancel Credit Note** atau ubah status menjadi **Dibatalkan**. Sistem membalik
  jurnal, menghitung ulang `credit_note_amount`/`amount_due` invoice, dan menyimpan dokumen sebagai
  jejak audit.
- **Hapus hanya untuk Draf.** CN yang sudah dikonfirmasi atau diterapkan tidak dapat dihapus;
  gunakan pembatalan agar jurnal pembalik tercatat.

### Ekspor Nota Kredit

Tombol **Ekspor** mengunduh data sesuai filter aktif. Kolom yang diekspor: ID, Credit Note Number,
Customer, Invoice Number, Branch, Department, Fiscal Year, Credit Note Date, Reason, Status,
Subtotal, Tax Amount, Grand Total, Notes, dan Created At.

---

## 4. Laporan

### AR Aging Report

Menampilkan invoice yang belum lunas dikelompokkan berdasarkan umur:
- **Current** — belum jatuh tempo
- **1-30 hari** — overdue 1-30 hari
- **31-60 hari** — overdue 31-60 hari
- **61-90 hari** — overdue 61-90 hari
- **>90 hari** — overdue lebih dari 90 hari

**Filter**: Customer, Branch, Status, Date Range

### AR Outstanding Report

Daftar semua invoice yang belum lunas (status: Sent, Partially Paid, Overdue), diurutkan berdasarkan jatuh tempo. Menampilkan `Days Overdue` untuk invoice yang melewati due date.

**Filter**: Customer, Branch, Due Date Range

### Customer Statement

Laporan transaksi per pelanggan dalam periode tertentu — menampilkan semua invoice dengan status pembayaran.

**Filter**: Customer (wajib), Date Range

### Export

Semua laporan bisa di-export ke **Excel (.xlsx)** dengan klik tombol Export.

---

## 5. Alur Kerja Lengkap

### Skenario: Penjualan dan Penerimaan Pembayaran

```
1. Buat Customer Invoice (Draft)
2. Send Invoice ke pelanggan (status → Sent)
3. Pelanggan membayar sebagian
   → Buat AR Receipt + alokasi ke invoice
   → Invoice otomatis → Partially Paid
4. Pelanggan membayar sisa
   → Buat AR Receipt + alokasi sisa
   → Invoice otomatis → Paid
```

### Skenario: Retur Barang

```
1. Invoice sudah Sent, amount_due = Rp 10.000.000
2. Pelanggan retur barang senilai Rp 2.000.000
   → Buat Credit Note (reason: Return), grand_total = Rp 2.000.000
   → Confirm CN
   → Apply CN ke invoice
   → Invoice amount_due berkurang jadi Rp 8.000.000
3. Pelanggan bayar sisa Rp 8.000.000
   → Buat AR Receipt
   → Invoice → Paid
```

### Skenario: Bad Debt Write-off

```
1. Invoice overdue, pelanggan tidak bisa bayar
2. Buat Credit Note (reason: Bad Debt), grand_total = sisa amount_due
3. Confirm → Apply
4. Invoice → Paid (piutang dihapusbukukan)
```

---

## 6. Pipeline Integration

Customer Invoices memiliki pipeline lifecycle:

- **Customer Invoice Lifecycle**: 7 states, 10 transitions

Pipeline memungkinkan:
- Pelacakan riwayat transisi status
- Konfigurasi permission per transisi
- Konfirmasi dan komentar wajib untuk transisi sensitif (Void)

---

## 7. Permissions

| Permission | Akses |
|-----------|-------|
| `customer_invoice` | View & list customer invoices |
| `customer_invoice.create` | Membuat invoice baru |
| `customer_invoice.edit` | Mengedit invoice |
| `customer_invoice.delete` | Menghapus invoice |
| `ar_receipt` | View & list AR receipts |
| `ar_receipt.create` | Membuat receipt baru |
| `ar_receipt.edit` | Mengedit receipt |
| `ar_receipt.delete` | Menghapus receipt |
| `credit_note` | View & list credit notes |
| `credit_note.create` | Membuat credit note baru |
| `credit_note.edit` | Mengedit credit note |
| `credit_note.delete` | Menghapus credit note |
| `ar_aging_report` | Akses AR Aging Report |
| `ar_outstanding_report` | Akses AR Outstanding Report |
| `customer_statement_report` | Akses Customer Statement Report |

---

## 8. Tips & Best Practices

1. **Selalu send invoice** sebelum menerima pembayaran — hanya invoice sent yang bisa dialokasi
2. **Gunakan Credit Note** untuk retur/koreksi — jangan edit invoice yang sudah sent
3. **Cek AR Aging Report** secara berkala untuk follow-up pelanggan yang overdue
4. **Customer Statement** berguna untuk rekonsiliasi dengan pelanggan
5. **Jangan hapus receipt** yang sudah confirmed — gunakan Void untuk audit trail
6. **Apply CN segera** setelah dikonfirmasi — CN yang belum applied tidak mengurangi piutang
7. **Satu CN per invoice** — jika perlu multiple pengurangan, buat beberapa CN

---

## FAQ & Tips

**Q:** Bagaimana cara mencatat penerimaan pembayaran dari pelanggan jika pelanggan membayar beberapa invoice sekaligus?

**J:** Buka menu **Penerimaan Piutang**, klik **Add New**. Isi header: pilih Customer, Branch, Fiscal Year, Receipt Date, Payment Method, Bank Account, dan Total Amount sesuai jumlah yang diterima. Kemudian klik **Add Allocation** untuk setiap invoice yang dilunasi. Pilih Customer Invoice yang sesuai, masukkan Allocated Amount (tidak boleh melebihi sisa amount_due invoice tersebut), dan klik Save. Ulangi untuk setiap invoice yang termasuk dalam pembayaran tersebut. Pastikan total seluruh alokasi tidak melebihi Total Amount di header. Setelah semua alokasi ditambahkan, klik Save pada form utama. Sistem otomatis membuat nomor receipt (format RCV-YYYY-XXXXXX) dan memperbarui status invoice terkait.

**Q:** Apa perbedaan status Receipt "Draft", "Confirmed", dan "Cancelled"? Kapan masing-masing digunakan?

**J:** Status **Draf** adalah status awal saat receipt baru dibuat dan belum diposting ke jurnal. Receipt masih bisa diedit atau dihapus. Status **Dikonfirmasi** berarti receipt sudah diposting — jurnal sudah tercatat, alokasi ke invoice sudah diterapkan, dan amount_received di invoice sudah bertambah. Receipt berstatus Dikonfirmasi tidak bisa diedit, hanya bisa di-void. Status **Dibatalkan** berarti receipt dibatalkan sebelum diposting (dari status Draf). Saat dibatalkan, alokasi dikembalikan dan invoice kembali ke status sebelumnya. Gunakan Draf untuk simulasi atau pengecekan ulang, Konfirmasi saat pembayaran sudah diterima dan siap dicatat secara akuntansi, dan Batalkan jika ada kesalahan input sebelum posting.

**Q:** Bagaimana cara mencari receipt tertentu dengan cepat?

**J:** Gunakan fitur pencarian di halaman AR Receipts. Anda bisa mencari berdasarkan:
- **Nomor Receipt** (mis. RCV-2026-000042) — ketik di kolom search
- **Nama Customer** — ketik nama pelanggan
- **Reference** — nomor referensi transfer atau dokumen eksternal

Selain pencarian teks, gunakan filter untuk mempersempit hasil:
- Filter **Status** untuk melihat receipt Draft, Confirmed, atau Cancelled
- Filter **Customer** untuk melihat semua receipt dari pelanggan tertentu
- Filter **Branch** untuk receipt per cabang
- Filter **Receipt Date** untuk rentang tanggal tertentu

Filter dan pencarian bisa dikombinasikan — misalnya mencari semua receipt Confirmed untuk Customer "PT Maju Jaya" di bulan Juni 2026.

**Q:** Apa hubungan antara AR Receipt dengan Customer Invoice? Bagaimana jika invoice sudah lunas?

**J:** AR Receipt adalah dokumen penerimaan pembayaran yang mengalokasikan dana ke satu atau beberapa Customer Invoice. Setiap alokasi di receipt mengurangi amount_due pada invoice terkait. Sistem otomatis memperbarui status invoice:
- Saat receipt di-Confirm, amount_received invoice bertambah, amount_due berkurang
- Jika amount_due menjadi 0, status invoice otomatis berubah menjadi **Dibayar**
- Jika amount_due berkurang tapi belum 0, status invoice menjadi **Dibayar Sebagian**

Invoice yang sudah **Dibayar** atau **Dibatalkan** atau **Batal** tidak akan muncul lagi di daftar invoice yang bisa dialokasi saat membuat receipt baru. Hanya invoice dengan status **Terkirim**, **Dibayar Sebagian**, atau **Jatuh Tempo** yang tersedia untuk alokasi.

**Q:** Bagaimana cara mengekspor data AR Receipts ke Excel?

**J:** Buka halaman **AR Receipts**. Terapkan filter sesuai kebutuhan (Status, Customer, Branch, atau Receipt Date) untuk menentukan data yang akan diekspor. Klik tombol **Export** di toolbar bagian atas. Sistem akan mengunduh file Excel (.xlsx) yang berisi semua receipt sesuai filter aktif. Kolom yang diekspor mencakup: Receipt Number, Customer, Branch, Receipt Date, Payment Method, Status, dan Total Amount. Jika tidak ada filter aktif, seluruh data receipt akan diekspor. Proses ekspor otomatis menerapkan limit dan sorting yang sama dengan tampilan DataTable.

**Q:** Metode pembayaran apa saja yang didukung? Apakah ada pengaruhnya terhadap akun yang digunakan?

**J:** Sistem mendukung enam metode pembayaran:
- **Bank Transfer** — transfer antar bank, gunakan akun bank yang sesuai
- **Cash** — pembayaran tunai, gunakan akun kas
- **Check** — cek dari pelanggan
- **Giro** — bilyet giro
- **Credit Card** — pembayaran kartu kredit
- **Other** — metode lain (mis. payment gateway, e-wallet)

Metode pembayaran menentukan akun kas/bank yang akan didebit di jurnal penerimaan. Pastikan memilih **Bank Account** yang sesuai dengan metode pembayaran. Misalnya, untuk Bank Transfer pilih akun bank penerima, untuk Cash pilih akun kas kecil/kas besar. Akun yang tersedia di dropdown adalah akun COA dengan tipe aset lancar (kas dan setara kas).

**Q:** Apa yang terjadi jika receipt yang sudah di-Confirm dihapus? Apakah invoice kembali ke status sebelumnya?

**J:** Receipt yang sudah berstatus **Dikonfirmasi** tidak bisa dihapus langsung — harus melalui proses **Batal**. Saat receipt di-void:
- Jurnal penerimaan yang sudah tercatat akan direverse (jurnal reversal)
- Semua alokasi ke invoice dikembalikan — amount_received di setiap invoice dikurangi
- amount_due di invoice bertambah kembali sesuai jumlah yang sebelumnya dialokasi
- Status invoice otomatis disesuaikan: jika sebelumnya Paid bisa kembali ke Sent atau Partially Paid, jika sebelumnya Partially Paid bisa kembali ke Sent

Proses Void memastikan audit trail tetap tercatat — receipt yang di-void tetap ada di sistem dengan status Dibatalkan/Batal, bukan hilang. Ini penting untuk kepatuhan audit dan rekonsiliasi.

**Q:** Apa tips terbaik untuk mengelola piutang pelanggan secara efektif menggunakan modul ini?

**J:** Beberapa tips praktis:
1. **Pantau AR Aging Report setiap minggu** — identifikasi pelanggan yang overdue lebih dari 30 hari dan lakukan follow-up segera. Semakin lama piutang menunggak, semakin sulit ditagih.
2. **Gunakan Reference field** — selalu isi nomor referensi transfer atau bukti pembayaran eksternal untuk memudahkan rekonsiliasi bank.
3. **Jangan tunda posting receipt** — receipt yang masih Draft tidak tercatat di jurnal dan tidak mengurangi piutang. Konfirmasi segera setelah pembayaran diterima.
4. **Manfaatkan Customer Statement** — kirimkan laporan transaksi ke pelanggan secara berkala (bulanan) untuk konfirmasi saldo. Ini mencegah dispute di kemudian hari.
5. **Gunakan Credit Note untuk koreksi, bukan menghapus receipt** — jika ada kesalahan nominal pembayaran, jangan void receipt lalu buat baru. Gunakan Credit Note untuk mencatat selisih. Ini menjaga jejak audit tetap bersih.
6. **Periksa Due Date saat membuat invoice** — pastikan tanggal jatuh tempo realistis. Invoice yang overdue menumpuk akan menyulitkan manajemen kas.
7. **Alokasi receipt tepat ke invoice yang benar** — jangan mengalokasikan pembayaran ke invoice yang salah. Jika terjadi kesalahan, void receipt dan buat baru dengan alokasi yang benar.
8. **Batasi akses Void dan Delete** — pastikan hanya pengguna dengan permission `ar_receipt.delete` yang bisa menghapus/membatalkan receipt untuk mencegah penyalahgunaan.

**Q:** Bagaimana jika pelanggan memberikan diskon atau potongan saat membayar? Apakah bisa dicatat di AR Receipt?

**J:** Ya, sistem mendukung pencatatan diskon pembayaran melalui field **Discount Given** di setiap alokasi. Saat menambahkan alokasi ke invoice, isi field Discount Given dengan nominal diskon yang diberikan. Sistem akan menghitung: allocated_amount + discount_given tidak boleh melebihi amount_due invoice. Diskon ini dicatat terpisah dari nilai pembayaran dan akan mempengaruhi jurnal (biasanya ke akun Diskon Penjualan). Alternatifnya, jika diskon berupa pengurangan harga setelah invoice terbit, gunakan **Credit Note** dengan reason "Discount" lalu apply ke invoice terkait.

**Q:** Apa yang harus dilakukan jika terjadi selisih pembayaran — misalnya pelanggan membayar lebih atau kurang dari jumlah invoice?

**J:** **Jika pelanggan membayar lebih** (overpayment): Catat receipt sebesar jumlah yang diterima. Alokasikan ke invoice sebesar amount_due invoice (sehingga invoice menjadi Paid). Sisa kelebihan akan muncul sebagai unallocated amount. Hubungi finance/admin untuk menentukan apakah kelebihan akan dikembalikan (refund) atau dicatat sebagai deposit/prepayment untuk invoice berikutnya.

**Jika pelanggan membayar kurang** (underpayment): Catat receipt sebesar jumlah yang diterima. Alokasikan ke invoice sebesar jumlah yang diterima. Invoice akan berubah menjadi Partially Paid. Sisa amount_due tetap tercatat di invoice dan bisa ditagih kemudian. Jika underpayment disebabkan oleh potongan/diskon yang disepakati, gunakan Credit Note untuk menutup selisihnya.

**Q:** Bisakah satu receipt digunakan untuk membayar invoice dari beberapa pelanggan berbeda?

**J:** Tidak. Satu AR Receipt hanya bisa dialokasikan ke invoice dari **satu pelanggan** yang sama — customer dipilih di header receipt. Jika Anda menerima pembayaran dari beberapa pelanggan sekaligus (misalnya via batch transfer), Anda harus membuat receipt terpisah untuk masing-masing pelanggan. Ini memastikan setiap pelanggan memiliki catatan penerimaan yang jelas dan memudahkan rekonsiliasi piutang per pelanggan.
