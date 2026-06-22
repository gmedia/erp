# User Guide: Accounts Payable (AP)

## Gambaran Umum

Modul Accounts Payable mengelola **siklus hutang lengkap** — dari pencatatan tagihan vendor (Supplier Bills) hingga pelunasan pembayaran (AP Payments). Modul ini terintegrasi dengan Purchasing (PO/GR), Pipeline, dan Chart of Accounts.

---

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Supplier Bills | `/supplier-bills` | Kelola tagihan dari supplier |
| AP Payments | `/ap-payments` | Kelola pembayaran ke supplier |
| AP Aging Report | `/reports/ap-aging-report` | Laporan umur hutang |
| AP Outstanding Report | `/reports/ap-outstanding-report` | Daftar tagihan belum lunas |
| AP Payment History | `/reports/ap-payment-history-report` | Riwayat pembayaran |

---

## 1. Supplier Bills (Tagihan Vendor)

### Membuat Supplier Bill

1. Buka menu **Supplier Bills** → klik **Add New**
2. Isi header:
   - **Supplier** — pilih vendor penerima tagihan
   - **Branch** — cabang yang bertanggung jawab
   - **Fiscal Year** — tahun fiskal aktif
   - **Bill Date** — tanggal pencatatan tagihan
   - **Due Date** — tanggal jatuh tempo
   - **Payment Terms** — syarat pembayaran (opsional, mis. "Net 30")
   - **Supplier Invoice Number** — nomor faktur dari supplier (opsional)
   - **Currency** — mata uang (default: IDR)
3. Tambahkan item:
   - Klik **Add Item** untuk membuka dialog
   - Isi: Description, Account (akun beban/persediaan), Quantity, Unit Price, Discount %, Tax %
   - Line total dihitung otomatis
4. Klik **Save** — bill dibuat dengan status `Draft` dan nomor otomatis (BILL-2026-000001)

### Status Lifecycle

```
Draft → Confirmed → Partially Paid → Paid
  ↓                       ↓
Cancelled              Overdue (jika melewati due date)
                          ↓
                     Void (jurnal reversal)
```

| Status | Penjelasan |
|--------|------------|
| **Draft** | Baru dibuat, masih bisa diedit |
| **Confirmed** | Dikonfirmasi, menunggu pembayaran |
| **Partially Paid** | Sebagian sudah dibayar |
| **Paid** | Lunas (amount_due = 0) |
| **Overdue** | Melewati jatuh tempo dan belum lunas |
| **Cancelled** | Dibatalkan sebelum confirmed |
| **Void** | Dibatalkan setelah confirmed |

> **Catatan**: Status `Partially Paid` dan `Paid` diupdate **otomatis** saat pembayaran dialokasikan.

### Mengubah Status

- **Confirm**: Edit bill → ubah status ke `Confirmed` → Save
- **Cancel**: Edit bill (status Draft) → ubah ke `Cancelled` → Save
- **Void**: Edit bill (status Confirmed) → ubah ke `Void` → Save

---

## 2. AP Payments (Pembayaran)

### Membuat Pembayaran

1. Buka menu **AP Payments** → klik **Add New**
2. Isi header:
   - **Supplier** — supplier yang dibayar
   - **Branch** — cabang pembayar
   - **Fiscal Year** — tahun fiskal
   - **Payment Date** — tanggal pembayaran
   - **Payment Method** — Bank Transfer / Cash / Check / Giro / Other
   - **Bank Account** — akun kas/bank yang digunakan
   - **Total Amount** — nominal pembayaran
   - **Reference** — nomor referensi transfer (opsional)
3. Tambahkan alokasi:
   - Klik **Add Allocation** untuk membuka dialog
   - Pilih **Supplier Bill** yang akan dibayar
   - Isi **Allocated Amount** (tidak boleh melebihi sisa tagihan)
   - Isi **Discount Taken** jika ada diskon pembayaran dini (opsional)
4. Klik **Save** — payment dibuat dengan nomor otomatis (PAY-2026-000001)

### Multi-Bill Payment

Satu pembayaran bisa melunasi **beberapa tagihan sekaligus**:
- Tambahkan beberapa alokasi ke bill yang berbeda
- Total alokasi tidak boleh melebihi Total Amount pembayaran

### Partial Payment

Satu tagihan bisa dibayar **bertahap**:
- Payment 1: alokasi Rp 5.000.000 ke Bill A
- Payment 2: alokasi Rp 3.000.000 ke Bill A
- Bill A otomatis berubah ke `Partially Paid`, lalu `Paid` saat lunas

