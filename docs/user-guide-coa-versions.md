# Panduan: Versi Bagan Akun

## Gambaran Umum

Versi Bagan Akun (COA Version) mengelompokkan satu set akun untuk satu tahun fiskal. Setiap versi
memiliki nama, tahun fiskal, dan status. Versi membuat perubahan struktur akun dapat dilakukan
tanpa merusak laporan periode sebelumnya: versi lama tetap menyimpan akun dan saldo historisnya.

Satu tahun fiskal dapat memiliki beberapa versi, misalnya versi standar dan versi revisi. Versi
berstatus **Aktif** menjadi acuan default saat halaman Bagan Akun dibuka atau saat formulir
mengambil daftar akun tanpa memilih versi secara eksplisit.

## Akses & Izin

| Permission | Kegunaan |
|---|---|
| `coa_version` | Melihat daftar dan detail versi COA, serta mengekspor data |
| `coa_version.create` | Membuat versi COA baru |
| `coa_version.edit` | Mengubah versi COA |
| `coa_version.delete` | Menghapus versi COA |

Menu berada di grup **Data Master**.

## Menu & Navigasi

| Menu | URL | Grup menu |
|---|---|---|
| Versi Bagan Akun | `/coa-versions` | Data Master |

Halaman menampilkan tabel versi COA dengan kolom **Nama**, **Tahun Fiskal**, **Status**,
**Dibuat Pada**, dan kolom aksi. Toolbar menyediakan tombol **Filter**, **Tambah**, dan
**Ekspor**, serta kolom pencarian.

## 1. Menampilkan dan Mencari Versi

1. Buka menu **Versi Bagan Akun**.
2. Ketik nama versi pada kolom pencarian, lalu tekan **Enter**.
3. Gunakan **Filter** untuk menyaring berdasarkan **Status** (Draf, Aktif, Diarsipkan).
4. Klik judul kolom untuk mengurutkan data, misalnya berdasarkan nama atau tanggal dibuat.

## 2. Membuat Versi COA

1. Klik **Tambah**.
2. Isi formulir **Buat Versi COA**:
   - **Nama**: nama versi, misalnya `COA 2026 Standar`.
   - **Tahun Fiskal**: pilih tahun fiskal yang sesuai.
   - **Status**: Draf, Aktif, atau Diarsipkan. Versi baru umumnya dimulai sebagai **Draf**.
3. Klik **Simpan**.

Setelah versi dibuat, tambahkan akun melalui halaman **Bagan Akun** dengan memilih versi tersebut.

## 3. Mengubah Versi COA

1. Pada baris versi, buka menu aksi lalu pilih **Edit**.
2. Ubah nama, tahun fiskal, atau status.
3. Klik **Simpan**.

Mengubah status menjadi **Aktif** membuat versi tersebut menjadi default pada halaman Bagan Akun.
Pastikan hanya satu versi yang berstatus Aktif untuk periode yang sedang dipakai agar pencatatan
tidak berpindah versi tanpa disengaja.

## 4. Melihat Detail Versi

Buka menu aksi pada baris versi lalu pilih **View**. Dialog detail menampilkan nama, tahun fiskal,
status, tanggal dibuat, dan tanggal diperbarui.

## 5. Menghapus Versi COA

1. Pada baris versi, buka menu aksi lalu pilih **Delete**.
2. Baca konfirmasi penghapusan.
3. Klik **Delete** untuk melanjutkan atau **Cancel** untuk membatalkan.

Penghapusan versi juga menghapus seluruh akun di dalamnya. Bila salah satu akun sudah dipakai pada
baris jurnal, penghapusan akan gagal. Untuk versi yang sudah dipakai, ubah statusnya menjadi
**Diarsipkan** alih-alih menghapus.

## 6. Ekspor

Klik **Ekspor** untuk mengunduh daftar versi sesuai filter yang aktif. File Excel memuat kolom
ID, Name, Fiscal Year, Status, Created At, dan Updated At.

## Aturan & Validasi Penting

- **Nama wajib diisi**, maksimal 255 karakter, dan unik **per tahun fiskal**. Nama yang sama boleh
  dipakai pada tahun fiskal berbeda.
- **Tahun Fiskal wajib diisi** dan harus tahun fiskal yang sudah ada.
- **Status wajib diisi** dan harus salah satu dari `draft`, `active`, atau `archived`.
- Menghapus versi COA ikut menghapus akun di dalamnya.
- Versi yang akunnya sudah dipakai jurnal tidak dapat dihapus karena integritas riwayat terjaga.

## Tanya Jawab Singkat

**Apa bedanya Draf, Aktif, dan Diarsipkan?**

Draf adalah versi yang masih disusun. Aktif adalah versi yang dipakai sebagai acuan default. Arsip
adalah versi lama yang disimpan untuk riwayat dan tidak dipakai untuk pencatatan baru.

**Bolehkah satu tahun fiskal punya beberapa versi?**

Boleh, selama nama versi berbeda di tahun fiskal tersebut. Praktik umumnya satu versi aktif dan
versi lain berstatus draf atau arsip.

**Mengapa halaman Bagan Akun tidak menampilkan akun?**

Pastikan versi yang dipilih sudah memiliki akun. Bila tidak ada versi aktif, halaman Bagan Akun
memilih versi pertama yang tersedia.

**Apakah versi bisa dihapus setelah dipakai?**

Bila akun pada versi tersebut sudah dipakai baris jurnal, penghapusan gagal. Arsipkan versi
tersebut agar riwayat tetap utuh.
