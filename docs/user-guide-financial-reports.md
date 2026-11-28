# Panduan: Laporan Keuangan

Panduan ini mencakup tujuh laporan keuangan pada menu **Laporan**: Neraca, Laba Rugi, Arus Kas,
Perbandingan, Neraca Saldo, Neraca Saldo Rinci, dan Buku Besar. Semua halaman bersifat hanya baca:
data diambil dari jurnal yang sudah diposting, bukan dari input langsung di halaman laporan.

## Gambaran Umum

| Laporan | URL | Isi utama |
|---|---|---|
| Laporan Neraca | `/reports/balance-sheet` | Aset, liabilitas, dan ekuitas pada satu tahun fiskal |
| Laporan Laba Rugi | `/reports/income-statement` | Pendapatan, beban, dan laba bersih |
| Laporan Arus Kas | `/reports/cash-flow` | Arus kas operasi, investasi, dan pendanaan |
| Laporan Perbandingan | `/reports/comparative` | Posisi dan kinerja dua tahun fiskal berdampingan |
| Laporan Neraca Saldo | `/reports/trial-balance` | Total debit dan kredit per akun |
| Laporan Neraca Saldo Rinci | `/reports/trial-balance-detailed` | Saldo awal, mutasi, dan saldo akhir per akun |
| Laporan Buku Besar | `/reports/general-ledger` | Baris jurnal per akun beserta saldo berjalan |

## Akses & Izin

| Laporan | Permission |
|---|---|
| Laporan Neraca | `balance_sheet_report` |
| Laporan Laba Rugi | `income_statement_report` |
| Laporan Arus Kas | `cash_flow_report` |
| Laporan Perbandingan | `comparative_report` |
| Laporan Neraca Saldo | `trial_balance_report` |
| Laporan Neraca Saldo Rinci | `trial_balance_report` |
| Laporan Buku Besar | `general_ledger_report` |

Permission yang sama dipakai untuk melihat laporan dan menekan tombol **Ekspor**; tidak ada
permission ekspor terpisah. Pengguna tanpa permission akan melihat pesan akses ditolak.

## Menu & Navigasi

Semua laporan berada di grup **Laporan** pada sidebar:

- **Laporan > Laporan Neraca**
- **Laporan > Laporan Laba Rugi**
- **Laporan > Laporan Arus Kas**
- **Laporan > Laporan Perbandingan**
- **Laporan > Laporan Neraca Saldo**
- **Laporan > Laporan Neraca Saldo Rinci**
- **Laporan > Laporan Buku Besar**

Setiap halaman menampilkan pemilih tahun fiskal, filter tambahan sesuai laporan, dan tombol
**Ekspor** di sisi kanan judul.

## Filter yang tersedia

- **Fiscal Year**: tahun fiskal yang dihitung. Bila belum dipilih, sistem memakai tahun fiskal
  pilihan (biasanya tahun fiskal yang sedang berjalan).
- **Bandingkan tahun fiskal**: tahun fiskal pembanding. Pilih **None** untuk menonaktifkan
  perbandingan. Tersedia pada Neraca, Laba Rugi, dan Perbandingan.
- **Cabang**: membatasi laporan pada satu cabang. Kosong berarti semua cabang.
- **Departemen**: hanya pada Laba Rugi. Pilih **Tanpa Departemen** untuk menghitung baris yang
  tidak memiliki departemen.

Tahun fiskal pembanding tidak boleh sama dengan tahun fiskal utama. Bila perbandingan aktif,
laporan menampilkan nilai tahun pembanding beserta selisih dan persentase perubahan.

## 1. Laporan Neraca

Menampilkan posisi keuangan pada akhir tahun fiskal terpilih.

1. Pilih **Fiscal Year**.
2. Bila perlu, pilih **Bandingkan tahun fiskal** dan **Cabang**.
3. Baca bagian **Assets**, **Liabilities**, dan **Equity**.

Ringkasan di bawah laporan membandingkan **Total Assets** dengan **Total Liabilities & Equity**.
Lencana **Balanced** berarti keduanya sama; **Unbalanced** menampilkan selisih dan banner
peringatan berisi nilai selisih. Selisih di bawah 1 dianggap seimbang.

## 2. Laporan Laba Rugi

Menampilkan pendapatan dan beban dalam satu tahun fiskal.

1. Pilih **Fiscal Year**, lalu **Bandingkan tahun fiskal**, **Cabang**, dan **Departemen** bila
   diperlukan.
2. Baca bagian **Revenue** dan **Expense**.
3. Periksa ringkasan **Total Revenue**, **Total Expense**, dan **Net Income**.

Lencana **Profit** muncul bila laba bersih nol atau positif; **Loss** muncul bila negatif. Saat
perbandingan aktif, ringkasan juga menampilkan **Net Income (Comparison)** dan **Net Income
Change** beserta persentasenya.

## 3. Laporan Arus Kas

Menampilkan pergerakan kas per aktivitas pada tahun fiskal terpilih.

1. Pilih **Fiscal Year** dan, bila perlu, **Cabang**.
2. Periksa tiga kartu ringkasan: **Kas Awal Periode**, **Kenaikan/Penurunan Kas Bersih**, dan
   **Kas Akhir Periode**.
3. Baca kartu **Arus Kas per Aktivitas** (operasi, investasi, pendanaan) dan **Penerimaan dan
   Pengeluaran Kas**.
4. Tabel **Rincian Arus Kas per Akun** menampilkan kolom **Inflow** dan **Outflow** per akun.

