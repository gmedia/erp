# User Guide: Inter-Branch Clearing (Transaksi Antar Cabang)

## Gambaran Umum

Inter-Branch Clearing adalah mekanisme yang memungkinkan **satu jurnal mencatat transaksi yang melibatkan lebih dari satu cabang** sambil tetap menjaga setiap cabang seimbang secara mandiri (debit = kredit per cabang).

Saat sebuah jurnal menyentuh dua cabang atau lebih, sistem **otomatis menyisipkan baris penyeimbang** ke akun kliring khusus `1999-IBC` (Inter-Branch Clearing) untuk setiap cabang yang tidak seimbang. Hasilnya:

- Setiap cabang punya laporan keuangan yang utuh dan seimbang sendiri.
- Saldo kliring antar cabang **saling meniadakan menjadi nol** secara company-wide.
- Di laporan per cabang, saldo kliring tampil sebagai **Due From** (piutang antar cabang) atau **Due To** (utang antar cabang).

> **Penting**: Fitur ini bekerja **transparan**. Untuk jurnal satu cabang (mayoritas transaksi), tidak ada perubahan apa pun — tidak ada baris kliring yang disisipkan.

---

## Konsep Kunci

| Istilah | Penjelasan |
|---------|------------|
| **Akun Kliring** | Akun dengan kode `1999-IBC` ("Inter-Branch Clearing"). Wajib ada di COA Version tahun fiskal terkait. |
| **Branch per Baris** | Setiap baris jurnal kini punya `branch_id` sendiri, terpisah dari cabang di header. |
| **Auto-Inject** | Sistem menambah baris kliring otomatis hanya jika satu jurnal menyentuh ≥ 2 cabang dan ada cabang yang belum seimbang. |
| **Due From** | Saldo kliring positif di sebuah cabang → cabang lain berutang ke cabang ini (disajikan sebagai **aset**). |
| **Due To** | Saldo kliring negatif di sebuah cabang → cabang ini berutang ke cabang lain (disajikan sebagai **liabilitas**). |

---

## Kapan Baris Kliring Disisipkan?

Sistem mengevaluasi setiap jurnal saat disimpan:

1. **Jurnal satu cabang** → tidak ada perubahan. Baris disimpan apa adanya.
2. **Jurnal multi-cabang yang sudah seimbang per cabang** → tidak ada perubahan (tidak perlu kliring).
3. **Jurnal multi-cabang dengan cabang tidak seimbang** → sistem menyisipkan satu baris `1999-IBC` per cabang yang net-nya bukan nol, agar setiap cabang seimbang.

### Contoh

Kantor Pusat (Cabang A) membayar beban Rp 1.000.000 milik Cabang B dari kas Pusat:

```
Baris input:
  Beban Operasional (52000) — Cabang B   Debit  1.000.000
  Kas (11110)               — Cabang A   Kredit 1.000.000
```

Tanpa kliring, Cabang A net -1.000.000 dan Cabang B net +1.000.000 → tidak seimbang per cabang. Sistem otomatis menambahkan:

```
Baris hasil auto-inject:
  Beban Operasional (52000) — Cabang B    Debit  1.000.000
  Kas (11110)               — Cabang A    Kredit 1.000.000
  1999-IBC (Clearing)       — Cabang A    Debit  1.000.000   ← Due From B
  1999-IBC (Clearing)       — Cabang B    Kredit 1.000.000   ← Due To A
```

Sekarang:
- **Cabang A** seimbang: Kas keluar 1.000.000, diimbangi piutang kliring (Due From) 1.000.000.
- **Cabang B** seimbang: Beban 1.000.000, diimbangi utang kliring (Due To) 1.000.000.
- **Company-wide**: saldo `1999-IBC` = +1.000.000 (A) − 1.000.000 (B) = **0**.

---

## Cara Pakai (Membuat Jurnal Multi-Cabang)

### Journal Entries

1. Buka menu **Journal Entries** → klik **Add New**.
2. Isi header seperti biasa (Date, Description, Fiscal Year, dll).
3. Klik **Add Line** untuk membuka dialog baris.
4. Di dialog baris terdapat **dua pilihan penting**:
   - **Account** — akun yang dipakai.
   - **Branch** — cabang untuk baris ini (boleh berbeda dari cabang baris lain).
5. Isi Debit/Kredit, lalu Save baris.
6. Ulangi untuk baris dengan cabang berbeda jika diperlukan.
7. Klik **Save** — jika jurnal menyentuh > 1 cabang dan ada yang belum seimbang, baris `1999-IBC` muncul otomatis setelah disimpan.

