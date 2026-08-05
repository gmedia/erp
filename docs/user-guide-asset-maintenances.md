# User Guide: Asset Maintenances

## Gambaran Umum

Modul Asset Maintenances mencatat jadwal dan riwayat perawatan aset perusahaan. Setiap perawatan memiliki tipe (preventive, corrective, calibration, other), status, jadwal, dan biaya. Terintegrasi dengan modul Assets dan Supplier untuk tracking perawatan lengkap.

Fitur utama:
- Penjadwalan perawatan preventif dan korektif
- Tracking status perawatan (scheduled, in_progress, completed, cancelled)
- Pencatatan biaya perawatan dan supplier/vendor
- Integrasi dengan data aset (nama, kode, lokasi)
- Export data ke Excel

[Screenshot: Daftar Asset Maintenances]

## Menu & Navigasi

| Menu | Route | Fungsi |
|------|-------|--------|
| Asset Maintenances | `/asset-maintenances` | Daftar perawatan dengan filter, search, CRUD, export |
| Assets | `/assets` | Data aset dan asset profile |
| Asset Dashboard | `/asset-dashboard` | Dashboard aset termasuk alert maintenance |

## 1. Daftar Asset Maintenances

Halaman `/asset-maintenances` menampilkan tabel perawatan:

**Kolom tabel:**
- Asset: Nama dan kode aset
- Type: preventive, corrective, calibration, other
- Status: scheduled, in_progress, completed, cancelled
- Scheduled: Tanggal jadwal perawatan
- Performed: Tanggal pelaksanaan
- Supplier: Vendor/supplier pelaksana
- Notes: Catatan perawatan
- Cost: Biaya perawatan

**Filter tersedia:**
- Search: berdasarkan code, name, atau notes
- Asset: dropdown async select
- Type: dropdown (preventive/corrective/calibration/other)
- Status: dropdown (scheduled/in_progress/completed/cancelled)
- Supplier: dropdown async select

**Sorting:** Semua kolom sortable.

[Screenshot: Tabel Asset Maintenances dengan filter]

## 2. Membuat Perawatan Baru

Klik tombol **Add** untuk membuka form perawatan.

**Field form:**
- Asset: pilih aset yang dirawat (wajib)
- Maintenance Type: pilih tipe perawatan (wajib)
  - **Preventive**: Perawatan rutin terjadwal
  - **Corrective**: Perbaikan karena kerusakan
  - **Calibration**: Kalibrasi alat
  - **Other**: Jenis perawatan lainnya
- Status: default "scheduled"
- Scheduled Date: tanggal jadwal perawatan (wajib)
- Performed Date: tanggal pelaksanaan (opsional, diisi saat selesai)
- Supplier: vendor pelaksana (opsional)
- Cost: biaya perawatan (opsional)
- Notes: catatan atau deskripsi pekerjaan (opsional)

[Screenshot: Form Asset Maintenance]

Klik **Save**. Perawatan dibuat dengan status **scheduled**.

## 3. Workflow Perawatan

| Status | Keterangan |
|--------|------------|
| Scheduled | Perawatan dijadwalkan, belum dimulai |
| In Progress | Perawatan sedang berlangsung |
| Completed | Perawatan selesai, isi performed date |
| Cancelled | Perawatan dibatalkan |

## 4. Menyelesaikan Perawatan

1. Buka detail perawatan
2. Klik **Edit**
3. Ubah status menjadi **Completed**
4. Isi **Performed Date** dan **Cost** (jika ada)
5. Klik **Save**

## 5. Export Data

Klik tombol **Export** untuk mengunduh data perawatan ke Excel.

## FAQ

**Q: Apakah bisa menjadwalkan perawatan berulang?**
A: Saat ini perawatan dibuat per kejadian. Untuk perawatan rutin, buat jadwal baru setiap periode.

**Q: Apakah biaya perawatan mempengaruhi nilai aset?**
A: Biaya perawatan dicatat terpisah dan tidak otomatis mempengaruhi nilai buku aset.

**Q: Di mana melihat riwayat perawatan sebuah aset?**
A: Buka Asset Profile di `/assets/{ulid}`, tab "Maintenance" menampilkan semua riwayat perawatan aset tersebut.
