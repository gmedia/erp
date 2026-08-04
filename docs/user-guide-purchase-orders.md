# User Guide: Purchase Orders

## Gambaran Umum

Modul Purchase Orders (PO) digunakan untuk membuat pesanan pembelian resmi ke supplier. PO dapat dibuat secara manual atau dikonversi dari Purchase Request (PR) yang sudah disetujui. PO mencatat detail item yang dipesan, harga, jadwal pengiriman, dan menjadi dasar untuk penerimaan barang (Goods Receipt).

Fitur utama:
- Pembuatan PO manual atau konversi dari PR
- Detail item dengan kuantitas, harga, dan diskon
- Tracking status dari draft hingga received
- Integrasi dengan Goods Receipt dan Supplier Return
- Export data ke Excel

[Screenshot: Daftar Purchase Orders]

## Menu & Navigasi

| Menu | Route | Fungsi |
|------|-------|--------|
| Purchase Orders | `/purchase-orders` | Daftar PO dengan filter, search, CRUD, export |
| Purchase Requests | `/purchase-requests` | Sumber PR untuk konversi ke PO |
| Goods Receipts | `/goods-receipts` | Penerimaan barang dari PO |
| Supplier Returns | `/supplier-returns` | Retur barang ke supplier |

## 1. Daftar Purchase Orders

Halaman `/purchase-orders` menampilkan tabel PO:

**Kolom tabel:**
- PO Number: Nomor PO (auto-generated, format: PO-XXXXX)
- Supplier: Nama supplier
- Warehouse: Gudang tujuan
- Order Date: Tanggal pemesanan
- Expected Delivery: Tanggal perkiraan tiba
- Status: draft, submitted, approved, ordered, partially_received, received, cancelled
- Grand Total: Total nilai PO

**Filter tersedia:**
- Search: berdasarkan PO number, payment terms, notes, atau shipping address
- Supplier: dropdown async select
- Warehouse: dropdown async select
- Status: dropdown status

**Sorting:** Semua kolom sortable.

[Screenshot: Tabel PO dengan filter]

## 2. Membuat Purchase Order Baru

Klik tombol **Add** untuk membuka form PO.

**Field form header:**
- Supplier: pilih supplier (wajib)
- Warehouse: pilih gudang tujuan (wajib)
- Order Date: tanggal pemesanan (wajib)
- Expected Delivery: tanggal perkiraan tiba (wajib)
- Payment Terms: syarat pembayaran (opsional)
- Shipping Address: alamat pengiriman (opsional)
- Notes: catatan tambahan (opsional)

[Screenshot: Form header PO]

### Menambahkan Item PO

Klik **Add Item** untuk menambahkan produk yang dipesan:

- Product: pilih produk (wajib)
- Unit: satuan produk (auto-terisi)
- Quantity: jumlah dipesan (wajib)
- Unit Price: harga satuan (wajib)
- Discount (%): diskon dalam persen (opsional)
- Tax (%): pajak dalam persen (opsional)
- Notes: catatan per item (opsional)

Grand Total dihitung dari subtotal semua item setelah diskon dan pajak.

[Screenshot: Form item PO]

Klik **Save**. PO dibuat dengan status **draft**.

## 3. Konversi dari Purchase Request

PO dapat dibuat otomatis dari PR yang sudah disetujui:

1. Buka PR yang berstatus **Approved**
2. Klik tombol **Convert to PO**
3. Form PO akan terisi otomatis dengan data dari PR
4. Sesuaikan jika diperlukan, lalu klik **Save**

## 4. Workflow Status PO

| Status | Keterangan |
|--------|------------|
| Draft | PO baru dibuat |
| Submitted | PO diajukan untuk approval |
| Approved | PO disetujui, siap dikirim ke supplier |
| Ordered | PO sudah dikirim ke supplier |
| Partially Received | Sebagian barang sudah diterima |
| Received | Semua barang sudah diterima |
| Cancelled | PO dibatalkan |

## 5. Penerimaan Barang (Goods Receipt)

Barang yang diterima dari PO dicatat di modul Goods Receipts. GR akan otomatis mengupdate status PO:
- Jika sebagian diterima → Partially Received
- Jika semua diterima → Received

## 6. Export Data

Klik tombol **Export** untuk mengunduh data PO ke Excel.

## FAQ

**Q: Apakah PO yang sudah received masih bisa diedit?**
A: Tidak. PO dengan status received atau partially_received tidak dapat diedit.

**Q: Bagaimana jika barang yang diterima tidak sesuai?**
A: Gunakan modul Supplier Returns untuk mencatat pengembalian barang ke supplier.

**Q: Apakah bisa membuat PO tanpa PR?**
A: Ya, PO bisa dibuat langsung tanpa melalui PR.
