# User Guide: Aging Dashboard

## Gambaran Umum

Aging Dashboard adalah halaman dashboard read-only yang menampilkan analisis aging piutang (AR) dan hutang (AP) per tanggal tertentu. Dashboard ini membantu tim keuangan mengidentifikasi prioritas penagihan dan urgensi pembayaran berdasarkan usia piutang/hutang.

Dashboard menampilkan 4 kartu KPI utama, 2 grafik batang horizontal untuk bucket aging AR dan AP, serta 2 tabel top-10 overdue untuk pelanggan dan supplier. Data dapat difilter berdasarkan tanggal dan cabang.

**Lokasi Menu:** Accounting > Aging Dashboard  
**URL:** `/aging-dashboard`  
**Permission:** `aging_dashboard`

[Screenshot: Tampilan lengkap Aging Dashboard dengan KPI cards, grafik aging, dan tabel overdue]

---

## Menu & Navigasi

| Elemen | Lokasi | Fungsi |
|--------|--------|--------|
| Aging Dashboard | Accounting Menu | Membuka halaman Aging Dashboard |
| Filter As Of Date | Toolbar kiri atas | Mengatur tanggal cutoff perhitungan aging |
| Filter Branch | Toolbar kiri atas | Memfilter data berdasarkan cabang |
| KPI Cards | Bagian atas | Menampilkan ringkasan total dan overdue |
| AR Aging Chart | Bagian tengah kiri | Grafik distribusi aging piutang |
| AP Aging Chart | Bagian tengah kanan | Grafik distribusi aging hutang |
| Top 10 Overdue Customers | Bagian bawah kiri | Tabel pelanggan dengan overdue tertinggi |
| Top 10 Overdue Suppliers | Bagian bawah kanan | Tabel supplier dengan overdue tertinggi |

---

## 1. Kartu KPI (Key Performance Indicators)

Dashboard memiliki 4 kartu KPI yang menampilkan ringkasan kondisi piutang dan hutang:

### 1.1 Total Receivables

Menampilkan total saldo piutang pelanggan yang belum dibayarkan pada tanggal cutoff.

**Informasi yang ditampilkan:**
- Nominal total piutang dalam mata uang default perusahaan
- Dihitung dari semua invoice pelanggan yang statusnya belum lunas

**Cara membaca:**
- Nilai tinggi menunjukkan banyak piutang yang belum tertagih
- Bandingkan dengan AR Overdue untuk melihat proporsi yang sudah melewati jatuh tempo

### 1.2 AR Overdue

Menampilkan total piutang yang sudah melewati tanggal jatuh tempo.

**Informasi yang ditampilkan:**
- Nominal total piutang overdue
- Badge persentase overdue terhadap total receivables
- Warna badge mengindikasikan tingkat risiko (merah untuk persentase tinggi)

**Cara membaca:**
- Persentase overdue > 30% menunjukkan masalah penagihan yang perlu perhatian
- Gunakan sebagai trigger untuk tindakan koleksi

[Screenshot: KPI cards Total Receivables dan AR Overdue dengan badge persentase]

### 1.3 Total Payables

Menampilkan total saldo hutang ke supplier yang belum dibayarkan pada tanggal cutoff.

**Informasi yang ditampilkan:**
- Nominal total hutang dalam mata uang default perusahaan
- Dihitung dari semua bill supplier yang statusnya belum lunas

**Cara membaca:**
- Nilai tinggi menunjukkan kewajiban pembayaran yang besar
- Bandingkan dengan AP Overdue untuk melihat proporsi yang sudah terlambat

### 1.4 AP Overdue

Menampilkan total hutang yang sudah melewati tanggal jatuh tempo.

**Informasi yang ditampilkan:**
- Nominal total hutang overdue
- Badge persentase overdue terhadap total payables
- Warna badge mengindikasikan tingkat urgensi pembayaran

**Cara membaca:**
- Persentase overdue tinggi berisiko merusak hubungan dengan supplier
- Prioritaskan pembayaran untuk supplier kunci

