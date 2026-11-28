# Panduan: Pengaturan Admin

## Gambaran Umum

Halaman **Pengaturan Admin** (`/admin-settings`) berisi pengaturan yang berlaku untuk seluruh
aplikasi: identitas perusahaan, format regional, email keluar, metode masuk, akun akuntansi, retensi
log keamanan, dan ambang kesehatan sistem. Perubahan di sini memengaruhi semua pengguna, jadi
lakukan hanya bila Anda memahami dampaknya.

## Akses & Izin

| Kebutuhan | Permission |
|---|---|
| Melihat seluruh kelompok pengaturan | `admin_setting` |
| Mengubah pengaturan dan mengirim email uji SMTP | `admin_setting.edit` |

Tanpa `admin_setting.edit`, kolom ditampilkan dalam keadaan nonaktif dan tombol simpan disembunyikan.
Menu berada di **Admin > Pengaturan Admin**.

## Menu & Navigasi

Sidebar halaman memiliki kelompok berikut:

- **General** untuk identitas perusahaan.
- **Regional** untuk mata uang dan format tanggal/angka.
- **SMTP** untuk email keluar.
- **Authentication** untuk metode masuk dan organisasi yang diizinkan.
- **Accounting** untuk akun pelepasan aset dan persediaan dalam perjalanan.
- **Security Logs** untuk retensi dan ambang peringatan login gagal.
- **Kesehatan Sistem** untuk ambang pemeriksaan dan email notifikasi.

Setiap kelompok disimpan terpisah. Periksa kelompok yang benar sebelum menekan tombol simpan.

## General: identitas perusahaan

| Kolom | Keterangan |
|---|---|
| **Company Name** | Nama perusahaan yang tampil di header, dokumen, dan email. |
| **Company Address** | Alamat yang dicetak pada faktur dan pesanan pembelian. |
| **Company Phone** | Nomor kontak utama perusahaan. |
| **Company Email** | Email kontak utama; harus berformat email yang valid. |
| **Company Logo (SVG)** | Logo berformat SVG. Pratinjau logo saat ini tampil di atas tombol unggah. |

Logo yang diunggah harus berupa SVG dan maksimal 2 MB. Bila unggahan logo gagal, tidak ada
pengaturan lain yang disimpan. Bila logo dikosongkan, aplikasi memakai logo default.

## Regional: mata uang dan format

| Kolom | Keterangan |
|---|---|
| **Currency** | Mata uang default. Pilihan yang tersedia mengikuti konfigurasi sistem. |
| **Date Format** | Pola tanggal gaya PHP, misalnya `d/m/Y`. |
| **Decimal Separator** | Karakter pemisah desimal, misalnya koma. |
| **Thousand Separator** | Karakter pemisah ribuan, misalnya titik. |
| **Hide Decimal Separator** | Bila dicentang, angka ditampilkan tanpa desimal di seluruh modul. |

Perubahan format langsung dipakai setelah disimpan dan halaman dimuat ulang. Pastikan format tanggal
dan angka sesuai kebiasaan pengguna perusahaan.

## SMTP: email keluar

Isi **Mail Host**, **Mail Port**, **Mail Username**, **Mail Password**, **Mail Encryption**,
**From Address**, dan **From Name**. Port yang umum adalah 587 untuk TLS atau 465 untuk SSL. Isi
**Mail Encryption** dengan `tls`, `ssl`, atau kosongkan bila tidak memakai enkripsi.

Setelah menyimpan, gunakan **Test SMTP Configuration**:

1. Isi **Test Email** dengan alamat penerima yang valid.
2. Klik **Send Test Email**.
3. Periksa kotak masuk. Bila gagal, pesan kesalahan ditampilkan pada kolom uji.

Simpan pengaturan SMTP terlebih dahulu sebelum mengirim email uji.

## Authentication: metode masuk

