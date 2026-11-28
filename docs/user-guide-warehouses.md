# Panduan: Gudang

Halaman **Gudang** (`/warehouses`) menyimpan lokasi penyimpanan yang menampung stok produk.
Gudang dipakai pada pesanan pembelian, penerimaan barang, transfer stok, penyesuaian stok, dan
stock opname.

## Gambaran Umum

Setiap gudang memiliki kode, nama, dan cabang. Kode harus unik dalam satu cabang, sedangkan nama
gudang unik di seluruh perusahaan. Satu cabang dapat memiliki beberapa gudang.

## Akses & Izin

- Melihat daftar dan detail: permission **`warehouse`**.
- Menambah gudang: permission **`warehouse.create`**.
- Mengubah gudang: permission **`warehouse.edit`**.
- Menghapus gudang: permission **`warehouse.delete`**.
- Tombol **Ekspor** memakai permission baca **`warehouse`**.

## Menu & Navigasi

Buka menu **Master Data > Gudang**. Halaman menampilkan tabel gudang, kolom pencarian, filter
**Cabang**, serta tombol **Tambah** dan **Ekspor**.

## Menambah gudang

1. Klik **Tambah**.
2. Isi **Kode** (mis. `GD-01`), maksimal 50 karakter.
3. Isi **Nama** (mis. `Gudang Utama`), maksimal 255 karakter.
4. Pilih **Cabang** (mis. `Jakarta`).
5. Klik **Simpan**.

Kode boleh sama antar cabang, tetapi tidak boleh sama dalam satu cabang. Nama gudang tidak boleh
sama dengan gudang lain.

## Mengubah gudang

Klik **Ubah** pada baris yang dituju, ubah kolom yang diperlukan, lalu klik **Simpan**. Bila cabang
diubah, pastikan kode gudang tetap unik di cabang tujuan.

## Menghapus gudang

Klik **Hapus** pada baris yang dituju dan konfirmasi. Gudang yang sudah dipakai transaksi, seperti
kartu stok, pesanan pembelian, penerimaan barang, atau transfer stok, tidak dapat dihapus. Stock
opname dan penyesuaian stok yang terkait gudang ikut terhapus.

## Mencari, memfilter, dan mengurutkan

- **Cari** mencocokkan kode dan nama gudang.
- Filter **Cabang** membatasi daftar pada satu cabang.
- Klik judul kolom untuk mengurutkan berdasarkan kode, nama, atau cabang.

Catatan: bila kolom **Cari** diisi, filter **Cabang** diabaikan. Kosongkan **Cari** bila ingin
memfilter per cabang.

## Melihat detail

Klik **Lihat** untuk melihat kode, nama, dan cabang gudang.

## Ekspor

Klik **Ekspor** untuk mengunduh hasil sesuai filter ke Excel. Kolom yang diekspor: ID, Kode, Nama,
Cabang, dan Dibuat Pada.

## Aturan & validasi penting

- **Kode** wajib, maksimal 50 karakter, dan unik dalam satu cabang.
- **Nama** wajib, maksimal 255 karakter, unik di seluruh gudang, dan minimal 2 karakter di formulir.
- **Cabang** wajib dipilih dan harus sudah terdaftar.
- Gudang tanpa transaksi dapat dihapus; gudang yang sudah dipakai tidak dapat dihapus.

## Tanya jawab singkat

**Bisakah dua cabang memakai kode gudang yang sama?**
Bisa. Keunikan kode dihitung per cabang.

**Mengapa nama gudang ditolak?**
Nama gudang harus unik di seluruh perusahaan. Gunakan nama yang membedakan, misalnya
`Gudang Utama Jakarta` dan `Gudang Utama Bandung`.

**Mengapa gudang tidak bisa dihapus?**
Gudang masih dirujuk oleh transaksi stok atau pembelian. Pindahkan atau selesaikan transaksi
tersebut terlebih dahulu.