[Screenshot: KPI cards Total Payables dan AP Overdue dengan badge persentase]

---

## 2. Grafik Aging (AR & AP Aging Buckets)

Dashboard menampilkan 2 grafik batang horizontal yang menunjukkan distribusi piutang dan hutang berdasarkan bucket aging.

### 2.1 Bucket Aging

Setiap grafik membagi saldo menjadi 5 bucket berdasarkan usia:

| Bucket | Keterangan | Warna |
|--------|------------|-------|
| Current | Belum jatuh tempo | Emerald (hijau) |
| 1-30 days | 1-30 hari melewati jatuh tempo | Emerald muda |
| 31-60 days | 31-60 hari melewati jatuh tempo | Yellow/Amber |
| 61-90 days | 61-90 hari melewati jatuh tempo | Orange |
| Over 90 days | Lebih dari 90 hari melewati jatuh tempo | Rose (merah) |

### 2.2 Grafik AR Aging

Grafik ini menampilkan distribusi piutang pelanggan per bucket aging.

**Cara membaca:**
- Bar paling panjang menunjukkan konsentrasi piutang
- Warna membantu identifikasi risiko secara visual (semakin merah = semakin berisiko)
- Bandingkan proporsi Current vs Overdue untuk menilai kualitas piutang

**Tindak lanjut:**
- Fokus koleksi pada bucket 61-90 days dan Over 90 days
- Evaluasi kredit pelanggan dengan proporsi overdue tinggi

[Screenshot: Grafik AR Aging dengan 5 bucket berwarna]

### 2.3 Grafik AP Aging

Grafik ini menampilkan distribusi hutang ke supplier per bucket aging.

**Cara membaca:**
- Bar paling panjang menunjukkan konsentrasi hutang
- Warna membantu identifikasi urgensi pembayaran
- Bandingkan dengan jadwal arus kas untuk rencana pembayaran

**Tindak lanjut:**
- Prioritaskan pembayaran pada bucket Over 90 days untuk menjaga hubungan supplier
- Koordinasikan dengan tim treasury untuk manajemen kas

[Screenshot: Grafik AP Aging dengan 5 bucket berwarna]

---

## 3. Tabel Top 10 Overdue

Dashboard menampilkan 2 tabel yang berisi 10 pelanggan dan 10 supplier dengan nilai overdue tertinggi.

### 3.1 Top 10 Overdue Customers

Tabel ini menampilkan pelanggan dengan piutang overdue tertinggi.

**Kolom yang ditampilkan:**
| Kolom | Keterangan |
|-------|------------|
| Customer | Nama pelanggan |
| Total Overdue | Nominal total piutang yang sudah overdue |
| Oldest Invoice | Tanggal invoice tertua yang belum dibayar |
| Days Overdue | Jumlah hari sejak invoice tertua melewati jatuh tempo |

**Cara menggunakan:**
- Gunakan sebagai prioritas penagihan
- Klik nama pelanggan untuk melihat detail piutang
- Koordinasikan dengan tim sales untuk follow-up

[Screenshot: Tabel Top 10 Overdue Customers dengan data contoh]

### 3.2 Top 10 Overdue Suppliers

Tabel ini menampilkan supplier dengan hutang overdue tertinggi.

**Kolom yang ditampilkan:**
| Kolom | Keterangan |
|-------|------------|
| Supplier | Nama supplier |
| Total Overdue | Nominal total hutang yang sudah overdue |
| Oldest Bill | Tanggal bill tertua yang belum dibayar |
| Days Overdue | Jumlah hari sejak bill tertua melewati jatuh tempo |

**Cara menggunakan:**
- Gunakan sebagai prioritas pembayaran
- Klik nama supplier untuk melihat detail hutang
- Koordinasikan dengan tim purchasing untuk negosiasi

[Screenshot: Tabel Top 10 Overdue Suppliers dengan data contoh]

---

## 4. Filter Data

Dashboard menyediakan 2 filter untuk mempersempit data yang ditampilkan.

### 4.1 Filter As Of Date

