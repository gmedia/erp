# Changelog Tugas

Terakhir diperbarui: 2026-04-28

File ini menyimpan catatan perubahan produk dan fitur.
Baca `task.md` untuk status handoff aktif dan `task.handoff-archive.md` untuk riwayat checkpoint E2E lama.

Catatan penamaan: heading modul memakai pola `Nama Modul di Kode (Label Bisnis)` agar tetap mudah dipahami manusia tanpa kehilangan anchor teknis di repo.

## Changelog Produk

### Modul Employee (Karyawan)

- [x] Form Add/Edit: `Salary` menjadi optional
- [x] Form Add/Edit: tambah input `Employee ID` (NIK)
- [x] Form Add/Edit: tambah input `Termination Date` (tanggal karyawan keluar dari perusahaan)
- [x] Form Add/Edit: tambah input `Employment Status` untuk `Regular` dan `Intern`
- [x] Import: tambah import data Excel dan CSV beserta template file

### Modul Supplier (Pemasok)

- [x] Form Add/Edit: `Email` menjadi optional
- [x] Form Add/Edit: `Address` menjadi optional
- [x] Import: tambah import data Excel dan CSV beserta template file

### Modul Asset (Aset)

- [x] Form Add/Edit: `Serial Number` menjadi optional
- [x] Form Add/Edit: `Barcode` menjadi optional
- [x] Form Add/Edit: `Model` menjadi optional
- [x] Form Add/Edit: `Supplier` menjadi optional
- [x] Form Add/Edit: `Warranty End Date` menjadi optional
- [x] Import: tambah import data Excel dan CSV beserta template file
- [x] Filter: tambah `Employee`, `Location`, `Department`, dan `Supplier`

### Modul Asset Maintenance (Perawatan Aset)

- [x] Form Add/Edit: `Supplier` menjadi optional

### Modul Asset Movement (Mutasi Aset)

- [ ] Dokumen: tambah upload dokumen movement

## Dokumen Terkait

- Status handoff aktif: `task.md`
- Arsip historis handoff E2E: `task.handoff-archive.md`
