# User Guide: Dashboard

## Gambaran Umum

Dashboard adalah halaman utama aplikasi yang menampilkan ringkasan kondisi keuangan perusahaan dalam satu tampilan. Halaman ini dirancang untuk memberikan gambaran cepat mengenai kesehatan finansial bisnis melalui KPI cards, grafik, dan breakdown pengeluaran. Dashboard bersifat read-only dan tidak memiliki operasi CRUD.

Untuk mengakses Dashboard, pengguna memerlukan permission `dashboard`. Data yang ditampilkan berasal dari `GetFinancialDashboardDataAction` dan dapat difilter berdasarkan tahun fiskal.

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Dashboard | / | Menampilkan ringkasan keuangan perusahaan dengan KPI cards, grafik pendapatan vs pengeluaran, dan breakdown pengeluaran teratas |

## 1. Mengakses Dashboard

### Langkah-langkah:

1. Login ke aplikasi dengan akun yang memiliki permission `dashboard`.
2. Klik menu **Dashboard** di sidebar atau akses langsung melalui URL `/`.
3. Halaman Dashboard akan menampilkan data keuangan terkini.

[Screenshot: Halaman Dashboard utama dengan KPI cards dan grafik]

## 2. Memahami KPI Cards

Dashboard menampilkan 7 KPI cards di bagian atas yang menunjukkan kondisi keuangan utama:

### KPI yang Tersedia:

| KPI | Deskripsi |
|-----|-----------|
| Revenue | Total pendapatan untuk periode yang dipilih |
| Expenses | Total pengeluaran untuk periode yang dipilih |
| Net Income | Selisih antara Revenue dan Expenses |
| Assets | Total nilai aset perusahaan |
| Liabilities | Total kewajiban perusahaan |
| Equity | Total ekuitas perusahaan |
| Cash Balance | Saldo kas saat ini |

Setiap KPI card dilengkapi dengan badge Year-over-Year (YoY) yang menunjukkan perubahan persentase dibandingkan periode yang sama tahun sebelumnya.

[Screenshot: KPI cards dengan badge YoY]

### Interpretasi Badge YoY:

- **Hijau (positif)**: Peningkatan dibandingkan tahun sebelumnya
- **Merah (negatif)**: Penurunan dibandingkan tahun sebelumnya
- **Abu-abu**: Tidak ada data perbandingan dari tahun sebelumnya

## 3. Menggunakan Filter Tahun Fiskal

Dashboard menyediakan selector tahun fiskal untuk memfilter data yang ditampilkan.

### Langkah-langkah:

1. Temukan dropdown **Fiscal Year** di bagian atas halaman.
2. Klik dropdown untuk melihat daftar tahun fiskal yang tersedia.
3. Pilih tahun fiskal yang diinginkan.
4. Data Dashboard akan otomatis diperbarui sesuai tahun fiskal yang dipilih.

[Screenshot: Dropdown selector tahun fiskal]

### Catatan:

- Sistem secara otomatis memilih tahun fiskal yang paling relevan saat pertama kali membuka Dashboard.
- Tahun fiskal dengan status `open` akan muncul di daftar pilihan.

## 4. Membaca Grafik Revenue vs Expenses

Grafik bar chart menampilkan perbandingan pendapatan dan pengeluaran bulanan selama 12 bulan.

### Cara Membaca:

- **Bar biru**: Pendapatan (Revenue) per bulan
- **Bar oranye**: Pengeluaran (Expenses) per bulan
- **Sumbut X**: Bulan (Jan - Dec)
- **Sumbut Y**: Nilai dalam mata uang yang digunakan

### Langkah-langkah:

1. Arahkan kursor ke salah satu bar untuk melihat nilai detail.
2. Bandingkan tinggi bar Revenue dan Expenses untuk melihat profitabilitas per bulan.
3. Identifikasi pola musiman dari fluktuasi grafik.

[Screenshot: Grafik Revenue vs Expenses dengan tooltip]

