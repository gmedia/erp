# Panduan: Dasbor Aset

## Gambaran Umum

Asset Dashboard adalah halaman khusus yang menampilkan ringkasan visual seluruh aset perusahaan dalam satu tampilan terintegrasi. Halaman ini dirancang untuk membantu manajer aset memantau kesehatan aset, perawatan yang akan datang, dan status garansi secara waktu nyata melalui kartu ringkasan, grafik distribusi, dan bagian peringatan. Asset Dashboard bersifat hanya baca dan tidak memiliki operasi CRUD.

Untuk mengakses Asset Dashboard, pengguna memerlukan permission `asset_dashboard`. Data yang ditampilkan berasal dari `GetAssetDashboardDataAction` dan dapat difilter berdasarkan branch scope melalui `ResolvesBranchScope` trait.

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Asset Dashboard | /asset-dashboard | Menampilkan ringkasan visual aset dengan kartu ringkasan, status distribution, category distribution, condition overview, perawatan mendatang, dan peringatan garansi |

## 1. Mengakses Asset Dashboard

### Langkah-langkah:

1. Login ke aplikasi dengan akun yang memiliki permission `asset_dashboard`.
2. Klik menu **Dasbor Aset** di sidebar pada grup menu Aset atau akses langsung melalui URL `/asset-dashboard`.
3. Halaman Asset Dashboard akan menampilkan data aset terkini.

### Catatan:

- Halaman ini bersifat hanya baca. Untuk mengubah data aset, navigasi ke modul Aset atau Asset Maintenances.
- Tombol **Refresh Data** tersedia di bagian atas halaman untuk memperbarui data secara manual.

## 2. Memahami Kartu Ringkasan

Asset Dashboard menampilkan 4 kartu ringkasan di bagian atas yang menunjukkan statistik utama aset perusahaan:

### KPI yang Tersedia:

| KPI | Deskripsi | Warna Indikator |
|-----|-----------|-----------------|
| Total Assets | Jumlah total aset yang terdaftar dalam sistem | Biru |
| Purchase Cost | Total nilai perolehan seluruh aset (harga pembelian) | Emerald (hijau) |
| Book Value | Total nilai buku saat ini (nilai setelah depresiasi) | Indigo |
| Accum. Depreciation | Total akumulasi depresiasi seluruh aset | Rose (merah) |

### Interpretasi Nilai:

- **Total Assets**: Menunjukkan volume aset yang dikelola. Nilai ini mencakup semua status aset (draft, active, maintenance, disposed, lost).
- **Purchase Cost**: Nilai historis saat aset dibeli atau diperoleh. Angka ini statis dan tidak berubah sepanjang life cycle aset.
- **Book Value**: Nilai estimasi saat ini setelah dikurangi depresiasi. Nilai ini dinamis dan berubah setiap kali depreciation run dijalankan.
- **Accum. Depreciation**: Total penurunan nilai seluruh aset sejak perolehan. Semakin tinggi angka ini, semakin tua aset-aset perusahaan.

### Tips:

- Bandingkan Book Value dengan Purchase Cost untuk memahami rata-rata umur aset.
- Jika Accum. Depreciation mendekati Purchase Cost, banyak aset sudah mendekati nilai nol dan mungkin perlu diganti.

## 3. Membaca Status Distribution Chart

Diagram donat di bagian kiri menampilkan distribusi aset berdasarkan status.

### Status yang Ditampilkan:

