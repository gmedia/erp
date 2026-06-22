# User Guide: Recurring Journals

## Gambaran Umum

Recurring Journals adalah modul untuk mengelola jurnal berulang yang dijadwalkan secara otomatis. Modul ini memungkinkan Anda membuat template jurnal yang akan dieksekusi secara berkala (harian, mingguan, bulanan, triwulanan, atau tahunan) tanpa perlu membuat jurnal manual setiap periode.

Modul ini digunakan untuk:
- Mencatat beban rutin seperti sewa, gaji, dan utilitas
- Membuat amortisasi dan penyusutan berkala
- Menjadwalkan alokasi biaya antar departemen
- Mengotomatisasi jurnal penyesuaian akhir periode

Setiap recurring journal memiliki:
- **Name**: Nama jurnal berulang
- **Frequency**: Frekuensi eksekusi (Daily, Weekly, Monthly, Quarterly, Yearly)
- **Next Run Date**: Tanggal eksekusi berikutnya
- **Auto Post**: Opsi posting otomatis ke buku besar
- **Reference Template**: Template untuk generate nomor referensi otomatis
- **Description Template**: Template untuk generate deskripsi jurnal
- **Lines**: Daftar baris jurnal (akun, debit, kredit, memo, cabang)
- **Status**: Active atau Inactive

Sistem secara otomatis menjalankan jurnal yang jatuh tempo setiap hari melalui penjadwal `recurring-journals:execute`. Anda juga dapat mengeksekusi jurnal secara manual kapan saja.

[Screenshot: Halaman daftar Recurring Journals dengan tabel dan filter]

## Menu & Navigasi

| Menu | URL | Permission / Function |
|------|-----|----------------------|
| Recurring Journals | `/recurring-journals` | `recurring_journal` — Melihat daftar jurnal berulang |
| Add New | Tombol "Add New" di toolbar | `recurring_journal.create` — Membuat jurnal berulang baru |
| View Detail | Klik icon mata pada baris | `recurring_journal` — Melihat detail jurnal |
| Edit | Klik icon pensil pada baris | `recurring_journal.edit` — Mengedit jurnal |
| Delete | Klik icon sampah pada baris | `recurring_journal.delete` — Menghapus jurnal |
| Export | Tombol "Export" di toolbar | `recurring_journal` — Mengekspor data ke Excel |
| Execute | Tombol di modal detail / endpoint API | `recurring_journal` — Menjalankan jurnal secara manual |

---

## 1. Melihat Daftar Recurring Journals

Halaman utama menampilkan tabel semua jurnal berulang dengan kolom:

| Kolom | Keterangan | Dapat Diurutkan |
|-------|------------|-----------------|
| Name | Nama jurnal berulang | Ya |
| Frequency | Frekuensi (Daily/Weekly/Monthly/Quarterly/Yearly) | Ya |
| Next Run Date | Tanggal eksekusi berikutnya | Ya |
| Total Amount | Total nilai debit | Ya |
| Auto Post | Status auto post (Yes/No) | Ya |
| Is Active | Status aktif (Active/Inactive) | Ya |
| Created At | Tanggal pembuatan | Ya |

[Screenshot: Tabel Recurring Journals dengan beberapa data]

### 1.1 Menggunakan Filter

Tersedia tiga filter untuk memudahkan pencarian:

- **Search**: Cari berdasarkan nama atau deskripsi jurnal
- **Frequency**: Filter berdasarkan frekuensi (Daily, Weekly, Monthly, Quarterly, Yearly)
- **Status**: Filter berdasarkan status (Active atau Inactive)

[Screenshot: Panel filter Recurring Journals]

### 1.2 Mengurutkan Data

Klik header kolom yang dapat diurutkan untuk mengurutkan data naik atau turun. Kolom yang dapat diurutkan: Name, Frequency, Next Run Date, Total Amount, Auto Post, Is Active, Created At.

### 1.3 Mengekspor Data

1. Klik tombol **Export** di toolbar atas
2. Sistem akan mengunduh file Excel berisi data jurnal berulang sesuai filter aktif
3. Kolom ekspor meliputi: ID, Name, Frequency, Next Run Date, Last Run Date, Total Amount, Auto Post, Active, Fiscal Year, Created By

---

## 2. Menambah Recurring Journal Baru

### 2.1 Membuka Form Tambah

1. Klik tombol **Add New** di toolbar
2. Formulir tambah recurring journal akan muncul

