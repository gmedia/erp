# Panduan: Lokasi Aset

Halaman **Lokasi Aset** (`/asset-locations`) menyimpan lokasi fisik di dalam cabang untuk
penempatan aset, misalnya gedung, lantai, ruang, atau rak. Lokasi dipakai pada data aset dan
mutasi aset.

## Gambaran Umum

Setiap lokasi memiliki kode, nama, cabang, dan lokasi induk opsional. Lokasi induk membentuk
hierarki, misalnya `Gedung A` → `Lantai 2` → `Ruang Server`. Kode lokasi harus unik dalam satu
cabang. Lokasi tanpa induk dianggap lokasi akar.

## Akses & Izin

- Melihat daftar dan detail: permission **`asset_location`**.
- Menambah lokasi: permission **`asset_location.create`**.
- Mengubah lokasi: permission **`asset_location.edit`**.
- Menghapus lokasi: permission **`asset_location.delete`**.
- Tombol **Ekspor** memakai permission baca **`asset_location`**.

## Menu & Navigasi

Buka menu **Master Data > Lokasi Aset**. Halaman menampilkan tabel lokasi, kolom pencarian, filter
**Cabang** dan **Lokasi Induk**, serta tombol **Tambah** dan **Ekspor**.

## Menambah lokasi

1. Klik **Tambah**.
2. Isi **Kode** (mis. `GD-A-R2`), maksimal 50 karakter.
3. Isi **Nama** (mis. `Ruang Server`), maksimal 255 karakter.
4. Pilih **Cabang** (mis. `Jakarta`).
5. Pilih **Lokasi Induk** bila lokasi berada di bawah lokasi lain; biarkan kosong untuk lokasi
   akar.
6. Klik **Simpan**.

## Mengubah lokasi

Klik **Ubah** pada baris yang dituju, ubah kolom yang diperlukan, lalu klik **Simpan**. Hindari
memilih lokasi itu sendiri atau turunannya sebagai lokasi induk agar hierarki tidak melingkar.

## Menghapus lokasi

Klik **Hapus** pada baris yang dituju dan konfirmasi. Penghapusan lokasi tidak menghapus aset.
Aset, lokasi anak, dan riwayat mutasi yang merujuk lokasi tersebut kehilangan referensi lokasi
(kolomnya menjadi kosong).

## Mencari, memfilter, dan mengurutkan

- **Cari** mencocokkan kode dan nama lokasi.
- Filter **Cabang** membatasi daftar pada satu cabang.
- Filter **Lokasi Induk** menampilkan lokasi di bawah induk tertentu.
- Klik judul kolom untuk mengurutkan berdasarkan kode, nama, cabang, atau lokasi induk.

Catatan: bila kolom **Cari** diisi, filter **Cabang** diabaikan, sedangkan filter **Lokasi Induk**
tetap berlaku.

## Melihat detail

Klik **Lihat** untuk melihat kode, nama, cabang, dan lokasi induk.

## Ekspor

Klik **Ekspor** untuk mengunduh hasil sesuai filter ke Excel. Kolom yang diekspor: ID, Kode, Nama,
Cabang, Lokasi Induk, dan Dibuat Pada.

## Aturan & validasi penting

- **Kode** wajib, maksimal 50 karakter, dan unik dalam satu cabang.
- **Nama** wajib, maksimal 255 karakter, dan minimal 2 karakter di formulir.
- **Cabang** wajib dipilih dan harus sudah terdaftar.
- **Lokasi Induk** opsional; bila diisi, lokasi induk harus sudah terdaftar.
- Cabang yang masih memiliki lokasi tidak dapat dihapus.

## Tanya jawab singkat

**Apakah kode lokasi boleh sama di cabang berbeda?**
Boleh. Keunikan kode dihitung per cabang.

**Apa yang terjadi pada aset saat lokasi dihapus?**
Aset tidak ikut terhapus, tetapi referensi lokasinya menjadi kosong. Pindahkan aset ke lokasi lain
terlebih dahulu bila lokasinya masih dibutuhkan.

**Bisakah lokasi dipindah ke cabang lain?**
Bisa melalui **Ubah**, tetapi pastikan kode lokasi tetap unik di cabang tujuan.
