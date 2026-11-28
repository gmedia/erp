# Panduan: Produk

Halaman **Produk** (`/products`) menyimpan daftar produk, bahan, dan jasa beserta kategori,
satuan, harga, dan statusnya. Data ini dipakai modul persediaan, pembelian, dan penjualan.

## Gambaran Umum

Setiap produk memiliki kode unik, nama, kategori, dan satuan. Cabang bersifat opsional. Harga
pokok dan harga jual dipakai sebagai nilai default. Jenis produk membedakan bahan baku, barang
setengah jadi, barang jadi, barang beli, dan layanan. Status menentukan apakah produk masih
dipakai dalam operasional.

## Akses & Izin

- Melihat daftar dan detail: permission **`product`**.
- Menambah produk: permission **`product.create`**.
- Mengubah produk: permission **`product.edit`**.
- Menghapus produk: permission **`product.delete`**.
- Tombol **Ekspor** memakai permission baca **`product`**; tidak ada permission ekspor terpisah.

Pengguna tanpa permission yang sesuai akan melihat pesan akses ditolak atau tombol tidak muncul.

## Menu & Navigasi

Buka menu **Master Data > Produk**. Halaman menampilkan tabel produk, kolom pencarian, tombol
filter, serta tombol **Tambah** dan **Ekspor**.

## Menambah produk

1. Klik **Tambah**.
2. Isi **Kode** (mis. `PRD-001`), **Nama** (mis. `Kertas A4 80 gsm`), dan **Deskripsi** bila perlu.
3. Pilih **Kategori**, **Satuan**, dan **Cabang** (opsional).
4. Pilih **Jenis**: **Bahan Baku**, **Barang Setengah Jadi**, **Barang Jadi**, **Barang Beli**,
   atau **Layanan**.
5. Isi **Harga Pokok** dan **Harga Jual** (angka, minimal 0).
6. Pilih **Model Penagihan**: **Sekali Bayar**, **Langganan**, atau **Keduanya**.
7. Pilih **Status**: **Aktif**, **Nonaktif**, atau **Dihentikan**.
8. Isi **Catatan** bila perlu, lalu klik **Simpan**.

Kategori dan satuan harus sudah ada. Buat lebih dahulu di menu **Kategori Produk** dan **Satuan**
bila belum tersedia.

## Mengubah produk

Klik **Ubah** pada baris yang dituju, ubah kolom yang diperlukan, lalu klik **Simpan**. Kode harus
tetap unik. Mengubah satuan atau kategori tidak mengubah transaksi yang sudah ada.

## Menghapus produk

Klik **Hapus** pada baris yang dituju dan konfirmasi. Produk yang sudah dipakai transaksi, seperti
kartu stok atau pesanan pembelian, tidak dapat dihapus. Penghapusan juga menghapus data terkait
produk, misalnya harga khusus, bill of material, dan dependensi produk.

## Mencari, memfilter, dan mengurutkan

- **Cari** mencocokkan kode, nama, dan deskripsi.
- Filter tersedia untuk **Kategori**, **Satuan**, **Cabang**, **Jenis**, dan **Status**.
- Klik judul kolom untuk mengurutkan naik atau turun.

Catatan: bila kolom **Cari** diisi, filter **Kategori**, **Satuan**, dan **Cabang** diabaikan,
sedangkan filter **Jenis** dan **Status** tetap berlaku. Kosongkan **Cari** bila ingin memfilter
berdasarkan kategori, satuan, atau cabang.

## Melihat detail

Klik **Lihat** untuk membuka modal detail yang memuat informasi umum, harga, model penagihan,
status, dan catatan.

## Ekspor

Klik **Ekspor** untuk mengunduh hasil sesuai filter ke Excel. Kolom yang diekspor: ID, Kode, Nama,
Jenis, Kategori, Satuan, Harga Pokok, Harga Jual, Status, dan Dibuat Pada.

## Aturan & validasi penting

- **Kode** wajib, maksimal 255 karakter, dan unik di seluruh produk. Formulir menolak kode kurang
  dari 2 karakter.
- **Nama** wajib, maksimal 255 karakter, dan minimal 2 karakter di formulir.
- **Kategori** dan **Satuan** wajib dipilih dan harus sudah terdaftar.
- **Cabang** opsional; bila dipilih, cabang harus ada.
- **Harga Pokok** dan **Harga Jual** wajib berupa angka minimal 0.
- **Jenis**, **Model Penagihan**, dan **Status** wajib dipilih dari pilihan yang tersedia.
- Deskripsi dan catatan bebas diisi dan tidak wajib.

## Tanya jawab singkat

**Apakah layanan harus punya satuan?**
Ya. Satuan wajib dipilih; gunakan satuan yang sesuai, misalnya `Jam` atau `Unit`.

**Mengapa produk tidak muncul di transaksi?**
Periksa **Status**. Produk **Nonaktif** atau **Dihentikan** sebaiknya tidak dipakai untuk transaksi
baru. Pastikan juga kategori dan satuannya masih ada.

**Bisakah kode produk diubah?**
Bisa, selama kode baru belum dipakai produk lain.

**Apakah harga jual mengikat semua transaksi?**
Tidak. Harga jual adalah nilai default pada data produk; dokumen transaksi tetap menyimpan harga
yang dipakai saat dokumen dibuat.
