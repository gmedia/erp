# User Guide: Posting Journals

## Gambaran Umum

Posting Journals adalah alat batch posting yang memungkinkan akuntan untuk mereview dan memposting beberapa jurnal draft sekaligus. Fitur ini menampilkan daftar jurnal yang belum diposting dengan checkbox untuk seleksi batch. Pengguna dapat memfilter berdasarkan tanggal, tahun fiskal, dan status. Setelah review, pengguna mengklik "Post Selected" untuk memposting semua jurnal yang dipilih secara bersamaan.

Untuk mengakses Posting Journals, pengguna memerlukan permission `posting_journal`. Fitur ini bersifat non-CRUD karena merupakan workflow tool, bukan halaman manajemen data. Posting Journals sangat penting untuk proses tutup buku akhir bulan karena memastikan semua jurnal sudah direview sebelum diposting ke buku besar.

Backend menggunakan `PostJournalAction` untuk validasi batch posting dan eksekusi. Setiap jurnal yang dipilih akan divalidasi apakah balanced (total debit = total kredit) sebelum diposting.

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Posting Journals | /posting-journals | Menampilkan daftar jurnal draft untuk batch posting ke buku besar |

## 1. Mengakses Posting Journals

### Langkah-langkah:

1. Login ke aplikasi dengan akun yang memiliki permission `posting_journal`.
2. Klik menu **Posting Journals** di sidebar atau akses langsung melalui URL `/posting-journals`.
3. Halaman akan menampilkan daftar jurnal dengan status `draft` yang siap untuk diposting.

[Screenshot: Halaman Posting Journals dengan daftar jurnal draft]

### Catatan:

Hanya jurnal dengan status `draft` yang akan ditampilkan di halaman ini. Jurnal yang sudah diposting atau void tidak akan muncul.

## 2. Memahami Tampilan Daftar Jurnal

Halaman Posting Journals menampilkan tabel dengan informasi jurnal draft.

### Kolom yang Ditampilkan:

| Kolom | Deskripsi |
|-------|-----------|
| Checkbox | Untuk memilih jurnal yang akan diposting |
| Journal | Nomor jurnal, tanggal, dan deskripsi |
| Lines | Jumlah baris jurnal dan preview akun |
| Debit | Total debit jurnal |
| Credit | Total kredit jurnal |
| Status | Status jurnal (draft/posted/void) |
| Actions | Tombol untuk melihat detail jurnal |

[Screenshot: Tabel daftar jurnal dengan checkbox dan detail]

### Informasi Ringkasan:

Di bagian atas tabel terdapat informasi ringkasan:

- **Draft journals**: Total jurnal draft yang tersedia
- **Showing**: Rentang data yang ditampilkan di halaman saat ini
- **Page totals**: Total debit dan kredit untuk semua jurnal di halaman saat ini
- **Selected**: Jumlah jurnal yang dipilih
- **Selected totals**: Total debit dan kredit untuk jurnal yang dipilih

## 3. Mencari Jurnal

Gunakan fitur pencarian untuk menemukan jurnal spesifik.

### Langkah-langkah:

1. Temukan field pencarian di bagian kanan atas halaman.
2. Ketik kata kunci pencarian (nomor jurnal, deskripsi, atau referensi).
3. Tabel akan otomatis memfilter berdasarkan kata kunci yang dimasukkan.

[Screenshot: Field pencarian dengan hasil filter]

### Tips Pencarian:

- Pencarian bersifat case-insensitive
- Hasil pencarian akan mencocokkan dengan nomor jurnal, deskripsi, atau referensi
- Kosongkan field pencarian untuk menampilkan semua jurnal

## 4. Memilih Jurnal untuk Posting

Pilih satu atau beberapa jurnal yang akan diposting.

### Memilih Jurnal Individual:

1. Klik checkbox di sisi kiri baris jurnal yang diinginkan.
2. Checkbox akan tercentang dan jurnal masuk ke daftar seleksi.
3. Ulangi untuk jurnal lain yang ingin dipilih.

### Memilih Semua Jurnal di Halaman:

1. Klik checkbox di header tabel (pojok kiri atas).
2. Semua jurnal di halaman saat ini akan terpilih.
3. Klik lagi untuk membatalkan seleksi semua.

[Screenshot: Beberapa jurnal tercentang dengan ringkasan selected totals]

### Membatalkan Seleksi:

Klik tombol **Clear** untuk menghapus semua seleksi. Tombol ini hanya aktif jika ada jurnal yang dipilih.

## 5. Melihat Detail Jurnal

Sebelum memposting, pastikan untuk mereview detail jurnal.

### Langkah-langkah:

1. Klik tombol **Eye** (ikon mata) di kolom Actions pada baris jurnal yang ingin dilihat.
2. Modal detail jurnal akan muncul menampilkan informasi lengkap.
3. Periksa baris-baris jurnal, akun, dan nominal debit/kredit.
4. Pastikan total debit sama dengan total kredit (balanced).
5. Klik tombol **Close** atau klik di luar modal untuk menutup.