| Status | Warna | Deskripsi |
|--------|-------|-----------|
| Draft | Abu-abu (#6B7280) | Aset dalam proses registrasi, belum aktif digunakan |
| Active | Hijau (#10B981) | Aset sedang digunakan secara normal |
| Maintenance | Oranye (#F59E0B) | Aset sedang dalam proses perbaikan atau maintenance |
| Disposed | Merah (#EF4444) | Aset sudah dihapus dari inventaris (dijual/dibuang) |
| Lost | Merah tua (#DC2626) | Aset hilang atau tidak ditemukan |

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

Diagram batang di bagian kanan menampilkan 10 kategori aset teratas berdasarkan jumlah aset per kategori.

### Informasi yang Ditampilkan:

- Nama kategori aset (misalnya: IT Equipment, Vehicles, Furniture)
- Jumlah aset per kategori
- Visualisasi bar horizontal untuk perbandingan

### Cara Membaca:

1. Bar yang lebih panjang menunjukkan kategori dengan jumlah aset lebih banyak.
2. Angka di sebelah kanan bar menunjukkan jumlah exact per kategori.
3. Kategori diurutkan dari jumlah terbesar ke terkecil (top 10).

### Tips Analisis:

- Identifikasi kategori dengan jumlah aset tertinggi untuk prioritas pemantauan.
- Kategori dengan jumlah rendah bisa jadi kurang termanfaatkan atau belum terdata dengan baik.
- Gunakan informasi ini untuk perencanaan anggaran perolehan aset baru per kategori.

## 5. Memahami Condition Overview

Bagian di bagian bawah menampilkan rincian kondisi fisik aset dalam 3 kategori:

### Kondisi yang Ditampilkan:

| Kondisi | Warna | Deskripsi |
|---------|-------|-----------|
| Good | Hijau (#10B981) | Aset dalam kondisi prima, tidak ada masalah |
| Needs Repair | Oranye (#F59E0B) | Aset butuh perbaikan, masih bisa digunakan dengan batasan |
| Damaged | Merah (#EF4444) | Aset rusak, tidak bisa digunakan normal |

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
- Proporsi Needs Repair/Damaged yang tinggi menunjukkan butuh anggaran perawatan tambahan.
- Investigasi aset dengan kondisi Damaged untuk keputusan pembuangan atau perbaikan.

## 6. Melihat Upcoming Maintenance

Bagian ini menampilkan daftar maintenance yang sedang scheduled atau in-progress. Data difilter dari `AssetMaintenance` dengan status `scheduled` atau `in_progress`.

### Informasi per Item:

| Kolom | Deskripsi |
|-------|-----------|
| Asset Name | Nama aset yang akan/sedang di-maintenance |
| Asset Code | Kode aset (unique identifier) |
| Maintenance Type | Jenis maintenance (preventive, corrective, upgrade, etc.) |
| Lencana Status | Status maintenance (Scheduled / In Progress) |
| Scheduled Date | Tanggal maintenance dijadwalkan |

### Fitur:

- Link **View All** di header navigasi ke halaman `/asset-maintenances` untuk daftar lengkap.
- Scroll area untuk maksimal 5 item dengan scroll jika overflow.
- Lencana warna: Scheduled (default), In Progress (secondary).

### Cara Menggunakan:

1. Tinjau daftar perawatan yang akan datang.
2. Prioritaskan maintenance dengan tanggal terdekat.
3. Klik link asset untuk navigasi ke detail aset.
4. Klik **View All** untuk ke halaman Asset Maintenances.

## 7. Memahami Peringatan Garansi

Bagian ini menampilkan daftar aset dengan garansi yang akan berakhir dalam 30 hari ke depan. Peringatan ini penting untuk perencanaan perpanjangan atau penggantian aset.

### Informasi per Item:

| Kolom | Deskripsi |
|-------|-----------|
| Asset Name | Nama aset dengan garansi yang mendekati kedaluwarsa |
| Asset Code | Kode aset (link ke detail aset) |
| Warranty End Date | Tanggal warranty berakhir |
| Days Left Badge | Jumlah hari tersisa sebelum warranty expire |

### Interpretasi Badge:

- **Lencana merah (destructive)**: 7 hari atau kurang tersisa (kritis)
- **Lencana oranye (secondary)**: 8-30 hari tersisa (peringatan)

### Cara Menggunakan:

1. Tinjau daftar aset dengan garansi yang mendekati kedaluwarsa.
2. Prioritaskan aset dengan lencana merah (kritis).
3. Klik nama aset untuk navigasi ke detail dan review ketentuan garansi.
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
Tinjau Kartu Ringkasan
   |
   v
Analisis Status Distribution
   |
   v
Tinjau Top Asset Categories
   |                    |
   v                    v
Periksa Condition Overview   |
   |                    |
   v                    v
Tinjau Perawatan Mendatang
   |                    |
   v                    v
Check Peringatan Garansi
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
2. Tinjau Kartu Ringkasan untuk perubahan signifikan.
3. Cek Status Distribution untuk anomali (misalnya peningkatan Maintenance).
4. Tinjau Condition Overview untuk aset Needs Repair/Damaged baru.
5. Catat aset yang butuh follow-up.

### Skenario 2: Weekly Maintenance Planning

1. Buka Asset Dashboard.
2. Fokus pada Upcoming Maintenance section.
3. Review maintenance scheduled untuk minggu ini.
4. Koordinasi dengan maintenance team untuk resource allocation.
5. Pastikan maintenance dengan tanggal terdekat sudah prepared.

### Skenario 3: Monthly Warranty Review

1. Buka Asset Dashboard awal bulan.
2. Tinjau bagian Peringatan Garansi.
3. Identifikasi aset dengan warranty expiring bulan ini.
4. List aset yang butuh renewal atau replacement.
5. Koordinasi dengan vendor dan finance untuk budget approval.

### Skenario 4: Asset Health Assessment

1. Buka Asset Dashboard.
2. Tinjau Condition Overview untuk rincian kondisi.
3. Jika proporsi Good rendah (< 70%), investigasi cause.
4. Cek Status Distribution untuk proporsi Maintenance.
5. Tinjau Perawatan Mendatang untuk backlog maintenance.
6. Buat action plan untuk improve asset health score.

### Skenario 5: Category-based Budget Planning

1. Buka Asset Dashboard.
2. Tinjau Top Asset Categories untuk distribusi per kategori.
3. Identifikasi kategori dengan jumlah aset tertinggi (maintenance cost potential).
4. Kombinasikan dengan Condition Overview per kategori (cross-reference via Assets list).
5. Estimasi anggaran perawatan per kategori untuk periode berikutnya.

## FAQ & Tips

### Apakah data Asset Dashboard real-time?

Data Asset Dashboard diambil dari database pada saat halaman dibuka. Tidak ada penyegaran otomatis. Gunakan tombol **Refresh Data** untuk memperbarui data manual.

### Bagaimana cara mengubah branch scope yang ditampilkan?

Branch scope ditentukan oleh permission user dan request parameter. Jika user memiliki akses multi-branch, data dapat difilter melalui branch selector jika tersedia. Hubungi administrator untuk konfigurasi branch access.

### Mengapa beberapa aset tidak muncul di Peringatan Garansi?

Peringatan Garansi hanya menampilkan aset dengan kriteria:
- Status = active
- Warranty end date tidak null
- Warranty end date dalam range 0-30 hari dari hari ini
- Warranty belum expire (warranty end date >= hari ini)

Aset dengan garansi sudah kedaluwarsa atau warranty end date null tidak muncul di alert.

### Apakah Asset Dashboard bisa di-export?

Asset Dashboard tidak memiliki fitur export langsung. Untuk kebutuhan pelaporan, gunakan fitur export di modul:
- Assets (export list aset)
- Asset Maintenances (ekspor catatan perawatan)
- Asset Register Report (laporan detail aset)
- Book Value & Depreciation Report (laporan nilai buku)

### Mengapa saya tidak bisa mengakses Asset Dashboard?

Pastikan akun Anda memiliki permission `asset_dashboard`. Hubungi administrator sistem untuk memverifikasi dan menambahkan permission jika diperlukan.

### Bagaimana cara menambah aset baru dari Asset Dashboard?

Asset Dashboard bersifat hanya baca. Untuk menambah aset baru:
1. Navigasi ke menu **Aset** di sidebar.
2. Gunakan fitur Add New Asset di modul Aset.
3. Kembali ke Asset Dashboard untuk review hasil.

### Apakah depreciation run mempengaruhi data Asset Dashboard?

Ya. Setelah depreciation run dijalankan:
- Book Value di Kartu Ringkasan akan berubah (berkurang)
- Accum. Depreciation di Kartu Ringkasan akan berubah (bertambah)
- Status Distribution bisa berubah jika aset status change

Refresh Asset Dashboard setelah depreciation run untuk melihat data terbaru.

### Mengapa Condition Overview menampilkan "No condition data available"?

Kondisi hanya ditampilkan jika aset memiliki field `condition` yang terisi. Jika semua aset memiliki condition null atau kosong, Condition Overview menampilkan empty state. Update condition aset via Asset Profile atau Asset Stocktake module.

### Bagaimana cara mengetahui maintenance history aset dari Asset Dashboard?

Asset Dashboard hanya menampilkan perawatan mendatang. Untuk riwayat perawatan:
1. Klik nama aset di Upcoming Maintenance section.
2. Navigasi ke Asset Profile.
3. Review maintenance history di asset detail.

Atau navigasi langsung ke **Asset Maintenances** module untuk filter history per aset.

### Tips Optimasi Penggunaan Asset Dashboard

1. **Routine check**: Buka Asset Dashboard minimal 1x per hari untuk monitoring proaktif.
2. **Prioritize alerts**: Lencana merah di Peringatan Garansi butuh tindakan segera.
3. **Cross-reference**: Kombinasikan data dari dashboard dengan laporan detail untuk keputusan yang tepat.
4. **Document anomalies**: Catat perubahan signifikan untuk trending analysis.
5. **Integrate with workflow**: Gunakan dashboard sebagai starting point, lanjutkan ke module terkait untuk action.
6. **Review before depreciation run**: Cek Asset Dashboard sebelum menjalankan depreciation run untuk baseline comparison.
7. **Coordinate with team**: Share dashboard view dengan maintenance team dan finance untuk alignment.

## FAQ & Tips

**Q:** Bagaimana cara membaca grafik distribusi status aset (diagram donat) dengan benar?

**J:** Donut chart menampilkan proporsi aset berdasarkan status (Draft, Active, Maintenance, Disposed, Lost). Angka di tengah donut adalah total aset. Setiap irisan berwarna mewakili satu status — semakin lebar irisan, semakin banyak aset dengan status tersebut. Legend di bawah chart menunjukkan persentase exact. Jika irisan Active dominan (di atas 70%), mayoritas aset dalam kondisi operasional baik. Jika irisan Maintenance melebar dari waktu ke waktu, ini sinyal peningkatan beban perbaikan yang perlu investigasi. Klik atau hover pada irisan untuk melihat jumlah exact.

**Q:** Apa arti setiap status kondisi aset dan kapan status tersebut berubah?

**J:** Kondisi aset memiliki tiga nilai: Good (hijau) — aset berfungsi normal tanpa masalah; Needs Repair (oranye) — aset masih bisa digunakan tetapi memerlukan perbaikan; Damaged (merah) — aset rusak dan tidak dapat digunakan secara normal. Status kondisi diperbarui melalui modul Asset Stocktake (saat stock opname) atau melalui Asset Profile. Kondisi tidak berubah otomatis — harus di-update manual oleh user yang berwenang. Jika Condition Overview menampilkan "No condition data available", artinya belum ada aset yang memiliki data kondisi terisi.

**Q:** Bagaimana cara memfilter dashboard berdasarkan cabang atau kategori tertentu?

**J:** Filter cabang ditentukan oleh permission user melalui `ResolvesBranchScope`. Jika user memiliki akses multi-branch, data yang ditampilkan sudah otomatis difilter berdasarkan branch scope yang berlaku. Untuk melihat data per kategori, dashboard menampilkan Top Asset Categories (10 kategori teratas) sebagai diagram batang — ini adalah filter visual bawaan. Jika Anda membutuhkan filter lebih rinci (kategori spesifik, rentang tanggal, lokasi), gunakan modul Asset Register Report yang memiliki filter lanjutan lengkap.

**Q:** Kapan Peringatan Garansi muncul dan bagaimana cara menindaklanjutinya?

**J:** Peringatan Garansi muncul otomatis untuk aset dengan kriteria: status Aktif, memiliki garansi end date yang valid, dan warranty akan berakhir dalam 0-30 hari ke depan. Lencana merah (destructive) menandakan 7 hari atau kurang — ini prioritas kritis. Lencana oranye menandakan 8-30 hari — ini peringatan. Langkah tindak lanjut: (1) klik nama aset untuk melihat detail ketentuan garansi, (2) hubungi vendor untuk informasi perpanjangan, (3) evaluasi apakah aset perlu diganti atau diperpanjang garansinya, (4) koordinasikan dengan bagian keuangan untuk anggaran perpanjangan atau pengadaan aset pengganti. Aset dengan garansi sudah kedaluwarsa TIDAK muncul di peringatan ini.

**Q:** Bagaimana data dashboard diperbarui dan seberapa sering?

**J:** Data Asset Dashboard diambil dari database secara waktu nyata setiap kali halaman dibuka (on-demand). Tidak ada penyegaran otomatis berkala — Anda harus klik tombol Refresh Data atau muat ulang halaman (F5) untuk mendapatkan data terbaru. Data yang ditampilkan bersumber dari tabel assets, asset_maintenances, dan hasil kalkulasi depresiasi. Setelah menjalankan depreciation run, menambah aset baru, mengubah status aset, atau membuat catatan perawatan baru, selalu segarkan dashboard untuk melihat perubahan.

**Q:** Apa perbedaan antara Asset Dashboard dengan Asset Register Report?

**J:** Asset Dashboard adalah tampilan ringkasan visual (tingkat tinggi) dengan kartu ringkasan, grafik distribusi, dan bagian peringatan — dirancang untuk pemantauan cepat dan identifikasi anomali. Asset Register Report (`/reports/assets/register`) adalah laporan tabel rinci dengan filter lanjutan (kategori, cabang, status, rentang tanggal) dan fitur ekspor ke Excel — dirancang untuk analisis mendalam dan pelaporan. Gunakan dashboard untuk pemeriksaan harian dan kesadaran situasi. Gunakan Asset Register Report untuk audit, rekonsiliasi, dan pelaporan ke manajemen.

**Q:** Tips memanfaatkan Asset Dashboard untuk pengambilan keputusan manajemen?

**J:** (1) Tinjau Kartu Ringkasan setiap awal minggu — bandingkan Book Value vs Purchase Cost untuk menilai kesehatan portofolio aset. (2) Jika Accum. Depreciation mendekati Purchase Cost, siapkan anggaran untuk penggantian aset. (3) Pantau Status Distribution bulanan — tren peningkatan Maintenance menandakan perlu evaluasi program perawatan preventif. (4) Gunakan Peringatan Garansi sebagai pemicu untuk meninjau kontrak vendor dan negosiasi renewal. (5) Rujuk silang Top Asset Categories dengan Condition Overview — kategori dengan jumlah besar dan kondisi Needs Repair tinggi adalah prioritas anggaran perawatan. (6) Sebelum mengajukan pengadaan aset baru, cek dashboard untuk memastikan tidak ada aset yang ada yang kurang termanfaatkan. (7) Dokumentasikan cuplikan dashboard bulanan untuk analisis tren jangka panjang.

**Q:** Apakah Asset Dashboard bisa menampilkan data historis atau perbandingan periode sebelumnya?

**J:** Tidak. Asset Dashboard hanya menampilkan data terkini (current state) — bukan data historis atau perbandingan antar periode. Untuk analisis tren dan perbandingan periode, gunakan: (1) Book Value & Depreciation Report untuk melihat perubahan nilai buku dari waktu ke waktu, (2) Asset Register Report dengan filter tanggal untuk snapshot historis, (3) Maintenance Cost Report untuk analisis biaya maintenance per periode. Tips: ambil screenshot atau catat angka KPI dari dashboard secara berkala (mingguan/bulanan) untuk membuat tren manual.

**Q:** Bagaimana jika grafik Top Asset Categories tidak menampilkan kategori yang saya harapkan?

**J:** Top Asset Categories hanya menampilkan 10 kategori dengan jumlah aset terbanyak. Jika kategori Anda tidak muncul, kemungkinan: (1) jumlah aset di kategori tersebut lebih sedikit dibanding 10 kategori teratas, (2) aset di kategori tersebut belum terdaftar dengan benar (cek di modul Aset), atau (3) kategori tidak memiliki aset sama sekali. Untuk melihat SEMUA kategori termasuk yang jumlahnya kecil, navigasi ke modul Asset Categories (`/asset-categories`) untuk daftar lengkap, atau gunakan Asset Register Report yang menampilkan seluruh data tanpa batasan top-10.

**Q:** Apakah ada batasan jumlah data yang ditampilkan di dashboard?

**J:** Kartu Ringkasan dan grafik (Status Distribution, Top Asset Categories, Condition Overview) menampilkan SELURUH data aset tanpa batasan jumlah — data diagregasi dari semua aset yang dapat diakses oleh pengguna. Perawatan Mendatang menampilkan maksimal 5 item dengan area gulir jika lebih banyak. Peringatan Garansi juga menampilkan maksimal 5 item. Jika Anda membutuhkan daftar lengkap (lebih dari 5 item), klik tautan View All pada bagian terkait untuk navigasi ke modul penuh (Asset Maintenances atau Aset).