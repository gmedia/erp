# Panduan: Memasang & Memperbarui Aplikasi Android (APK)

Panduan ini untuk pengguna yang memasang aplikasi ERP di Android **tanpa Play Store**. Aplikasi
diunduh sebagai berkas APK dari server ERP perusahaan Anda. Tanyakan tautan unduh dan versi terbaru
kepada administrator ERP bila belum menerimanya.

## 1. Sebelum memasang

- Pastikan Anda mengunduh APK dari server ERP perusahaan sendiri (bukan dari situs lain).
- Catat versi yang diumumkan administrator (mis. `1.4.0`) agar mudah dicocokkan setelah pemasangan.
- Pastikan perangkat memiliki ruang penyimpanan yang cukup.

## 2. Mengizinkan pemasangan dari sumber tidak dikenal

Android memblokir pemasangan APK di luar Play Store secara default.

1. Unduh APK terlebih dahulu, lalu buka berkasnya. Android akan menampilkan peringatan.
2. Pilih **Setelan** pada peringatan tersebut (atau buka **Setelan → Aplikasi → Akses khusus →
   Instal aplikasi tidak dikenal**).
3. Pilih aplikasi yang Anda pakai untuk mengunduh (mis. browser atau pengelola berkas), lalu
   aktifkan **Izinkan dari sumber ini**.
4. Kembali dan lanjutkan pemasangan. Setelah selesai, izin tersebut boleh dimatikan kembali.

Nama menu dapat berbeda tergantung versi Android dan merek perangkat.

## 3. Memasang aplikasi

1. Buka berkas `app-release.apk` yang sudah diunduh.
2. Pilih **Instal**, lalu tunggu hingga selesai.
3. Buka aplikasi, masukkan alamat server ERP bila diminta, lalu login dengan akun Anda.
4. Izinkan notifikasi bila aplikasi menawarkannya, agar pengingat dan persetujuan masuk.

Jika muncul peringatan Play Protect, pilih tetap melanjutkan hanya bila APK berasal dari server ERP
perusahaan Anda.

## 4. Memperbarui aplikasi

1. Unduh APK versi terbaru dari tautan yang diberikan administrator.
2. Buka berkas APK dan pilih **Perbarui** (bukan memasang aplikasi baru).
3. Setelah selesai, buka aplikasi dan pastikan nomor versi sesuai pengumuman.

Selama aplikasi dipasang dari sumber dan tanda tangan yang sama, pembaruan **tidak menghapus data
pengguna**. Anda tidak perlu mencopot pemasangan lebih dulu.

Jangan menurunkan versi secara manual. Bila pembaruan gagal karena versi lebih rendah, unduh versi
terbaru yang diumumkan administrator.

## 5. Memeriksa checksum (opsional)

Administrator dapat mengumumkan nilai SHA-256 untuk berkas APK. Jika ingin memastikan unduhan utuh,
bandingkan nilai tersebut dengan hasil perhitungan di komputer:

```bash
sha256sum app-release.apk
```

Nilai harus sama persis. Bila berbeda, unduh ulang berkasnya.

## 6. Masalah umum

| Gejala | Penyebab umum | Tindakan |
|---|---|---|
| "Aplikasi tidak terpasang" | Versi lebih rendah atau APK rusak | Unduh versi terbaru; pastikan unduhan selesai |
| "Aplikasi tidak terpasang" setelah ganti APK | Tanda tangan APK berbeda | Hubungi administrator; jangan copot dulu |
| Tidak ada tombol Instal | Izin sumber tidak dikenal belum aktif | Ulangi langkah bagian 2 |
| Login gagal setelah pembaruan | Sesi/token dicabut atau akun dinonaktifkan | Hubungi administrator |
| Notifikasi tidak masuk | Izin notifikasi dimatikan | Aktifkan izin notifikasi untuk aplikasi ERP |

## 7. Kehilangan perangkat atau akses

Hubungi administrator segera. Akses dapat dicabut dari server (token login dibatalkan atau akun
dinonaktifkan), sehingga aplikasi yang masih terpasang tidak dapat membuka data perusahaan.