[Screenshot: Modal detail jurnal dengan daftar baris debit/kredit]

### Informasi dalam Modal Detail:

- Nomor jurnal dan tanggal
- Deskripsi dan referensi
- Daftar baris jurnal dengan:
  - Kode dan nama akun
  - Nominal debit
  - Nominal kredit
  - Deskripsi per baris (jika ada)
- Total debit dan kredit

## 6. Memposting Jurnal Terpilih

Setelah yakin dengan seleksi, lakukan posting.

### Prasyarat Posting:

- Minimal satu jurnal harus dipilih
- Setiap jurnal yang dipilih harus **balanced** (total debit = total kredit)

### Langkah-langkah:

1. Pilih jurnal-jurnal yang akan diposting (lihat bagian 4).
2. Periksa ringkasan **Selected totals** untuk memastikan jumlahnya benar.
3. Klik tombol **Post Selected (X)** di bagian kanan atas.
4. Sistem akan memvalidasi dan memposting semua jurnal terpilih.
5. Notifikasi sukses akan muncul menampilkan jumlah jurnal yang berhasil diposting.

[Screenshot: Tombol Post Selected dengan notifikasi sukses]

### Hasil Posting:

Setelah posting berhasil:

- Status jurnal berubah dari `draft` menjadi `posted`
- Field `posted_at` terisi dengan timestamp posting
- Field `posted_by` terisi dengan ID user yang melakukan posting
- Jurnal tidak akan lagi muncul di halaman Posting Journals

## 7. Menangani Kegagalan Posting

Jika ada jurnal yang gagal diposting, sistem akan menampilkan informasi kegagalan.

### Penyebab Kegagalan Umum:

| Penyebab | Deskripsi | Solusi |
|----------|-----------|--------|
| Unbalanced journal | Total debit tidak sama dengan total kredit | Edit jurnal di modul Journal Entries, pastikan debit = kredit |
| Already posted | Jurnal sudah pernah diposting | Tidak perlu tindakan, jurnal sudah di buku besar |
| Status changed | Status jurnal berubah (misalnya di-void) | Refresh halaman untuk melihat status terbaru |

### Langkah-langkah Menangani Kegagalan:

1. Perhatikan notifikasi kegagalan yang menampilkan daftar jurnal gagal dan alasannya.
2. Catat nomor jurnal yang gagal.
3. Buka modul **Journal Entries** untuk mengedit jurnal tersebut.
4. Perbaiki masalah (misalnya menambah baris untuk menyeimbangkan).
5. Kembali ke halaman Posting Journals dan posting ulang.

[Screenshot: Notifikasi kegagalan posting dengan detail error]

## 8. Paginasi dan Navigasi Data

Jika jumlah jurnal draft melebihi kapasitas halaman, gunakan fitur paginasi.

### Kontrol Paginasi:

- **Previous/Next**: Navigasi ke halaman sebelumnya atau berikutnya
- **Page number**: Klik nomor halaman untuk langsung menuju halaman tersebut
- **Per page**: Ubah jumlah data per halaman (biasanya 10, 25, 50, 100)

### Catatan Penting:

Seleksi checkbox bersifat per-halaman. Jika Anda memilih jurnal di halaman 1 lalu pindah ke halaman 2, seleksi di halaman 1 tidak akan terbawa. Posting hanya mempengaruhi jurnal yang saat ini tercentang di halaman yang sedang ditampilkan.

[Screenshot: Kontrol paginasi di bagian bawah tabel]

## 9. Workflow Posting Journals

```
Login dengan permission posting_journal
    |
    v
Buka Posting Journals (/posting-journals)
    |
    v
Review Daftar Jurnal Draft
    |
    v
Cari/Filter jika perlu
    |
    v
Pilih Jurnal yang Akan Diposting
    |
    v
Lihat Detail (opsional tapi direkomendasikan)
    |
    v
Verifikasi Selected Totals
    |
    v
Klik Post Selected
    |
    v
+------------------+------------------+
|                  |                  |
v                  v                  v
Sukses           Gagal             Partial
|                  |                  |
v                  v                  v
Jurnal berstatus  Perbaiki di       Review
posted, hilang    Journal Entries    kegagalan
dari daftar       lalu retry         per jurnal
```

## 10. Skenario Penggunaan Umum

### Skenario 1: Posting Jurnal Akhir Bulan

1. Buka halaman Posting Journals pada hari kerja terakhir bulan berjalan.
2. Review seluruh jurnal draft yang ada.
3. Gunakan pencarian jika ada jurnal spesifik yang dicari.
4. Klik tombol detail untuk jurnal-jurnal yang perlu diperiksa.
5. Setelah yakin, klik checkbox di header untuk memilih semua.
6. Klik **Post Selected**.
7. Verifikasi notifikasi sukses.
8. Jika ada kegagalan, perbaiki dan retry.

### Skenario 2: Posting Jurnal Terpilih

