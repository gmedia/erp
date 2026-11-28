# Panduan: Satuan

Halaman **Satuan** (`/units`) menyimpan satuan ukur yang dipakai produk dan transaksi, misalnya
kilogram, liter, pcs, atau jam. Satuan membantu menjaga jumlah dan harga tetap konsisten.

## Gambaran Umum

Setiap satuan memiliki nama unik dan simbol opsional. Simbol dipakai sebagai singkatan pada
tampilan, misalnya `kg` untuk kilogram. Satu satuan dapat dipakai banyak produk dan bill of
material.

## Akses & Izin

- Melihat daftar dan detail: permission **`unit`**.
- Menambah satuan: permission **`unit.create`**.
- Mengubah satuan: permission **`unit.edit`**.
- Menghapus satuan: permission **`unit.delete`**.
- Tombol **Ekspor** memakai permission baca **`unit`**.

## Menu & Navigasi

Buka menu **Master Data > Satuan**. Halaman menampilkan tabel satuan, kolom pencarian, serta
tombol **Tambah** dan **Ekspor**.

## Menambah satuan

1. Klik **Tambah**.
2. Isi **Nama** (mis. `Kilogram`). Nama wajib dan belum dipakai satuan lain.
3. Isi **Simbol** bila perlu (mis. `kg`), maksimal 10 karakter.
4. Klik **Simpan**.

## Mengubah satuan

Klik **Ubah** pada baris yang dituju, ubah nama atau simbol, lalu klik **Simpan**. Nama baru harus
tetap unik.

## Menghapus satuan

Klik **Hapus** pada baris yang dituju dan konfirmasi. **Perhatian:** penghapusan satuan akan
mencoba menghapus produk dan bill of material yang memakainya. Bila produk masih dirujuk
transaksi, penghapusan dapat gagal. Pindahkan produk ke satuan lain terlebih dahulu.

## Mencari dan mengurutkan

- **Cari** mencocokkan nama satuan.
- Klik judul kolom untuk mengurutkan berdasarkan nama, dibuat pada, atau diperbarui pada.

## Melihat detail

Klik **Lihat** untuk melihat nama, simbol, waktu dibuat, dan waktu diperbarui.

## Ekspor

Klik **Ekspor** untuk mengunduh hasil pencarian ke Excel. Kolom yang diekspor: ID, Nama, Simbol,
Dibuat Pada, dan Diperbarui Pada.

## Aturan & validasi penting

- **Nama** wajib, maksimal 255 karakter, unik, dan minimal 2 karakter di formulir.
- **Simbol** opsional, maksimal 10 karakter.
- Satuan yang masih dipakai produk tidak dapat dihapus dengan aman; produk dan bill of material
  terkait dapat ikut terhapus atau penghapusan gagal karena transaksi.

## Tanya jawab singkat

**Apakah simbol wajib?**
Tidak. Simbol hanya membantu menampilkan singkatan satuan.

**Bolehkah dua satuan memakai simbol sama?**
Boleh, karena yang harus unik adalah nama satuan. Tetap disarankan memakai simbol yang berbeda agar
tidak membingungkan.

**Mengapa produk hilang setelah satuan dihapus?**
Relasi produk ke satuan memakai penghapusan berantai. Bila produk masih dirujuk transaksi,
penghapusan satuan dapat gagal. Pindahkan produk ke satuan lain terlebih dahulu.
