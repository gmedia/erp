# Panduan: Bagan Akun

## Gambaran Umum

Bagan Akun (Chart of Accounts/COA) adalah daftar akun yang dipakai untuk mencatat transaksi
keuangan. Setiap akun berada di dalam satu **Versi Bagan Akun** dan dapat memiliki **akun induk**
sehingga terbentuk struktur hierarkis. Halaman Bagan Akun menampilkan struktur tersebut sebagai
pohon (tree) yang bisa dibuka dan ditutup per cabang.

Akun dipakai oleh jurnal, laporan keuangan, anggaran, dan pemetaan akun. Karena itu, perubahan
kode atau nama akun sebaiknya dilakukan setelah memahami dampaknya ke laporan dan dokumen yang
sudah ada.

## Akses & Izin

| Permission | Kegunaan |
|---|---|
| `account` | Melihat daftar dan detail akun, serta mengekspor data |
| `account.create` | Menambah akun baru |
| `account.edit` | Mengubah akun |
| `account.delete` | Menghapus akun |

Pengguna tanpa permission `account` tidak dapat membuka halaman ini. Menu berada di grup
**Data Master**.

## Menu & Navigasi

| Menu | URL | Grup menu |
|---|---|---|
| Bagan Akun | `/accounts` | Data Master |

Di bagian atas halaman terdapat pemilih versi COA, tombol ekspor (ikon unduh), kolom pencarian,
dan tombol **Search**. Pemilih versi menampilkan nama versi beserta statusnya, misalnya
`COA 2026 (active)`.

## 1. Memilih Versi Bagan Akun

1. Buka menu **Bagan Akun**.
2. Pada pemilih versi di bagian atas, pilih versi yang ingin dilihat.
3. Saat halaman pertama kali dibuka, sistem memilih versi berstatus **Aktif**. Bila tidak ada
   versi aktif, sistem memilih versi pertama yang tersedia.

Pohon akun yang tampil selalu mengikuti versi yang dipilih. Akun dari versi lain tidak
ditampilkan.

## 2. Membaca Pohon Akun

Bagian **Hierarchy** menampilkan akun dalam bentuk pohon:

- Ikon folder menandai akun yang memiliki akun anak (akun kelompok).
- Ikon dokumen menandai akun tanpa anak (akun daun).
- Kode akun tampil di kotak kecil, diikuti nama akun.
- Di bawah nama tampil jenis akun dan saldo normal, misalnya `asset • debit`.
- Akun nonaktif tampil lebih redup.
- Gunakan tombol panah untuk membuka atau menutup cabang. Akun diurutkan berdasarkan kode.

Akun tanpa induk tampil di tingkat paling atas. Bila versi yang dipilih belum memiliki akun,
halaman menampilkan pesan bahwa belum ada akun dan tombol untuk membuat akun pertama.

## 3. Mencari Akun

1. Ketik kode atau nama akun pada kolom pencarian.
2. Tekan **Enter** atau klik **Search**.
3. Sistem memfilter akun berdasarkan kode atau nama, lalu menyusun ulang pohon dari hasil
   tersebut.

Kosongkan kolom pencarian dan tekan **Enter** atau **Search** untuk menampilkan semua akun pada
versi yang dipilih.

## 4. Menambah Akun Induk

1. Pilih versi COA yang benar.
2. Klik **New Root Account**.
3. Isi formulir **Buat Akun**:
   - **Kode**: kode akun, misalnya `1-1000`.
   - **Nama**: nama akun, misalnya `Kas`.
   - **Jenis**: Aset, Kewajiban, Ekuitas, Pendapatan, atau Beban.
   - **Saldo Normal**: Debit atau Kredit.
   - **Aktif**: centang agar akun dapat dipakai transaksi.
   - **Arus Kas**: centang agar akun disertakan dalam laporan arus kas.
   - **Deskripsi**: keterangan tambahan (opsional).
4. Klik **Simpan**.

