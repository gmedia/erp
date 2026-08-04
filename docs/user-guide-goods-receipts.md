# User Guide: Goods Receipts

## Gambaran Umum

Modul Goods Receipts (GR) mencatat penerimaan barang dari supplier berdasarkan Purchase Order (PO). Setiap GR mencatat kuantitas yang diterima, diterima sebagian, atau ditolak, dan otomatis memperbarui status PO terkait.

Fitur utama:
- Penerimaan barang berdasarkan PO
- Pencatatan kuantitas diterima vs ditolak
- Supplier Delivery Note untuk referensi
- Otomatis update status PO
- Integrasi dengan Supplier Returns untuk retur
- Export data ke Excel

[Screenshot: Daftar Goods Receipts]

## Menu & Navigasi

| Menu | Route | Fungsi |
|------|-------|--------|
| Goods Receipts | `/goods-receipts` | Daftar GR dengan filter, search, CRUD, export |
| Purchase Orders | `/purchase-orders` | PO sumber penerimaan barang |
| Supplier Returns | `/supplier-returns` | Retur barang yang sudah diterima |

## 1. Daftar Goods Receipts

Halaman `/goods-receipts` menampilkan tabel GR:

**Kolom tabel:**
- GR Number: Nomor GR (auto-generated)
- PO Number: Nomor PO terkait
- Warehouse: Gudang penerima
- Receipt Date: Tanggal penerimaan
- Supplier Delivery Note: Nomor surat jalan supplier
- Status: draft, confirmed, cancelled

**Filter tersedia:**
- Search: berdasarkan GR number, supplier delivery note, atau notes
- Warehouse: dropdown async select
- Status: dropdown status
- PO Number: dropdown async select

**Sorting:** Semua kolom sortable.

[Screenshot: Tabel GR dengan filter]

## 2. Membuat Goods Receipt Baru

Klik tombol **Add** untuk membuka form GR.

**Field form header:**
- Purchase Order: pilih PO yang barangnya diterima (wajib)
- Warehouse: gudang penerima (auto-terisi dari PO)
- Receipt Date: tanggal penerimaan (wajib)
- Supplier Delivery Note: nomor surat jalan supplier (opsional)
- Notes: catatan tambahan (opsional)

[Screenshot: Form header GR]

### Item Penerimaan

Setelah PO dipilih, item akan otomatis terisi dari PO. Untuk setiap item:

- Product: nama produk (read-only)
- Ordered Qty: jumlah dipesan (read-only)
- Received Qty: jumlah diterima (isi manual)
- Accepted Qty: jumlah diterima kondisi baik
- Rejected Qty: jumlah ditolak
- Notes: catatan per item (opsional)

[Screenshot: Form item GR]

Klik **Save**. GR dibuat dengan status **draft**.

## 3. Konfirmasi GR

Setelah GR di-save, klik tombol **Confirm** untuk mengkonfirmasi penerimaan. Sistem akan:
- Mengupdate stok di gudang sesuai Accepted Qty
- Mengupdate status PO (partially_received atau received)
- Mencatat rejected qty untuk ditindaklanjuti

## 4. Melihat Detail GR

Klik ikon **View** untuk melihat detail GR termasuk item yang diterima dan ditolak.

[Screenshot: Detail GR dengan item diterima/ditolak]

## 5. Export Data

Klik tombol **Export** untuk mengunduh data GR ke Excel.

## FAQ

**Q: Apakah GR yang sudah confirmed bisa diedit?**
A: Tidak. GR yang sudah confirmed bersifat final.

**Q: Bagaimana jika ada barang yang ditolak?**
A: Barang yang ditolak dicatat di GR. Jika perlu retur ke supplier, gunakan modul Supplier Returns.

**Q: Bisakah satu GR untuk beberapa PO?**
A: Tidak. Satu GR hanya untuk satu PO. Buat GR terpisah untuk setiap PO.