[Screenshot: Form tambah Recurring Journal kosong]

### 2.2 Mengisi Header Jurnal

Isi field berikut:

| Field | Keterangan | Wajib |
|-------|------------|-------|
| Name | Nama jurnal berulang (maks. 255 karakter) | Ya |
| Frequency | Pilih frekuensi: Daily, Weekly, Monthly, Quarterly, Yearly | Ya |
| Next Run Date | Tanggal eksekusi pertama/berikutnya | Ya |
| Auto Post | Centang jika jurnal hasil eksekusi langsung diposting | Tidak |
| Is Active | Centang untuk mengaktifkan jadwal | Tidak (default: aktif) |
| Reference Template | Template nomor referensi (contoh: `RJ-{YYYY}-{MM}-{DD}`) | Tidak |
| Description Template | Template deskripsi jurnal | Ya |

**Catatan tentang template**:
- `{YYYY}` akan diganti dengan tahun saat eksekusi
- `{MM}` akan diganti dengan bulan saat eksekusi
- `{DD}` akan diganti dengan tanggal saat eksekusi
- Contoh: Template `RJ-{YYYY}-{MM}-{DD}` akan menghasilkan `RJ-2026-06-22`

### 2.3 Menambah Baris Jurnal

1. Klik tombol **Add Line** di bagian Journal Lines
2. Dialog form baris akan muncul

[Screenshot: Dialog form baris Recurring Journal]

Isi field pada dialog:

| Field | Keterangan | Wajib |
|-------|------------|-------|
| Account | Pilih akun dari Chart of Accounts (hanya akun aktif tanpa sub-akun) | Ya |
| Branch | Pilih cabang terkait (jika multi-cabang) | Tidak |
| Debit | Nilai debit (minimal 0) | Tidak (default: 0) |
| Credit | Nilai kredit (minimal 0) | Tidak (default: 0) |
| Memo | Catatan tambahan untuk baris ini | Tidak |

3. Klik **Save** untuk menambahkan baris
4. Ulangi langkah ini untuk setiap baris jurnal (minimal 1 baris)

### 2.4 Menyeimbangkan Jurnal

Sistem akan otomatis menghitung total debit dan kredit. Indikator keseimbangan menunjukkan:
- **Balanced**: Total debit = total kredit, tombol Submit aktif
- **Unbalanced**: Total debit != total kredit, peringatan ditampilkan

[Screenshot: Indikator keseimbangan di form Recurring Journal]

> **Penting**: Jurnal tidak dapat disimpan hingga total debit sama dengan total kredit. Ini adalah prinsip double-entry bookkeeping.

### 2.5 Menyimpan Jurnal

1. Pastikan jurnal sudah seimbang (total debit = total kredit)
2. Klik tombol **Submit**
3. Jurnal berulang akan tersimpan dan siap dieksekusi sesuai jadwal

### 2.6 Mengelola Baris Jurnal

Setelah menambah baris, Anda dapat:
- **Edit**: Klik icon pensil pada baris yang ingin diubah
- **Hapus**: Klik icon sampah pada baris yang ingin dihapus
- **Total**: Footer tabel menampilkan total debit dan total kredit

---

## 3. Melihat Detail Recurring Journal

### 3.1 Membuka Modal Detail

1. Klik icon **mata** (View) pada baris jurnal yang ingin dilihat
2. Modal detail akan menampilkan informasi lengkap

[Screenshot: Modal detail Recurring Journal]

### 3.2 Informasi yang Ditampilkan

Modal detail menampilkan:

| Field | Keterangan |
|-------|------------|
| Name | Nama jurnal berulang |
| Frequency | Frekuensi eksekusi |
| Next Run Date | Tanggal eksekusi berikutnya |
| Last Run Date | Tanggal eksekusi terakhir (atau "-" jika belum pernah) |
| Auto Post | Status auto post (Yes/No) |
| Status | Active atau Inactive |
| Total Amount | Total nilai debit |
| Description | Deskripsi jurnal (template yang sudah dirender) |

**Tabel Lines**: Daftar baris jurnal dengan kolom:
- Account (kode dan nama akun)
- Debit
- Credit
- Memo

**Footer**: Total Debit dan Total Credit

---

## 4. Mengedit Recurring Journal

### 4.1 Langkah Edit