## 5. Melihat Cash Flow Summary

Bagian Cash Flow Summary menampilkan ringkasan arus kas perusahaan.

### Informasi yang Ditampilkan:

- Opening Balance (saldo awal periode)
- Cash Inflows (arus kas masuk)
- Cash Outflows (arus kas keluar)
- Closing Balance (saldo akhir periode)

[Screenshot: Cash Flow Summary section]

## 6. Menganalisis Top Expense Breakdown

Bagian ini menampilkan breakdown pengeluaran berdasarkan kategori.

### Informasi yang Ditampilkan:

- Nama kategori pengeluaran
- Nilai total per kategori
- Persentase terhadap total pengeluaran

### Langkah-langkah:

1. Lihat daftar kategori pengeluaran teratas.
2. Identifikasi kategori dengan kontribusi terbesar.
3. Gunakan informasi ini untuk analisis dan perencanaan anggaran.

[Screenshot: Top Expense Breakdown dengan kategori dan persentase]

## 7. Workflow Penggunaan Dashboard

```
Login
   |
   v
Buka Dashboard (/)
   |
   v
Pilih Tahun Fiskal <--|
   |                   |
   v                   |
Lihat KPI Cards       |
   |                   |
   v                   |
Analisis Grafik ------|
   |                   |
   v                   |
Review Cash Flow      |
   |                   |
   v                   |
Cek Expense Breakdown-|
   |
   v
Ambil Keputusan Bisnis
```

## 8. Skenario Penggunaan Umum

### Skenario 1: Review Kinerja Keuangan Bulanan

1. Buka Dashboard.
2. Pilih tahun fiskal yang ingin dianalisis.
3. Perhatikan KPI cards untuk melihat gambaran umum.
4. Analisis grafik Revenue vs Expenses untuk mengidentifikasi tren bulanan.
5. Catat bulan-bulan dengan performa terbaik dan terburuk.

### Skenario 2: Persiapan Laporan Manajemen

1. Buka Dashboard.
2. Pilih tahun fiskal sesuai periode laporan.
3. Catat nilai-nilai KPI yang relevan.
4. Screenshot grafik Revenue vs Expenses untuk presentasi.
5. Ambil data Cash Flow Summary untuk laporan arus kas.
6. Gunakan Top Expense Breakdown untuk analisis pengeluaran.

### Skenario 3: Identifikasi Area Penghematan

1. Buka Dashboard.
2. Perhatikan KPI Expenses dan badge YoY.
3. Jika Expenses naik signifikan, buka Top Expense Breakdown.
4. Identifikasi kategori dengan pertumbuhan terbesar.
5. Lakukan investigasi lebih lanjut pada kategori tersebut.

## FAQ & Tips

### Apakah data Dashboard real-time?

Data Dashboard diambil dari database pada saat halaman dibuka atau filter diubah. Tidak ada auto-refresh otomatis. Untuk melihat data terbaru, refresh halaman atau ubah filter tahun fiskal.

### Mengapa beberapa KPI menampilkan badge abu-abu?

Badge abu-abu menunjukkan tidak ada data perbandingan dari tahun fiskal sebelumnya. Hal ini bisa terjadi jika tahun fiskal yang dipilih adalah tahun pertama dalam sistem atau data tahun sebelumnya belum tersedia.

### Bagaimana cara mengubah mata uang yang ditampilkan?

Mata uang yang ditampilkan mengikuti pengaturan regional di Admin Settings. Hubungi administrator untuk mengubah konfigurasi mata uang.

### Apakah Dashboard bisa di-export?

Dashboard tidak memiliki fitur export langsung. Untuk kebutuhan pelaporan, gunakan fitur export di masing-masing laporan keuangan seperti Balance Sheet, Income Statement, atau Cash Flow Report.

### Mengapa saya tidak bisa mengakses Dashboard?

Pastikan akun Anda memiliki permission `dashboard`. Hubungi administrator sistem untuk memverifikasi dan menambahkan permission jika diperlukan.

