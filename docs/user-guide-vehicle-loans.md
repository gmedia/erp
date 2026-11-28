# Panduan: Peminjaman Kendaraan

Modul ini mengelola permintaan, persetujuan, pengambilan, dan pengembalian kendaraan perusahaan. Semua formulir dapat diakses melalui QR yang tersedia di pos keamanan; halaman QR mensyaratkan login.

## Alur singkat

1. **Karyawan peminjam** memindai QR / membuka `/vehicle-loans/request`, mengisi form, menyimpan draf, lalu mengirim permintaan.
2. Form permintaan berisi: nama & departemen (otomatis dari akun), rentang tanggal & jam (format 24 jam), tujuan, pengemudi (opsional), dan unit kendaraan (opsional).
3. **Penyetuju** menerima notifikasi, membuka **Persetujuan Saya**, dapat mengganti pengemudi/unit, lalu menyetujui atau menolak beserta alasan.
4. Peminjam menerima notifikasi hasil persetujuan (termasuk alasan penolakan).
5. Setelah disetujui, peminjam membuka `/vehicle-loans/pickup`, memilih permintaan, mengunggah foto (selfie + SIM bila tanpa pengemudi; selfie bersama pengemudi bila ada pengemudi), lalu mengambil kunci di pos keamanan.
6. **Petugas keamanan** membuka `/vehicle-loans/security` (**Peminjaman Kendaraan** › Keamanan), memastikan status persetujuan, lalu menyerahkan kunci.
7. Setelah selesai, peminjam membuka `/vehicle-loans/return`, mengunggah foto serah terima & kondisi kendaraan, serta mencatat insiden bila ada.

## Hak akses

- `vehicle_loan`, `vehicle_loan.request`, `vehicle_loan.pickup`, `vehicle_loan.return`, `vehicle_loan.security`, `vehicle_loan.report`, `vehicle_loan.master`.
- Petugas keamanan hanya dapat melihat antrean dan menyerahkan kunci, tidak dapat menyetujui permintaan.

## Catatan

- Nama perusahaan/logo diatur lewat **Pengaturan Admin**, bukan kode.
- Foto disimpan privat dan hanya dapat dibuka oleh peminjam, penyetuju, dan petugas keamanan.
- Dasbor ringkasan: `/vehicle-loans/dashboard` (**Dasbor Peminjaman Kendaraan**).