**Fungsi:** Mengatur tanggal cutoff untuk perhitungan aging.

**Cara menggunakan:**
1. Klik field "As Of Date" di toolbar
2. Pilih tanggal yang diinginkan dari date picker
3. Data akan otomatis diperbarui berdasarkan tanggal yang dipilih

**Default:** Tanggal hari ini

**Use case:**
- Melihat kondisi aging pada tanggal tertentu di masa lalu
- Analisis tren aging dengan membandingkan berbagai tanggal cutoff
- Persiapan laporan keuangan periode tertentu

### 4.2 Filter Branch

**Fungsi:** Memfilter data berdasarkan cabang.

**Cara menggunakan:**
1. Klik dropdown "Branch" di toolbar
2. Pilih cabang yang diinginkan atau "All Branches"
3. Data akan otomatis diperbarui berdasarkan pilihan

**Default:** All Branches (semua cabang)

**Use case:**
- Analisis aging per cabang
- Identifikasi cabang dengan masalah koleksi atau pembayaran
- Evaluasi kinerja credit control per cabang

[Screenshot: Filter As Of Date dan Branch di toolbar]

---

## 5. Interpretasi Data & Tindak Lanjut

### 5.1 Skenario Kondisi Sehat

**Indikator:**
- Persentase AR Overdue < 15%
- Persentase AP Overdue < 10%
- Mayoritas saldo berada di bucket Current
- Tidak ada konsentrasi tinggi pada pelanggan/supplier tertentu

**Tindak lanjut:**
- Pertahankan kebijakan kredit dan pembayaran saat ini
- Lakukan monitoring rutin bulanan

### 5.2 Skenario Perlu Perhatian

**Indikator:**
- Persentase AR Overdue 15-30%
- Persentase AP Overdue 10-20%
- Ada konsentrasi piutang/hutang pada bucket 31-60 days

**Tindak lanjut:**
- Tingkatkan intensitas koleksi untuk pelanggan di tabel top 10
- Review jadwal pembayaran untuk supplier di tabel top 10
- Evaluasi term kredit untuk pelanggan dengan pola pembayaran lambat

### 5.3 Skenario Kritis

**Indikator:**
- Persentase AR Overdue > 30%
- Persentase AP Overdue > 20%
- Ada saldo signifikan di bucket Over 90 days
- Konsentrasi tinggi pada beberapa pelanggan/supplier

**Tindak lanjut:**
- Segera lakukan koleksi intensif untuk piutang over 90 days
- Pertimbangkan penulisan piutang tak tertagih (bad debt)
- Prioritaskan pembayaran ke supplier kritis untuk menjaga supply chain
- Review dan perketat kebijakan kredit
- Koordinasikan dengan manajemen untuk strategi recovery

---

## 6. Persyaratan & Akses

### 6.1 Permission

Untuk mengakses Aging Dashboard, user memerlukan permission `aging_dashboard`.

**Cara cek permission:**
- Hubungi administrator sistem
- Pastikan role user memiliki permission ini

### 6.2 Menu Navigation

Aging Dashboard berada di bawah menu group Accounting.

**Lokasi menu:**
1. Buka sidebar/menu utama
2. Pilih menu group "Accounting"
3. Klik "Aging Dashboard"

---

## FAQ

**Q: Apa perbedaan antara "Current" dan "Overdue"?**
A: Current adalah piutang/hutang yang belum jatuh tempo (masih dalam periode pembayaran normal). Overdue adalah piutang/hutang yang sudah melewati tanggal jatuh tempo pembayaran.

**Q: Bagaimana cara menghitung hari overdue?**
A: Hari overdue dihitung dari tanggal jatuh tempo hingga tanggal "As Of Date" yang dipilih. Jika tanggal jatuh tempo adalah 1 Januari dan As Of Date adalah 31 Januari, maka hari overdue adalah 30 hari.

**Q: Apakah data dapat diedit dari dashboard ini?**
A: Tidak. Aging Dashboard adalah halaman read-only. Untuk melakukan pembayaran atau penerimaan, navigasikan ke modul AP Payment atau AR Receipt.

