# User Guide: Financial Dashboard

## Gambaran Umum

Financial Dashboard adalah halaman khusus yang menampilkan Key Performance Indicator (KPI) keuangan perusahaan secara komprehensif. Halaman ini dirancang untuk memberikan gambaran mendalam mengenai kesehatan finansial bisnis melalui 7 KPI cards dengan perbandingan Year-over-Year (YoY), grafik pendapatan vs pengeluaran bulanan, ringkasan arus kas, dan breakdown pengeluaran teratas. Financial Dashboard bersifat read-only dan tidak memiliki operasi CRUD.

Untuk mengakses Financial Dashboard, pengguna memerlukan permission `financial_dashboard`. Data yang ditampilkan berasal dari `GetFinancialDashboardDataAction` dan `FinancialReportService::getMonthlyTrends`, dengan kemampuan filter berdasarkan tahun fiskal dan perbandingan otomatis dengan tahun sebelumnya.

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Financial Dashboard | /financial-dashboard | Menampilkan KPI keuangan dengan perbandingan YoY, grafik pendapatan vs pengeluaran 12 bulan, ringkasan arus kas, dan breakdown pengeluaran teratas |

## 1. Mengakses Financial Dashboard

### Langkah-langkah:

1. Login ke aplikasi dengan akun yang memiliki permission `financial_dashboard`.
2. Klik menu **Financial Dashboard** di sidebar pada grup menu Accounting atau akses langsung melalui URL `/financial-dashboard`.
3. Halaman Financial Dashboard akan menampilkan data keuangan terkini.

[Screenshot: Halaman Financial Dashboard utama dengan KPI cards dan grafik]

## 2. Memahami KPI Cards

Financial Dashboard menampilkan 7 KPI cards di bagian atas yang menunjukkan kondisi keuangan utama:

### KPI yang Tersedia:

| KPI | Deskripsi |
|-----|-----------|
| Revenue | Total pendapatan untuk tahun fiskal yang dipilih |
| Expenses | Total pengeluaran untuk tahun fiskal yang dipilih |
| Net Income | Selisih antara Revenue dan Expenses (laba bersih) |
| Assets | Total nilai aset perusahaan |
| Liabilities | Total kewajiban perusahaan |
| Equity | Total ekuitas perusahaan |
| Cash Balance | Saldo kas saat ini |

Setiap KPI card dilengkapi dengan badge Year-over-Year (YoY) yang menunjukkan perubahan persentase dibandingkan tahun fiskal sebelumnya.

[Screenshot: KPI cards dengan badge YoY]

### Interpretasi Badge YoY:

- **Hijau (positif)**: Peningkatan dibandingkan tahun sebelumnya
- **Merah (negatif)**: Penurunan dibandingkan tahun sebelumnya
- **Abu-abu**: Tidak ada data perbandingan dari tahun sebelumnya

### Catatan Khusus:

- Badge YoY dihitung secara otomatis berdasarkan tahun fiskal sebelumnya yang dipilih sistem.
- Untuk Net Income, badge hijau menunjukkan profitabilitas meningkat, sedangkan badge merah menunjukkan profitabilitas menurun atau kerugian meningkat.

## 3. Menggunakan Filter Tahun Fiskal

Financial Dashboard menyediakan selector tahun fiskal untuk memfilter data yang ditampilkan.

### Langkah-langkah:

1. Temukan dropdown **Fiscal Year** di bagian atas halaman.
2. Klik dropdown untuk melihat daftar tahun fiskal yang tersedia.
3. Pilih tahun fiskal yang diinginkan.
4. Data Financial Dashboard akan otomatis diperbarui sesuai tahun fiskal yang dipilih.

[Screenshot: Dropdown selector tahun fiskal]

### Auto-Comparison Feature:

Saat tahun fiskal dipilih, sistem secara otomatis memilih tahun fiskal sebelumnya sebagai perbandingan untuk badge YoY. Fitur ini memastikan perbandingan selalu tersedia tanpa perlu memilih manual.

