# User Guide: Supplier Returns

## Gambaran Umum

Modul Supplier Returns mencatat pengembalian barang ke supplier. Retur dapat dilakukan berdasarkan Purchase Order (PO) atau Goods Receipt (GR) yang sudah dikonfirmasi. Setiap retur mencatat alasan pengembalian, kuantitas, dan dampaknya terhadap stok gudang.

Fitur utama:
- Pencatatan retur berdasarkan PO atau GR
- Berbagai alasan retur (rusak, tidak sesuai, kadaluarsa, dll)
- Otomatis mengurangi stok gudang
- Tracking status retur
- Export data ke Excel

[Screenshot: Daftar Supplier Returns]

## Menu & Navigasi

| Menu | Route | Fungsi |
|------|-------|--------|
| Supplier Returns | `/supplier-returns` | Daftar retur dengan filter, search, CRUD, export |
| Purchase Orders | `/purchase-orders` | PO sumber retur |
| Goods Receipts | `/goods-receipts` | GR sumber retur |

## 1. Daftar Supplier Returns

Halaman `/supplier-returns` menampilkan tabel retur:

**Kolom tabel:**
- Return Number: Nomor retur (auto-generated)
- PO Number: Nomor PO terkait
- GR Number: Nomor GR terkait
- Supplier: Nama supplier
- Warehouse: Gudang asal retur
- Return Date: Tanggal retur
- Reason: Alasan retur
- Status: draft, confirmed, cancelled

**Filter tersedia:**
- Search: berdasarkan return number atau notes
- Supplier: dropdown async select
- Warehouse: dropdown async select
- Status: dropdown status

**Sorting:** Semua kolom sortable.

[Screenshot: Tabel Supplier Returns dengan filter]

## 2. Membuat Supplier Return Baru

Klik tombol **Add** untuk membuka form retur.

**Field form header:**
- Purchase Order: pilih PO (wajib)
- Goods Receipt: pilih GR (opsional, filter berdasarkan PO)
- Supplier: auto-terisi dari PO
- Warehouse: gudang asal (auto-terisi)
- Return Date: tanggal retur (wajib)
- Reason: alasan retur (wajib)
- Notes: catatan tambahan (opsional)

[Screenshot: Form header Supplier Return]

### Item Retur

Setelah PO dipilih, item yang sudah diterima akan muncul. Untuk setiap item:

- Product: nama produk (read-only)
- Received Qty: jumlah yang sudah diterima (read-only)
- Return Qty: jumlah yang diretur (isi manual)
- Unit Price: harga satuan (read-only)
- Reason: alasan retur per item (opsional)

[Screenshot: Form item Supplier Return]

Klik **Save**. Retur dibuat dengan status **draft**.

## 3. Konfirmasi Retur

Klik tombol **Confirm** untuk mengkonfirmasi retur. Sistem akan mengurangi stok gudang sesuai return qty dan membuat jurnal retur.

## 4. Membatalkan Retur Confirmed

Retur yang sudah **confirmed** dapat dibatalkan dari halaman detail (tombol **Cancel**). Sistem akan menampilkan dialog konfirmasi berisi alasan pembatalan; isi alasan lalu konfirmasi.

Saat dibatalkan:
- Stok gudang dikembalikan lewat movement pembalik. Riwayat movement asli tidak dihapus.
- Jurnal retur dibalik dengan jurnal pembalik yang mencerminkan baris jurnal asli (akun dan nilai dibalik). Tanggal jurnal dan tanggal movement pembalik adalah tanggal pembatalan.
- Status retur menjadi **cancelled**.
- Pembatalan ditolak jika periode fiskal tanggal pembatalan sudah ditutup. Retur yang sudah cancelled tidak bisa dibatalkan lagi.

Pembatalan hanya tersedia untuk pengguna yang memiliki izin ubah supplier return.

## 5. Export Data

Klik tombol **Export** untuk mengunduh data retur ke Excel.

## FAQ

**Q: Apakah retur yang sudah confirmed bisa diedit?**
A: Tidak. Retur yang sudah confirmed tidak bisa diedit. Jika keliru, batalkan retur (lihat bagian 4) lalu buat retur baru.

**Q: Apakah retur yang sudah confirmed bisa dibatalkan?**
A: Bisa, lewat aksi Cancel dengan alasan. Stok dan jurnal dibalik otomatis, dan tanggal pembatalan dipakai sebagai tanggal pembalik.

**Q: Apakah retur bisa dilakukan tanpa GR?**
A: Ya, retur bisa langsung dari PO tanpa melalui GR.

**Q: Apa yang terjadi pada stok setelah retur dikonfirmasi?**
A: Stok di gudang akan berkurang sesuai return qty yang dikonfirmasi.