### Validasi Otomatis

| Validasi | Penjelasan |
|----------|------------|
| Over-allocation | Tidak bisa mengalokasikan lebih dari sisa tagihan |
| Bill status | Hanya bill `Confirmed`/`Partially Paid`/`Overdue` yang bisa dibayar |
| Amount sync | `amount_paid` dan `amount_due` di bill diupdate otomatis |

### Menghapus Pembayaran

Saat payment dihapus:
- Semua alokasi di-revert
- `amount_paid` di setiap bill yang terkait dikurangi
- Status bill otomatis disesuaikan (bisa kembali ke `Confirmed`)

---

## 3. Laporan

### AP Aging Report

Menampilkan tagihan yang belum lunas dikelompokkan berdasarkan umur:
- **Current** — belum jatuh tempo
- **1-30 hari** — overdue 1-30 hari
- **31-60 hari** — overdue 31-60 hari
- **61-90 hari** — overdue 61-90 hari
- **>90 hari** — overdue lebih dari 90 hari

**Filter**: Supplier, Branch, Status, Date Range

### AP Outstanding Report

Daftar semua tagihan yang belum lunas (status: Confirmed, Partially Paid, Overdue), diurutkan berdasarkan jatuh tempo.

**Filter**: Supplier, Branch, Due Date Range

### AP Payment History

Riwayat semua pembayaran yang sudah dikonfirmasi/reconciled.

**Filter**: Supplier, Branch, Payment Method, Date Range

### Export

Semua laporan bisa di-export ke **Excel (.xlsx)** dengan klik tombol Export.

---

## 4. Pipeline Integration

Supplier Bills dan AP Payments memiliki pipeline lifecycle yang bisa dikelola melalui Entity State Actions:

- **Supplier Bill Lifecycle**: 7 states, 10 transitions
- **AP Payment Lifecycle**: 6 states, 7 transitions

Pipeline memungkinkan:
- Tracking history transisi status
- Konfigurasi permission per transisi
- Konfirmasi dan komentar wajib untuk transisi sensitif (Void)

---

## 5. Permissions

| Permission | Akses |
|-----------|-------|
| `supplier_bill` | View & list supplier bills |
| `supplier_bill.create` | Membuat bill baru |
| `supplier_bill.edit` | Mengedit bill |
| `supplier_bill.delete` | Menghapus bill |
| `ap_payment` | View & list AP payments |
| `ap_payment.create` | Membuat payment baru |
| `ap_payment.edit` | Mengedit payment |
| `ap_payment.delete` | Menghapus payment |
| `ap_aging_report` | Akses AP Aging Report |
| `ap_outstanding_report` | Akses AP Outstanding Report |
| `ap_payment_history_report` | Akses AP Payment History Report |

---

## 6. Tips & Best Practices

1. **Selalu confirm bill** sebelum membuat pembayaran — hanya bill confirmed yang bisa dibayar
2. **Gunakan Payment Terms** untuk tracking jatuh tempo yang konsisten
3. **Cek AP Aging Report** secara berkala untuk identifikasi tagihan overdue
4. **Jangan hapus payment** yang sudah confirmed — gunakan Void untuk audit trail
5. **Supplier Invoice Number** membantu rekonsiliasi dengan faktur fisik dari vendor

---

## FAQ & Tips

**Q:** Bagaimana cara membuat pembayaran ke supplier yang benar?

**J:** Buka menu AP Payments, klik Add New. Isi header: pilih Supplier, Branch, Fiscal Year, Payment Date, Payment Method, dan Bank Account. Masukkan Total Amount sesuai nominal yang akan dibayarkan. Tambahkan alokasi dengan klik Add Allocation: pilih Supplier Bill yang ingin dibayar, isi Allocated Amount (tidak boleh melebihi sisa tagihan), dan opsional isi Discount Taken jika ada potongan pembayaran dini. Klik Save. Nomor payment (PAY-YYYY-NNNNNN) akan dibuat otomatis.

**Q:** Apa saja status yang dimiliki AP Payment dan apa artinya?

**J:** Status payment terdiri dari: **Draft** (baru dibuat, bisa diedit dan dihapus), **Pending Approval** (menunggu persetujuan dari approver), **Confirmed** (sudah dikonfirmasi dan jurnal otomatis terposting ke buku besar), **Reconciled** (sudah dicocokkan dengan rekening koran/bank statement), **Cancelled** (dibatalkan sebelum confirmed), dan **Void** (dibatalkan setelah confirmed dengan jurnal reversal).

