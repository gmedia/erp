# User Guide: Employee

## Gambaran Umum

Modul Employee mengelola **data karyawan** lengkap — NIK, nama, email, telepon, departemen, posisi, cabang, gaji, status kepegawaian, dan tanggal masuk. Modul ini mendukung pencatatan karyawan Regular dan Intern, dengan fitur export ke Excel dan import massal dari file CSV/Excel.

---

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| Employees | `/employees` | Kelola data karyawan |
| Import Employees | `/api/employees/import` | Import massal dari Excel/CSV |

---

## 1. Menambah Karyawan Baru

### Langkah-langkah

1. Buka menu **Employees** → klik **Add New**
2. Isi form dengan data karyawan:

   **Informasi Dasar:**
   - **Employee ID (NIK)** — Nomor induk karyawan (contoh: `EMP-001`)
   - **Name** — Nama lengkap karyawan
   - **Email** — Alamat email (contoh: `john.doe@example.com`)
   - **Phone** — Nomor telepon (contoh: `+1 (555) 123-4567`)

   **Informasi Kepegawaian:**
   - **Employment Status** — Status kepegawaian: `Regular` atau `Intern`
   - **Department** — Departemen tempat karyawan bekerja (pilih dari dropdown)
   - **Position** — Posisi/jabatan karyawan (pilih dari dropdown)
   - **Branch** — Cabang tempat karyawan bekerja (pilih dari dropdown)
   - **Salary** — Gaji karyawan (opsional, format: Rp)

   **Tanggal:**
   - **Hire Date** — Tanggal masuk kerja (wajib, tidak bisa lebih dari hari ini)
   - **Termination Date** — tanggal berakhir kepegawaian (opsional)

3. Klik **Save** — karyawan tersimpan dengan data lengkap

[Screenshot: Form Add Employee menampilkan semua field input]

### Validasi Form

| Field | Validasi |
|-------|----------|
| Employee ID (NIK) | Wajib diisi |
| Name | Wajib diisi |
| Email | Format email valid |
| Phone | Opsional |
| Department | Wajib dipilih |
| Position | Wajib dipilih |
| Branch | Wajib dipilih |
| Salary | Angka positif (opsional) |
| Hire Date | Tanggal valid, tidak melebihi hari ini |
| Termination Date | Opsional, format tanggal valid |

---

## 2. Melihat Detail Karyawan

1. Buka menu **Employees**
2. Cari karyawan yang ingin dilihat menggunakan search atau filter
3. Klik icon **View** (eye) pada baris karyawan tersebut
4. Dialog detail akan menampilkan semua informasi karyawan

[Screenshot: Dialog View Employee menampilkan detail karyawan]

### Informasi yang Ditampilkan

- NIK (Employee ID)
- Nama lengkap
- Email dan telepon
- Status kepegawaian (Regular/Intern)
- Departemen, posisi, dan cabang
- Gaji (format Rupiah)
- Hire Date
- Termination Date (jika ada)

---

## 3. Mengedit Data Karyawan

1. Buka menu **Employees**
2. Cari karyawan yang ingin diedit
3. Klik icon **Edit** (pencil) pada baris karyawan
4. Form edit akan terbuka dengan data karyawan yang sudah terisi
5. Ubah field yang diperlukan
6. Klik **Save** untuk menyimpan perubahan

[Screenshot: Form Edit Employee dengan data existing]

---

## 4. Menghapus Karyawan

1. Buka menu **Employees**
2. Cari karyawan yang ingin dihapus
3. Klik icon **Delete** (trash) pada baris karyawan
4. Konfirmasi penghapusan pada dialog yang muncul
5. Karyawan akan terhapus dari daftar

> **Catatan**: Hapus karyawan hanya jika data tidak diperlukan untuk historis transaksi atau laporan.

---

## 5. Search dan Filter

### Search

Ketik kata kunci pada field **Search employees...** untuk mencari berdasarkan:
- NIK (Employee ID)
- Nama
- Email
- Phone

[Screenshot: Search field dengan hasil pencarian]

### Filter Tersedia

| Filter | Penjelasan |
|--------|------------|
| **Department** | Filter berdasarkan departemen |
| **Position** | Filter berdasarkan posisi/jabatan |
| **Branch** | Filter berdasarkan cabang |
| **Status** | Filter berdasarkan employment status: Regular atau Intern |