> **Tidak perlu menambah baris `1999-IBC` secara manual.** Jika Anda menambahkannya manual, sistem akan menghapus dan menghitung ulang versinya sendiri agar konsisten.

### Recurring Journals

Pola yang sama berlaku untuk **Recurring Journals** — dialog baris recurring juga punya pemilih **Branch** per baris. Saat recurring dieksekusi, auto-inject kliring berjalan sama seperti jurnal biasa.

### Bank Reconciliation

Header **Bank Reconciliation** kini punya field **Branch** untuk menetapkan cabang rekonsiliasi. Jurnal yang dihasilkan mengikuti aturan kliring yang sama jika menyentuh lebih dari satu cabang.

---

## Membaca Laporan Per Cabang

Semua laporan keuangan inti memfilter berdasarkan **branch per baris**, bukan cabang header. Saat Anda memfilter laporan ke satu cabang:

| Laporan | Perilaku saldo kliring |
|---------|------------------------|
| **Trial Balance** | Baris `1999-IBC` muncul dengan saldo net cabang tersebut. Debit = Kredit tetap terjaga. |
| **Balance Sheet** | Saldo `1999-IBC` **diklasifikasi ulang otomatis**: net positif → **Due From (Aset)**, net negatif → **Due To (Liabilitas)**. Aset = Liabilitas + Ekuitas tetap balance. |
| **Income Statement** | Beban/pendapatan muncul di cabang sesuai `branch_id` baris, bukan cabang header. |
| **General Ledger** | Mutasi `1999-IBC` per cabang dapat ditelusuri per jurnal. |

> **Company-wide** (tanpa filter cabang): saldo `1999-IBC` selalu **nol** karena Due From satu cabang meniadakan Due To cabang lain.

---

## Validasi & Pengaman Otomatis

| Validasi | Penjelasan |
|----------|------------|
| **Per-branch balance guard** | Setelah auto-inject, sistem memastikan setiap cabang net = 0. Jika gagal, jurnal ditolak. |
| **Branch wajib saat multi-cabang** | Jika jurnal multi-cabang punya baris tanpa cabang (`branch_id` kosong), penyimpanan ditolak dengan pesan jelas. |
| **Akun kliring wajib ada** | Jika `1999-IBC` belum ada di COA Version tahun fiskal terkait, jurnal multi-cabang ditolak. Pastikan seeder akun kliring sudah dijalankan. |
| **Resolusi per COA Version** | Akun kliring di-resolve berdasarkan **kode** `1999-IBC` pada COA Version aktif tahun fiskal, bukan ID hardcode. |

---

## Monitoring Otomatis

Sistem menjalankan pemantauan terjadwal untuk mendeteksi jurnal lintas cabang:

- **Command**: `journals:detect-cross-branch --posted-only`
- **Jadwal**: otomatis setiap **Senin pukul 06:00**.
- **Perilaku**: jika ditemukan jurnal multi-cabang ekonomis, sistem menulis `Log::warning` ("Cross-branch journals detected by scheduled monitor.") berisi jumlah entry multi-cabang, baris kliring, dan baris tanpa cabang — agar muncul di log/Sentry. Jika nol, monitor diam.

Untuk pengecekan manual kapan saja:

```bash
sail artisan journals:detect-cross-branch            # semua entry
sail artisan journals:detect-cross-branch --posted-only   # hanya yang sudah posted
sail artisan journals:detect-cross-branch --limit=50      # batasi contoh yang ditampilkan
```

---

## Tips & Best Practices

1. **Biarkan sistem yang menyisipkan kliring** — jangan menambah baris `1999-IBC` manual; sistem menghitung ulang otomatis.
2. **Tetapkan Branch di setiap baris** untuk jurnal yang menyentuh lebih dari satu cabang — baris tanpa cabang akan ditolak pada konteks multi-cabang.
3. **Gunakan Balance Sheet per cabang** untuk melihat posisi Due From / Due To antar cabang.
4. **Pastikan akun `1999-IBC` ter-seed** di setiap COA Version tahun fiskal sebelum mencatat transaksi antar cabang.
5. **Pantau log warning mingguan** sebagai sinyal bahwa volume transaksi antar cabang mulai material.
6. **Untuk transaksi satu cabang**, tidak ada yang berubah — fitur ini sepenuhnya transparan.

---

## FAQ

**Q: Apakah saya harus mengubah cara mencatat jurnal satu cabang?**
A: Tidak. Untuk jurnal yang hanya menyentuh satu cabang, tidak ada baris kliring yang disisipkan dan perilaku sama persis seperti sebelumnya.

