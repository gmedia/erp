# Panduan: Alur Persetujuan

## Gambaran Umum

Kelompok **Alur Persetujuan** mengatur bagaimana dokumen dikirim, disetujui, ditolak, dan diaudit.
Panduan ini mencakup empat menu administrator:

- **Alur Persetujuan** untuk mendefinisikan langkah persetujuan.
- **Delegasi Persetujuan** untuk mengalihkan hak menyetujui selama periode tertentu.
- **Pemantauan Persetujuan** untuk melihat beban kerja dan persetujuan yang melewati tenggat.
- **Jejak Audit Persetujuan** untuk menelusuri seluruh tindakan persetujuan.

Halaman **Persetujuan Saya** untuk approver dijelaskan pada panduan terpisah:
[Persetujuan Saya](./user-guide-my-approvals.md).

## Akses & Izin

| Menu | Permission dasar | Permission tindakan |
|---|---|---|
| Alur Persetujuan | `approval_flow` | `approval_flow.create`, `approval_flow.edit`, `approval_flow.delete` |
| Delegasi Persetujuan | `approval_delegation` | `approval_delegation.create`, `.edit`, `.delete` |
| Pemantauan Persetujuan | `approval_monitoring` | Tidak ada permission tindakan terpisah |
| Jejak Audit Persetujuan | `approval_audit_trail` | `approval_audit_trail.export` |

Permission dasar dipakai untuk melihat daftar dan detail. Tombol **Ekspor** pada Alur Persetujuan
memakai permission dasar. Semua menu berada di grup **Admin**.

## Menu & Navigasi

- **Admin > Alur Persetujuan** (`/approval-flows`)
- **Admin > Delegasi Persetujuan** (`/approval-delegations`)
- **Admin > Pemantauan Persetujuan** (`/approval-monitoring`)
- **Admin > Jejak Audit Persetujuan** (`/approval-audit-trail`)
- **Persetujuan Saya** (`/my-approvals`) untuk approver

## Alur Persetujuan

### Melihat daftar

Tabel menampilkan kolom **Code**, **Name**, **Approvable Type**, **Status**, dan **Created At**.
Status berupa **Active** atau **Inactive**. Gunakan pencarian untuk mencari kode atau nama, lalu
buka panel filter untuk mempersempit **Approvable Type** dan **Status**.

### Membuat alur

1. Klik tombol tambah pada halaman Alur Persetujuan.
2. Isi **Flow Name**, **Code**, **Approvable Type**, **Status**, **Description**, dan
   **Conditions (JSON)**.
3. Tambahkan minimal satu langkah persetujuan.
4. Klik simpan.

**Code** harus unik. **Approvable Type** menentukan jenis dokumen yang memakai alur, misalnya
Purchase Request, Purchase Order, Journal Entry, Asset, Asset Movement, Asset Maintenance, atau
Asset Stocktake. **Conditions (JSON)** bersifat opsional dan dipakai untuk membatasi kapan alur
berlaku.

### Mengatur langkah persetujuan

Pada bagian **Approval Steps**, klik **Add Step**, lalu isi:

| Kolom | Keterangan |
|---|---|
| **Step Name** | Nama langkah, misalnya **Persetujuan Manajer**. |
| **Required Action** | **Approve**, **Review**, atau **Acknowledge**. |
| **Approver** | Pengguna yang menjadi penyetuju. Wajib dipilih. |
| **Auto Approve After (Hours)** | Setujui otomatis setelah jumlah jam tertentu. |
| **Escalate After (Hours)** | Eskalasi setelah jumlah jam tertentu. |
| **Escalate To User** | Pengguna penerima eskalasi. |
| **Can Reject** | **Yes** atau **No**. |

Langkah disimpan berurutan sesuai urutan pada tabel. Gunakan ikon pensil untuk mengubah dan ikon
tempat sampah untuk menghapus. Minimal satu langkah harus tersisa.

Saat ini approver yang didukung adalah **pengguna tertentu**. Jenis approver lain tidak diterima
oleh validasi sistem.

### Mengubah dan menghapus

- **Ubah** membuka formulir yang sama. Mengubah daftar langkah akan mengganti seluruh langkah lama
  dengan daftar yang dikirim.
- **Hapus** meminta konfirmasi dan tidak dapat dibatalkan. Alur yang masih dipakai sebaiknya
  dinonaktifkan, bukan dihapus.
- **Ekspor** mengunduh daftar sesuai filter dalam format Excel.

## Delegasi Persetujuan

### Melihat daftar

Kolom yang tampil: **Delegator**, **Delegate**, **Approvable Type**, **Start Date**, **End Date**,
**Reason**, dan **Status**. Status dihitung dari tanggal dan sakelar aktif:

| Status | Arti |
|---|---|
| **Active** | Delegasi aktif hari ini. |
| **Scheduled** | Delegasi belum mulai. |
| **Expired** | Tanggal selesai sudah lewat. |
| **Inactive** | Delegasi dinonaktifkan manual. |

Filter yang tersedia: pencarian alasan, **Delegator**, **Delegate**, **Status**, serta rentang
**Start Date From** dan **Start Date To**.

### Membuat delegasi

1. Klik tombol tambah pada halaman Delegasi Persetujuan.
2. Pilih **Delegator** dan **Delegate**. Keduanya harus pengguna berbeda.
3. Isi **Start Date** dan **End Date**. Tanggal selesai harus sama atau setelah tanggal mulai.
4. Isi **Approvable Type** bila delegasi hanya berlaku untuk satu jenis dokumen. Kosongkan untuk
   semua jenis.