### Catatan:

- Sistem secara otomatis memilih tahun fiskal yang paling relevan saat pertama kali membuka Financial Dashboard. Tahun fiskal dengan journal entry yang sudah posted akan diprioritaskan.
- Hanya tahun fiskal dengan status `open` yang muncul di daftar pilihan.

## 4. Membaca Grafik Revenue vs Expenses

Grafik bar chart menampilkan perbandingan pendapatan dan pengeluaran bulanan selama 12 bulan dalam tahun fiskal yang dipilih.

### Cara Membaca:

- **Bar biru**: Pendapatan (Revenue) per bulan
- **Bar oranye**: Pengeluaran (Expenses) per bulan
- **Sumbut X**: Bulan (Jan - Dec atau sesuai periode tahun fiskal)
- **Sumbut Y**: Nilai dalam mata uang yang digunakan

### Langkah-langkah:

1. Arahkan kursor ke salah satu bar untuk melihat nilai detail pada tooltip.
2. Bandingkan tinggi bar Revenue dan Expenses untuk melihat profitabilitas per bulan.
3. Identifikasi pola musiman dari fluktuasi grafik.
4. Perhatikan bulan-bulan dengan selisih terbesar antara Revenue dan Expenses.

[Screenshot: Grafik Revenue vs Expenses dengan tooltip]

### Analisis yang Bisa Dilakukan:

- **Profitabilitas bulanan**: Bulan dengan bar Revenue lebih tinggi menunjukkan profit.
- **Pola musiman**: Identifikasi bulan dengan pendapatan tertinggi dan terendah.
- **Tren pengeluaran**: Lihat apakah pengeluaran meningkat di bulan-bulan tertentu.

## 5. Melihat Cash Flow Summary

Bagian Cash Flow Summary menampilkan ringkasan arus kas perusahaan untuk tahun fiskal yang dipilih.

### Informasi yang Ditampilkan:

| Komponen | Deskripsi |
|----------|-----------|
| Opening Balance | Saldo kas awal periode tahun fiskal |
| Cash Inflows | Total arus kas masuk selama periode |
| Cash Outflows | Total arus kas keluar selama periode |
| Closing Balance | Saldo kas akhir periode tahun fiskal |

[Screenshot: Cash Flow Summary section]

### Cara Membaca:

1. Lihat Opening Balance untuk memahami posisi kas awal.
2. Bandingkan Cash Inflows dengan Cash Outflows untuk melihat net cash flow.
3. Closing Balance menunjukkan posisi kas akhir setelah seluruh transaksi.

### Catatan:

- Cash Flow Summary dihitung berdasarkan transaksi aktual yang sudah posted.
- Jika Closing Balance lebih rendah dari Opening Balance, perusahaan mengalami net cash outflow.

## 6. Menganalisis Top Expense Breakdown

Bagian ini menampilkan breakdown pengeluaran berdasarkan kategori untuk tahun fiskal yang dipilih.

### Informasi yang Ditampilkan:

- Nama kategori akun pengeluaran
- Nilai total per kategori
- Persentase terhadap total pengeluaran
- Visualisasi bar atau chart untuk perbandingan

### Langkah-langkah:

1. Lihat daftar kategori pengeluaran teratas.
2. Identifikasi kategori dengan kontribusi terbesar terhadap total pengeluaran.
3. Bandingkan proporsi antar kategori.
4. Gunakan informasi ini untuk analisis dan perencanaan anggaran.

[Screenshot: Top Expense Breakdown dengan kategori dan persentase]

### Tips Analisis:

- Kategori dengan persentase tertinggi adalah target utama untuk efisiensi biaya.
- Bandingkan dengan periode sebelumnya untuk melihat tren pengeluaran per kategori.
- Identifikasi kategori yang mengalami peningkatan signifikan untuk investigasi lebih lanjut.

## 7. Workflow Penggunaan Financial Dashboard