**Q: Mengapa muncul baris `1999-IBC` yang tidak saya buat?**
A: Itu baris penyeimbang otomatis karena jurnal Anda menyentuh lebih dari satu cabang. Baris ini menjaga setiap cabang tetap seimbang.

**Q: Apa beda Due From dan Due To?**
A: Due From = cabang lain berutang ke cabang ini (aset). Due To = cabang ini berutang ke cabang lain (liabilitas). Keduanya adalah dua sisi dari saldo kliring yang sama dan saling meniadakan company-wide.

**Q: Jurnal multi-cabang saya ditolak. Kenapa?**
A: Kemungkinan: (1) ada baris tanpa cabang pada jurnal multi-cabang, atau (2) akun `1999-IBC` belum ada di COA Version tahun fiskal terkait. Periksa kedua hal tersebut.

**Q: Bagaimana cara tahu apakah ada transaksi antar cabang di sistem?**
A: Jalankan `sail artisan journals:detect-cross-branch`, atau cek log warning mingguan dari monitor terjadwal.

**Q: Apa itu Inter-Branch Clearing secara sederhana?**
A: Mekanisme yang membuat satu jurnal bisa mencatat transaksi lintas cabang sambil menjaga setiap cabang tetap seimbang sendiri. Sistem otomatis menambah baris penyeimbang ke akun kliring `1999-IBC` saat sebuah jurnal menyentuh dua cabang atau lebih.

**Q: Bagaimana cara membuat jurnal yang melibatkan dua cabang?**
A: Buka Journal Entries → Add New, isi header, lalu klik Add Line. Pada dialog baris pilih Account dan Branch untuk masing-masing baris (boleh berbeda cabang). Setelah Save, baris `1999-IBC` muncul otomatis jika ada cabang yang belum seimbang.

**Q: Apakah saya perlu menambahkan baris akun kliring `1999-IBC` secara manual?**
A: Tidak. Sistem yang menyisipkannya otomatis. Bahkan jika Anda menambahkannya manual, sistem akan menghapus dan menghitung ulang versinya sendiri agar konsisten.

**Q: Kapan tepatnya baris kliring otomatis muncul?**
A: Hanya saat satu jurnal menyentuh dua cabang atau lebih DAN ada cabang yang net debit/kreditnya belum nol. Jurnal satu cabang dan jurnal multi-cabang yang sudah seimbang per cabang tidak mendapat baris kliring.

**Q: Mengapa saya harus mengisi Branch di setiap baris jurnal?**
A: Karena saldo dan laporan dihitung per branch baris, bukan cabang di header. Pada jurnal multi-cabang, baris tanpa cabang (`branch_id` kosong) akan ditolak agar saldo per cabang tetap akurat.

**Q: Di mana saya bisa melihat posisi Due From dan Due To antar cabang?**
A: Pada Balance Sheet yang difilter per cabang. Saldo `1999-IBC` diklasifikasi ulang otomatis: net positif tampil sebagai Due From (aset), net negatif sebagai Due To (liabilitas).

**Q: Mengapa saldo `1999-IBC` company-wide selalu nol?**
A: Karena Due From di satu cabang selalu diimbangi Due To di cabang lawan. Saat laporan dilihat tanpa filter cabang, kedua sisi saling meniadakan sehingga net-nya nol.

**Q: Apakah Recurring Journals dan Bank Reconciliation juga mendukung antar cabang?**
A: Ya. Dialog baris Recurring Journals punya pemilih Branch per baris, dan saat dieksekusi auto-inject kliring berjalan sama. Bank Reconciliation punya field Branch di header, dan jurnal hasilnya mengikuti aturan kliring yang sama.

**Q: Jurnal multi-cabang saya ditolak dengan pesan akun kliring tidak ditemukan. Apa solusinya?**
A: Akun `1999-IBC` belum ada di COA Version tahun fiskal terkait. Pastikan seeder akun kliring sudah dijalankan untuk COA Version aktif pada tahun fiskal tersebut, lalu coba simpan ulang.

**Q: Apakah fitur ini memengaruhi transaksi satu cabang yang sudah saya catat selama ini?**
A: Tidak sama sekali. Untuk jurnal satu cabang tidak ada baris kliring yang disisipkan dan perilakunya identik seperti sebelumnya. Fitur ini sepenuhnya transparan untuk transaksi non-lintas-cabang.

**Q: Seberapa sering sistem memeriksa transaksi antar cabang secara otomatis?**
A: Monitor terjadwal berjalan otomatis setiap Senin pukul 06:00 menjalankan `journals:detect-cross-branch --posted-only`. Jika ditemukan jurnal multi-cabang, sistem menulis `Log::warning` agar muncul di log/Sentry; jika tidak ada, monitor diam.