Akun baru langsung muncul di pohon. Level akun induk dihitung otomatis sebagai level 1.

## 5. Menambah Akun Anak

1. Arahkan kursor ke akun induk yang dituju.
2. Klik tombol plus (**Add Child Account**).
3. Formulir **Buat Akun** terbuka dengan informasi akun induk di bagian atas.
4. Isi **Kode** dan **Nama**. Jenis dan saldo normal mengikuti akun induk dan tidak dapat diubah.
5. Atur **Aktif**, **Arus Kas**, dan **Deskripsi** bila perlu.
6. Klik **Simpan**.

Level akun anak otomatis menjadi level akun induk ditambah satu. Kedalaman pohon tidak dibatasi
selama kode akun tetap unik di dalam versi tersebut.

## 6. Mengubah Akun

1. Pada baris akun, buka menu aksi lalu pilih **Edit**.
2. Ubah data yang diperlukan.
3. Klik **Simpan**.

Saat mengubah akun anak, jenis dan saldo normal tetap mengikuti akun induk. Perubahan kode atau
nama akan terlihat pada jurnal dan laporan yang menampilkan akun tersebut.

## 7. Menghapus Akun

1. Pada baris akun, buka menu aksi lalu pilih **Delete**.
2. Baca konfirmasi yang menampilkan kode dan nama akun.
3. Klik **Delete** untuk menghapus atau **Cancel** untuk membatalkan.

Penghapusan ditolak bila:

- Akun masih memiliki akun anak. Hapus atau pindahkan akun anak terlebih dahulu.
- Akun sudah dipakai pada baris jurnal. Akun yang pernah dipakai jurnal tidak boleh dihapus agar
  riwayat laporan tetap utuh.

## 8. Ekspor

Klik tombol ekspor (ikon unduh) di bagian atas halaman. Ekspor mengikuti versi COA yang dipilih
dan kata kunci pencarian yang sedang aktif. File Excel memuat kolom ID, Code, Name, Type, Normal
Balance, Active, dan Level.

## Aturan & Validasi Penting

- **Kode wajib diisi** dan unik **di dalam satu versi COA**. Kode yang sama boleh dipakai pada
  versi COA yang berbeda.
- **Nama wajib diisi** dan maksimal 255 karakter.
- **Jenis** harus salah satu dari `asset`, `liability`, `equity`, `revenue`, atau `expense`.
- **Saldo Normal** harus `debit` atau `credit`.
- **Akun induk** harus akun yang sudah ada pada versi yang sama.
- **Level** minimal 1 dan dihitung otomatis dari akun induk.
- Akun yang sudah dipakai baris jurnal tidak dapat dihapus.
- Menghapus versi COA akan menghapus seluruh akun di dalamnya, sehingga lakukan dengan hati-hati.

## Tanya Jawab Singkat

**Apakah kode akun boleh sama di dua versi COA?**

Boleh. Keunikan kode hanya berlaku di dalam satu versi COA.

**Mengapa daftar akun kosong?**

Periksa versi COA yang dipilih. Bila versi tersebut belum memiliki akun, buat akun pertama melalui
**New Root Account**. Bila tidak ada versi aktif, halaman memilih versi pertama yang tersedia.

**Mengapa akun tidak bisa dihapus?**

Akun masih memiliki akun anak atau sudah dipakai pada baris jurnal. Hapus akun anak terlebih
dahulu, atau biarkan akun tetap ada karena riwayat jurnal harus dipertahankan.

**Apa arti centang Arus Kas?**

Akun yang ditandai Arus Kas akan disertakan saat laporan arus kas disusun.

**Bagaimana memindahkan akun ke induk lain?**

Saat ini akun induk ditetapkan ketika akun dibuat melalui tombol plus pada akun induk. Untuk
memindahkan akun, siapkan struktur akun pada versi COA yang sesuai dan gunakan Pemetaan Akun bila
tujuannya memindahkan saldo antarversi.