### Apakah grafik bisa di-zoom atau di-filter per bulan?

Grafik Revenue vs Expenses menampilkan data 12 bulan dalam satu tampilan. Fitur zoom atau filter per bulan tidak tersedia. Untuk analisis detail per bulan, gunakan laporan Financial Dashboard atau laporan keuangan spesifik.

### Tips Optimasi Penggunaan Dashboard

1. **Akses rutin**: Buka Dashboard setiap hari untuk memantau perubahan keuangan.
2. **Perhatikan badge YoY**: Badge YoY memberikan konteks apakah performa membaik atau memburuk.
3. **Bandingkan dengan laporan detail**: Gunakan Dashboard sebagai starting point, lalu telusuri laporan detail untuk analisis mendalam.
4. **Catat anomali**: Jika ada lonjakan atau penurunan signifikan, catat dan investigasi lebih lanjut melalui modul terkait.

**Q: Bagaimana cara membaca summary cards di Dashboard?**

Summary cards atau KPI cards menampilkan 7 indikator utama: Revenue, Expenses, Net Income, Assets, Liabilities, Equity, dan Cash Balance. Setiap card memiliki nilai absolut di bagian tengah dan badge YoY di bagian atas atau samping. Badge hijau dengan angka positif menunjukkan pertumbuhan, badge merah menunjukkan penurunan, dan badge abu-abu berarti tidak ada data perbandingan. Untuk membaca secara efektif, mulai dari Revenue dan Expenses sebagai indikator aktivitas bisnis, lalu Net Income untuk profitabilitas, kemudian Assets, Liabilities, dan Equity untuk posisi keuangan, dan Cash Balance untuk likuiditas.

**Q: Apa saja KPI yang ditampilkan di Dashboard utama?**

Dashboard utama menampilkan 7 KPI cards: Revenue (total pendapatan periode), Expenses (total pengeluaran periode), Net Income (selisih revenue dan expenses), Assets (total nilai aset perusahaan), Liabilities (total kewajiban), Equity (total ekuitas), dan Cash Balance (saldo kas saat ini). Selain itu, ada grafik Revenue vs Expenses bulanan, Cash Flow Summary dengan Opening Balance, Cash Inflows, Cash Outflows, dan Closing Balance, serta Top Expense Breakdown yang menampilkan kategori pengeluaran terbesar dengan nilai dan persentase.

**Q: Bagaimana cara filter Dashboard berdasarkan tahun fiskal?**

Filter tahun fiskal berada di dropdown selector bagian atas halaman. Klik dropdown untuk melihat daftar tahun fiskal dengan status open. Pilih tahun yang ingin dianalisis, dan semua data Dashboard akan otomatis diperbarui. Sistem memilih tahun fiskal paling relevan saat pertama kali membuka halaman, biasanya tahun dengan journal entry posted terbaru atau tahun pertama dengan status open. Perubahan filter bersifat instan tanpa perlu refresh halaman manual.

**Q: Apa perbedaan Dashboard utama dengan Financial Dashboard?**

Dashboard utama berada di URL / dan menampilkan ringkasan keuangan high-level dengan 7 KPI cards, grafik Revenue vs Expenses 12 bulan, Cash Flow Summary, dan Top Expense Breakdown. Financial Dashboard berada di /financial-dashboard dan lebih detail dengan fitur comparison year, monthly trends chart yang lebih granular, dan analisis breakdown yang lebih mendalam. Financial Dashboard juga memiliki badge YoY untuk setiap KPI dengan visualisasi yang lebih informatif. Gunakan Dashboard utama untuk overview cepat, Financial Dashboard untuk analisis trend dan comparison.

**Q: Apa perbedaan Dashboard utama dengan Aging Dashboard?**

