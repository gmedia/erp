# User Guide: Journal Entries

## Gambaran Umum

Journal Entries adalah modul inti akuntansi untuk mencatat transaksi keuangan dalam bentuk jurnal umum. Setiap entri jurnal mengikuti prinsip double-entry bookkeeping di mana total debit harus sama dengan total kredit.

Modul ini digunakan untuk:
- Mencatat transaksi manual yang tidak otomatis dihasilkan dari modul lain
- Membuat adjusting entries untuk penyesuaian akhir periode
- Merekam jurnal penutup (closing entries)
- Memasukkan opening balance saat migrasi data

Setiap journal entry memiliki:
- **Entry Number**: Nomor jurnal otomatis dengan format `JV-{Tahun}-{NomorUrut}`
- **Date**: Tanggal transaksi
- **Reference**: Nomor referensi opsional (misalnya nomor bukti)
- **Description**: Keterangan transaksi
- **Lines**: Daftar baris jurnal (akun, debit, kredit, memo)
- **Status**: Draft atau Posted

[Screenshot: Halaman daftar Journal Entries dengan tabel dan filter]

## Menu & Navigasi

| Menu | Path | Deskripsi |
|------|------|-----------|
| Journal Entries | `/journal-entries` | Daftar semua jurnal umum |
| New Journal Entry | Tombol "Add New" di toolbar | Formulir tambah jurnal baru |
| View Detail | Klik icon mata pada baris | Modal detail jurnal lengkap |
| Edit | Klik icon pensil pada baris (hanya status Draft) | Formulir edit jurnal |
| Delete | Klik icon sampah pada baris (hanya status Draft) | Hapus jurnal |
| Export | Tombol "Export" di toolbar | Ekspor ke Excel |

**Izin Akses**: Diperlukan permission `journal_entry` untuk mengakses modul ini.

---

## 1. Melihat Daftar Journal Entries

Halaman utama menampilkan tabel semua jurnal dengan kolom:

| Kolom | Keterangan | Dapat Diurutkan |
|-------|------------|-----------------|
| Entry Number | Nomor jurnal otomatis | Ya |
| Date | Tanggal transaksi | Ya |
| Description | Keterangan jurnal | Tidak |
| Reference | Nomor referensi | Ya |
| Total Amount | Nilai total (debit/kredit) | Ya |
| Status | Status jurnal (DRAFT/POSTED) | Ya |

[Screenshot: Tabel Journal Entries dengan beberapa data]

### 1.1 Menggunakan Filter

Tersedia beberapa filter untuk memudahkan pencarian:

- **Search**: Cari berdasarkan nomor jurnal, deskripsi, atau referensi
- **Status**: Filter berdasarkan status (Draft atau Posted)
- **Date Range**: Filter berdasarkan rentang tanggal

[Screenshot: Panel filter Journal Entries]

### 1.2 Mengurutkan Data

Klik header kolom yang dapat diurutkan untuk mengurutkan data naik atau turun. Kolom yang dapat diurutkan ditandai dengan ikon panah pada header.

### 1.3 Mengekspor Data

1. Klik tombol **Export** di toolbar atas
2. Sistem akan mengunduh file Excel berisi data jurnal sesuai filter aktif
3. Kolom ekspor meliputi: Entry Number, Date, Description, Reference, Total Amount, Status, dan detail baris

---

## 2. Menambah Journal Entry Baru

### 2.1 Membuka Form Tambah

1. Klik tombol **Add New Journal Entry** di toolbar
2. Formulir tambah jurnal akan muncul

[Screenshot: Form tambah Journal Entry kosong]

### 2.2 Mengisi Header Jurnal

Isi field berikut:
- **Date**: Tanggal transaksi (wajib)
- **Reference**: Nomor referensi/bukti (opsional)
- **Description**: Keterangan transaksi (wajib)

### 2.3 Menambah Baris Jurnal

1. Klik tombol **Add Line** di bagian Journal Lines
2. Dialog form baris akan muncul

[Screenshot: Dialog form baris jurnal]

Isi field pada dialog:
- **Account**: Pilih akun dari Chart of Accounts (wajib)
- **Debit**: Nilai debit (isi salah satu: debit atau kredit)
- **Credit**: Nilai kredit (isi salah satu: debit atau kredit)
- **Memo**: Catatan tambahan untuk baris ini (opsional)
- **Branch**: Cabang terkait (jika multi-cabang)