```
Login
   |
   v
Buka Financial Dashboard (/financial-dashboard)
   |
   v
Pilih Tahun Fiskal <--|
   |                   |
   v                   |
Lihat KPI Cards       |
   |                   |
   v                   |
Analisis Badge YoY ---|
   |                   |
   v                   |
Baca Grafik Revenue vs Expenses
   |                   |
   v                   |
Review Cash Flow Summary
   |                   |
   v                   |
Analisis Top Expense Breakdown
   |
   v
Ambil Keputusan Bisnis
```

## 8. Skenario Penggunaan Umum

### Skenario 1: Review Kinerja Keuangan Tahunan

1. Buka Financial Dashboard.
2. Pilih tahun fiskal yang ingin dianalisis.
3. Perhatikan KPI cards untuk melihat gambaran umum.
4. Analisis badge YoY untuk memahami tren perubahan.
5. Telusuri grafik Revenue vs Expenses untuk mengidentifikasi pola bulanan.
6. Catat bulan-bulan dengan performa terbaik dan terburuk.

### Skenario 2: Persiapan Laporan Manajemen

1. Buka Financial Dashboard.
2. Pilih tahun fiskal sesuai periode laporan.
3. Catat nilai-nilai KPI yang relevan.
4. Screenshot grafik Revenue vs Expenses untuk presentasi.
5. Ambil data Cash Flow Summary untuk laporan arus kas.
6. Gunakan Top Expense Breakdown untuk analisis pengeluaran.

### Skenario 3: Identifikasi Area Penghematan

1. Buka Financial Dashboard.
2. Perhatikan KPI Expenses dan badge YoY.
3. Jika Expenses naik signifikan (badge merah), buka Top Expense Breakdown.
4. Identifikasi kategori dengan pertumbuhan terbesar.
5. Lakukan investigasi lebih lanjut pada kategori tersebut melalui modul Journal Entries atau laporan detail.

### Skenario 4: Analisis Profitabilitas Bulanan

1. Buka Financial Dashboard.
2. Fokus pada grafik Revenue vs Expenses.
3. Identifikasi bulan-bulan dengan profit tertinggi (Revenue jauh di atas Expenses).
4. Identifikasi bulan-bulan dengan profit terendah atau rugi.
5. Korelasikan dengan event atau aktivitas bisnis pada bulan tersebut.
6. Gunakan insight untuk perencanaan bulan serupa di tahun berikutnya.

## FAQ & Tips

### Apakah data Financial Dashboard real-time?

Data Financial Dashboard diambil dari database pada saat halaman dibuka atau filter diubah. Tidak ada auto-refresh otomatis. Untuk melihat data terbaru, refresh halaman atau ubah filter tahun fiskal.

### Mengapa beberapa KPI menampilkan badge abu-abu?

Badge abu-abu menunjukkan tidak ada data perbandingan dari tahun fiskal sebelumnya. Hal ini bisa terjadi jika tahun fiskal yang dipilih adalah tahun pertama dalam sistem atau data tahun sebelumnya belum tersedia.

### Bagaimana cara mengubah mata uang yang ditampilkan?

Mata uang yang ditampilkan mengikuti pengaturan regional di Admin Settings. Hubungi administrator untuk mengubah konfigurasi mata uang.

### Apakah Financial Dashboard bisa di-export?

Financial Dashboard tidak memiliki fitur export langsung. Untuk kebutuhan pelaporan, gunakan fitur export di masing-masing laporan keuangan seperti Balance Sheet, Income Statement, atau Cash Flow Report.

### Mengapa saya tidak bisa mengakses Financial Dashboard?

Pastikan akun Anda memiliki permission `financial_dashboard`. Hubungi administrator sistem untuk memverifikasi dan menambahkan permission jika diperlukan.

### Apakah grafik bisa di-zoom atau di-filter per bulan?

Grafik Revenue vs Expenses menampilkan data 12 bulan dalam satu tampilan. Fitur zoom atau filter per bulan tidak tersedia. Untuk analisis detail per bulan, gunakan laporan detail seperti Income Statement atau General Ledger.