Dashboard utama fokus pada kondisi keuangan agregat: Revenue, Expenses, Assets, Liabilities, Equity, dan Cash Balance. Aging Dashboard di /aging-dashboard fokus pada receivables dan payables dengan bucket aging: Current, 1-30 days, 31-60 days, 61-90 days, dan Over 90 days. Aging Dashboard menampilkan Total Receivables, AR Overdue, Total Payables, AP Overdue, plus top 10 overdue customers dan suppliers. Aging Dashboard juga memiliki filter branch dan as_of_date. Gunakan Dashboard utama untuk gambaran keuangan keseluruhan, Aging Dashboard untuk monitoring piutang dan hutang yang overdue.

**Q: Apa perbedaan Dashboard utama dengan Asset Dashboard?**

Dashboard utama menampilkan KPI keuangan agregat. Asset Dashboard di /asset-dashboard fokus pada visualisasi aset: distribution pie chart per category, condition breakdown, maintenance alerts, warranty expiry alerts, dan depreciation summary. Asset Dashboard membantu monitoring fisik aset perusahaan, sedangkan Dashboard utama hanya menampilkan nilai total Assets tanpa breakdown detail. Untuk analisis aset granular, gunakan Asset Dashboard atau modul Assets.

**Q: Bagaimana cara refresh data Dashboard?**

Data Dashboard tidak auto-refresh. Untuk mendapatkan data terbaru, ada 3 cara: refresh halaman dengan F5 atau Ctrl+R, ubah filter tahun fiskal ke tahun lain lalu kembali ke tahun yang diinginkan, atau buka Dashboard dari menu sidebar lagi. Data diambil dari database saat halaman dibuka atau filter diubah. Jika ada transaksi baru yang posted, perlu refresh manual untuk melihat impact pada KPI.

**Q: Apa yang harus dilakukan jika data Dashboard tidak muncul?**

Jika data tidak muncul, periksa 5 hal: permission akun (pastikan memiliki dashboard permission), tahun fiskal yang dipilih (cek apakah tahun tersebut memiliki data transaksi posted), koneksi jaringan (pastikan tidak ada network issue), console browser (buka Developer Tools untuk cek error API), dan filter yang digunakan (pastikan tidak ada filter yang menghasilkan empty dataset). Jika semua sudah benar tapi data masih tidak muncul, hubungi administrator untuk cek backend log atau database consistency.

**Q: Tips memantau bisnis dari Dashboard secara efektif?**

5 tips untuk monitoring efektif: buka Dashboard setiap pagi untuk cek overnight changes, perhatikan badge YoY untuk identify trend direction, compare Revenue vs Expenses ratio di grafik bulanan untuk spotting seasonality, cek Top Expense Breakdown untuk identify cost drivers, dan cross-reference dengan laporan detail jika ada anomaly. Jangan hanya lihat nilai absolut, selalu bandingkan dengan periode sebelumnya via YoY badge. Catat setiap anomaly dan telusuri source di modul terkait sebelum issue membesar.

**Q: Bagaimana cara menggunakan grafik Revenue vs Expenses untuk decision making?**

Grafik bar chart menampilkan 12 bulan Revenue dan Expenses. Untuk decision making: identify bulan dengan gap terbesar antara Revenue dan Expenses (high profit months), identify bulan dengan Expenses mendekati atau melebihi Revenue (warning months), spotting pattern seasonality untuk planning cash flow, dan compare consecutive months untuk detect sudden changes. Hover pada bar untuk see exact value. Jika ada bulan dengan Expenses spike tanpa Revenue increase, investigate di laporan detail untuk find root cause.

**Q: Apakah Dashboard bisa diakses oleh semua user?**

Tidak. Dashboard memerlukan permission dashboard yang harus di-assign oleh administrator. Tanpa permission ini, user akan mendapat error atau redirect saat mencoba akses /. Permission diatur di modul Permissions dengan role-based assignment. Administrator dapat memberikan permission dashboard ke role tertentu atau individual user. Untuk meminta access, hubungi administrator sistem dengan menyebutkan kebutuhan monitoring keuangan.
