# Peminjaman Kendaraan Perusahaan

Modul ini mengelola permintaan, persetujuan, pengambilan, dan pengembalian kendaraan perusahaan. Semua form dapat diakses melalui QR yang tersedia di pos security; halamanQR mensyaratkan login.

## Alur singkat

1. **Karyawan peminjam** scan QR / buka `/vehicle-loans/request`, isi form, simpan draft, lalu kirim permintaan.
2. Form permintaan berisi: nama & department (otomatis dari akun), rentang tanggal & jam, tujuan, driver (opsional), dan unit kendaraan (opsional).
3. **Approver** menerima notifikasi, membuka **My Approvals**, dapat mengganti driver/unit, lalu menyetujui atau menolak beserta alasan.
4. Peminjam menerima notifikasi hasil approval (termasuk alasan penolakan).
5. Setelah disetujui, peminjam membuka `/vehicle-loans/pickup`, memilih permintaan, mengunggah foto (selfie + SIM bila tanpa driver; selfie bersama driver bila ada driver), lalu mengambil kunci ke security.
6. **Security** membuka `/vehicle-loans/security`, memastikan status persetujuan, lalu menyerahkan kunci.
7. Setelah selesai, peminjam membuka `/vehicle-loans/return`, mengunggah foto serah terima & kondisi kendaraan, serta mencatat insiden bila ada.

## Hak akses

- `vehicle_loan`, `vehicle_loan.request`, `vehicle_loan.pickup`, `vehicle_loan.return`, `vehicle_loan.security`, `vehicle_loan.report`, `vehicle_loan.master`.
- Security hanya dapat melihat antrean dan menyerahkan kunci — tidak dapat menyetujui permintaan.

## Catatan

- Nama perusahaan/logo diatur lewat **Admin Settings**, bukan kode.
- Foto disimpan privat dan hanya dapat dibuka oleh peminjam, approver, dan security.
- Dashboard ringkasan: `/vehicle-loans/dashboard`.
