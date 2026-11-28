# Panduan: Kategori Produk

Halaman **Kategori Produk** (`/product-categories`) mengelompokkan produk dan bahan. Kategori
dipakai saat menambah produk dan membantu pencarian serta pelaporan.

## Gambaran Umum

Setiap kategori memiliki nama unik dan deskripsi opsional. Satu kategori dapat dipakai banyak
produk. Kategori membantu memisahkan jenis barang, misalnya elektronik, bahan baku, atau jasa.

## Akses & Izin

- Melihat daftar dan detail: permission **`product_category`**.
- Menambah kategori: permission **`product_category.create`**.
- Mengubah kategori: permission **`product_category.edit`**.
- Menghapus kategori: permission **`product_category.delete`**.
- Tombol **Ekspor** memakai permission baca **`product_category`**.

## Menu & Navigasi

Buka menu **Master Data > Kategori Produk**. Halaman menampilkan tabel kategori, kolom pencarian,
serta tombol **Tambah** dan **Ekspor**.

## Menambah kategori

1. Klik **Tambah**.
2. Isi **Nama** (mis. `Elektronik`). Nama minimal 2 karakter dan belum dipakai kategori lain.
3. Isi **Deskripsi** bila perlu (mis. `Perangkat dan aksesori elektronik`).
4. Klik **Simpan**.

## Mengubah kategori

Klik **Ubah** pada baris yang dituju, ubah nama atau deskripsi, lalu klik **Simpan**. Nama baru
harus tetap unik.

## Menghapus kategori

Klik **Hapus** pada baris yang dituju dan konfirmasi. **Perhatian:** penghapusan kategori akan
mencoba menghapus produk yang memakainya. Bila produk masih dirujuk transaksi, penghapusan dapat
gagal. Pindahkan produk ke kategori lain terlebih dahulu.

## Mencari dan mengurutkan

- **Cari** mencocokkan nama kategori.
- Klik judul kolom untuk mengurutkan berdasarkan nama, dibuat pada, atau diperbarui pada.

## Melihat detail

Klik **Lihat** untuk melihat nama, deskripsi, waktu dibuat, dan waktu diperbarui.

## Ekspor

Klik **Ekspor** untuk mengunduh hasil pencarian ke Excel. Kolom yang diekspor: ID, Nama, Deskripsi,
Dibuat Pada, dan Diperbarui Pada.

## Aturan & validasi penting

- **Nama** wajib, maksimal 255 karakter, unik, dan minimal 2 karakter di formulir.
- **Deskripsi** opsional dan boleh kosong.
- Kategori yang masih dipakai produk tidak dapat dihapus dengan aman; produk terkait dapat ikut
  terhapus atau penghapusan gagal karena transaksi.

## Tanya jawab singkat

**Apakah nama kategori boleh sama?**
Tidak. Nama kategori harus unik.

**Mengapa produk hilang setelah kategori dihapus?**
Relasi produk ke kategori memakai penghapusan berantai. Bila produk masih dirujuk transaksi,
penghapusan kategori dapat gagal. Pindahkan produk ke kategori lain terlebih dahulu.

**Apakah deskripsi wajib?**
Tidak. Deskripsi hanya membantu pengguna memahami isi kategori.