5. Pilih **Status** aktif atau nonaktif, lalu isi **Reason**.
6. Klik simpan.

Selama periode aktif, langkah persetujuan milik delegator dapat diproses oleh delegate. Tindakan
delegate tercatat pada jejak audit sebagai tindakan delegasi, dan pengguna melihat penanda
**Delegasi** pada **Persetujuan Saya**.

### Mengubah dan menghapus

Delegasi dapat diubah, dilihat, dihapus, atau diekspor. Menonaktifkan delegasi lebih aman daripada
menghapus bila riwayat masih dibutuhkan. Delegasi yang sudah kedaluwarsa tetap tampil untuk
keperluan audit.

## Pemantauan Persetujuan

Halaman ini menampilkan empat kartu ringkasan:

- **Pending Approvals**: permintaan yang masih menunggu tindakan.
- **Approved Today**: permintaan yang selesai disetujui hari ini.
- **Rejected Today**: permintaan yang ditolak hari ini.
- **Avg Processing Time**: rata-rata waktu penyelesaian dalam jam.

Gunakan filter **Status** (All Statuses, Pending, In Progress, Approved, Rejected) dan **Branch**
untuk mempersempit data. Data mengikuti cakupan cabang pengguna.

Bagian **Overdue Approvals** menampilkan langkah yang sudah melewati tenggat, dengan kolom
**Document Type**, **Document Name**, **Submitter**, **Pending Step**, **Due Date**, dan
**Hours Overdue**. Daftar dibatasi 50 baris dan diurutkan dari tenggat paling lama. Bila tidak ada
keterlambatan, halaman menampilkan **All caught up!**.

## Jejak Audit Persetujuan

### Tabel

Kolom yang tampil: **Date**, **Document Type**, **Document ID**, **Event**, **Actor**, **Step**,
dan aksi lihat detail. **Document ID** menampilkan nomor dokumen bila tersedia. **Event** memakai
lencana warna: persetujuan berwarna positif, penolakan berwarna bahaya, sedangkan pengajuan,
delegasi, dan eskalasi berwarna netral.

Event yang dapat muncul antara lain **Submitted**, **Step Approved**, **Step Rejected**,
**Step Skipped**, **Auto Approved**, **Escalated**, **Delegated**, **Cancelled**, **Resubmitted**,
dan **Completed**.

### Filter

- **Search** untuk ID dokumen atau nama pelaku.
- **Document Type** untuk jenis dokumen.
- **Event** untuk jenis kejadian.
- **Actor** untuk pengguna pelaku.
- Rentang tanggal untuk membatasi waktu kejadian.

### Detail dan ekspor

Klik aksi lihat untuk membuka **Audit Trail Detail**. Modal menampilkan event, request ID, urutan
langkah, jenis dan ID dokumen, pelaku, tanggal dan waktu, alamat IP, user agent, serta metadata
tambahan. Tombol **Ekspor** memerlukan permission `approval_audit_trail.export` dan mengunduh data
sesuai filter.

## Aturan dan validasi penting

- **Alur wajib memiliki minimal satu langkah** dan setiap langkah wajib memiliki approver.
- **Code alur unik**; kode yang sudah dipakai ditolak.
- **Required Action** hanya boleh **Approve**, **Review**, atau **Acknowledge**.
- **Delegator dan delegate tidak boleh pengguna yang sama.**
- **Tanggal selesai delegasi** tidak boleh sebelum tanggal mulai.
- **Delegasi hanya berlaku** bila aktif dan tanggal hari ini berada di antara tanggal mulai dan
  selesai.
- **Persetujuan berurutan**: langkah berikutnya baru dapat diproses setelah langkah sebelumnya
  selesai.
- **Penolakan menghentikan alur** dan mengembalikan dokumen kepada pengaju untuk diperbaiki.
- **Ekspor jejak audit** memuat data pribadi. Simpan berkas di tempat aman dan bagikan seperlunya.

## Tanya jawab singkat

**Mengapa pengguna tidak melihat permintaan di Persetujuan Saya?**
Periksa apakah pengguna tercatat sebagai approver pada langkah yang sedang aktif, alur berstatus
aktif, dan tidak ada delegasi yang mengalihkan langkah tersebut.

**Apa bedanya Review dan Approve?**
**Approve** menyetujui dan melanjutkan alur. **Review** menandai langkah sudah ditinjau.
**Acknowledge** menandai langkah sudah diketahui. Ketiganya mengikuti definisi pada alur.

**Apakah delegasi otomatis berakhir?**
Delegasi tidak berubah menjadi nonaktif, tetapi statusnya menjadi **Expired** setelah tanggal
selesai lewat dan tidak lagi dipakai untuk perutean.

**Bagaimana cara mengetahui siapa yang menyetujui dokumen?**
Buka **Jejak Audit Persetujuan**, filter dokumen atau pelaku, lalu buka detail baris terkait.

**Apakah alur yang sudah dipakai bisa dihapus?**
Bisa, tetapi tidak disarankan. Nonaktifkan alur agar tidak dipakai untuk dokumen baru dan riwayat
persetujuan tetap utuh.

**Mengapa tombol ekspor tidak muncul?**
Permission ekspor belum diberikan. Untuk jejak audit, permission yang diperlukan adalah
`approval_audit_trail.export`.