1. Klik icon **pensil** (Edit) pada baris jurnal yang ingin diubah
2. Form edit akan muncul dengan data jurnal yang sudah terisi
3. Ubah field header atau baris jurnal sesuai kebutuhan

[Screenshot: Form edit Recurring Journal]

### 4.2 Perilaku Edit Baris

Saat mengedit, sistem akan:
- Mengganti seluruh baris jurnal (delete semua baris lama + create baris baru)
- Menghitung ulang total_amount secara otomatis
- Memvalidasi keseimbangan debit-kredit

4. Pastikan jurnal tetap seimbang
5. Klik **Submit** untuk menyimpan perubahan

---

## 5. Menghapus Recurring Journal

### 5.1 Langkah Hapus

1. Klik icon **sampah** (Delete) pada baris jurnal yang ingin dihapus
2. Konfirmasi penghapusan pada dialog yang muncul
3. Jurnal dan seluruh barisnya akan dihapus permanen dari sistem

> **Peringatan**: Penghapusan bersifat permanen dan tidak dapat dibatalkan. Sistem akan menghapus semua baris jurnal terlebih dahulu, kemudian menghapus jurnal itu sendiri.

---

## 6. Mengeksekusi Recurring Journal

### 6.1 Eksekusi Otomatis

Sistem menjalankan penjadwal `recurring-journals:execute` setiap hari secara otomatis. Penjadwal akan:
1. Mencari semua jurnal yang jatuh tempo (due)
2. Jurnal dianggap due jika: `is_active = true` DAN `next_run_date <= hari ini` DAN (`end_date` kosong ATAU `end_date >= hari ini`)
3. Menjalankan setiap jurnal yang due
4. Membuat Journal Entry baru untuk setiap jurnal
5. Memperbarui `last_run_date` ke hari ini
6. Memajukan `next_run_date` berdasarkan frekuensi

### 6.2 Eksekusi Manual

Anda dapat mengeksekusi jurnal secara manual melalui API endpoint:
- Endpoint: `POST /api/recurring-journals/{id}/execute`
- Hanya dapat dilakukan jika jurnal dalam status Active

### 6.3 Hasil Eksekusi

Saat jurnal dieksekusi:
- Journal Entry baru dibuat dengan tipe `recurring`
- Entry Number di-generate otomatis dengan format `RJ-YYYYmmddHHiiss-NNN`
- Jika `auto_post = true`: status journal entry langsung **Posted**
- Jika `auto_post = false`: status journal entry **Draft**
- `last_run_date` diperbarui ke tanggal eksekusi
- `next_run_date` dimajukan sesuai frekuensi:
  - Daily → +1 hari
  - Weekly → +1 minggu
  - Monthly → +1 bulan
  - Quarterly → +1 kuartal
  - Yearly → +1 tahun

### 6.4 Batas Akhir (End Date)

Jika jurnal memiliki `end_date`:
- Jurnal tidak akan dieksekusi lagi setelah `next_run_date` melewati `end_date`
- Jurnal akan otomatis dikeluarkan dari daftar due

---

## 7. Validasi dan Aturan Bisnis

### 7.1 Double-Entry Bookkeeping

Setiap recurring journal harus memenuhi prinsip double-entry:
- Total Debit = Total Kredit (dicek dengan presisi 2 desimal)
- Setiap baris memiliki setidaknya satu nilai (debit atau kredit)
- Minimal 1 baris jurnal

### 7.2 Frekuensi dan Penjadwalan

| Frekuensi | Penambahan Next Run Date |
|-----------|--------------------------|
| Daily | +1 hari |
| Weekly | +1 minggu |
| Monthly | +1 bulan |
| Quarterly | +3 bulan |
| Yearly | +1 tahun |

### 7.3 Auto Post

- Jika Auto Post diaktifkan, journal entry hasil eksekusi akan langsung berstatus **Posted**
- Jika tidak diaktifkan, journal entry akan berstatus **Draft** dan perlu diposting manual
- Auto Post cocok untuk jurnal rutin yang sudah pasti kebenarannya

### 7.4 Template Reference

- Reference template mendukung placeholder `{YYYY}`, `{MM}`, `{DD}`
- Placeholder akan diganti dengan nilai aktual saat eksekusi
- Jika template kosong, reference akan kosong

### 7.5 Branch

- Setiap baris jurnal dapat dikaitkan dengan cabang tertentu (opsional)
- Transaksi antar cabang akan ditangani oleh sistem Inter-Branch Clearing secara otomatis

---