3. Klik **Save** untuk menambahkan baris
4. Ulangi langkah ini untuk setiap baris jurnal

### 2.4 Menyeimbangkan Jurnal

Sistem akan otomatis menghitung total debit dan kredit. Indikator keseimbangan menunjukkan:
- **Balanced (0)**: Total debit = total kredit, tombol Submit aktif
- **Unbalanced (selisih)**: Total debit ≠ total kredit, tombol Submit nonaktif

[Screenshot: Indikator keseimbangan di form jurnal]

> **Penting**: Jurnal tidak dapat disimpan hingga total debit sama dengan total kredit.

### 2.5 Menyimpan Jurnal

1. Pastikan jurnal sudah seimbang
2. Klik tombol **Submit**
3. Jurnal akan tersimpan dengan status **Draft**

### 2.6 Mengelola Baris Jurnal

Setelah menambah baris, Anda dapat:
- **Edit**: Klik icon pensil pada baris yang ingin diubah
- **Hapus**: Klik icon sampah pada baris yang ingin dihapus
- **Urutan**: Baris dapat diurutkan ulang sesuai kebutuhan

---

## 3. Melihat Detail Journal Entry

### 3.1 Membuka Modal Detail

1. Klik icon **mata** (View) pada baris jurnal yang ingin dilihat
2. Modal detail akan menampilkan informasi lengkap

[Screenshot: Modal detail Journal Entry]

### 3.2 Informasi yang Ditampilkan

Modal detail menampilkan:
- **Header**: Entry Number, Date, Reference, Status, Description
- **Tabel Lines**: Daftar baris jurnal dengan kolom:
  - Account (kode dan nama akun)
  - Debit
  - Credit
  - Memo
- **Footer**: Total Debit dan Total Credit

---

## 4. Mengedit Journal Entry

### 4.1 Syarat Edit

Jurnal hanya dapat diedit jika status masih **Draft**. Jurnal dengan status **Posted** tidak dapat diubah.

### 4.2 Langkah Edit

1. Klik icon **pensil** (Edit) pada baris jurnal dengan status Draft
2. Form edit akan muncul dengan data jurnal yang sudah terisi
3. Ubah field atau baris jurnal sesuai kebutuhan
4. Pastikan jurnal tetap seimbang
5. Klik **Submit** untuk menyimpan perubahan

[Screenshot: Form edit Journal Entry]

---

## 5. Menghapus Journal Entry

### 5.1 Syarat Hapus

Jurnal hanya dapat dihapus jika status masih **Draft**. Jurnal dengan status **Posted** tidak dapat dihapus.

### 5.2 Langkah Hapus

1. Klik icon **sampah** (Delete) pada baris jurnal dengan status Draft
2. Konfirmasi penghapusan pada dialog yang muncul
3. Jurnal akan dihapus permanen dari sistem

> **Peringatan**: Penghapusan jurnal bersifat permanen dan tidak dapat dibatalkan.

---

## 6. Status Journal Entry

### 6.1 Draft

- Status awal saat jurnal baru dibuat
- Masih dapat diedit dan dihapus
- Belum mempengaruhi laporan keuangan
- Belum tercatat di buku besar

### 6.2 Posted

- Status setelah jurnal diposting
- Tidak dapat diedit atau dihapus
- Sudah mempengaruhi laporan keuangan
- Sudah tercatat di buku besar dan trial balance

> **Catatan**: Proses posting dilakukan melalui workflow terpisah atau otomatis tergantung konfigurasi sistem.

---

## 7. Validasi dan Aturan Bisnis

### 7.1 Double-Entry Bookkeeping

Setiap jurnal harus memenuhi prinsip double-entry:
- Total Debit = Total Kredit
- Setiap baris minimal memiliki satu nilai (debit atau kredit)
- Satu baris tidak boleh memiliki nilai debit dan kredit sekaligus

### 7.2 Fiscal Year

- Tanggal jurnal harus berada dalam periode fiscal year yang aktif (status Open)
- Jika tidak ada fiscal year untuk tanggal tersebut, sistem akan menolak penyimpanan
- Fiscal year yang Closed atau Locked tidak dapat menerima jurnal baru