**Q: Mengapa ada perbedaan antara Total Receivables di Aging Dashboard dengan balance di Customer Ledger?**
A: Pastikan filter tanggal dan cabang sama. Aging Dashboard menggunakan tanggal cutoff "As Of Date", sedangkan Customer Ledger mungkin menggunakan periode berbeda. Periksa juga apakah ada adjustment atau write-off yang belum tercatat.

**Q: Bagaimana cara melihat detail invoice/bill yang menyusun angka aging?**
A: Klik nama pelanggan atau supplier di tabel top 10 overdue untuk melihat detail transaksi. Atau navigasi ke modul Customer Invoice atau Supplier Bill untuk melihat daftar lengkap.

**Q: Bagaimana cara menindaklanjuti piutang yang sudah overdue?**
A: Prioritaskan berdasarkan bucket aging (Over 90 hari paling kritis). Hubungi pelanggan, kirim reminder pembayaran, atau eskalasi ke tim collection. Untuk hutang overdue, jadwalkan pembayaran segera untuk menghindari denda atau kerusakan hubungan dengan supplier.

**Q: Apa arti dari bucket aging (Current, 1-30, 31-60, 61-90, Over 90)?**
A: Bucket mengelompokkan piutang/hutang berdasarkan umur keterlambatan sejak tanggal jatuh tempo. Current = belum jatuh tempo. 1-30 = overdue 1-30 hari. 31-60 = overdue 31-60 hari. 61-90 = overdue 61-90 hari. Over 90 = overdue lebih dari 90 hari (paling berisiko).

**Q: Bagaimana cara filter data per cabang?**
A: Gunakan dropdown filter "Branch" di bagian atas dashboard. Pilih cabang yang diinginkan untuk melihat data aging khusus cabang tersebut. Default menampilkan semua cabang.

**Q: Seberapa sering data dashboard di-refresh?**
A: Data direfresh setiap kali halaman dibuka atau saat filter diubah (As Of Date, Branch). Dashboard tidak melakukan auto-refresh real-time — refresh manual diperlukan untuk data terbaru.

**Q: Apakah ada batasan jumlah data yang ditampilkan?**
A: Tabel "Top 10 Overdue" menampilkan 10 pelanggan/supplier dengan overdue tertinggi. Chart aging bucket menampilkan seluruh data yang sesuai filter. Untuk melihat seluruh daftar, navigasi ke modul Customer Invoice atau Supplier Bill.

**Q: Apa hubungan Aging Dashboard dengan modul AP dan AR?**
A: Aging Dashboard menyajikan ringkasan visual dari data yang sama yang digunakan modul AP Payment (hutang ke supplier) dan AR Receipt (piutang dari pelanggan). Data di dashboard ini bersumber dari invoice/bill yang belum lunas di kedua modul tersebut.

**Q: Bagaimana cara mempercepat penagihan piutang?**
A: Gunakan dashboard untuk identifikasi pelanggan dengan overdue tinggi. Terapkan kebijakan reminder otomatis, tetapkan batas kredit yang ketat, dan lakukan follow-up rutin mingguan. Data aging juga dapat digunakan sebagai bahan negosiasi termin pembayaran dengan pelanggan.

**Q: Apakah dashboard ini bisa digunakan untuk laporan ke manajemen?**
A: Ya. Gunakan fitur screenshot atau export untuk mendokumentasikan kondisi aging secara berkala. Bandingkan data antar periode (gunakan filter As Of Date) untuk melihat tren perbaikan atau pemburukan aging dari waktu ke waktu.

---

## Terkait

- [User Guide: AR Receipt](user-guide-ar-receipts.md)
- [User Guide: AP Payment](user-guide-ap-payments.md)
- [User Guide: Customer Invoice](user-guide-customer-invoices.md)
- [User Guide: Supplier Bill](user-guide-supplier-bills.md)
- [User Guide: Financial Dashboard](user-guide-financial-dashboard.md)
