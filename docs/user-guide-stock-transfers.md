# Panduan: Transfer Stok

## Gambaran Umum

Modul Stock Transfers mencatat pemindahan stok barang antar gudang. Setiap transfer mencatat gudang asal, gudang tujuan, item yang dipindahkan, dan tanggal estimasi tiba. Transfer yang sudah dikonfirmasi akan mengurangi stok di gudang asal dan menambah stok di gudang tujuan.

Fitur utama:
- Pemindahan stok antar gudang
- Pelacakan status dari draf hingga diterima
- Tanggal transfer dan estimasi tiba
- Soft-cancel (status cancelled, bukan delete)
- Export data ke Excel

## Menu & Navigasi

| Menu | Route | Fungsi |
|------|-------|--------|
| Stock Transfers | `/stock-transfers` | Daftar transfer dengan filter, search, CRUD, export |
| Stock Movements | `/stock-movements` | Kartu stok yang mencatat semua pergerakan |
| Stock Monitor | `/stock-monitor` | Dashboard monitoring stok per gudang |

## 1. Daftar Stock Transfers

Halaman `/stock-transfers` menampilkan tabel transfer:

**Kolom tabel:**
- Transfer Number: Nomor transfer (auto-generated)
- From Warehouse: Gudang asal
- To Warehouse: Gudang tujuan
- Transfer Date: Tanggal transfer
- Expected Arrival: Estimasi tanggal tiba
- Status: draft, in_transit, received, cancelled

**Filter tersedia:**
- Search: berdasarkan transfer number atau notes
- From Warehouse: dropdown async select
- To Warehouse: dropdown async select
- Status: dropdown status

**Sorting:** Semua kolom sortable.

## 2. Membuat Stock Transfer Baru

Klik tombol **Add** untuk membuka form transfer.

**Field form header:**
- From Warehouse: pilih gudang asal (wajib)
- To Warehouse: pilih gudang tujuan (wajib, harus berbeda)
- Transfer Date: tanggal transfer (wajib)
- Expected Arrival: estimasi tanggal tiba (wajib)
- Notes: catatan tambahan (opsional)

### Menambahkan Item Transfer

Klik **Add Item** untuk menambahkan produk yang dipindahkan:

- Product: pilih produk (wajib, hanya produk yang ada di gudang asal)
- Unit: satuan produk (auto-terisi)
- Available Qty: stok tersedia di gudang asal (hanya baca)
- Transfer Qty: jumlah yang dipindahkan (wajib, maksimal available qty)
- Notes: catatan per item (opsional)

Klik **Save**. Transfer dibuat dengan status **draft**.

## 3. Workflow Transfer

| Status | Keterangan |
|--------|------------|
| Draft | Transfer baru dibuat, belum diproses |
| In Transit | Barang sedang dalam perjalanan |
| Received | Barang sudah diterima di gudang tujuan |
| Cancelled | Transfer dibatalkan (soft-cancel) |

## 4. Menerima Barang di Gudang Tujuan

Setelah transfer berstatus **Dalam Perjalanan**, barang dapat diterima di gudang tujuan:
1. Buka detail transfer
2. Klik tombol **Receive**
3. Stok di gudang asal berkurang, stok di gudang tujuan bertambah

### Nilai Persediaan Saat Transfer

- Saat transfer dikirim (In Transit), nilai barang dipindahkan dari akun **Persediaan** gudang asal ke akun **Persediaan dalam Perjalanan**. Nilainya = qty kirim x average cost gudang asal.
- Saat transfer diterima, nilai dipindahkan dari **Persediaan dalam Perjalanan** ke **Persediaan** gudang tujuan. Nilainya = qty terima x biaya kirim.
- Gudang tujuan menghitung average cost barunya dengan biaya kirim tersebut.
- Jika qty terima lebih kecil dari qty kirim, selisih kurang dicatat sebagai **Beban Selisih Persediaan** (qty kirim - qty terima) x biaya kirim.
- Transfer yang dibatalkan sebelum diterima dijurnal pembalik, sehingga saldo **Persediaan dalam Perjalanan** kembali nol untuk transfer tersebut.
- Akun Persediaan dalam Perjalanan diatur di **Admin Settings > Accounting**. Akun default tersedia di bagan akun (CoA) seed.
- Saldo barang dalam perjalanan ditampilkan di Stock Monitor dan laporan valuasi persediaan.

## 5. Membatalkan Transfer

Klik ikon **Delete** pada transfer berstatus **draft**. Sistem akan mengubah status menjadi **cancelled** (soft-cancel).

## 6. Export Data

Klik tombol **Export** untuk mengunduh data transfer ke Excel.

## FAQ

**Q: Apakah transfer bisa antar gudang yang sama?**
A: Tidak. From Warehouse dan To Warehouse harus berbeda.

**Q: Apakah bisa transfer melebihi stok yang tersedia?**
A: Tidak. Transfer Qty maksimal sesuai Available Qty di gudang asal.

**Q: Apakah transfer yang sudah received bisa dibatalkan?**
A: Tidak. Transfer dengan status received tidak dapat dibatalkan. Gunakan transfer baru untuk mengembalikan stok.