### 7.3 Entry Number

- Entry Number di-generate otomatis dengan format `JV-{Tahun}-{NomorUrut}`
- Nomor bersifat unik per fiscal year
- Tidak dapat diubah manual

### 7.4 Branch (Multi-Cabang)

- Untuk sistem multi-cabang, setiap baris jurnal dapat terkait dengan cabang tertentu
- Transaksi antar cabang akan otomatis menghasilkan jurnal inter-branch clearing

---

## 8. Integrasi dengan Modul Lain

Journal Entries terintegrasi dengan:

| Modul | Hubungan |
|-------|----------|
| Chart of Accounts | Sumber daftar akun untuk baris jurnal |
| Fiscal Years | Penentuan periode fiscal year yang valid |
| Branches | Assign cabang per baris jurnal |
| General Ledger | Posted entries tercatat di buku besar |
| Trial Balance | Posted entries mempengaruhi saldo akun |
| Financial Reports | Source data untuk laporan keuangan |

---

## FAQ

**Q: Bagaimana cara membuat adjusting entry?**

Buat journal entry baru dengan tanggal pada akhir periode. Isi deskripsi dengan keterangan "Adjusting Entry", masukkan baris-baris penyesuaian sesuai kebutuhan, dan pastikan jurnal seimbang sebelum menyimpan.

**Q: Mengapa tombol Submit tidak aktif?**

Tombol Submit tidak aktif jika total debit tidak sama dengan total kredit, ada field wajib yang belum diisi, atau tanggal jurnal di luar periode fiscal year yang aktif.

**Q: Apakah jurnal yang sudah posted bisa dibatalkan?**

Jurnal yang sudah posted tidak dapat diedit atau dihapus. Jika terjadi kesalahan, buat jurnal pembalikan (reversing entry) dengan nilai debit dan kredit yang dipertukarkan.

**Q: Bagaimana cara mencari jurnal tertentu?**

Gunakan filter Search untuk mencari berdasarkan nomor jurnal, deskripsi, atau nomor referensi. Filter Date Range untuk membatasi periode, dan Status untuk memfilter berdasarkan status jurnal.

**Q: Apa yang terjadi jika fiscal year sudah ditutup?**

Jika fiscal year sudah Closed atau Locked, tidak dapat membuat jurnal baru dengan tanggal dalam periode tersebut, tidak dapat mengedit atau menghapus jurnal existing, dan laporan keuangan untuk periode tersebut sudah final.

**Q: Bagaimana format nomor jurnal?**

Format: `JV-{Tahun}-{NomorUrut5Digit}`. Contoh: `JV-2025-00001`. Nomor di-generate otomatis dan berurutan per fiscal year.

**Q: Apakah bisa import journal entry dari file?**

Saat ini journal entry harus dibuat manual melalui form. Untuk volume besar, hubungi administrator sistem untuk proses batch upload.

**Q: Apa perbedaan jurnal Draft dan Posted?**

Draft adalah status awal saat jurnal baru dibuat — masih bisa diedit dan dihapus, belum mempengaruhi laporan keuangan. Posted berarti jurnal sudah final — tidak bisa diedit/dihapus, sudah tercatat di buku besar dan mempengaruhi trial balance.

**Q: Bagaimana jika satu baris jurnal butuh lebih dari satu akun?**

Setiap baris jurnal hanya bisa memiliki satu akun. Jika transaksi melibatkan banyak akun, tambahkan beberapa baris jurnal — masing-masing dengan akun, nilai debit atau kredit, dan memo tersendiri.

**Q: Apakah journal entry bisa memiliki cabang berbeda per baris?**

Ya. Untuk sistem multi-cabang, setiap baris jurnal dapat di-assign ke cabang tertentu. Ini berguna untuk transaksi antar cabang yang akan otomatis menghasilkan jurnal inter-branch clearing.

**Q: Tips pengisian journal entry yang efisien?**

Siapkan semua data (tanggal, akun, nilai) sebelum mulai mengisi. Gunakan Reference dengan konsisten untuk memudahkan tracing. Tulis Description yang jelas untuk audit trail. Periksa keseimbangan debit-kredit sebelum submit. Review semua detail di modal View sebelum melakukan posting.
