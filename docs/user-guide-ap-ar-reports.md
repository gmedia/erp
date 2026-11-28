# Panduan: Laporan Utang & Piutang

Panduan ini mencakup enam laporan utang (AP) dan piutang (AR) pada menu **Laporan**: Umur Utang,
Utang Belum Lunas, Riwayat Pembayaran Utang, Umur Piutang, Piutang Belum Lunas, dan Mutasi
Pelanggan. Semua halaman bersifat hanya baca dan dihitung dari dokumen tagihan, faktur, pembayaran,
serta penerimaan yang sudah tercatat.

## Gambaran Umum

| Laporan | URL | Isi utama |
|---|---|---|
| Laporan Umur Utang | `/reports/ap-aging` | Saldo utang per pemasok dikelompokkan menurut umur |
| Laporan Utang Belum Lunas | `/reports/ap-outstanding` | Tagihan pemasok yang masih memiliki sisa |
| Riwayat Pembayaran Utang | `/reports/ap-payment-history` | Pembayaran ke pemasok dalam suatu periode |
| Laporan Umur Piutang | `/reports/ar-aging` | Saldo piutang per pelanggan dikelompokkan menurut umur |
| Laporan Piutang Belum Lunas | `/reports/ar-outstanding` | Faktur pelanggan yang masih memiliki sisa |
| Laporan Mutasi Pelanggan | `/reports/customer-statement` | Riwayat transaksi dan saldo berjalan satu pelanggan |

## Akses & Izin

| Laporan | Permission |
|---|---|
| Laporan Umur Utang | `ap_aging_report` |
| Laporan Utang Belum Lunas | `ap_outstanding_report` |
| Riwayat Pembayaran Utang | `ap_payment_history_report` |
| Laporan Umur Piutang | `ar_aging_report` |
| Laporan Piutang Belum Lunas | `ar_outstanding_report` |
| Laporan Mutasi Pelanggan | `customer_statement_report` |

Permission yang sama dipakai untuk melihat laporan dan menekan tombol **Ekspor**. Pengguna tanpa
permission akan melihat pesan akses ditolak.

## Menu & Navigasi

Semua laporan berada di grup **Laporan** pada sidebar:

- **Laporan > Laporan Umur Utang**
- **Laporan > Laporan Utang Belum Lunas**
- **Laporan > Riwayat Pembayaran Utang**
- **Laporan > Laporan Umur Piutang**
- **Laporan > Laporan Piutang Belum Lunas**
- **Laporan > Laporan Mutasi Pelanggan**

Setiap halaman menampilkan kolom pencarian, panel **Filter**, menu **Kolom**, dan tombol **Ekspor**.
Panel filter memakai tombol **Terapkan filter**, **Atur ulang**, dan **Hapus semua**.

## 1. Laporan Umur Utang

Menampilkan saldo tagihan pemasok yang belum dibayar, dikelompokkan menurut umur.

1. Isi **Cari** untuk mencocokkan nomor tagihan, faktur pemasok, pemasok, cabang, nomor PO, nomor
   penerimaan barang, atau catatan.
2. Pilih **Pemasok**, **Cabang**, dan **Status** bila perlu.
3. Atur **Per Tanggal** sebagai tanggal perhitungan umur.
4. Terapkan filter.

Kolom yang ditampilkan: **No. Tagihan**, **Pemasok**, **Cabang**, **Tanggal Jatuh Tempo**,
**Total Akhir**, **Sisa Tagihan**, **Belum Jatuh Tempo**, **1-30 Hari**, **31-60 Hari**,
**61-90 Hari**, **Lebih 90 Hari**, dan **Status**. Baris total di bawah tabel merangkum sisa
tagihan dan setiap kelompok umur. Bila tanggal perhitungan diisi, halaman menampilkan keterangan
**Per <tanggal>**.

Status tagihan yang dapat difilter: **Dikonfirmasi**, **Dibayar Sebagian**, dan **Jatuh Tempo**.

## 2. Laporan Utang Belum Lunas

Menampilkan tagihan pemasok yang masih memiliki saldo belum dibayar.

1. Isi **Cari**, lalu pilih **Pemasok**, **Cabang**, dan **Status**.
2. Atur **Jatuh Tempo Dari** dan **Jatuh Tempo Sampai** bila perlu.
3. Terapkan filter.

Kolom: **No. Tagihan**, **Pemasok**, **Cabang**, **Tanggal Jatuh Tempo**, **Total Akhir**,
**Jumlah Dibayar**, **Sisa Tagihan**, **Days Overdue**, dan **Status**. Ringkasan di bawah tabel
menampilkan **Total Akhir** dan **Sisa Tagihan**.

## 3. Riwayat Pembayaran Utang

Menampilkan pembayaran ke pemasok dalam periode terpilih.

1. Isi **Cari** untuk mencocokkan nomor pembayaran, pemasok, cabang, rekening bank, referensi,
   atau catatan.
2. Pilih **Pemasok**, **Cabang**, **Metode Pembayaran**, dan **Status**.
3. Atur **Tanggal Pembayaran Dari** dan **Tanggal Pembayaran Sampai**.
4. Terapkan filter.

Kolom: **No. Pembayaran**, **Pemasok**, **Cabang**, **Tanggal Pembayaran**, **Metode Pembayaran**,
**Rekening Bank**, **Total Nilai**, **Dialokasikan**, **Belum Dialokasikan**, dan **Status**.
Ringkasan menampilkan **Total Nilai**, **Dialokasikan**, dan **Belum Dialokasikan**.