### Apa perbedaan antara Dashboard utama dan Financial Dashboard?

Dashboard utama (URL `/`) menampilkan ringkasan keuangan umum untuk overview cepat. Financial Dashboard (URL `/financial-dashboard`) menampilkan analisis keuangan yang lebih mendalam dengan 7 KPI cards, grafik 12 bulan, cash flow summary, dan expense breakdown. Financial Dashboard cocok untuk analisis dan pelaporan keuangan yang lebih detail.

### Bagaimana badge YoY dihitung?

Badge YoY dihitung dengan membandingkan nilai KPI tahun fiskal yang dipilih dengan tahun fiskal sebelumnya. Rumus: ((Nilai Tahun Ini - Nilai Tahun Lalu) / Nilai Tahun Lalu) x 100%. Jika nilai tahun lalu adalah 0 atau tidak tersedia, badge akan menampilkan abu-abu.

### Tips Optimasi Penggunaan Financial Dashboard

1. **Akses rutin**: Buka Financial Dashboard secara berkala untuk memantau perubahan keuangan.
2. **Perhatikan badge YoY**: Badge YoY memberikan konteks apakah performa membaik atau memburuk.
3. **Bandingkan dengan laporan detail**: Gunakan Financial Dashboard sebagai starting point, lalu telusuri laporan detail untuk analisis mendalam.
4. **Catat anomali**: Jika ada lonjakan atau penurunan signifikan, catat dan investigasi lebih lanjut melalui modul terkait.
5. **Manfaatkan filter tahun fiskal**: Bandingkan performa antar tahun dengan mengganti tahun fiskal.
6. **Integrasikan dengan decision making**: Gunakan data dari Financial Dashboard untuk mendukung keputusan bisnis.

**Q: Bagaimana cara membaca KPI Revenue dengan benar?**

KPI Revenue menampilkan total pendapatan untuk tahun fiskal yang dipilih. Nilai ini diambil dari akun-akun dengan tipe income yang sudah posted di journal entries. Badge di sisi kanan menunjukkan persentase perubahan dibandingkan tahun sebelumnya. Badge hijau berarti revenue naik, badge merah berarti revenue turun, dan badge abu-abu berarti tidak ada data perbandingan. Untuk analisis mendalam, klik laporan Income Statement.

**Q: Apa arti KPI Net Income dan bagaimana interpretasinya?**

Net Income adalah selisih antara total Revenue dan total Expenses. Nilai positif menunjukkan laba bersih, sedangkan nilai negatif menunjukkan kerugian. Badge YoY pada Net Income perlu dibaca dengan hati-hati: badge hijau bisa berarti profit meningkat ATAU kerugian berkurang. Badge merah bisa berarti profit menurun ATAU kerugian membesar. Selalu cek nilai absolutnya untuk memahami kondisi riwal perusahaan.

**Q: Bagaimana cara membaca KPI Assets, Liabilities, dan Equity secara terpadu?**

Ketiga KPI ini membentuk persamaan akuntansi dasar: Assets = Liabilities + Equity. Assets menunjukkan total kekayaan perusahaan. Liabilities menunjukkan total utang dan kewajiban. Equity menunjukkan nilai kepemilikan pemegang saham. Jika Assets tumbuh lebih cepat dari Liabilities, perusahaan dalam kondisi sehat. Jika Liabilities tumbuh lebih cepat dari Assets, waspadai risiko keuangan. Bandingkan rasio Debt-to-Equity dengan industri sejenis.

**Q: Apa yang dimaksud dengan Cash Balance dan bagaimana berbeda dengan Net Income?**

Cash Balance menunjukkan saldo kas aktual di rekening perusahaan saat ini. Net Income adalah laba akuntansi dari selisih revenue dan expenses. Perusahaan bisa profit tapi cash balance rendah jika banyak piutang belum tertagih. Sebaliknya, perusahaan bisa rugi tapi cash balance tinggi karena ada investasi modal atau pinjaman. Keduanya harus dibaca bersamaan untuk memahami kondisi keuangan yang utuh.