- **Enable social login** mengizinkan masuk dengan Google Workspace atau Microsoft 365.
- **Enable password login** menampilkan formulir email dan kata sandi.
- **Allow personal accounts** mengizinkan akun Google atau Microsoft pribadi.
- **Auto-link by email** menghubungkan profil sosial otomatis bila emailnya sama.
- **Auto-create users** membuat pengguna saat pertama kali masuk lewat akun sosial.
- **Allowed email domains** berisi daftar domain yang dipisahkan koma. Kosongkan untuk menolak semua
  login Workspace.
- **Allowed Microsoft tenants** berisi daftar tenant yang dipisahkan koma. Kosongkan untuk menolak
  semua login Microsoft 365.

Matikan salah satu metode masuk hanya bila metode lain sudah siap dipakai, agar pengguna tidak
kehilangan akses.

## Accounting: akun pelepasan dan persediaan

Pilih akun daun yang aktif dari bagan akun aktif:

- **Akun laba pelepasan aset** untuk selisih positif saat aset dilepas.
- **Akun rugi pelepasan aset** untuk selisih negatif atau aset hilang.
- **Akun persediaan dalam perjalanan** untuk barang yang dikirim antar gudang sebelum diterima.

Akun induk tidak dapat dipilih. Bila akun yang dipilih tidak lagi aktif atau bukan akun daun,
sistem menolak penyimpanan.

## Security Logs: retensi dan peringatan

| Kolom | Rentang | Default |
|---|---|---|
| **Retention (days)** | 30 sampai 3650 hari | 365 |
| **Failed login alert threshold** | 1 sampai 100 | 5 |
| **Alert window (minutes)** | 1 sampai 1440 menit | 15 |

Log yang lebih tua dari masa retensi dihapus otomatis setiap hari. Ambang login gagal dipakai untuk
mengingatkan administrator bila satu akun atau satu alamat IP mencapai jumlah kegagalan tertentu
dalam jendela waktu yang ditentukan.

## Kesehatan Sistem

Isi ambang peringatan dan gagal untuk disk, memori container, job antrian gagal, scheduler yang
berhenti, dan jumlah koneksi database. Isi juga **Email notifikasi** untuk alamat administrator IT
yang menerima pemberitahuan saat status berubah. Kosongkan email bila notifikasi tidak diperlukan.

Nilai ambang **gagal** harus lebih besar daripada nilai **peringatan** untuk disk dan memori. Bila
tidak, penyimpanan ditolak.

## Aturan dan validasi penting

- **Company Email** dan **From Address** harus berformat email yang valid.
- **Company Name** maksimal 255 karakter; alamat maksimal 1000 karakter.
- **Logo** hanya menerima SVG dan maksimal 2 MB.
- **Currency** harus termasuk mata uang yang didukung sistem.
- **Date Format** maksimal 20 karakter; pemisah angka maksimal 5 karakter.
- **Retensi security log** minimal 30 hari dan maksimal 3650 hari.
- **Ambang gagal** disk dan memori harus lebih besar daripada ambang peringatan.
- **Akun accounting** harus akun daun yang aktif pada bagan akun aktif.

## Tanya jawab singkat

**Mengapa tombol simpan tidak muncul?**
Akun Anda tidak memiliki permission `admin_setting.edit`.

**Mengapa logo gagal diunggah?**
Pastikan berkas berformat SVG dan ukurannya tidak melebihi 2 MB. Bila tetap gagal, periksa
penyimpanan objek melalui administrator sistem.

**Apakah perubahan langsung berlaku untuk semua pengguna?**
Ya. Pengguna lain mungkin perlu memuat ulang halaman agar format atau identitas baru terlihat.

**Bagaimana cara menguji email tanpa mengirim ke pengguna?**
Simpan konfigurasi SMTP, lalu gunakan **Test SMTP Configuration** dengan alamat email Anda sendiri.

**Apakah nilai SMTP dan logo disimpan di audit log?**
Nilai rahasia seperti kata sandi dan token tidak disimpan. Perubahan pengaturan lain dapat tercatat
pada audit log sesuai kebijakan sistem.
