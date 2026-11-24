# Catatan Upgrade: Selaraskan Permission Route

Dokumen ini untuk administrator yang memasang pembaruan permission route (Refs #389).

## Ringkasan Perubahan

Permission route diselaraskan dengan konvensi:

| Metode HTTP | Permission |
|-------------|-----------|
| GET (index, show, export) | `modul` |
| POST create / import | `modul.create` |
| PUT / PATCH | `modul.edit` |
| DELETE | `modul.delete` |
| Aksi khusus (close, reopen, post, apply, approve dokumen non-approval-flow, lock, complete, match, execute, calculate, sync items, submit, test-smtp) | `modul.edit` |

Alur approver (`my-approvals`, `review-assignment`) dan endpoint milik sendiri (profile, notifications, device-tokens) tidak berubah. Endpoint entity-states tidak berubah.

`period-closings` memakai permission `period_closing` beserta aksinya.

## Permission Baru

Migration `2026_11_24_090000_add_edit_permissions_for_period_closing_and_user` menambahkan permission yang dipakai route dan belum ada di instalasi. Permission yang sudah ada tidak diubah, dan migration aman dijalankan ulang. Dua yang baru pada semua instalasi: Tidak ada izin yang diberikan otomatis, kecuali pengecekan pemulihan di bawah.

| Permission | Fungsi |
|-----------|--------|
| `period_closing.edit` | Aksi tutup/buka periode |
| `user.edit` | Mengatur user dan permission karyawan |

## Yang Perlu Diberikan per Peran

Setelah upgrade, peran yang sebelumnya bisa mengubah data perlu izin aksi baru agar tidak kehilangan akses:

- **Peran pengelola modul** (mis. purchasing, keuangan, gudang): berikan `modul.create`, `modul.edit`, dan `modul.delete` sesuai tugasnya.
- **Pemohon peminjaman kendaraan**: berikan `vehicle_loan.request`.
- **Peran penutup periode**: berikan `period_closing` dan `period_closing.edit`.
- **Peran administrator user**: berikan `user` dan `user.edit`.
- **Peran hanya baca**: cukup `modul` (GET).

Pemberian izin dilakukan dari menu pengaturan permission karyawan.

## Pemulihan Akses Admin

Migration memberikan `user.edit` secara otomatis hanya bila tidak ada satu pun karyawan yang memegang `user.edit` setelah migration. Ini mencegah terkunci total. Jika tetap tidak ada yang punya `user.edit`, berikan lewat command:

```bash
php artisan permissions:grant-user-edit {email}
```

Ganti `{email}` dengan email karyawan yang akan diberi izin. Command akan gagal dengan pesan jelas bila permission belum ada (jalankan migrate dahulu) atau email tidak ditemukan.

## Langkah Upgrade

1. Pull branch atau rilis terbaru.
2. Jalankan `php artisan migrate --force`.
3. Periksa karyawan yang memegang `user.edit`. Bila belum ada, jalankan command pemulihan di atas.
4. Berikan izin aksi baru sesuai tabel peran di atas.

## Verifikasi

- Login sebagai karyawan yang diberi izin, lalu buka modul terkait.
- Aksi tambah, ubah, dan hapus ditolak bila izin aksinya belum dimiliki. Tombolnya masih dapat terlihat sampai penyesuaian tampilan dirilis.