**Q: Bagaimana cara memilih fiscal year yang tepat untuk analisis?**

Dropdown Fiscal Year di bagian atas memungkinkan pemilihan tahun fiskal. Sistem otomatis memilih tahun dengan journal entry posted terbanyak saat pertama kali membuka halaman. Untuk analisis tren, pilih tahun berjalan dan bandingkan badge YoY dengan tahun sebelumnya. Untuk audit atau pelaporan historis, pilih tahun yang sesuai dengan periode yang diperlukan. Hanya tahun fiskal dengan status open yang muncul di daftar.

**Q: Bagaimana cara membaca grafik Revenue vs Expenses yang efektif?**

Grafik bar chart menampilkan 12 bulan dalam tahun fiskal. Bar biru adalah Revenue, bar oranye adalah Expenses. Arahkan kursor ke bar untuk melihat nilai detail. Perhatikan bulan-bulan dengan gap terlebar antara Revenue dan Expenses, ini menunjukkan profit tertinggi. Bulan dengan Expenses lebih tinggi dari Revenue menunjukkan kerugian. Identifikasi pola musiman seperti lonjakan revenue di akhir tahun atau peningkatan expenses di bulan tertentu.

**Q: Apa yang harus dilakukan jika data di Financial Dashboard tidak muncul?**

Jika KPI menampilkan nol atau kosong, periksa beberapa hal berikut. Pastikan ada journal entries yang sudah posted untuk tahun fiskal yang dipilih. Pastikan akun-akun dengan tipe income, expense, asset, liability, dan equity sudah dikonfigurasi dengan benar. Cek apakah periode tahun fiskal sudah dimulai dan ada transaksi. Jika tetap bermasalah, hubungi administrator untuk memverifikasi konfigurasi COA dan data journal entries.

**Q: Bagaimana cara refresh data Financial Dashboard?**

Financial Dashboard tidak memiliki auto-refresh. Untuk mendapatkan data terbaru, refresh halaman browser atau ubah filter tahun fiskal dan kembalikan ke semula. Data diambil dari database setiap kali halaman dibuka atau filter diubah. Jika ada journal entry baru yang baru saja diposting, refresh halaman untuk melihat perubahannya tercermin di KPI dan grafik.

**Q: Bagaimana cara menggunakan Cash Flow Summary untuk analisis likuiditas?**

Cash Flow Summary menampilkan Opening Balance, Cash Inflows, Cash Outflows, dan Closing Balance. Bandingkan Inflows dan Outflows untuk memahami arus kas bersih. Jika Outflows lebih besar, perusahaan mengalami net cash outflow dan perlu memantau likuiditas. Closing Balance yang menurun dari periode sebelumnya menunjukkan posisi kas yang semakin ketat. Gunakan informasi ini untuk perencanaan pengelolaan kas dan working capital.

**Q: Apa perbedaan antara Financial Dashboard dan Aging Dashboard?**

Financial Dashboard fokus pada kinerja keuangan keseluruhan: revenue, expenses, profit, dan posisi neraca. Aging Dashboard fokus pada piutang dan utang yang sudah jatuh tempo. Financial Dashboard menunjukkan gambaran makro kesehatan finansial, sedangkan Aging Dashboard menunjukkan risiko collection dan payment. Gunakan keduanya secara bersamaan untuk memahami kinerja dan risiko keuangan perusahaan secara komprehensif.

**Q: Bagaimana cara menggunakan Top Expense Breakdown untuk efisiensi biaya?**

Top Expense Breakdown menampilkan kategori pengeluaran terbesar dengan persentase terhadap total. Kategori dengan persentase tertinggi adalah target utama untuk efisiensi. Bandingkan dengan periode sebelumnya dengan mengganti tahun fiskal untuk melihat tren perubahan proporsi. Kategori yang tumbuh signifikan perlu diinvestigasi lebih lanjut melalui laporan detail. Gunakan insight ini untuk menyusun strategi penghematan dan budget allocation.
