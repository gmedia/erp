# Panduan Audit Log

Halaman **Audit Log** (`/audit-logs`) menampilkan jejak perubahan data keuangan dan pengaturan:
siapa mengubah apa, kapan, dan nilai lama → nilai baru. Halaman ini dipakai akuntan, auditor, dan
admin untuk menelusuri koreksi tanpa harus membuka setiap dokumen satu per satu.

## Siapa yang bisa membuka halaman ini

Halaman hanya bisa diakses pengguna yang memiliki permission **`audit_log`**. Tombol **Ekspor**
membutuhkan permission **`audit_log.export`**. Pengguna lain akan melihat pesan akses ditolak.
Menu ada di grup **Admin > Audit Log**.

## Data yang dicatat

Pencatatan aktif pada model berikut:

| Modul | Contoh data |
|---|---|
| Jurnal & baris jurnal | Jurnal umum, jurnal penyesuaian, baris debit/kredit |
| Invoice pelanggan | Nominal, status, termin, catatan |
| Tagihan pemasok | Nominal, status, termin, catatan |
| Nota kredit | Nominal dan status nota kredit |
| Penerimaan piutang (AR receipt) | Status dan nominal penerimaan |
| Pembayaran utang (AP payment) | Status dan nominal pembayaran |
| Tahun fiskal & penutupan periode | Status dan tanggal tutup buku |
| Bagan akun (CoA) | Kode, nama, tipe, dan status akun |
| Pengaturan admin | Nilai pengaturan yang tidak bersifat rahasia |
| Akun pengguna | Perubahan data akun selain kata sandi dan token |

Yang dicatat hanya **kolom yang benar-benar berubah**. Setiap baris menyimpan nilai lama dan nilai
baru, sehingga koreksi bisa ditelusuri. Pembuatan dan penghapusan data juga dicatat.

## Membaca tabel

Setiap baris berisi:

- **Waktu**: saat perubahan terjadi.
- **Pengguna**: akun yang melakukan perubahan. Bila perubahan berasal dari proses sistem
  (misalnya data awal), kolom ini menampilkan **Sistem**.
- **Modul**: nama model data yang berubah, misalnya `App\Models\JournalEntry`.
- **ID Dokumen**: ID data yang berubah.
- **Aksi**: **Dibuat**, **Diubah**, atau **Dihapus**.
- **Ringkasan**: kalimat singkat, misalnya "Mengubah 1 kolom: deskripsi". Klik ringkasan untuk
  membuka rincian.

## Melihat nilai lama dan baru

Klik teks **Ringkasan** pada baris yang diinginkan. Modal rincian menampilkan tabel
**Field / Old / New** berisi setiap kolom yang berubah beserta nilai sebelum dan sesudahnya.

Untuk jurnal, riwayat juga tampil langsung di modal detail jurnal pada bagian
**Riwayat perubahan**. Bagian ini memuat perubahan terbaru untuk jurnal tersebut, sehingga auditor
tidak perlu berpindah halaman.

## Memfilter dan mencari

Gunakan panel filter untuk mempersempit data:

- **Pencarian**: mencari di ringkasan dan ID dokumen.
- **Modul**: nama model, misalnya `JournalEntry` atau `CustomerInvoice`.
- **Pengguna**: akun pelaku perubahan.
- **Tanggal mulai / Tanggal selesai**: rentang waktu perubahan.

Filter dapat dikombinasikan. Tekan **Terapkan filter** untuk menjalankan filter dan **Reset** untuk
mengembalikan ke semua data.

## Ekspor

Tombol **Ekspor** mengunduh hasil sesuai filter dalam format Excel. Kolom yang diekspor: waktu,
aksi, pengguna, modul, ID dokumen, ringkasan, dan rincian perubahan (JSON nilai lama → baru). File
ekspor memuat data pribadi, jadi simpan di tempat aman dan hapus bila tidak lagi diperlukan.

## Retensi dan pembersihan

- Catatan audit disimpan **365 hari** (1 tahun) secara default.
- Catatan yang lebih tua dari masa retensi dihapus otomatis setiap hari oleh penjadwal
  (`audit-logs:prune`).
- Lama retensi dapat diubah administrator sistem melalui pengaturan `audit_log_retention_days`.
  Nilai harus lebih besar dari nol; bila kosong atau tidak valid, sistem memakai 365 hari.
- Catatan yang sudah dihapus **tidak dapat dipulihkan**. Lakukan ekspor sebelum menurunkan masa
  retensi bila data lama masih dibutuhkan untuk audit.

## Privasi dan Undang-Undang Pelindungan Data Pribadi (UU PDP No. 27/2022)

Audit log memuat data pribadi berupa nama pengguna dan jejak aktivitasnya. Perlakukan sesuai
ketentuan berikut:

- **Tujuan terbatas**: data hanya dipakai untuk audit perubahan dan pertanggungjawaban keuangan,
  bukan untuk memantau kinerja karyawan.
- **Akses terbatas**: hanya pemegang permission `audit_log` yang dapat melihat data, dan hanya
  pemegang `audit_log.export` yang dapat mengekspornya. Berikan permission ini seperlunya.
- **Field sensitif disaring**: kata sandi, token, secret, API key, dan kredensial tidak pernah
  disimpan dalam nilai lama maupun baru. Pengaturan yang namanya mengandung kata sandi, secret,
  token, API key, atau kredensial juga tidak menyimpan nilainya.
- **Retensi jelas**: data dihapus setelah masa retensi berakhir sesuai nilai yang berlaku di
  perusahaan Anda.
- **Ekspor dikendalikan**: file Excel berisi data pribadi. Simpan di tempat aman, bagikan hanya
  kepada pihak yang berwenang, dan hapus bila sudah tidak diperlukan.
- **Hak subjek data**: bila karyawan meminta informasi tentang data aktivitasnya, sampaikan sesuai
  prosedur internal perusahaan.

> Catatan: panduan ini bersifat umum dan bukan nasihat hukum. Penafsiran kewajiban kepatuhan UU PDP
> sebaiknya dikonsultasikan kepada tim hukum atau pejabat pelindungan data pribadi di perusahaan
> Anda.