1. Buka halaman Posting Journals.
2. Gunakan pencarian untuk menemukan jurnal dengan nomor atau deskripsi tertentu.
3. Klik checkbox hanya untuk jurnal yang ingin diposting.
4. Periksa ringkasan **Selected totals**.
5. Klik **Post Selected**.
6. Ulangi proses untuk jurnal lain jika diperlukan.

### Skenario 3: Review Sebelum Posting Massal

1. Buka halaman Posting Journals.
2. Untuk setiap jurnal di halaman:
   - Klik tombol detail (Eye)
   - Periksa akun-akun yang terlibat
   - Pastikan nominal sesuai
   - Pastikan jurnal balanced
3. Catat jurnal yang bermasalah.
4. Buka modul Journal Entries untuk memperbaiki jurnal bermasalah.
5. Kembali ke Posting Journals dan posting jurnal yang sudah benar.

## FAQ

**Q: Bagaimana cara memposting jurnal secara batch (sekaligus banyak)?**
A: Buka halaman Posting Journals, centang checkbox pada beberapa jurnal draft yang ingin diposting, lalu klik tombol Post. Sistem akan memproses semua jurnal terpilih dalam satu operasi posting. Pastikan setiap jurnal sudah balanced sebelum diposting.

**Q: Kenapa jurnal yang baru saja saya buat tidak muncul di daftar Posting Journals?**
A: Daftar Posting Journals hanya menampilkan jurnal berstatus draft. Jika jurnal dibuat langsung dengan status posted, jurnal tersebut tidak akan muncul. Coba refresh halaman jika jurnal baru saja dibuat dan pastikan filter periode tidak menyembunyikannya.

**Q: Apa yang terjadi setelah jurnal diposting?**
A: Setelah diposting, status jurnal berubah dari draft menjadi posted, saldo akun di buku besar (general ledger) diperbarui, dan field posted_at serta posted_by terisi. Jurnal yang sudah diposting tidak lagi muncul di daftar Posting Journals.

**Q: Bagaimana cara membatalkan posting yang sudah dilakukan?**
A: Tidak ada fitur unpost langsung karena posting bersifat permanen. Untuk mengoreksi jurnal yang sudah diposting, buat jurnal pembalik (reversing entry) di modul Journal Entries dengan mereferensikan jurnal asli, lalu posting jurnal pembalik tersebut.

**Q: Apa tips untuk posting di akhir bulan atau akhir periode?**
A: Pastikan semua transaksi sudah dicatat sebagai draft sebelum mulai posting, posting secara bertahap per jenis transaksi, dan verifikasi daftar Posting Journals kosong sebelum melakukan tutup buku (period closing). Koordinasikan dengan tim akuntansi agar tidak ada jurnal yang tertinggal.

**Q: Berapa jumlah maksimal jurnal yang bisa diposting dalam satu batch?**
A: Sistem tidak menetapkan hard limit, tetapi untuk performa optimal disarankan memposting maksimal 50-100 jurnal per batch. Jika ada ratusan jurnal, lakukan posting secara bertahap agar proses lebih stabil.

**Q: Kenapa posting gagal meskipun jurnal terlihat sudah benar?**
A: Posting bisa gagal karena beberapa kondisi: jurnal tidak balanced (total debit tidak sama dengan total kredit), periode fiskal sudah ditutup (status bukan open), ada lock pada record jurnal akibat akses bersamaan, atau gangguan koneksi database saat proses posting.

**Q: Bagaimana cara verifikasi setelah posting selesai?**
A: Buka modul Journal Entries dan filter berdasarkan status posted untuk memastikan jurnal sudah tercatat. Periksa juga field posted_at dan posted_by pada detail jurnal, serta cek saldo akun terkait di buku besar untuk memastikan pembaruan sudah benar.

**Q: Apa hubungan Posting Journals dengan proses tutup buku (period closing)?**
A: Semua jurnal harus sudah diposting sebelum periode ditutup. Period closing akan mengunci periode sehingga tidak ada lagi jurnal yang bisa diposting ke periode tersebut. Pastikan daftar Posting Journals kosong untuk periode yang akan ditutup.

**Q: Apa tips review sebelum memposting jurnal?**
A: Buka detail setiap jurnal untuk memeriksa akun-akun yang terlibat, pastikan nominal sesuai dokumen sumber, dan konfirmasi jurnal sudah balanced. Catat nomor jurnal yang akan diposting sebagai referensi, dan pastikan tidak ada rekan tim yang sedang mengedit jurnal tersebut.

**Q: Apakah saya bisa memposting jurnal dari periode atau tanggal yang berbeda sekaligus?**
A: Ya, Anda bisa memposting jurnal dari tanggal berbeda dalam satu operasi posting karena sistem tidak membatasi berdasarkan periode. Namun pastikan setiap periode fiskal yang terkait masih berstatus open agar jurnal dapat diposting.

**Q: Di mana saya bisa melihat history posting jurnal?**
A: History posting dapat dilihat dari modul Journal Entries dengan filter status posted, serta dari field posted_at dan posted_by di detail jurnal yang menunjukkan kapan dan oleh siapa jurnal diposting.