**Q:** Apa yang terjadi ketika payment dikonfirmasi (Confirmed)?

**J:** Saat status diubah menjadi Confirmed, sistem otomatis mencatat siapa yang mengkonfirmasi dan kapan (confirmed_by, confirmed_at). Sistem juga otomatis memposting jurnal akuntansi (mendebit akun hutang supplier dan mengkredit akun bank/kas yang dipilih) melalui PostApPaymentJournalAction. Setelah confirmed, payment tidak bisa diedit bebas -- hanya bisa di-Void jika perlu dibatalkan.

**Q:** Apa yang terjadi jika payment dibatalkan atau dihapus?

**J:** Saat payment dihapus (status Draft/Cancelled), semua alokasi akan di-revert: amount_paid di setiap Supplier Bill yang terkait akan dikurangi, amount_due dihitung ulang, dan status bill otomatis disesuaikan (misalnya dari Paid kembali ke Partially Paid atau Confirmed). Jika payment sudah Confirmed dan ingin dibatalkan, gunakan status Void agar jurnal reversal tercatat dan audit trail tetap lengkap.

**Q:** Bagaimana cara mencari dan memfilter data pembayaran?

**J:** Gunakan kolom pencarian di halaman AP Payments untuk mencari berdasarkan payment number, reference, atau notes. Filter tersedia untuk Supplier (pilih supplier tertentu), Branch, Payment Method (Bank Transfer/Cash/Check/Giro/Other), Status (Draft/Pending Approval/Confirmed/Reconciled/Cancelled/Void), dan Date Range. Klik kolom header untuk mengurutkan data berdasarkan Payment Number, Supplier, Branch, Payment Date, Payment Method, Status, atau Total Amount.

**Q:** Bagaimana hubungan antara AP Payment dengan Supplier Bill?

**J:** Satu AP Payment bisa membayar satu atau beberapa Supplier Bill sekaligus (multi-bill payment). Sebaliknya, satu Supplier Bill bisa dibayar bertahap melalui beberapa AP Payment (partial payment). Saat payment dibuat, alokasi menghubungkan payment ke bill tertentu dengan nominal tertentu. Sistem otomatis memvalidasi bahwa allocated amount tidak melebihi sisa tagihan (amount_due) dan hanya bill dengan status Confirmed/Partially Paid/Overdue yang bisa dialokasikan.

**Q:** Bagaimana cara mengekspor data pembayaran ke Excel?

**J:** Buka halaman AP Payments. Gunakan filter yang tersedia untuk mempersempit data yang ingin diekspor (supplier, branch, payment method, status, date range). Klik tombol Export di toolbar. File Excel (.xlsx) akan diunduh dengan kolom: Payment Number, Supplier, Branch, Payment Date, Payment Method, Currency, Status, Total Amount, Total Allocated, Total Unallocated, Reference, Notes, Created By, dan Created At. Kolom yang diekspor akan sesuai dengan filter yang diterapkan.

**Q:** Apakah sistem mendukung multi-currency dalam pembayaran?

**J:** Ya, setiap AP Payment memiliki field Currency. Secara default menggunakan IDR, namun bisa diubah ke mata uang lain saat membuat payment. Total Amount dan alokasi akan mengikuti mata uang yang dipilih. Format tampilan nominal di tabel dan form akan menyesuaikan dengan regional settings (menggunakan format mata uang yang sesuai).

**Q:** Apa yang dimaksud dengan Total Unallocated pada tabel AP Payments?

**J:** Total Unallocated adalah selisih antara Total Amount pembayaran dengan total alokasi ke Supplier Bill (total_allocated). Jika Anda membuat payment sebesar Rp 10.000.000 tetapi baru mengalokasikan Rp 7.000.000 ke bill, maka Total Unallocated akan menampilkan Rp 3.000.000. Gunakan informasi ini untuk memastikan seluruh dana pembayaran sudah dialokasikan dengan benar ke tagihan yang sesuai.

**Q:** Bagaimana workflow approval untuk AP Payment?

**J:** Setelah payment dibuat dengan status Draft, ubah status ke Pending Approval untuk mengajukan persetujuan. Approver yang memiliki permission dapat menyetujui payment melalui menu My Approvals atau langsung di halaman edit payment. Setelah disetujui, status berubah menjadi Confirmed dan jurnal otomatis terposting. Jika payment ditolak, status bisa dikembalikan ke Draft untuk diperbaiki. Proses approval dicatat lengkap dengan approved_by dan approved_at untuk keperluan audit trail.