Pilihan metode pembayaran: **Transfer Bank**, **Tunai**, **Cek**, **Giro**, dan **Lainnya**.
Status pembayaran yang dapat difilter: **Dikonfirmasi** dan **Direkonsiliasi**.

## 4. Laporan Umur Piutang

Menampilkan saldo piutang pelanggan yang belum dibayar, dikelompokkan menurut umur.

1. Isi **Cari** untuk mencocokkan nomor faktur, pelanggan, atau cabang.
2. Pilih **Pelanggan**, **Cabang**, dan **Status**.
3. Atur **Tanggal Mulai** dan **Tanggal Akhir** bila perlu.
4. Terapkan filter.

Kolom: **No. Faktur**, **Pelanggan**, **Cabang**, **Status**, **Total Akhir**, **Jumlah Diterima**,
**Jumlah Nota Kredit**, **Sisa Tagihan**, **Belum Jatuh Tempo**, **1-30 Hari**, **31-60 Hari**,
**61-90 Hari**, dan **Lebih 90 Hari**. Ringkasan di bawah tabel merangkum sisa tagihan dan setiap
kelompok umur.

Status faktur yang dapat difilter: **Draf**, **Terkirim**, **Dibayar Sebagian**, **Dibayar**,
**Jatuh Tempo**, **Dibatalkan**, dan **Batal**.

## 5. Laporan Piutang Belum Lunas

Menampilkan faktur pelanggan yang masih memiliki saldo belum dibayar.

1. Isi **Cari**, lalu pilih **Pelanggan**, **Cabang**, dan **Status**.
2. Atur **Tanggal Mulai** dan **Tanggal Akhir** bila perlu.
3. Terapkan filter.

Kolom: **No. Faktur**, **Pelanggan**, **Cabang**, **Status**, **Total Akhir**, **Jumlah Diterima**,
**Jumlah Nota Kredit**, **Sisa Tagihan**, dan **Days Overdue**. Ringkasan menampilkan **Total
Akhir**, **Jumlah Diterima**, **Jumlah Nota Kredit**, dan **Sisa Tagihan**.

Status yang dapat difilter: **Terkirim**, **Dibayar Sebagian**, dan **Jatuh Tempo**.

## 6. Laporan Mutasi Pelanggan

Menampilkan riwayat transaksi dan saldo berjalan untuk satu pelanggan.

1. Isi **Cari** untuk mencocokkan nomor faktur.
2. Pilih **Pelanggan** (wajib). Selama belum dipilih, halaman menampilkan petunjuk untuk memilih
   pelanggan.
3. Atur **Tanggal Mulai** dan **Tanggal Akhir**.
4. Terapkan filter.

Kolom: **No. Faktur**, **Pelanggan**, **Cabang**, **Status**, **Total Akhir**, **Jumlah Diterima**,
**Jumlah Nota Kredit**, **Sisa Tagihan**, dan **Saldo Berjalan**.

## Ekspor

Tombol **Ekspor** mengunduh data sesuai filter aktif dalam berkas Excel (`.xlsx`). Terapkan filter
terlebih dahulu agar berkas hanya memuat data yang dibutuhkan.

## Aturan & validasi penting

- Kolom **Cari** menerima teks maksimal 255 karakter.
- Pemasok, pelanggan, dan cabang yang dipilih harus terdaftar pada data master.
- Status Umur Utang dan Utang Belum Lunas: `confirmed`, `partially_paid`, `overdue`.
- Status Riwayat Pembayaran Utang: `confirmed` dan `reconciled`.
- Status Umur Piutang menerima seluruh status faktur pelanggan; Piutang Belum Lunas hanya
  `sent`, `partially_paid`, dan `overdue`.
- Metode pembayaran menerima `bank_transfer`, `cash`, `check`, `giro`, dan `other`.
- Tanggal akhir tidak boleh lebih awal dari tanggal mulai.
- Laporan Umur Utang memakai tanggal perhitungan (**Per Tanggal**); Umur Piutang memakai rentang
  **Tanggal Mulai** dan **Tanggal Akhir**.
- Semua laporan hanya membaca data yang sudah tercatat; tidak ada tambah, ubah, atau hapus dari
  halaman laporan.

## Tanya jawab singkat

**Mengapa tagihan tidak muncul di laporan?**
Periksa status tagihan. Laporan hanya memuat tagihan dengan sisa saldo; tagihan yang sudah lunas
atau dibatalkan tidak tampil.

**Apa arti kelompok umur?**
**Belum Jatuh Tempo** berarti belum melewati tanggal jatuh tempo. **1-30 Hari**, **31-60 Hari**,
**61-90 Hari**, dan **Lebih 90 Hari** menunjukkan jumlah hari keterlambatan.

**Apa beda Laporan Umur Utang dan Utang Belum Lunas?**
Laporan Umur Utang mengelompokkan saldo menurut umur, sedangkan Utang Belum Lunas mendaftar tagihan
yang masih memiliki sisa beserta jumlah hari keterlambatannya.

**Mengapa Saldo Berjalan berbeda dari Sisa Tagihan?**
**Saldo Berjalan** adalah akumulasi transaksi pelanggan sampai baris tersebut, sedangkan
**Sisa Tagihan** hanya nilai faktur yang belum dibayar pada baris itu.

**Mengapa ekspor kosong?**
Pastikan filter sudah diterapkan dan tidak ada kombinasi filter yang terlalu sempit, misalnya
pelanggan yang tidak memiliki transaksi pada rentang tanggal tersebut.
