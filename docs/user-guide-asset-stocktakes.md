# User Guide: Asset Stocktakes

## Gambaran Umum

Modul Asset Stocktakes mengelola proses stock opname (penghitungan fisik) aset perusahaan. Setiap stocktake mencatat referensi, cabang, tanggal rencana dan pelaksanaan, serta hasil pengecekan per aset. Stocktake yang sudah dilakukan menghasilkan laporan variance untuk aset yang tidak ditemukan atau berada di lokasi yang salah.

Fitur utama:
- Perencanaan stocktake per cabang
- Proses "Perform" untuk mencatat hasil pengecekan per aset
- Tracking status (draft, in_progress, completed)
- Laporan variance stocktake
- Export data ke Excel

[Screenshot: Daftar Asset Stocktakes]

## Menu & Navigasi

| Menu | Route | Fungsi |
|------|-------|--------|
| Asset Stocktakes | `/asset-stocktakes` | Daftar stocktake dengan filter, search, CRUD |
| Asset Stocktake Perform | `/asset-stocktakes/{ulid}/perform` | Halaman khusus untuk mencatat hasil pengecekan |
| Asset Stocktake Variance Report | `/asset-stocktake-variances` | Laporan variance hasil stocktake |

## 1. Daftar Asset Stocktakes

Halaman `/asset-stocktakes` menampilkan tabel stocktake:

**Kolom tabel:**
- Reference: Nomor referensi stocktake (auto-generated)
- Branch: Cabang yang di-stocktake
- Planned Date: Tanggal rencana pelaksanaan
- Performed Date: Tanggal pelaksanaan aktual
- Status: draft, in_progress, completed
- Created By: User yang membuat

**Filter tersedia:**
- Search: berdasarkan reference atau notes
- Branch: dropdown async select
- Status: dropdown (draft/in_progress/completed)

**Sorting:** Semua kolom sortable.

[Screenshot: Tabel Asset Stocktakes dengan filter]

## 2. Membuat Stocktake Baru

Klik tombol **Add** untuk membuka form stocktake.

**Field form:**
- Branch: pilih cabang yang akan di-stocktake (wajib)
- Planned Date: tanggal rencana pelaksanaan (wajib)
- Notes: catatan atau instruksi (opsional)

[Screenshot: Form Asset Stocktake]

Klik **Save**. Stocktake dibuat dengan status **draft**.

## 3. Melakukan Stocktake (Perform)

Setelah stocktake dibuat, proses pengecekan dilakukan di halaman khusus:

1. Buka daftar stocktake
2. Klik tombol **Perform** pada stocktake yang ingin dijalankan
3. Anda akan diarahkan ke halaman `/asset-stocktakes/{ulid}/perform`
4. Halaman ini menampilkan daftar aset di cabang tersebut dengan kolom:
   - **Asset Code** dan **Asset Name**: Identitas aset
   - **Expected Location**: Lokasi seharusnya menurut sistem
   - **Found Location**: Lokasi aktual ditemukan (isi manual)
   - **Result**: found, not_found, damaged, relocated
   - **Notes**: Catatan pengecekan
   - **Checked By**: Nama petugas pengecekan
   - **Checked At**: Waktu pengecekan

5. Untuk setiap aset, isi **Found Location** dan **Result**
6. Klik **Save** untuk menyimpan progress

[Screenshot: Halaman Perform Asset Stocktake]

## 4. Menyelesaikan Stocktake

Setelah semua aset selesai dicek:

1. Klik tombol **Complete**
2. Status berubah menjadi **completed**
3. Performed Date terisi otomatis
4. Laporan variance tersedia di Asset Stocktake Variance Report

## 5. Workflow Status

| Status | Keterangan |
|--------|------------|
| Draft | Stocktake direncanakan, belum dimulai |
| In Progress | Stocktake sedang berjalan, pengecekan dapat dilakukan bertahap |
| Completed | Stocktake selesai, variance report tersedia |

## 6. Laporan Variance

Hasil stocktake yang sudah completed dapat dilihat di **Asset Stocktake Variance Report** (`/asset-stocktake-variances`). Laporan ini menampilkan aset yang:
- **Not Found**: Aset tidak ditemukan di lokasi manapun
- **Relocated**: Aset ditemukan di lokasi yang berbeda dari expected
- **Damaged**: Aset ditemukan dalam kondisi rusak

## 7. Export Data

Klik tombol **Export** untuk mengunduh data stocktake ke Excel.

## FAQ

**Q: Apakah stocktake yang sudah completed bisa diedit?**
A: Tidak. Stocktake yang sudah completed bersifat final.

**Q: Apakah bisa melakukan stocktake sebagian?**
A: Ya. Anda bisa menyimpan progress di halaman Perform dan melanjutkan nanti. Status tetap In Progress sampai semua aset dicek.

**Q: Apa perbedaan Asset Stocktake dengan Inventory Stocktake?**
A: Asset Stocktake untuk aset tetap (fixed assets), sedangkan Inventory Stocktake untuk stok persediaan (inventory).