Kas akhir dihitung dari kas awal ditambah kenaikan/penurunan kas bersih. Laporan ini tidak memakai
tahun pembanding.

## 4. Laporan Perbandingan

Membandingkan posisi dan kinerja dua tahun fiskal.

1. Pilih **Fiscal Year**.
2. Pilih **Bandingkan tahun fiskal**. Bila tidak diubah, sistem memakai tahun fiskal sebelumnya.
3. Pilih **Cabang** bila perlu.

Laporan menampilkan bagian **Assets**, **Liabilities**, **Equity**, **Revenue**, dan **Expense**.
Setiap baris memperlihatkan nilai tahun utama, nilai tahun pembanding, selisih, dan persentase
perubahan. Warna selisih membantu menandai kenaikan atau penurunan.

## 5. Laporan Neraca Saldo

Menampilkan total debit dan kredit setiap akun pada tahun fiskal terpilih.

1. Pilih **Fiscal Year** dan, bila perlu, **Cabang**.
2. Baca kolom **Code**, **Account Name**, **Type**, **Debit**, dan **Credit**.
3. Periksa baris **Total** di bagian bawah tabel.

Lencana **Balanced** berarti total debit sama dengan total kredit. Bila tidak seimbang, muncul
pesan **Trial balance is not balanced** beserta nilai selisihnya.

## 6. Laporan Neraca Saldo Rinci

Menampilkan saldo awal, mutasi, dan saldo akhir per akun untuk tahun fiskal dan bulan terpilih.

1. Pilih **Tahun Fiskal** (wajib). Selama belum dipilih, halaman menampilkan petunjuk untuk memilih
   tahun fiskal.
2. Pilih **Bulan** atau biarkan **Semua bulan**.
3. Baca kolom **Account Code**, **Account Name**, **Type**, **Opening Balance**, **Debit**,
   **Credit**, **Closing Balance**, **Debit Balance**, dan **Credit Balance**.

Mutasi dihitung dari jurnal yang diposting pada rentang bulan terpilih. Saldo awal akun neraca
diambil dari seluruh jurnal sebelum periode, sedangkan saldo awal akun laba rugi hanya dari tahun
fiskal yang sama. Baris total di bawah tabel memakai label **Total (seluruh hasil filter)**.

## 7. Laporan Buku Besar

Menampilkan setiap baris jurnal yang diposting ke satu akun.

1. Pilih **Akun (Wajib)**. Daftar hanya memuat akun aktif yang tidak memiliki sub-akun.
2. Pilih **Tahun Fiskal** bila perlu.
3. Atur **Tanggal Mulai** dan **Tanggal Akhir**, lalu pilih **Jenis Jurnal** bila perlu.
4. Baca kolom **Tanggal**, **No. Jurnal**, **Deskripsi**, **Referensi**, **Debet**, **Kredit**, dan
   **Saldo Berjalan**.

Selama akun belum dipilih, halaman menampilkan petunjuk untuk memilih akun. Jenis jurnal yang
tersedia: **Umum**, **Penjualan**, **Pembelian**, **Penerimaan Kas**, dan **Pengeluaran Kas**.

## Ekspor

Tombol **Ekspor** mengunduh data sesuai filter aktif dalam berkas Excel (`.xlsx`). Lakukan ekspor
setelah tahun fiskal, cabang, departemen, akun, dan rentang tanggal diatur agar isi berkas sesuai
kebutuhan.

## Aturan & validasi penting

- Tahun fiskal wajib ada pada permintaan laporan dan ekspor; tanpa tahun fiskal laporan kosong.
- Tahun fiskal pembanding harus berbeda dari tahun fiskal utama.
- Cabang dan departemen bersifat opsional; ID yang dikirim harus benar-benar ada.
- Tanggal akhir tidak boleh lebih awal dari tanggal mulai.
- Akun pada Buku Besar wajib dipilih; hanya akun aktif tanpa sub-akun yang ditampilkan.
- Neraca Saldo Rinci menerima bulan 1–12; tanpa bulan, seluruh tahun fiskal dihitung.
- Semua laporan hanya membaca jurnal berstatus diposting. Jurnal draf atau batal tidak dihitung.
- Halaman laporan tidak menyediakan tambah, ubah, atau hapus data.

## Tanya jawab singkat

**Mengapa laporan kosong?**
Pastikan tahun fiskal sudah dipilih dan sudah ada jurnal yang diposting pada periode tersebut. Untuk
Buku Besar, pastikan akun juga sudah dipilih.

**Mengapa Neraca menampilkan Unbalanced?**
Total aset tidak sama dengan total liabilitas ditambah ekuitas. Periksa jurnal yang belum seimbang
atau konfigurasi laporan pada **Konfigurasi Laporan**.

**Apa beda Neraca Saldo dan Neraca Saldo Rinci?**
Neraca Saldo hanya menampilkan total debit dan kredit per akun. Neraca Saldo Rinci menambahkan
saldo awal, mutasi, saldo akhir, serta saldo debit/kredit.

**Mengapa nilai perbandingan kosong?**
Tahun pembanding belum dipilih atau tidak ada data pada tahun tersebut. Pada Laporan Perbandingan,
tahun sebelumnya dipilih otomatis bila tersedia.

**Apakah saya bisa mengubah data dari laporan?**
Tidak. Perubahan dilakukan di modul jurnal atau dokumen sumber, lalu laporan dihitung ulang.
