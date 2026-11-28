# Panduan: Pemetaan Akun

## Gambaran Umum

Pemetaan Akun menghubungkan satu **akun sumber** pada sebuah versi COA dengan satu **akun tujuan**
pada versi COA lain. Pemetaan dipakai saat struktur bagan akun berubah antarperiode, misalnya
ketika akun diganti nama, digabung, atau dipecah. Dengan pemetaan, saldo dan referensi dari versi
lama dapat diarahkan ke akun yang tepat pada versi baru.

Setiap pemetaan memiliki jenis:

- **Ganti nama (rename)**: akun sumber berpindah ke akun tujuan dengan makna yang sama.
- **Gabung (merge)**: beberapa akun sumber diarahkan ke satu akun tujuan.
- **Pecah (split)**: akun sumber diarahkan ke akun tujuan pada versi baru.

## Akses & Izin

| Permission | Kegunaan |
|---|---|
| `account_mapping` | Melihat daftar dan detail pemetaan, serta mengekspor data |
| `account_mapping.create` | Membuat pemetaan baru |
| `account_mapping.edit` | Mengubah pemetaan |
| `account_mapping.delete` | Menghapus pemetaan |

Menu berada di grup **Data Master**.

## Menu & Navigasi

| Menu | URL | Grup menu |
|---|---|---|
| Pemetaan Akun | `/account-mappings` | Data Master |

Tabel menampilkan kolom **Akun Sumber**, **Akun Tujuan**, **Jenis**, **Catatan**, dan kolom aksi.
Nama akun ditampilkan dalam format `versi • kode - nama`, misalnya
`COA 2025 • 1-1000 - Kas`. Toolbar menyediakan tombol **Filter**, **Tambah**, dan **Ekspor**.

## 1. Mencari dan Memfilter Pemetaan

1. Buka menu **Pemetaan Akun**.
2. Ketik kata kunci pada kolom pencarian, lalu tekan **Enter**.
3. Klik **Filter** untuk menyaring berdasarkan:
   - **Jenis**: Ubah Nama, Gabung, atau Pecah.
   - **Versi COA Sumber**: versi asal akun sumber.
   - **Versi COA Tujuan**: versi tujuan akun tujuan.
4. Klik **Terapkan filter** untuk menjalankan filter atau **Atur ulang** untuk mengembalikan.

## 2. Membuat Pemetaan

1. Klik **Tambah**.
2. Isi formulir **Buat Pemetaan Akun**:
   - **Versi COA Sumber**: pilih versi asal.
   - **Akun Sumber**: pilih akun dari versi sumber. Akun kelompok boleh dipilih bila saldo lama
     berada pada akun kelompok.
   - **Versi COA Tujuan**: pilih versi tujuan yang berbeda dari versi sumber.
   - **Akun Tujuan**: pilih akun daun pada versi tujuan.
   - **Jenis**: Ganti nama, Gabung, atau Pecah.
   - **Catatan**: keterangan tambahan (opsional).
3. Klik **Simpan**.

Bila **Versi COA Sumber** atau **Versi COA Tujuan** diubah, pilihan akun pada sisi tersebut
direset agar tidak tersisa akun dari versi lama.

## 3. Mengubah Pemetaan

1. Pada baris pemetaan, buka menu aksi lalu pilih **Edit**.
2. Ubah versi, akun, jenis, atau catatan sesuai kebutuhan.
3. Klik **Simpan**.

## 4. Melihat Detail Pemetaan

Buka menu aksi pada baris lalu pilih **View**. Dialog detail menampilkan akun sumber, akun tujuan,
jenis, catatan, tanggal dibuat, dan tanggal diperbarui.

## 5. Menghapus Pemetaan

1. Pada baris pemetaan, buka menu aksi lalu pilih **Delete**.
2. Baca konfirmasi penghapusan.
3. Klik **Delete** untuk melanjutkan atau **Cancel** untuk membatalkan.

Penghapusan pemetaan tidak menghapus akun sumber maupun akun tujuan.

## 6. Ekspor

Klik **Ekspor** untuk mengunduh daftar pemetaan sesuai filter yang aktif. File Excel memuat kolom
ID, Type, Source COA Version, Source Account Code, Source Account Name, Target COA Version,
Target Account Code, Target Account Name, Notes, Created At, dan Updated At.

## Aturan & Validasi Penting

- **Akun Sumber wajib diisi** dan harus akun yang sudah ada.
- **Akun Tujuan wajib diisi**, harus akun yang sudah ada, dan **berbeda** dari akun sumber.
- **Akun sumber dan akun tujuan harus berasal dari versi COA yang berbeda**. Pemetaan dalam versi
  yang sama ditolak.
- **Akun tujuan harus akun daun** (tidak memiliki akun anak). Akun sumber boleh akun kelompok.
- **Jenis wajib diisi** dan harus salah satu dari `rename`, `merge`, atau `split`.
- **Catatan** opsional.

## Tanya Jawab Singkat

**Mengapa akun tujuan tidak muncul di pilihan?**

Pilihan akun tujuan hanya menampilkan akun daun pada versi COA tujuan. Pastikan versi tujuan sudah
dipilih dan akun yang dicari bukan akun kelompok.

**Bolehkah akun sumber berupa akun kelompok?**

Boleh. Akun kelompok sering menyimpan saldo pada instalasi lama sehingga perlu dipetakan ke akun
daun pada versi baru.

**Mengapa pemetaan ditolak saat akun sumber dan tujuan dari versi yang sama?**

Pemetaan hanya untuk perpindahan antarversi COA. Untuk perubahan di dalam satu versi, ubah akun
langsung melalui halaman Bagan Akun.

**Apakah menghapus pemetaan memengaruhi saldo?**

Tidak. Pemetaan adalah penghubung referensi antarversi; saldo tetap tersimpan pada akun dan jurnal.
