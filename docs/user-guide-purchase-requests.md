# User Guide: Purchase Requests

## Gambaran Umum

Modul Purchase Requests (PR) digunakan untuk mengajukan permintaan pembelian barang atau jasa dari departemen internal. Setiap PR dapat berisi beberapa item dengan spesifikasi kuantitas, estimasi harga, dan prioritas. PR yang disetujui dapat dikonversi menjadi Purchase Order (PO).

Fitur utama:
- Pengajuan permintaan pembelian dengan detail item
- Prioritas permintaan (low, medium, high, urgent)
- Workflow approval sebelum konversi ke PO
- Tracking status dari draft hingga approved/rejected/converted
- Export data ke Excel

[Screenshot: Daftar Purchase Requests dengan tabel dan filter]

## Menu & Navigasi

| Menu | Route | Fungsi |
|------|-------|--------|
| Purchase Requests | `/purchase-requests` | Daftar PR dengan filter, search, CRUD, export |
| Purchase Orders | `/purchase-orders` | Daftar PO yang dapat dibuat dari PR yang disetujui |

## 1. Daftar Purchase Requests

Halaman `/purchase-requests` menampilkan tabel PR dengan fitur:

**Kolom tabel:**
- PR Number: Nomor PR (auto-generated)
- Branch: Cabang pemohon
- Department: Departemen pemohon
- Requester: Nama pemohon
- Request Date: Tanggal pengajuan
- Required Date: Tanggal dibutuhkan
- Priority: low, medium, high, urgent
- Status: draft, submitted, approved, rejected, converted
- Estimated Amount: Estimasi total nilai

**Filter tersedia:**
- Search: berdasarkan PR number, notes, atau rejection reason
- Branch: dropdown async select
- Department: dropdown async select
- Status: dropdown (draft/submitted/approved/rejected/converted)
- Priority: dropdown (low/medium/high/urgent)

**Sorting:** Semua kolom sortable. Klik header kolom untuk sort ascending/descending.

[Screenshot: Tabel PR dengan kolom dan filter]

## 2. Membuat Purchase Request Baru

Klik tombol **Add** di toolbar untuk membuka form PR baru.

**Field form header:**
- Branch: pilih cabang (wajib)
- Department: pilih departemen (wajib)
- Requester: pilih karyawan pemohon (wajib)
- Request Date: tanggal pengajuan (default hari ini)
- Required Date: tanggal dibutuhkan (wajib)
- Priority: pilih prioritas (wajib, default: medium)
- Notes: catatan tambahan (opsional)

[Screenshot: Form header PR]

### Menambahkan Item PR

Klik **Add Item** untuk menambahkan barang/jasa yang diminta:

- Product: pilih produk (wajib)
- Unit: satuan produk (auto-terisi)
- Quantity: jumlah yang diminta (wajib)
- Estimated Unit Price: estimasi harga satuan (opsional)
- Notes: catatan per item (opsional)

Estimated Amount total dihitung dari jumlah item.

[Screenshot: Form item PR]

Klik **Save** untuk menyimpan. PR dibuat dengan status **draft**.

## 3. Melihat dan Mengedit PR

Klik ikon **View** (mata) pada kolom Actions untuk melihat detail PR.
Klik ikon **Edit** (pensil) untuk mengubah PR yang masih berstatus draft.

[Screenshot: Detail PR dengan daftar item]

## 4. Workflow Status PR

| Status | Keterangan | Action Berikutnya |
|--------|------------|-------------------|
| Draft | PR baru dibuat, belum diajukan | Edit, Submit, Delete |
| Submitted | PR diajukan untuk approval | Approve, Reject |
| Approved | PR disetujui, siap konversi ke PO | Konversi ke PO |
| Rejected | PR ditolak | Lihat alasan penolakan |
| Converted | PR sudah dikonversi menjadi PO | Lihat PO terkait |

## 5. Menghapus PR

Klik ikon **Delete** (trash) pada PR berstatus **draft**. PR dengan status selain draft tidak dapat dihapus.

## 6. Export Data

Klik tombol **Export** untuk mengunduh data PR ke Excel. Filter yang aktif akan diterapkan ke hasil export.

## FAQ

**Q: Bagaimana cara mengkonversi PR ke PO?**
A: Setelah PR berstatus Approved, buka detail PR dan klik tombol "Convert to PO". Sistem akan membuka form PO baru dengan data dari PR.

**Q: Apakah PR yang sudah dikonversi masih bisa diedit?**
A: Tidak. PR yang sudah dikonversi (status: converted) bersifat read-only.

**Q: Siapa yang bisa menyetujui PR?**
A: Approval mengikuti workflow approval yang dikonfigurasi di modul Approval Flows untuk tipe PurchaseRequest.
