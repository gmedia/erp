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
