# Panduan: Izin Akses

## Gambaran Umum

Halaman **Izin Akses** (`/permissions`) dipakai untuk mengatur hak akses setiap karyawan. Hak akses
disimpan per karyawan, bukan per akun login. Karena itu, pilih karyawan yang sudah terhubung dengan
akun pengguna, lalu centang permission yang boleh dipakai.

Daftar permission berasal dari katalog sistem dan disusun berjenjang: permission induk untuk modul,
dengan permission anak untuk tindakan seperti tambah, ubah, hapus, atau ekspor.

## Akses & Izin

| Kebutuhan | Permission |
|---|---|
| Membuka menu Izin Akses dan membaca katalog permission | `permission` |
| Memilih karyawan dan membaca izin yang sudah dimiliki | `user` |
| Menyimpan perubahan izin karyawan | `user.edit` |

Jika salah satu permission di atas tidak dimiliki, menu, data, atau tombol **Save Changes** tidak
tersedia. Perubahan izin juga dicatat pada security log sebagai kejadian `role_changed`, lengkap
dengan daftar izin yang ditambah dan dihapus.

## Menu & Navigasi

- **Admin > Izin Akses**
- Halaman berisi pemilih karyawan dan panel **Permissions** berbentuk pohon centang.
- Selama belum ada karyawan dipilih, halaman menampilkan pesan **Belum ada karyawan dipilih** dan
  **Pilih karyawan di atas untuk melihat dan mengubah izin sistemnya.**

## Memilih karyawan

1. Buka **Admin > Izin Akses**.
2. Pada kolom **Select Employee**, ketik nama karyawan lalu pilih dari daftar.
3. Sistem memuat izin yang sudah dimiliki karyawan tersebut.
4. Untuk mengganti karyawan, pilih nama lain pada kolom yang sama. Pilihan sebelumnya otomatis
   diganti.

Karyawan yang belum memiliki akun pengguna tetap dapat diberi izin. Izin baru berlaku setelah akun
login karyawan tersebut dipakai.

## Memahami pohon izin

- Baris paling atas adalah nama modul, misalnya **Pipeline** atau **Alur Persetujuan**.
- Baris di bawahnya adalah tindakan yang dapat diberikan, misalnya **Create Pipeline**,
  **Edit Pipeline**, atau **Delete Pipeline**.
- Setiap kotak centang berdiri sendiri. Mencentang permission induk **tidak** otomatis mencentang
  permission anak, dan sebaliknya. Centang setiap tindakan yang memang diperlukan.
- Gunakan tombol panah di kiri untuk melipat atau membuka kelompok permission.
- Nama yang tampil adalah nama tampilan permission dari katalog sistem. Nama teknisnya dapat dilihat
  pada dokumentasi administrator.

## Mengubah izin karyawan

1. Pilih karyawan.
2. Centang permission yang akan diberikan, dan hapus centang permission yang akan dicabut.
3. Klik **Save Changes**.
4. Tunggu hingga muncul notifikasi bahwa izin berhasil diperbarui.

Penyimpanan bersifat **mengganti seluruh daftar**. Daftar yang dikirim menjadi daftar izin final
karyawan; izin yang tidak dicentang akan dicabut. Karena itu, periksa kembali seluruh centang
sebelum menyimpan.

## Aturan dan validasi penting

- **Satu karyawan, satu daftar izin.** Perubahan tidak memengaruhi karyawan lain.
- **Hanya ID permission yang valid** yang diterima. Permission yang tidak ada di katalog akan
  ditolak.
- **Perubahan langsung berlaku** setelah disimpan. Pengguna mungkin perlu memuat ulang halaman agar
  menu dan tombol mengikuti izin terbaru.
- **Permission ekspor** bersifat terpisah untuk modul yang menyediakannya, misalnya
  `approval_audit_trail.export`.
- **Hak akses minimum**: berikan permission sesuai tugas. Hindari memberikan permission
  administrator penuh tanpa kebutuhan yang jelas.

## Tanya jawab singkat

**Apakah saya bisa memberi izin ke akun pengguna langsung?**
Tidak dari halaman ini. Pilih karyawan yang terhubung dengan akun tersebut.

**Mengapa daftar izin kosong?**
Karyawan belum dipilih, atau katalog permission gagal dimuat. Pilih karyawan terlebih dahulu; bila
tetap kosong, hubungi administrator sistem.

**Apakah mencentang modul induk otomatis memberi semua tindakan?**
Tidak. Setiap kotak centang harus dipilih sendiri.

**Bagaimana cara mencabut semua izin?**
Hapus semua centang, lalu klik **Save Changes**. Karyawan tetap ada, tetapi tidak memiliki akses
modul sampai izin diberikan kembali.

**Di mana perubahan izin dapat diaudit?**
Buka **Admin > Log Keamanan**. Perubahan izin tercatat sebagai `role_changed` beserta daftar izin
yang ditambah dan dihapus.