[Screenshot: Panel filter employee dengan dropdown department, position, branch, status]

### Menggunakan Filter

1. Klik field filter yang ingin digunakan
2. Pilih nilai dari dropdown (misal: Department = "Finance")
3. Tabel akan otomatis menampilkan hasil yang sesuai
4. Kombinasi filter bisa digunakan untuk pencarian lebih spesifik

---

## 6. Sorting Kolom

Klik header kolom untuk mengurutkan data. Kolom yang bisa di-sort:

| Kolom | Penjelasan |
|-------|------------|
| **NIK** | Urutkan berdasarkan Employee ID |
| **Name** | Urutkan berdasarkan nama |
| **Email** | Urutkan berdasarkan email |
| **Phone** | Urutkan berdasarkan nomor telepon |
| **Department** | Urutkan berdasarkan nama departemen |
| **Position** | Urutkan berdasarkan nama posisi |
| **Branch** | Urutkan berdasarkan nama cabang |
| **Salary** | Urutkan berdasarkan gaji |
| **Status** | Urutkan berdasarkan employment status |
| **Hire Date** | Urutkan berdasarkan tanggal masuk |

Klik kolom sekali untuk ascending (A-Z, 0-9), klik lagi untuk descending (Z-A, 9-0).

---

## 7. Export ke Excel

1. Buka menu **Employees**
2. Terapkan filter jika ingin export data tertentu
3. Klik tombol **Export** pada toolbar
4. File Excel (.xlsx) akan diunduh dengan semua kolom data

[Screenshot: Tombol Export pada toolbar employee]

### Kolom dalam File Export

| Kolom | Penjelasan |
|-------|------------|
| ID | ID internal sistem |
| NIK | Employee ID karyawan |
| Name | Nama lengkap |
| Email | Alamat email |
| Phone | Nomor telepon |
| Department | Nama departemen |
| Position | Nama posisi/jabatan |
| Branch | Nama cabang |
| Salary | Gaji dalam Rupiah |
| Status | Employment status (Regular/Intern) |
| Hire Date | Tanggal masuk |
| Created At | Tanggal data dibuat |
| Updated At | Tanggal data terakhir diupdate |

---

## 8. Import Massal

### Langkah Import

1. Buka menu **Employees** → klik tombol **Import Employees**
2. Dialog import akan muncul

[Screenshot: Dialog Import Employees]

3. Download template import jika diperlukan:
   - Klik **Download Template** untuk mendapatkan file Excel contoh
   - Template berisi header kolom yang diperlukan

4. Isi file template dengan data karyawan:
   - Pastikan header kolom sesuai dengan template
   - `department`, `position`, `branch` harus berisi nama yang sudah ada di sistem

5. Upload file:
   - Klik **Choose File** atau drag-drop file ke area upload
   - Format yang diterima: Excel (.xlsx, .xls) atau CSV (.csv)

6. Klik **Import** untuk memproses

7. Sistem akan menampilkan hasil import:
   - Jumlah record berhasil
   - Jumlah record gagal (jika ada)
   - Detail error untuk record gagal

### Header Kolom Import

| Header | Wajib | Penjelasan |
|--------|-------|------------|
| `employee_id` | Ya | NIK karyawan |
| `name` | Ya | Nama lengkap |
| `email` | Ya | Email valid |
| `phone` | Opsional | Nomor telepon |
| `department` | Ya | Nama departemen (harus ada di sistem) |
| `position` | Ya | Nama posisi (harus ada di sistem) |
| `branch` | Ya | Nama cabang (harus ada di sistem) |
| `salary` | Opsional | Gaji (angka) |
| `hire_date` | Ya | Format: YYYY-MM-DD (contoh: 2026-01-15) |
| `employment_status` | Ya | `regular` atau `intern` |
| `termination_date` | Opsional | Format: YYYY-MM-DD |

### Validasi Import

| Validasi | Penjelasan |
|----------|------------|
| Department exists | Nama departemen harus sudah ada di sistem |
| Position exists | Nama posisi harus sudah ada di sistem |
| Branch exists | Nama cabang harus sudah ada di sistem |
| Email format | Email harus format valid |
| Employment status | Harus `regular` atau `intern` |
| Hire date format | Format YYYY-MM-DD, tidak melebihi hari ini |

---

## 9. Checkbox Selection

### Select Individual Row

