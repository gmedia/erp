# Panduan Kesehatan Sistem

Halaman **Kesehatan Sistem** (`/system-health`) membantu admin IT memantau kondisi ERP tanpa perlu membuka terminal. Halaman ini menampilkan status tiap komponen (sehat, peringatan, atau gagal), angka ringkas, riwayat masalah terakhir, dan informasi versi.

## Siapa yang bisa membuka halaman ini

Halaman hanya bisa diakses pengguna yang memiliki permission **`system_health`**. Pengguna lain akan melihat pesan akses ditolak. Menu ada di grup **Admin > System Health**.

## Membaca status

Setiap komponen ditampilkan sebagai kartu dengan tiga kemungkinan status:

| Status | Arti |
|---|---|
| **Sehat** | Pemeriksaan berhasil, nilai berada dalam batas normal. |
| **Peringatan** | Nilai sudah melewati ambang peringatan. Sistem masih berjalan, tetapi perlu diperhatikan. |
| **Gagal** | Pemeriksaan gagal atau nilai sudah melewati ambang gagal. Perlu tindakan segera. |

Setiap kartu juga menampilkan angka terkait (misalnya persentase disk) dan waktu pemeriksaan terakhir.

### Komponen yang diperiksa

| Komponen | Yang diperiksa |
|---|---|
| Database | Koneksi ke MariaDB, ukuran database, dan jumlah koneksi aktif. |
| Valkey / Redis | Respons ping dan memori yang terpakai. |
| Object storage | Menulis, membaca, lalu menghapus berkas kecil di bucket (MinIO/S3). |
| Migrasi | Apakah ada migrasi yang belum dijalankan. |
| Antrian | Jumlah job gagal dan apakah worker masih hidup. |
| Scheduler | Kapan scheduler terakhir berjalan (heartbeat). |
| Disk aplikasi | Sisa ruang pada storage aplikasi. |
| Konfigurasi produksi | Peringatan bila mode debug aktif di lingkungan produksi. |
| Versi | Versi aplikasi, PHP, dan Laravel. |
| CPU & memori container | Beban CPU dan pemakaian memori. |

> **Catatan CPU dan memori:** angka ini adalah milik **container** tempat aplikasi berjalan, bukan milik seluruh server host. Angka dapat berbeda dengan yang terlihat di monitor host.

## Memeriksa sekarang

Tekan tombol **Periksa sekarang** untuk menjalankan seluruh pemeriksaan saat itu juga. Waktu pemeriksaan pada kartu akan diperbarui setelah selesai. Tombol ini dibatasi sekitar 10 kali per menit untuk mencegah pemakaian berlebihan.

Selain itu, sistem memeriksa secara otomatis **setiap 5 menit**.

## Ambang batas

Ambang batas bawaan dapat diubah di **Pengaturan Admin**, pada grup **Kesehatan Sistem**:

| Pengaturan | Bawaan |
|---|---|
| Disk: peringatan di atas (%) | 85 |
| Disk: gagal di atas (%) | 95 |
| Memori container: peringatan di atas (%) | 80 |
| Memori container: gagal di atas (%) | 95 |
| Antrian: job gagal peringatan (jumlah) | 10 |
| Scheduler: tidak berjalan lebih dari (menit) | 10 |
| Database: koneksi aktif peringatan (jumlah) | 100 |

Pengaturan ini juga memuat **alamat email admin** untuk menerima notifikasi email.

## Notifikasi

Notifikasi dikirim **sekali setiap kali status sebuah komponen berubah** ke peringatan atau gagal, dan juga saat komponen kembali pulih. Dengan begitu notifikasi tidak dikirim berulang untuk masalah yang sama.

Notifikasi dikirim dalam dua bentuk:

- **Notifikasi di dalam aplikasi** untuk pengguna yang memiliki permission `system_health`.
- **Email** ke alamat admin yang diatur di Pengaturan Admin.

## Keamanan data

Halaman ini sengaja tidak menampilkan password, isi berkas `.env`, kredensial, atau host/IP internal secara lengkap. Pesan error sudah disaring sebelum ditampilkan.

## Memantau dari luar aplikasi

Halaman ini hanya bisa memberi tahu saat **aplikasi masih berjalan**. Jika aplikasi atau server mati seluruhnya, halaman dan notifikasi juga ikut tidak berjalan.

Untuk mendeteksi kondisi tersebut, gunakan **monitor eksternal** yang berjalan di luar server ERP, misalnya **Uptime Kuma**. Monitor eksternal memeriksa apakah alamat ERP masih bisa diakses dan mengirim peringatan bila tidak.

## Versi aplikasi

Bagian versi menampilkan versi aplikasi, PHP, dan Laravel yang sedang berjalan. Informasi ini membantu saat melaporkan masalah ke tim pengembang.
