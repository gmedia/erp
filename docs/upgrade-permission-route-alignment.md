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

Migration `2026_11_24_090000_add_edit_permissions_for_period_closing_and_user` menambahkan dua permission. Migration bersifat idempoten dan hanya menambah baris. Tidak ada izin yang diberikan otomatis, kecuali pengecekan pemulihan di bawah.

| Permission | Fungsi |
|-----------|--------|
| `period_closing.edit` | Aksi tutup/buka periode |
| `user.edit` | Mengatur user dan permission karyawan |

## Yang Perlu Diberikan per Peran

Setelah upgrade, peran yang sebelumnya bisa mengubah data perlu izin aksi baru agar tidak kehilangan akses:

- **Peran pengelola modul** (mis. purchasing, keuangan, gudang): berikan `modul.edit` dan `modul.create` sesuai tugasnya.
- **Peran penutup periode**: berikan `period_closing` dan `period_closing.edit`.
- **Peran administrator user**: berikan `user` dan `user.edit`.
- **Peran hanya baca**: cukup `modul` (GET).

Pemberian izin dilakukan dari menu pengaturan permission karyawan.

## Pemulihan Akses Admin

Migration memberikan `user.edit` secara otomatis hanya bila tidak ada satu pun karyawan yang memegang `user.edit` setelah migration. Ini mencegah terkunci total. Jika tetap tidak ada yang punya `user.edit`, berikan lewat command:

```bash
./vendor/bin/sail artisan permissions:grant-user-edit {email}
```

Ganti `{email}` dengan email karyawan yang akan diberi izin. Command akan gagal dengan pesan jelas bila permission belum ada (jalankan migrate dahulu) atau email tidak ditemukan.

## Langkah Upgrade

1. Pull branch atau rilis terbaru.
2. Jalankan `./vendor/bin/sail artisan migrate --force`.
3. Periksa karyawan yang memegang `user.edit`. Bila belum ada, jalankan command pemulihan di atas.
4. Berikan izin aksi baru sesuai tabel peran di atas.
5. Jalankan `./vendor/bin/sail artisan db:seed --force` bila memakai data demo. Seeder bersifat idempoten.

## Verifikasi

- Login sebagai karyawan yang diberi izin, lalu buka modul terkait.
- Tombol aksi hanya tampil dan berfungsi bila izin `modul.edit` atau `modul.create` dimiliki.