1. Klik checkbox pada baris karyawan untuk memilih satu record
2. Checkbox header tidak tersedia untuk modul Employee (hanya row selection)

[Screenshot: Checkbox selection pada row employee]

### Bulk Actions dengan Checkbox

Checkbox digunakan untuk:
- Multi-select untuk operasi batch
- Menandai record untuk review

---

## 10. Permissions

| Permission | Akses |
|-----------|-------|
| `employee` | View & list employees |
| `employee.create` | Membuat employee baru |
| `employee.edit` | Mengedit employee |
| `employee.delete` | Menghapus employee |

> **Catatan**: Pastikan user memiliki permission yang sesuai untuk menjalankan fungsi yang diperlukan.

---

## FAQ & Tips

**Q: Apa saja field wajib yang harus diisi saat menambah karyawan baru?**

Field wajib: NIK (Employee ID), Name, Email, Department, Position, Branch, Hire Date, dan Employment Status. Pastikan departemen, posisi, dan cabang sudah tersedia di sistem sebelum membuat karyawan baru.

**Q: Apa perbedaan antara status Regular dan Intern?**

Status Regular untuk karyawan tetap atau kontrak penuh waktu. Status Intern untuk peserta magang atau trainee dengan masa kerja terbatas. Keduanya memiliki akses dan hak yang berbeda dalam sistem tergantung konfigurasi permission.

**Q: Bagaimana format NIK yang benar?**

NIK bebas format, namun disarankan menggunakan prefix dan nomor urut untuk konsistensi. Contoh: `EMP-001`, `REG-2026-001`, atau `INT-001` untuk intern. NIK harus unik dan tidak boleh duplikat.

**Q: Bisakah satu karyawan dipindahkan ke departemen atau cabang lain?**

Ya, edit data karyawan dan ubah field Department atau Branch. Perubahan akan langsung terefleksi di semua laporan yang memfilter berdasarkan departemen atau cabang tersebut.

**Q: Bagaimana cara mencari karyawan dengan kombinasi filter?**

Gunakan beberapa filter sekaligus. Misalnya, pilih Department = "Finance", Position = "Manager", dan Status = "Regular" untuk menampilkan semua manajer tetap di departemen Finance. Klik Reset untuk menghapus semua filter.

**Q: Apa yang harus disiapkan sebelum import data karyawan massal?**

Siapkan master data referensi terlebih dahulu: Department, Position, dan Branch harus sudah ada di sistem. Nama pada file import harus sama persis dengan nama di sistem (case-sensitive). Download template import untuk memastikan format header yang benar.

**Q: Bagaimana cara menghubungkan asset dengan karyawan?**

Di modul Assets, setiap asset bisa di-assign ke karyawan melalui field Employee. Pilih karyawan dari dropdown saat membuat atau mengedit asset. Asset yang sudah di-assign akan tampil di profil karyawan dan bisa dilacak movement history-nya melalui modul Asset Movements.

**Q: Apakah data gaji karyawan bisa dilihat semua user?**

Tidak. Data gaji hanya terlihat oleh user yang memiliki permission `employee` atau `employee.edit`. User dengan permission terbatas tidak bisa mengakses informasi sensitif seperti gaji.

**Q: Bagaimana menangani karyawan yang resign atau berhenti?**

Isi field Termination Date dengan tanggal terakhir kerja. Ini menandakan karyawan sudah tidak aktif tanpa perlu menghapus data. Data historis tetap tersimpan untuk keperluan laporan dan audit trail.

**Q: Bisakah import file dengan format bahasa Indonesia?**

Header kolom harus mengikuti template yang disediakan (dalam bahasa Inggris): `employee_id`, `name`, `email`, `department`, `position`, `branch`, `salary`, `hire_date`, `employment_status`, `termination_date`, `phone`. Isi data boleh dalam bahasa Indonesia.

**Q: Bagaimana cara export hanya karyawan tertentu?**

Terapkan filter terlebih dahulu (misalnya Department = "Sales"), lalu klik Export. File Excel hanya akan berisi data yang sesuai dengan filter aktif. Untuk export semua data, kosongkan semua filter sebelum klik Export.

**Q: Apa yang terjadi jika import gagal sebagian?**

Sistem akan menampilkan detail error untuk setiap baris yang gagal beserta alasan validasinya. Perbaiki data pada file import dan upload ulang. Baris yang berhasil tidak perlu di-upload ulang karena sudah tersimpan.