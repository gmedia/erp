# Panduan: Anggaran

## Gambaran Umum

Modul **Anggaran** mencatat rencana anggaran per akun beserta rincian periode. Setiap anggaran
memiliki tahun fiskal, jenis anggaran, status, dan satu atau lebih baris alokasi. Total nilai
anggaran dihitung otomatis dari jumlah seluruh baris.

Anggaran menjalani alur status **Draf → Disetujui → Terkunci**:

- **Draf**: masih dapat diubah, dihapus, dan diajukan untuk disetujui.
- **Disetujui**: nilai dan baris tidak dapat diubah lagi, tetapi masih dapat dikunci.
- **Terkunci**: status akhir; anggaran tidak dapat diubah atau dihapus.

Setelah anggaran disetujui, realisasi dapat dibandingkan dengan rencana melalui **Laporan Selisih
Anggaran** (`/reports/budget-variance`).

## Akses & Izin

| Permission | Kegunaan |
|---|---|
| `budget` | Melihat daftar dan detail anggaran, serta mengekspor data |
| `budget.create` | Membuat anggaran baru |
| `budget.edit` | Mengubah anggaran, menyetujui, dan mengunci |
| `budget.delete` | Menghapus anggaran berstatus Draf |
| `budget_variance_report` | Membuka dan mengekspor Laporan Selisih Anggaran |

Katalog izin juga memuat `budget.approve`. Pada rute saat ini, aksi **Setujui Anggaran** dan
**Kunci Anggaran** mengikuti izin `budget.edit`.

Menu **Anggaran** berada di grup **Akuntansi**. Menu **Laporan Selisih Anggaran** berada di grup
**Laporan**.

## Menu & Navigasi

| Menu | URL | Grup menu |
|---|---|---|
| Anggaran | `/budgets` | Akuntansi |
| Laporan Selisih Anggaran | `/reports/budget-variance` | Laporan |

## 1. Menampilkan dan Memfilter Anggaran

Tabel anggaran menampilkan kolom **Nama**, **Tahun Fiskal**, **Jenis Anggaran**, **Status**,
**Total Nilai**, **Dibuat Pada**, dan kolom aksi. Jenis anggaran tampil sebagai badge
(`Operational`, `Capital`, `Project`, `Revenue`).

1. Buka menu **Anggaran**.
2. Ketik nama atau deskripsi pada kolom pencarian, lalu tekan **Enter**.
3. Klik **Filter** untuk menyaring berdasarkan:
   - **Status**: Draf, Disetujui, atau Terkunci.
   - **Jenis Anggaran**: Operasional, Modal, Proyek, atau Pendapatan.
   - **Tahun Fiskal**.
4. Klik **Terapkan filter** untuk menjalankan filter atau **Atur ulang** untuk mengembalikan.

## 2. Membuat Anggaran

1. Klik **Tambah**.
2. Isi bagian header formulir **Tambah Anggaran Baru**:
   - **Nama**: nama anggaran, misalnya `Anggaran Operasional 2026`.
   - **Jenis Anggaran**: Operasional, Modal, Proyek, atau Pendapatan.
   - **Tahun Fiskal**: tahun fiskal anggaran.
   - **Deskripsi**: keterangan tambahan (opsional).
3. Pada bagian **Baris Anggaran**, klik **Tambah Baris**.
4. Untuk setiap baris, isi:
   - **Akun**: akun yang dianggarkan. Pilihan akun mengikuti versi COA aktif.
   - **Awal Periode** dan **Akhir Periode**: rentang tanggal alokasi.
   - **Jumlah Dialokasikan**: nominal rencana.
   - **Catatan**: keterangan baris (opsional).
5. Klik ikon pensil untuk menyelesaikan pengisian baris, atau ikon tempat sampah untuk
   menghapusnya. **Total** di bawah tabel dihitung otomatis.
6. Klik **Simpan**.

Anggaran baru tersimpan sebagai **Draf**.

## 3. Mengubah Anggaran

1. Pada baris anggaran berstatus **Draf**, buka menu aksi lalu pilih **Edit**.
2. Ubah header atau baris sesuai kebutuhan. Baris yang sudah ada dapat diubah, dihapus, atau
   ditambah.
3. Klik **Simpan**.

Anggaran berstatus **Disetujui** atau **Terkunci** tidak dapat diubah. Sistem menolak perubahan
dengan pesan bahwa hanya anggaran Draf yang dapat diubah.

## 4. Menyetujui Anggaran

1. Pada baris anggaran berstatus **Draf**, buka menu aksi lalu pilih **Setujui Anggaran**.
2. Dialog konfirmasi menampilkan nama anggaran.
3. Klik **Ya, setujui**.

Status berubah menjadi **Disetujui**, dan sistem mencatat nama penyetuju beserta waktu
persetujuan. Setelah disetujui, nilai anggaran tidak dapat diubah lagi.

## 5. Mengunci Anggaran

1. Pada baris anggaran berstatus **Disetujui**, buka menu aksi lalu pilih **Kunci Anggaran**.
2. Dialog konfirmasi menampilkan nama anggaran.
3. Klik **Ya, kunci**.

