# Panduan: Rilis & Unduh Aplikasi Mobile

Panduan ini menjelaskan cara administrator menerbitkan rilis APK Android dari server ERP dan cara
pengguna mengunduh, memasang, serta memverifikasi aplikasinya. Aplikasi didistribusikan langsung
dari server perusahaan, bukan melalui Play Store.

## 1. Untuk pengguna: mengunduh aplikasi

1. Masuk ke ERP, lalu buka menu **Unduh Aplikasi** (`/mobile-app`). Halaman ini terbuka untuk semua
   pengguna yang sudah login.
2. Halaman menampilkan rilis aktif: nama versi, ukuran berkas, tanggal rilis, kode versi minimum
   yang didukung, checksum SHA-256, dan catatan rilis.
3. Pilih **Unduh APK**. Berkas diunduh dengan nama `<nama-perusahaan>-<versi>.apk`.
4. Bila belum ada rilis aktif, halaman menampilkan keterangan bahwa administrator belum menerbitkan
   rilis. Hubungi administrator ERP perusahaan Anda.

Untuk membuka halaman ini di ponsel, pindai **QR code** yang tersedia di halaman Unduh Aplikasi.

## 2. Memasang di Android

1. Unduh APK terlebih dahulu, lalu buka berkasnya.
2. Izinkan pemasangan dari sumber ini saat diminta
   (**Setelan → Aplikasi → Akses khusus → Instal aplikasi tidak dikenal**).
3. Pilih **Instal** dan tunggu hingga selesai, lalu buka aplikasinya.
4. Bila muncul peringatan Play Protect, lanjutkan hanya bila APK berasal dari server ERP perusahaan
   Anda.

Panduan pemasangan dan pembaruan yang lebih rinci ada di panduan **Pasang Aplikasi Android**.

## 3. Verifikasi SHA-256

Server menghitung checksum SHA-256 setiap APK. Cocokkan nilai yang tampil di halaman Unduh Aplikasi
dengan checksum berkas yang sudah diunduh. Nilainya harus sama persis.

- **Linux:** `sha256sum <nama-berkas>.apk`
- **macOS:** `shasum -a 256 <nama-berkas>.apk`
- **Windows (PowerShell):** `Get-FileHash .\<nama-berkas>.apk -Algorithm SHA256`

Bila checksum berbeda, **jangan pasang** berkas tersebut. Unduh ulang dari server ERP dan laporkan
kepada administrator bila tetap berbeda.

## 4. Versi minimum yang didukung

Setiap rilis menyimpan **kode versi minimum yang didukung** (`min_supported_version_code`). Perangkat
yang memakai kode versi lebih rendah dari nilai tersebut harus memperbarui aplikasi ke rilis aktif
agar tetap kompatibel dengan server.

Aplikasi mobile dapat memeriksa rilis terbaru melalui endpoint publik
`GET /api/mobile-app/latest` (tanpa berkas). Respons berisi `version_name`, `version_code`,
`min_supported_version_code`, `sha256`, dan `download_page_url`. Bila `version_code` perangkat lebih
rendah dari `min_supported_version_code`, pengguna diarahkan untuk memperbarui.

## 5. Untuk administrator: mengunggah rilis

Administrator memerlukan izin `mobile_app_release` (tersedia pada peran admin). Menu berada di grup
**Admin → Rilis Aplikasi Mobile** (`/mobile-app-releases`).

1. Pilih **Unggah Rilis**.
2. Pilih berkas **APK** (ekstensi `.apk`). Ukuran maksimum mengikuti pengaturan
   `mobile_app_max_upload_mb` (default 150 MB).
3. Isi **Nama versi** (mis. `1.4.0`), **Kode versi** (bilangan bulat), **Kode versi minimum yang
   didukung**, dan **Catatan rilis**.
4. Pilih **Unggah**. Server menyimpan berkas secara privat di object storage dan menghitung SHA-256
   secara otomatis.

Ketentuan penting:

- **Kode versi** harus lebih besar daripada kode versi rilis yang sedang aktif.
- Rilis baru tersimpan sebagai **Nonaktif**. Aktifkan satu rilis agar menjadi target unduhan; hanya
  ada satu rilis aktif pada satu waktu.
- Rilis dapat **dinonaktifkan** (ditarik) kapan saja. Bila tidak ada rilis aktif, halaman Unduh
  Aplikasi menampilkan empty state.
- Nama berkas unduhan memakai nama perusahaan dari pengaturan, tanpa merek yang di-hardcode.

Riwayat rilis menampilkan versi, kode versi, kode versi minimum, ukuran, SHA-256, status, pengunggah,
dan tanggal unggah.
