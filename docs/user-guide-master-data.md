# Panduan: Master Data Sederhana

Panduan ini mencakup lima data master dengan pola yang sama: **Departemen**, **Jabatan**,
**Cabang**, **Kategori Pelanggan**, dan **Kategori Pemasok**. Semua hanya memiliki satu kolom
utama, yaitu nama.

## Gambaran Umum

Data master sederhana dipakai sebagai pilihan pada formulir modul lain:

- **Departemen** mengelompokkan karyawan dan aset.
- **Jabatan** menentukan posisi karyawan.
- **Cabang** dipakai di seluruh transaksi dan laporan.
- **Kategori Pelanggan** mengelompokkan pelanggan.
- **Kategori Pemasok** mengelompokkan pemasok.

## Akses & Izin

| Menu | Permission dasar |
|---|---|
| Departemen | `department` |
| Jabatan | `position` |
| Cabang | `branch` |
| Kategori Pelanggan | `customer_category` |
| Kategori Pemasok | `supplier_category` |

Permission dasar dipakai untuk melihat daftar dan detail. Tambah memakai akhiran `.create`, ubah
memakai `.edit`, dan hapus memakai `.delete`; contohnya `department.create` dan
`customer_category.delete`. Tombol **Ekspor** memakai permission dasar; tidak ada permission
ekspor terpisah.

## Menu & Navigasi

Semua menu berada di grup **Master Data**:

- **Master Data > Departemen**
- **Master Data > Jabatan**
- **Master Data > Cabang**
- **Master Data > Kategori Pelanggan**
- **Master Data > Kategori Pemasok**

Setiap halaman menampilkan tabel data, kolom pencarian, serta tombol **Tambah** dan **Ekspor**.

## Menambah data

1. Buka menu yang dituju.
2. Klik **Tambah**.
3. Isi **Nama** (mis. `Keuangan`, `Staf Akuntansi`, `Jakarta`, `Pelanggan Korporat`, atau
   `Pemasok Bahan Baku`).
4. Klik **Simpan**.

Nama wajib diisi, maksimal 255 karakter, dan minimal 2 karakter di formulir. Nama harus unik di
modul yang sama.

## Mengubah data

Klik **Ubah** pada baris yang dituju, ubah nama, lalu klik **Simpan**. Nama baru harus tetap unik.

## Menghapus data

Klik **Hapus** pada baris yang dituju dan konfirmasi. Dampak penghapusan berbeda per modul:

- **Departemen** dan **Jabatan**: data karyawan dan aset yang memakainya tetap ada, tetapi kolom
  departemen atau jabatannya menjadi kosong.
- **Cabang**: tidak dapat dihapus bila masih dipakai aset, gudang, atau transaksi. Periksa dan
  pindahkan data terkait terlebih dahulu.
- **Kategori Pelanggan**: pelanggan yang memakai kategori tersebut ikut terhapus. Bila pelanggan
  masih dirujuk transaksi, penghapusan dapat gagal.
- **Kategori Pemasok**: pemasok yang memakai kategori tersebut ikut terhapus. Bila pemasok masih
  dirujuk transaksi, penghapusan dapat gagal.

## Mencari dan mengurutkan

- **Cari** mencocokkan nama data pada modul yang sedang dibuka.
- Klik judul kolom untuk mengurutkan berdasarkan nama, waktu dibuat, atau waktu diperbarui.

## Melihat detail

Klik **Lihat** untuk menampilkan nama serta waktu dibuat dan diperbarui.

## Ekspor

Klik **Ekspor** untuk mengunduh hasil pencarian ke Excel. Kolom yang diekspor: ID, Nama, Dibuat
Pada, dan Diperbarui Pada.

## Aturan & validasi penting

- **Nama** wajib, unik per modul, maksimal 255 karakter, dan minimal 2 karakter di formulir.
- Tidak ada kolom deskripsi pada lima modul ini.
- Hapus data master hanya setelah memastikan tidak ada data lain yang masih memakainya.
- Kategori pelanggan dan kategori pemasok memakai penghapusan berantai; menghapus kategori akan
  mencoba menghapus pelanggan atau pemasok terkait. Bila data itu masih dirujuk transaksi,
  penghapusan dapat gagal.

## Tanya jawab singkat

**Mengapa nama ditolak?**
Nama harus unik di modul yang sama. Gunakan nama pembeda, misalnya `Pelanggan Korporat` dan
`Pelanggan Ritel`.

**Apakah menghapus departemen menghapus karyawan?**
Tidak. Karyawan tetap ada, tetapi kolom departemennya menjadi kosong.

**Mengapa cabang tidak bisa dihapus?**
Cabang masih dipakai gudang, aset, atau transaksi. Selesaikan atau pindahkan data terkait terlebih
dahulu.

**Apakah menghapus kategori pelanggan berbahaya?**
Ya. Pelanggan yang memakai kategori tersebut ikut terhapus. Pindahkan pelanggan ke kategori lain
terlebih dahulu bila masih dibutuhkan.
