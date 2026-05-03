# User Guide: Accounts Receivable (AR)

## Gambaran Umum

Modul Accounts Receivable mengelola **siklus piutang lengkap** — dari penerbitan faktur pelanggan (Customer Invoices), penerimaan pembayaran (AR Receipts), hingga pengurangan piutang melalui nota kredit (Credit Notes). Modul ini terintegrasi dengan Pipeline dan Chart of Accounts.

---

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Customer Invoices | `/customer-invoices` | Kelola faktur pelanggan |
| AR Receipts | `/ar-receipts` | Kelola penerimaan pembayaran |
| Credit Notes | `/credit-notes` | Kelola nota kredit |
| AR Aging Report | `/reports/ar-aging-report` | Laporan umur piutang |
| AR Outstanding Report | `/reports/ar-outstanding-report` | Daftar faktur belum lunas |
| Customer Statement | `/reports/customer-statement-report` | Laporan transaksi per pelanggan |

---

## 1. Customer Invoices (Faktur Pelanggan)

### Membuat Invoice

1. Buka menu **Customer Invoices** → klik **Add New**
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
| **Draft** | Baru dibuat, masih bisa diedit |
| **Sent** | Dikirim ke pelanggan, piutang tercatat |
| **Partially Paid** | Sebagian sudah diterima pembayarannya |
| **Paid** | Lunas (amount_due = 0) |
| **Overdue** | Melewati jatuh tempo dan belum lunas |
| **Cancelled** | Dibatalkan sebelum sent |
| **Void** | Dibatalkan setelah sent |

> **Catatan**: Status `Partially Paid` dan `Paid` diupdate **otomatis** saat pembayaran diterima atau credit note diterapkan.

### Mengubah Status

- **Send**: Edit invoice → ubah status ke `Sent` → Save (otomatis set `sent_by` dan `sent_at`)
- **Cancel**: Edit invoice (status Draft) → ubah ke `Cancelled` → Save
- **Void**: Edit invoice (status Sent) → ubah ke `Void` → Save

---

## 2. AR Receipts (Penerimaan Pembayaran)

### Membuat Penerimaan

1. Buka menu **AR Receipts** → klik **Add New**
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

---

## 3. Credit Notes (Nota Kredit)

### Membuat Credit Note

1. Buka menu **Credit Notes** → klik **Add New**
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
  ↓ (setelah confirmed)
Void
```

| Status | Penjelasan |
|--------|------------|
| **Draft** | Baru dibuat, masih bisa diedit |
| **Confirmed** | Dikonfirmasi, siap diterapkan |
| **Applied** | Sudah diterapkan ke invoice, piutang berkurang |
| **Cancelled** | Dibatalkan sebelum confirmed |
| **Void** | Dibatalkan setelah confirmed |

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
- Tracking history transisi status
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
6. **Apply CN segera** setelah confirmed — CN yang belum applied tidak mengurangi piutang
7. **Satu CN per invoice** — jika perlu multiple pengurangan, buat beberapa CN