## 8. Integrasi dengan Modul Lain

| Modul | Hubungan |
|-------|----------|
| Chart of Accounts | Sumber daftar akun untuk baris jurnal |
| Fiscal Years | Referensi tahun fiskal jurnal |
| Branches | Assign cabang per baris jurnal |
| Journal Entries | Hasil eksekusi recurring journal |
| General Ledger | Posted entries tercatat di buku besar |
| Trial Balance | Posted entries mempengaruhi saldo akun |
| Financial Reports | Source data untuk laporan keuangan |

---

## FAQ & Tips

**Q: Apa perbedaan Recurring Journal dengan Journal Entry biasa?**

**J:** Recurring Journal adalah template jurnal yang dijadwalkan berulang, sedangkan Journal Entry adalah jurnal satu kali. Recurring Journal akan otomatis membuat Journal Entry baru setiap kali dieksekusi sesuai jadwal.

**Q: Bagaimana jika saya ingin menghentikan jurnal berulang sementara?**

**J:** Nonaktifkan jurnal dengan mengedit dan menghapus centang pada **Is Active**. Jurnal yang tidak aktif tidak akan dieksekusi oleh penjadwal otomatis maupun manual.

**Q: Apakah bisa mengatur jurnal berulang dengan batas akhir?**

**J:** Ya. Isi field **End Date** pada form. Jurnal akan otomatis berhenti dieksekusi setelah `next_run_date` melewati tanggal tersebut.

**Q: Bagaimana cara mengeksekusi jurnal sebelum jadwalnya?**

**J:** Gunakan API endpoint `POST /api/recurring-journals/{id}/execute` untuk mengeksekusi jurnal secara manual kapan saja, tanpa menunggu penjadwal otomatis.

**Q: Apa yang terjadi jika Auto Post diaktifkan?**

**J:** Setiap Journal Entry yang dihasilkan dari eksekusi recurring journal akan langsung berstatus **Posted** dan tercatat di buku besar. Jika tidak diaktifkan, journal entry akan berstatus **Draft** dan perlu diposting manual.

**Q: Mengapa tombol Submit tidak aktif saat membuat jurnal baru?**

**J:** Tombol Submit tidak aktif jika:
- Total debit tidak sama dengan total kredit
- Ada field wajib yang belum diisi (Name, Frequency, Next Run Date, Description Template)
- Jumlah baris jurnal kurang dari 1
- Ada baris jurnal yang belum memilih akun

**Q: Bagaimana cara menggunakan Reference Template?**

**J:** Isi field Reference Template dengan format yang diinginkan. Gunakan placeholder:
- `{YYYY}` untuk tahun
- `{MM}` untuk bulan
- `{DD}` untuk tanggal

Contoh: Template `RJ-{YYYY}-{MM}-{DD}` akan menghasilkan nomor referensi seperti `RJ-2026-06-22` saat dieksekusi.

**Q: Apakah recurring journal yang sudah dieksekusi bisa dihapus?**

**J:** Ya, recurring journal dapat dihapus kapan saja. Namun perlu diperhatikan:
- Penghapusan hanya menghapus template jurnal dan barisnya
- Journal Entry yang sudah dibuat dari eksekusi sebelumnya TIDAK akan terhapus
- Journal Entry yang sudah Posted tetap ada di buku besar

**Q: Apa yang terjadi jika ada kesalahan pada baris jurnal setelah dieksekusi?**

**J:** Jika journal entry hasil eksekusi sudah Posted, buat jurnal pembalik (reversing entry) manual melalui modul Journal Entries. Jika masih Draft, Anda dapat mengedit journal entry tersebut langsung.

**Q: Tips penggunaan Recurring Journal yang efektif**

**J:** 
1. **Beri nama yang deskriptif**: Gunakan nama yang jelas seperti "Sewa Kantor Bulanan" atau "Penyusutan Kendaraan Tahunan"
2. **Gunakan Auto Post dengan bijak**: Aktifkan hanya untuk jurnal yang sudah pasti benar dan tidak perlu review
3. **Manfaatkan template**: Gunakan Reference Template dan Description Template untuk konsistensi penamaan
4. **Set end date untuk jurnal sementara**: Jika jurnal hanya berlaku untuk periode tertentu, isi end date agar tidak lupa menonaktifkan
5. **Review berkala**: Periksa daftar recurring journal secara berkala untuk memastikan tidak ada jurnal yang sudah tidak relevan
