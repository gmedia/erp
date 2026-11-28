# Panduan: Log Keamanan

Halaman **Log Keamanan** (`/security-logs`) membantu admin IT mengaudit akses ke ERP: siapa yang login dan logout, kapan, dari alamat IP dan perangkat apa, serta perubahan hak akses dan akun. Halaman ini juga dipakai untuk mendeteksi kemungkinan akun dibobol, misalnya rangkaian login gagal yang tidak wajar.

## Siapa yang bisa membuka halaman ini

Halaman hanya bisa diakses pengguna yang memiliki permission **`security_log`**. Pengguna lain akan melihat pesan akses ditolak. Menu ada di grup **Admin > Security Logs**.

## Kejadian yang dicatat

| Kejadian | Keterangan |
|---|---|
| Login berhasil | Login dengan kata sandi atau login sosial (ditandai penyedia, misalnya Google). |
| Login gagal | Kredensial salah, akun nonaktif, atau terkunci oleh pembatas percobaan. Email yang dicoba ikut tercatat. |
| Logout | Pengguna keluar dari sesi. |
| Ganti kata sandi / reset | Perubahan kata sandi oleh pengguna atau lewat reset. |
| Perubahan hak akses | Permission yang ditambah atau dicabut dari karyawan/pengguna. |
| Pembuatan akun | Akun pengguna baru dibuat dari data karyawan. |

Kata sandi, token, dan rahasia lain **tidak pernah disimpan** dalam log, dalam bentuk apa pun.

## Membaca tabel

Setiap baris berisi:

- **Waktu**: saat kejadian terjadi.
- **Event**: jenis kejadian (lihat tabel di atas).
- **Pengguna**: akun yang terkait. Kosong untuk login gagal bila email tidak dikenali.
- **Email yang dicoba**: hanya untuk login gagal.
- **IP**: alamat IP sumber permintaan.
- **Perangkat**: ringkasan browser dan sistem operasi (bukan agen pengguna lengkap).

## Memfilter dan mencari

Gunakan filter untuk mempersempit data:

- **Tanggal**: rentang waktu kejadian.
- **Event**: satu jenis kejadian, misalnya hanya login gagal.
- **User**: akun tertentu.
- **IP**: alamat IP tertentu, berguna untuk melacak satu sumber.

Kolom pencarian mencari di email yang dicoba, IP, nama pengguna, dan email pengguna. Tekan **Export** untuk mengunduh hasil filter dalam format Excel.

## Peringatan login gagal

Sistem memberi peringatan kepada admin bila satu akun atau satu IP mengalami sejumlah login gagal dalam waktu singkat. Default-nya **5 kali dalam 15 menit**. Peringatan hanya dikirim sekali per rangkaian, sehingga admin tidak dibanjiri notifikasi yang sama.

Nilai ambang (1–100 kali) dan jendela waktu (1–1440 menit) dapat diubah di **Pengaturan Admin > Security Logs**. Bila muncul peringatan, periksa dulu apakah itu percobaan dari pengguna yang lupa kata sandi atau benar-benar percobaan tidak sah, lalu hubungi pemilik akun.

## Retensi dan pembersihan

- Log disimpan **365 hari** (1 tahun) secara default.
- Log yang lebih tua dari masa retensi dihapus otomatis setiap hari oleh penjadwal.
- Lama retensi dapat diubah di **Pengaturan Admin > Security Logs**, antara **30 sampai 3650 hari**. Nilai di luar rentang itu ditolak.
- Log yang sudah dihapus tidak dapat dipulihkan. Ekspor ke Excel sebelum menurunkan retensi bila data lama masih dibutuhkan untuk audit.

## Privasi dan Undang-Undang Pelindungan Data Pribadi (UU PDP No. 27/2022)

Log ini memuat data pribadi berupa alamat IP, email yang dicoba, dan informasi perangkat. Perlakukan sesuai ketentuan berikut:

- **Tujuan terbatas**: data hanya dipakai untuk keamanan sistem dan audit akses, bukan untuk pemantauan kinerja karyawan.
- **Akses terbatas**: hanya pemegang permission `security_log` yang dapat melihat dan mengekspor log. Berikan permission ini seperlunya.
- **Retensi jelas**: data dihapus setelah masa retensi berakhir, sesuai nilai yang berlaku di perusahaan Anda.
- **Ekspor dikendalikan**: file Excel berisi data pribadi. Simpan di tempat yang aman dan hapus bila sudah tidak diperlukan.
- **Hak subjek data**: bila karyawan meminta informasi tentang datanya, sampaikan sesuai prosedur internal perusahaan.

> Catatan: panduan ini bersifat umum dan bukan nasihat hukum. Penafsiran kewajiban kepatuhan UU PDP sebaiknya dikonsultasikan kepada tim hukum atau pejabat pelindungan data pribadi di perusahaan Anda.
