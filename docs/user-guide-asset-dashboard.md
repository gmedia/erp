# User Guide: Asset Dashboard

## Gambaran Umum

Asset Dashboard adalah halaman khusus yang menampilkan overview visual seluruh aset perusahaan dalam satu tampilan terintegrasi. Halaman ini dirancang untuk membantu asset manager memantau kesehatan aset, maintenance yang akan datang, dan status warranty secara real-time melalui summary cards, grafik distribusi, dan alert sections. Asset Dashboard bersifat read-only dan tidak memiliki operasi CRUD.

Untuk mengakses Asset Dashboard, pengguna memerlukan permission `asset_dashboard`. Data yang ditampilkan berasal dari `GetAssetDashboardDataAction` dan dapat difilter berdasarkan branch scope melalui `ResolvesBranchScope` trait.

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Asset Dashboard | /asset-dashboard | Menampilkan overview visual aset dengan summary cards, status distribution, category distribution, condition overview, upcoming maintenance, dan warranty alerts |

## 1. Mengakses Asset Dashboard

### Langkah-langkah:

1. Login ke aplikasi dengan akun yang memiliki permission `asset_dashboard`.
2. Klik menu **Asset Dashboard** di sidebar pada grup menu Asset atau akses langsung melalui URL `/asset-dashboard`.
3. Halaman Asset Dashboard akan menampilkan data aset terkini.

[Screenshot: Halaman Asset Dashboard utama dengan summary cards dan grafik]

### Catatan:

- Halaman ini bersifat read-only. Untuk mengubah data aset, navigasi ke modul Assets atau Asset Maintenances.
- Tombol **Refresh Data** tersedia di bagian atas halaman untuk memperbarui data secara manual.

## 2. Memahami Summary Cards

Asset Dashboard menampilkan 4 summary cards di bagian atas yang menunjukkan statistik utama aset perusahaan:

### KPI yang Tersedia:

| KPI | Deskripsi | Warna Indikator |
|-----|-----------|-----------------|
| Total Assets | Jumlah total aset yang terdaftar dalam sistem | Biru |
| Purchase Cost | Total nilai perolehan seluruh aset (harga pembelian) | Emerald (hijau) |
| Book Value | Total nilai buku saat ini (nilai setelah depresiasi) | Indigo |
| Accum. Depreciation | Total akumulasi depresiasi seluruh aset | Rose (merah) |

[Screenshot: Summary cards dengan nilai dan indikator warna]

### Interpretasi Nilai:

- **Total Assets**: Menunjukkan volume aset yang dikelola. Nilai ini mencakup semua status aset (draft, active, maintenance, disposed, lost).
- **Purchase Cost**: Nilai historis saat aset dibeli atau diperoleh. Angka ini statis dan tidak berubah sepanjang life cycle aset.
- **Book Value**: Nilai estimasi saat ini setelah dikurangi depresiasi. Nilai ini dinamis dan berubah setiap kali depreciation run dijalankan.
- **Accum. Depreciation**: Total penurunan nilai seluruh aset sejak perolehan. Semakin tinggi angka ini, semakin tua aset-aset perusahaan.

### Tips:

- Bandingkan Book Value dengan Purchase Cost untuk memahami rata-rata umur aset.
- Jika Accum. Depreciation mendekati Purchase Cost, banyak aset sudah mendekati nilai nol dan mungkin perlu diganti.

## 3. Membaca Status Distribution Chart

Grafik donut chart di bagian kiri menampilkan distribusi aset berdasarkan status.

### Status yang Ditampilkan:

| Status | Warna | Deskripsi |
|--------|-------|-----------|
| Draft | Abu-abu (#6B7280) | Aset dalam proses registrasi, belum aktif digunakan |
| Active | Hijau (#10B981) | Aset sedang digunakan secara normal |
| Maintenance | Oranye (#F59E0B) | Aset sedang dalam proses perbaikan atau maintenance |
| Disposed | Merah (#EF4444) | Aset sudah dihapus dari inventaris (dijual/dibuang) |
| Lost | Merah tua (#DC2626) | Aset hilang atau tidak ditemukan |

[Screenshot: Status Distribution donut chart dengan legend]

### Cara Membaca:

1. Lihat bagian tengah donut untuk total jumlah aset.
2. Perhatikan warna-warna yang membentuk donut untuk memahami proporsi setiap status.
3. Legend di bawah chart menampilkan persentase setiap status.
4. Hover atau klik pada bagian chart untuk melihat detail jumlah.

### Analisis yang Bisa Dilakukan:

- Proporsi Active yang tinggi menunjukkan aset dalam kondisi operasional baik.
- Proporsi Maintenance yang tinggi menunjukkan banyak aset butuh perhatian.
- Proporsi Draft yang signifikan menunjukkan proses registrasi aset belum selesai.
- Proporsi Disposed/Lost menunjukkan turnover aset tinggi atau masalah inventory control.

## 4. Membaca Top Asset Categories Chart

Grafik bar chart di bagian kanan menampilkan 10 kategori aset teratas berdasarkan jumlah aset per kategori.

### Informasi yang Ditampilkan:

- Nama kategori aset (misalnya: IT Equipment, Vehicles, Furniture)
- Jumlah aset per kategori
- Visualisasi bar horizontal untuk perbandingan

[Screenshot: Top Asset Categories bar chart]

### Cara Membaca:

1. Bar yang lebih panjang menunjukkan kategori dengan jumlah aset lebih banyak.
2. Angka di sebelah kanan bar menunjukkan jumlah exact per kategori.
3. Kategori diurutkan dari jumlah terbesar ke terkecil (top 10).

### Tips Analisis:

- Identifikasi kategori dengan jumlah aset tertinggi untuk prioritas monitoring.
- Kategori dengan jumlah rendah bisa jadi underutilized atau belum terdata dengan baik.
- Gunakan informasi ini untuk planning budget acquisition aset baru per kategori.

## 5. Memahami Condition Overview

Section di bagian bawah menampilkan breakdown kondisi fisik aset dalam 3 kategori:

### Kondisi yang Ditampilkan:

| Kondisi | Warna | Deskripsi |
|---------|-------|-----------|
| Good | Hijau (#10B981) | Aset dalam kondisi prima, tidak ada masalah |
| Needs Repair | Oranye (#F59E0B) | Aset butuh perbaikan, masih bisa digunakan dengan batasan |
| Damaged | Merah (#EF4444) | Aset rusak, tidak bisa digunakan normal |

[Screenshot: Condition Overview dengan progress bar]

### Cara Membaca:

1. Progress bar menunjukkan proporsi setiap kondisi.
2. Angka di sebelah kanan menunjukkan jumlah exact aset per kondisi.
3. Persentase di sebelah kanan angka menunjukkan proporsi terhadap total aset yang punya data kondisi.

### Catatan:

- Kondisi hanya ditampilkan untuk aset yang memiliki data kondisi (`condition` field terisi).
- Aset dengan kondisi kosong tidak termasuk dalam perhitungan persentase.
- Progress bar menggunakan warna yang sama dengan status chart untuk konsistensi visual.

### Tips:

- Proporsi Good yang tinggi menunjukkan maintenance program efektif.
- Proporsi Needs Repair/Damaged yang tinggi menunjukkan butuh budget maintenance tambahan.
- Investigasi aset dengan kondisi Damaged untuk decision disposal atau repair.

## 6. Melihat Upcoming Maintenance

Section ini menampilkan daftar maintenance yang sedang scheduled atau in-progress. Data difilter dari `AssetMaintenance` dengan status `scheduled` atau `in_progress`.

### Informasi per Item:

| Kolom | Deskripsi |
|-------|-----------|
| Asset Name | Nama aset yang akan/sedang di-maintenance |
| Asset Code | Kode aset (unique identifier) |
| Maintenance Type | Jenis maintenance (preventive, corrective, upgrade, etc.) |
| Status Badge | Status maintenance (Scheduled / In Progress) |
| Scheduled Date | Tanggal maintenance dijadwalkan |

[Screenshot: Upcoming Maintenance list dengan badge status]

### Fitur:

- Link **View All** di header navigasi ke halaman `/asset-maintenances` untuk daftar lengkap.
- Scroll area untuk maksimal 5 item dengan scroll jika overflow.
- Badge warna: Scheduled (default), In Progress (secondary).

### Cara Menggunakan:

1. Review daftar maintenance yang akan datang.
2. Prioritaskan maintenance dengan tanggal terdekat.
3. Klik link asset untuk navigasi ke detail aset.
4. Klik **View All** untuk ke halaman Asset Maintenances.

## 7. Memahami Warranty Alerts

Section ini menampilkan daftar aset dengan warranty yang akan expire dalam 30 hari ke depan. Alert ini penting untuk planning renewal atau replacement aset.

### Informasi per Item:

| Kolom | Deskripsi |
|-------|-----------|
| Asset Name | Nama aset dengan warranty nearing expiry |
| Asset Code | Kode aset (link ke detail aset) |
| Warranty End Date | Tanggal warranty berakhir |
| Days Left Badge | Jumlah hari tersisa sebelum warranty expire |

[Screenshot: Warranty Alerts dengan badge days remaining]

### Interpretasi Badge:

- **Badge merah (destructive)**: 7 hari atau kurang tersisa (critical)
- **Badge oranye (secondary)**: 8-30 hari tersisa (warning)

### Cara Menggunakan:

1. Review daftar aset dengan warranty nearing expiry.
2. Prioritaskan aset dengan badge merah (critical).
3. Klik nama aset untuk navigasi ke detail dan review warranty terms.
4. Koordinasi dengan vendor untuk renewal atau replacement planning.

### Tips:

- Aset dengan warranty expiring dan kondisi Needs Repair/Damaged butuh prioritas decision.
- Review purchase cost vs replacement cost untuk aset dengan warranty expiring.
- Gunakan alert ini untuk budget planning warranty renewal atau acquisition aset baru.

## 8. Workflow Penggunaan Asset Dashboard

```
Login
   |
   v
Buka Asset Dashboard (/asset-dashboard)
   |
   v
Refresh Data (optional)
   |
   v
Review Summary Cards
   |
   v
Analisis Status Distribution
   |
   v
Review Top Asset Categories
   |                    |
   v                    v
Check Condition Overview   |
   |                    |
   v                    v
Review Upcoming Maintenance
   |                    |
   v                    v
Check Warranty Alerts
   |
   v
Navigasi ke modul terkait
(Assets, Asset Maintenances, Asset Depreciation Runs)
   |
   v
Action Plan & Decision Making
```

## 9. Skenario Penggunaan Umum

### Skenario 1: Daily Monitoring Kondisi Aset

1. Buka Asset Dashboard setiap pagi.
2. Review Summary Cards untuk perubahan signifikan.
3. Cek Status Distribution untuk anomali (misalnya peningkatan Maintenance).
4. Review Condition Overview untuk aset Needs Repair/Damaged baru.
5. Catat aset yang butuh follow-up.

### Skenario 2: Weekly Maintenance Planning

1. Buka Asset Dashboard.
2. Fokus pada Upcoming Maintenance section.
3. Review maintenance scheduled untuk minggu ini.
4. Koordinasi dengan maintenance team untuk resource allocation.
5. Pastikan maintenance dengan tanggal terdekat sudah prepared.

### Skenario 3: Monthly Warranty Review

1. Buka Asset Dashboard awal bulan.
2. Review Warranty Alerts section.
3. Identifikasi aset dengan warranty expiring bulan ini.
4. List aset yang butuh renewal atau replacement.
5. Koordinasi dengan vendor dan finance untuk budget approval.

### Skenario 4: Asset Health Assessment

1. Buka Asset Dashboard.
2. Review Condition Overview untuk breakdown kondisi.
3. Jika proporsi Good rendah (< 70%), investigasi cause.
4. Cek Status Distribution untuk proporsi Maintenance.
5. Review Upcoming Maintenance untuk backlog maintenance.
6. Buat action plan untuk improve asset health score.

### Skenario 5: Category-based Budget Planning

1. Buka Asset Dashboard.
2. Review Top Asset Categories untuk distribusi per kategori.
3. Identifikasi kategori dengan jumlah aset tertinggi (maintenance cost potential).
4. Kombinasikan dengan Condition Overview per kategori (cross-reference via Assets list).
5. Estimasi budget maintenance per kategori untuk periode berikutnya.

## FAQ & Tips

### Apakah data Asset Dashboard real-time?

Data Asset Dashboard diambil dari database pada saat halaman dibuka. Tidak ada auto-refresh otomatis. Gunakan tombol **Refresh Data** untuk memperbarui data manual.

### Bagaimana cara mengubah branch scope yang ditampilkan?

Branch scope ditentukan oleh permission user dan request parameter. Jika user memiliki akses multi-branch, data dapat difilter melalui branch selector jika tersedia. Hubungi administrator untuk konfigurasi branch access.

### Mengapa beberapa aset tidak muncul di Warranty Alerts?

Warranty Alerts hanya menampilkan aset dengan criteria:
- Status = active
- Warranty end date tidak null
- Warranty end date dalam range 0-30 hari dari hari ini
- Warranty belum expire (warranty end date >= hari ini)

Aset dengan warranty sudah expire atau warranty end date null tidak muncul di alert.

### Apakah Asset Dashboard bisa di-export?

Asset Dashboard tidak memiliki fitur export langsung. Untuk kebutuhan pelaporan, gunakan fitur export di modul:
- Assets (export list aset)
- Asset Maintenances (export maintenance records)
- Asset Register Report (laporan detail aset)
- Book Value & Depreciation Report (laporan nilai buku)

### Mengapa saya tidak bisa mengakses Asset Dashboard?

Pastikan akun Anda memiliki permission `asset_dashboard`. Hubungi administrator sistem untuk memverifikasi dan menambahkan permission jika diperlukan.

### Bagaimana cara menambah aset baru dari Asset Dashboard?

Asset Dashboard bersifat read-only. Untuk menambah aset baru:
1. Navigasi ke menu **Assets** di sidebar.
2. Gunakan fitur Add New Asset di modul Assets.
3. Kembali ke Asset Dashboard untuk review hasil.

### Apakah depreciation run mempengaruhi data Asset Dashboard?

Ya. Setelah depreciation run dijalankan:
- Book Value di Summary Cards akan berubah (berkurang)
- Accum. Depreciation di Summary Cards akan berubah (bertambah)
- Status Distribution bisa berubah jika aset status change

Refresh Asset Dashboard setelah depreciation run untuk melihat data terbaru.

### Mengapa Condition Overview menampilkan "No condition data available"?

Kondisi hanya ditampilkan jika aset memiliki field `condition` yang terisi. Jika semua aset memiliki condition null atau kosong, Condition Overview menampilkan empty state. Update condition aset via Asset Profile atau Asset Stocktake module.

### Bagaimana cara mengetahui maintenance history aset dari Asset Dashboard?

Asset Dashboard hanya menampilkan upcoming maintenance. Untuk history maintenance:
1. Klik nama aset di Upcoming Maintenance section.
2. Navigasi ke Asset Profile.
3. Review maintenance history di asset detail.

Atau navigasi langsung ke **Asset Maintenances** module untuk filter history per aset.

### Tips Optimasi Penggunaan Asset Dashboard

1. **Routine check**: Buka Asset Dashboard minimal 1x per hari untuk monitoring proaktif.
2. **Prioritize alerts**: Badge merah di Warranty Alerts butuh immediate action.
3. **Cross-reference**: Kombinasikan data dari dashboard dengan report detail untuk decision informed.
4. **Document anomalies**: Catat perubahan signifikan untuk trending analysis.
5. **Integrate with workflow**: Gunakan dashboard sebagai starting point, lanjutkan ke module terkait untuk action.
6. **Review before depreciation run**: Cek Asset Dashboard sebelum menjalankan depreciation run untuk baseline comparison.
7. **Coordinate with team**: Share dashboard view dengan maintenance team dan finance untuk alignment.

## FAQ & Tips

**Q:** Bagaimana cara membaca grafik distribusi status aset (donut chart) dengan benar?

**J:** Donut chart menampilkan proporsi aset berdasarkan status (Draft, Active, Maintenance, Disposed, Lost). Angka di tengah donut adalah total aset. Setiap irisan berwarna mewakili satu status — semakin lebar irisan, semakin banyak aset dengan status tersebut. Legend di bawah chart menunjukkan persentase exact. Jika irisan Active dominan (di atas 70%), mayoritas aset dalam kondisi operasional baik. Jika irisan Maintenance melebar dari waktu ke waktu, ini sinyal peningkatan beban perbaikan yang perlu investigasi. Klik atau hover pada irisan untuk melihat jumlah exact.

**Q:** Apa arti setiap status kondisi aset dan kapan status tersebut berubah?

**J:** Kondisi aset memiliki tiga nilai: Good (hijau) — aset berfungsi normal tanpa masalah; Needs Repair (oranye) — aset masih bisa digunakan tetapi memerlukan perbaikan; Damaged (merah) — aset rusak dan tidak dapat digunakan secara normal. Status kondisi diperbarui melalui modul Asset Stocktake (saat stock opname) atau melalui Asset Profile. Kondisi tidak berubah otomatis — harus di-update manual oleh user yang berwenang. Jika Condition Overview menampilkan "No condition data available", artinya belum ada aset yang memiliki data kondisi terisi.

**Q:** Bagaimana cara memfilter dashboard berdasarkan cabang atau kategori tertentu?

**J:** Filter cabang ditentukan oleh permission user melalui `ResolvesBranchScope`. Jika user memiliki akses multi-branch, data yang ditampilkan sudah otomatis difilter berdasarkan branch scope yang berlaku. Untuk melihat data per kategori, dashboard menampilkan Top Asset Categories (10 kategori teratas) sebagai bar chart — ini adalah filter visual bawaan. Jika Anda membutuhkan filter lebih granular (kategori spesifik, rentang tanggal, lokasi), gunakan modul Asset Register Report yang memiliki advanced filter lengkap.

**Q:** Kapan Warranty Alerts muncul dan bagaimana cara menindaklanjutinya?

**J:** Warranty Alerts muncul otomatis untuk aset dengan kriteria: status Active, memiliki warranty end date yang valid, dan warranty akan berakhir dalam 0-30 hari ke depan. Badge merah (destructive) menandakan 7 hari atau kurang — ini prioritas kritis. Badge oranye menandakan 8-30 hari — ini warning. Langkah tindak lanjut: (1) klik nama aset untuk melihat detail warranty terms, (2) hubungi vendor untuk informasi renewal, (3) evaluasi apakah aset perlu diganti atau diperpanjang warranty-nya, (4) koordinasikan dengan finance untuk budget renewal atau procurement aset pengganti. Aset dengan warranty sudah expire TIDAK muncul di alert ini.

**Q:** Bagaimana data dashboard diperbarui dan seberapa sering?

**J:** Data Asset Dashboard diambil dari database secara real-time setiap kali halaman dibuka (on-demand). Tidak ada auto-refresh berkala — Anda harus klik tombol Refresh Data atau reload halaman (F5) untuk mendapatkan data terbaru. Data yang ditampilkan bersumber dari tabel assets, asset_maintenances, dan hasil kalkulasi depresiasi. Setelah menjalankan depreciation run, menambah aset baru, mengubah status aset, atau membuat maintenance record baru, selalu refresh dashboard untuk melihat perubahan.

**Q:** Apa perbedaan antara Asset Dashboard dengan Asset Register Report?

**J:** Asset Dashboard adalah tampilan overview visual (high-level) dengan summary cards, grafik distribusi, dan alert section — dirancang untuk monitoring cepat dan identifikasi anomali. Asset Register Report (`/reports/assets/register`) adalah laporan tabular detail dengan advanced filter (kategori, cabang, status, rentang tanggal) dan fitur export ke Excel — dirancang untuk analisis mendalam dan pelaporan. Gunakan dashboard untuk daily check dan situational awareness. Gunakan Asset Register Report untuk audit, reconciliation, dan pelaporan ke manajemen.

**Q:** Tips memanfaatkan Asset Dashboard untuk pengambilan keputusan manajemen?

**J:** (1) Review Summary Cards setiap awal minggu — bandingkan Book Value vs Purchase Cost untuk menilai kesehatan portofolio aset. (2) Jika Accum. Depreciation mendekati Purchase Cost, siapkan budget untuk replacement aset. (3) Pantau Status Distribution bulanan — tren peningkatan Maintenance menandakan perlu evaluasi program preventive maintenance. (4) Gunakan Warranty Alerts sebagai trigger untuk review kontrak vendor dan negosiasi renewal. (5) Cross-reference Top Asset Categories dengan Condition Overview — kategori dengan jumlah besar dan kondisi Needs Repair tinggi adalah prioritas budget maintenance. (6) Sebelum mengajukan procurement aset baru, cek dashboard untuk memastikan tidak ada aset existing yang underutilized. (7) Dokumentasikan snapshot dashboard bulanan untuk analisis tren jangka panjang.

**Q:** Apakah Asset Dashboard bisa menampilkan data historis atau perbandingan periode sebelumnya?

**J:** Tidak. Asset Dashboard hanya menampilkan data terkini (current state) — bukan data historis atau perbandingan antar periode. Untuk analisis tren dan perbandingan periode, gunakan: (1) Book Value & Depreciation Report untuk melihat perubahan nilai buku dari waktu ke waktu, (2) Asset Register Report dengan filter tanggal untuk snapshot historis, (3) Maintenance Cost Report untuk analisis biaya maintenance per periode. Tips: ambil screenshot atau catat angka KPI dari dashboard secara berkala (mingguan/bulanan) untuk membuat tren manual.

**Q:** Bagaimana jika grafik Top Asset Categories tidak menampilkan kategori yang saya harapkan?

**J:** Top Asset Categories hanya menampilkan 10 kategori dengan jumlah aset terbanyak. Jika kategori Anda tidak muncul, kemungkinan: (1) jumlah aset di kategori tersebut lebih sedikit dibanding 10 kategori teratas, (2) aset di kategori tersebut belum terdaftar dengan benar (cek di modul Assets), atau (3) kategori tidak memiliki aset sama sekali. Untuk melihat SEMUA kategori termasuk yang jumlahnya kecil, navigasi ke modul Asset Categories (`/asset-categories`) untuk daftar lengkap, atau gunakan Asset Register Report yang menampilkan seluruh data tanpa batasan top-10.

**Q:** Apakah ada batasan jumlah data yang ditampilkan di dashboard?

**J:** Summary Cards dan grafik (Status Distribution, Top Asset Categories, Condition Overview) menampilkan SELURUH data aset tanpa batasan jumlah — data diagregasi dari semua aset yang accessible oleh user. Upcoming Maintenance menampilkan maksimal 5 item dengan scroll area jika lebih banyak. Warranty Alerts juga menampilkan maksimal 5 item. Jika Anda membutuhkan daftar lengkap (lebih dari 5 item), klik link View All pada section terkait untuk navigasi ke modul penuh (Asset Maintenances atau Assets).