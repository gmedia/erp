# Panduan: Pengguna

## Gambaran Umum

Halaman **Pengguna** (`/users`) menampilkan akun login yang terhubung dengan data karyawan.
Setiap akun memiliki nama, email, kata sandi, dan relasi ke satu karyawan. Akun tanpa relasi
karyawan tetap tampil, tetapi tidak dapat diubah dari halaman ini.

Halaman ini dipakai administrator untuk membuat akun baru, mengganti email, mengatur ulang kata
sandi, dan memeriksa apakah suatu akun sudah terhubung ke karyawan.

## Akses & Izin

| Kebutuhan | Permission |
|---|---|
| Melihat daftar dan detail pengguna | `user` |
| Menambah pengguna baru | `user.edit` |
| Mengubah nama, email, atau kata sandi | `user.edit` |

Permission `user.edit` juga dipakai untuk menyimpan izin akses karyawan dan menghubungkan akun
dengan karyawan. Tanpa permission tersebut, tombol **Tambah Pengguna** dan ikon pensil tidak
ditampilkan.

## Menu & Navigasi

- **Admin > Pengguna**
- Halaman menampilkan pencarian, tabel pengguna, jumlah baris per halaman, dan tombol
  **Tambah Pengguna** di kanan atas.

## Melihat dan mencari pengguna

Tabel menampilkan kolom berikut:

| Kolom | Keterangan |
|---|---|
| **Nama** | Nama akun login. |
| **Email** | Alamat email yang dipakai untuk masuk. |
| **Karyawan** | Nama karyawan yang terhubung, atau **Belum ada karyawan terhubung**. |
| **Status** | **Terhubung** atau **Belum terhubung**. |
| **Aksi** | Ikon pensil untuk mengubah pengguna. |

Gunakan kolom pencarian **Cari pengguna...** untuk mencari berdasarkan nama atau email. Pencarian
berjalan otomatis setelah jeda singkat dan mengembalikan halaman ke halaman pertama. Ubah jumlah
baris per halaman melalui kontrol pagination di bawah tabel.

## Menambah pengguna

1. Klik **Tambah Pengguna**.
2. Pada dialog, pilih karyawan melalui kolom **Select Employee**. Ketik nama karyawan untuk
   mempersempit pilihan.
3. Setelah karyawan dipilih, isi:
   - **User Name**: nama akun, wajib diisi, maksimal 255 karakter.
   - **User Email**: email login, wajib diisi, harus valid, dan belum dipakai akun lain.
   - **User Password**: wajib untuk akun baru, minimal 8 karakter.
4. Klik **Save Changes**.

Sistem membuat akun, menghubungkannya ke karyawan, dan mencatat kejadian `user_created` pada
security log. Dialog tetap menampilkan data akun yang baru dibuat sehingga Anda dapat langsung
memeriksa atau memperbaruinya.

## Mengubah pengguna

1. Cari pengguna pada tabel, lalu klik ikon pensil di kolom **Aksi**.
2. Dialog **Ubah Pengguna** terbuka dengan karyawan yang sudah terkunci.
3. Ubah **User Name**, **User Email**, atau isi **User Password** baru.
4. Kosongkan **User Password** bila kata sandi tidak ingin diubah. Kata sandi lama tetap berlaku.
5. Klik **Save Changes**.

Jika pengguna belum terhubung ke karyawan, ikon pensil dinonaktifkan dan tabel menampilkan pesan
**Hubungkan pengguna ini ke karyawan sebelum diubah.** Untuk kondisi ini, hubungkan akun melalui
data karyawan terlebih dahulu.

## Aturan dan validasi penting

- **Nama** wajib diisi dan maksimal 255 karakter.
- **Email** wajib diisi, harus berformat email, maksimal 255 karakter, dan unik antar pengguna.
- **Kata sandi** minimal 8 karakter. Saat mengubah akun, kata sandi boleh dikosongkan.
- **Satu karyawan** hanya memiliki satu akun pengguna.
- Penghapusan pengguna tidak dilakukan dari halaman ini. Nonaktifkan akses melalui pengaturan
  karyawan atau izin akses bila akun tidak boleh dipakai lagi.
- Perubahan izin akses dilakukan di halaman **Izin Akses**, bukan di halaman Pengguna.

## Tanya jawab singkat

**Mengapa tombol tambah tidak muncul?**
Akun Anda tidak memiliki permission `user.edit`. Hubungi administrator sistem.

**Mengapa ikon pensil tidak bisa diklik?**
Akun tersebut belum terhubung ke karyawan. Hubungkan terlebih dahulu melalui data karyawan.

**Apakah kata sandi lama terlihat di formulir?**
Tidak. Kolom kata sandi selalu kosong. Isi hanya bila ingin mengganti kata sandi.

**Bagaimana cara memberi akses modul kepada pengguna?**
Buka **Admin > Izin Akses**, pilih karyawan yang terhubung dengan akun tersebut, lalu centang
permission yang diperlukan.

**Apakah perubahan akun tercatat?**
Pembuatan akun dicatat pada security log. Perubahan data lain dapat ditelusuri melalui audit log
sesuai kebijakan retensi perusahaan.