Status berubah menjadi **Terkunci**. Anggaran terkunci tidak dapat diubah, disetujui ulang, atau
dihapus.

## 6. Melihat Detail dan Menghapus Anggaran

- Pilih **View** pada menu aksi untuk melihat header, total, pembuat, penyetuju (bila ada), dan
  seluruh baris anggaran.
- Pilih **Delete** untuk menghapus anggaran. Penghapusan hanya diizinkan untuk status **Draf**.
  Konfirmasi akan meminta persetujuan sebelum data dihapus permanen.

## 7. Ekspor Anggaran

Klik **Ekspor** untuk mengunduh daftar anggaran sesuai filter aktif dalam format Excel. File
memuat kolom ID, Name, Fiscal Year, Budget Type, Status, Total Amount, Created By, dan Created At.

## 8. Laporan Selisih Anggaran

Laporan ini membandingkan **alokasi anggaran** dengan **realisasi** dari jurnal yang sudah
diposting.

1. Buka menu **Laporan Selisih Anggaran**.
2. Klik **Filter**, lalu pilih **Budget** pada field **Pilih anggaran**. Pemilihan anggaran wajib
   sebelum laporan ditampilkan; tanpa anggaran, halaman menampilkan petunjuk
   "Pilih budget pada filter untuk melihat varians anggaran.".
3. Opsional, saring **Status** (Sesuai Anggaran, Peringatan, Melebihi Anggaran) dan **Account
   Type** (Aset, Liabilitas, Ekuitas, Pendapatan, Beban).
4. Klik **Terapkan filter**.

Kolom laporan: `Account Code`, `Account Name`, `Type`, `Period Start`, `Period End`, `Allocated`,
`Actual`, `Available`, `Variance %`, dan `Status`. Kolom `Period Start` dan `Period End`
disembunyikan secara default; aktifkan melalui menu **Kolom** bila diperlukan.

Arti status varians:

- **Within Budget** (Sesuai Anggaran): realisasi belum melewati alokasi dan masih di bawah 80%.
- **Warning** (Peringatan): realisasi sudah mencapai minimal 80% dari alokasi.
- **Over Budget** (Melebihi Anggaran): realisasi melebihi alokasi.

Rumus varians: `(Allocated - Actual) / Allocated × 100`. Bila alokasi nol, nilai varians
ditampilkan sebagai `-`.

Realisasi diambil dari baris jurnal berstatus **posted** pada tahun fiskal anggaran dan di dalam
rentang periode baris. Untuk akun beban dan aset, realisasi dihitung `debit - credit`; untuk akun
pendapatan, kewajiban, dan ekuitas dihitung `credit - debit`.

Klik **Ekspor** pada laporan untuk mengunduh hasil dalam file Excel. Nama file diawali
`budget_variance_`. Kolom sama dengan tabel, dan `Variance %` ditulis sebagai angka skala 0–100.

## Aturan & Validasi Penting

- **Nama anggaran wajib diisi**, maksimal 255 karakter.
- **Tahun Fiskal wajib diisi** dan harus tahun fiskal yang sudah ada.
- **Jenis Anggaran wajib diisi**: `operational`, `capital`, `project`, atau `revenue`.
- **Minimal satu baris anggaran** harus diisi.
- **Akun baris wajib diisi** dan harus akun yang sudah ada.
- **Awal Periode** dan **Akhir Periode** wajib berformat `YYYY-MM-DD`, dan akhir periode tidak
  boleh lebih awal dari awal periode.
- **Jumlah Dialokasikan** wajib berupa angka dan tidak boleh negatif.
- Satu akun hanya boleh muncul sekali untuk **tanggal mulai periode yang sama** dalam satu
  anggaran.
- Hanya anggaran **Draf** yang dapat diubah, dihapus, atau disetujui.
- Hanya anggaran **Disetujui** yang dapat dikunci.

## Tanya Jawab Singkat

**Mengapa tombol Setujui tidak muncul?**

Tombol **Setujui Anggaran** hanya muncul untuk anggaran berstatus Draf dan pengguna yang memiliki
izin `budget.edit`.

**Bisakah anggaran yang sudah disetujui diubah?**

Tidak. Setelah disetujui, nilai dan baris terkunci. Perubahan rencana dilakukan melalui anggaran
baru atau revisi pada periode berikutnya.

**Mengapa realisasi berbeda dari total jurnal?**

Realisasi hanya menghitung jurnal berstatus posted pada tahun fiskal anggaran dan di dalam rentang
periode baris. Jurnal draft atau jurnal di luar periode tidak dihitung.

**Mengapa varians menampilkan tanda minus?**

Nilai `Available` negatif berarti realisasi melebihi alokasi (Over Budget). Nilai `Variance %`
negatif menunjukkan pemakaian di atas rencana.

**Apakah laporan bisa diekspor tanpa memilih anggaran?**

Tidak. Anggaran wajib dipilih terlebih dahulu karena laporan dihitung per anggaran.
