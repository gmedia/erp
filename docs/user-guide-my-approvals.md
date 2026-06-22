# User Guide: My Approvals

## Gambaran Umum

**My Approvals** adalah halaman inbox persetujuan untuk user yang memiliki role approver. Halaman ini menampilkan semua request approval yang ditujukan kepada Anda, baik yang menunggu persetujuan (Pending), sudah disetujui (Approved), sudah ditolak (Rejected), maupun semua request yang pernah diproses (All Requests). Dari halaman ini, Anda bisa approve atau reject dokumen secara langsung tanpa harus membuka halaman detail dokumen terkait.

Fitur ini terintegrasi dengan sistem **Approval Flow** yang mengatur alur persetujuan berjenjang (multi-level approval). Setiap request menampilkan informasi lengkap: jenis dokumen, ID dokumen, requester, tanggal request, status, dan tombol aksi approve/reject.

---

## Menu & Navigasi

| Menu | URL | Fungsi |
|------|-----|--------|
| My Approvals | `/my-approvals` | Inbox persetujuan untuk user logged-in |
| Approval Flows | `/approval-flows` | Konfigurasi alur persetujuan (admin) |
| Approval Delegations | `/approval-delegations` | Delegasi approval ke user lain |
| Approval Monitoring | `/approval-monitoring` | Dashboard monitoring approval (manager) |
| Approval Audit Trail | `/approval-audit-trail` | Log histori semua aktivitas approval |

---

## 1. Mengakses My Approvals

### Langkah-langkah

1. Login ke sistem ERP dengan akun yang memiliki role approver.
2. Buka menu **My Approvals** melalui sidebar atau navigasi utama.
3. Halaman akan menampilkan inbox dengan 4 tab:
   - **Pending** — Request yang menunggu persetujuan Anda.
   - **Approved** — Request yang sudah Anda setujui.
   - **Rejected** — Request yang sudah Anda tolak.
   - **All Requests** — Semua request yang pernah ditujukan kepada Anda.

[Screenshot: Halaman My Approvals dengan 4 tab dan daftar request]

### Prasyarat

| Prasyarat | Penjelasan |
|-----------|------------|
| Permission | User harus memiliki permission yang sesuai dengan Approval Flow step (misal: `approve_purchase_order`, `approve_supplier_bill`) |
| Role Assignment | User harus terdaftar sebagai approver di salah satu step pada Approval Flow |
| Approval Flow Aktif | Approval Flow untuk dokumen tersebut harus berstatus `Active` |

---

## 2. Memahami Informasi Request

Setiap baris request menampilkan kolom informasi berikut:

| Kolom | Penjelasan | Contoh |
|-------|------------|---------|
| **Document Type** | Jenis dokumen yang meminta persetujuan | `Purchase Order`, `Supplier Bill`, `Asset Disposal` |
| **Document ID** | ID unik dokumen (klik untuk navigasi ke detail) | `PO-2026-000001` |
| **Requester** | Nama user yang mengirim request | `John Doe` |
| **Request Date** | Tanggal request dibuat | `2026-01-15 09:30` |
| **Status** | Status approval saat ini | `Pending`, `Approved`, `Rejected` |
| **Current Step** | Step approval yang sedang aktif | `Level 2 - Manager` |
| **Actions** | Tombol approve/reject (hanya di tab Pending) | `Approve`, `Reject` |

[Screenshot: Detail baris request dengan kolom-kolom informasi]

---

## 3. Approve Request

### Langkah-langkah

1. Buka tab **Pending** di halaman My Approvals.
2. Identifikasi request yang ingin Anda setujui.
3. Klik tombol **Approve** pada baris request tersebut.

   [Screenshot: Tombol Approve pada baris request]

4. Jika approval flow mengharuskan komentar:
   - Dialog konfirmasi akan muncul.
   - Isi kolom **Comment** dengan alasan atau catatan persetujuan.
   - Klik **Submit** untuk menyelesaikan approval.

   [Screenshot: Dialog approval dengan input komentar]

5. Jika tidak ada komentar wajib:
   - Approval langsung diproses.
   - Status berubah menjadi `Approved` (di step Anda).
   - Request berpindah ke step berikutnya jika masih ada level approval di atasnya, atau dokumen berstatus fully approved jika Anda adalah approver terakhir.

### Validasi Otomatis

| Validasi | Penjelasan |
|----------|------------|
| Permission Check | Sistem memverifikasi Anda memiliki permission untuk approve dokumen tersebut |
| Step Sequence | Approval harus berurutan sesuai step di Approval Flow |
| Delegation Check | Jika ada delegasi aktif, request bisa ditujukan ke delegate |

---

## 4. Reject Request

### Langkah-langkah

1. Buka tab **Pending** di halaman My Approvals.
2. Identifikasi request yang ingin Anda tolak.
3. Klik tombol **Reject** pada baris request tersebut.

   [Screenshot: Tombol Reject pada baris request]

4. Dialog rejection akan muncul (komentar **wajib** diisi):
   - Isi kolom **Reason** dengan alasan penolakan.
   - Klik **Submit Rejection**.

   [Screenshot: Dialog rejection dengan input alasan wajib]

5. Status request berubah menjadi `Rejected`.
6. Requester akan menerima notifikasi bahwa dokumen ditolak.
7. Dokumen kembali ke requester untuk revisi atau cancellation.

### Catatan Penting

| Catatan | Penjelasan |
|---------|------------|
| Komentar Wajib | Rejection selalu mengharuskan komentar untuk audit trail |
| Notifikasi | Requester mendapat notifikasi rejection dengan alasan yang Anda tulis |
| Tidak Bisa Undo | Rejection bersifat final — requester harus submit ulang dokumen jika ingin approval baru |

---

## 5. Melihat Detail Dokumen

Sebelum approve atau reject, Anda bisa membuka detail dokumen untuk memverifikasi informasi.

### Langkah-langkah

1. Klik **Document ID** pada baris request (misal: `PO-2026-000001`).
2. Sistem akan navigasi ke halaman detail dokumen tersebut.
3. Periksa informasi dokumen: amount, items, requester, attachments, dll.
4. Kembali ke My Approvals untuk melakukan aksi approve/reject.

[Screenshot: Navigasi dari My Approvals ke halaman detail Purchase Order]

---

## 6. Memahami Approval Flow Lifecycle

Approval request mengikuti lifecycle yang ditentukan oleh Approval Flow. Diagram berikut menunjukkan alur umum:

```
Request Created → Pending → (Approve/Reject) → Final Status
     ↓                              ↓
  Step 1 Pending              Step 1 Approved
     ↓                              ↓
  Step 2 Pending              Step 2 Approved
     ↓                              ↓
  Step N Pending              Fully Approved
     ↓                              ↓
  (Rejected)                   Document Processed
     ↓
  Returned to Requester
```

### Status Approval

| Status | Penjelasan |
|--------|------------|
| **Pending** | Menunggu persetujuan di step tertentu |
| **Approved (Partial)** | Sudah approved di beberapa step, masih ada step berikutnya |
| **Approved (Final)** | All steps approved, dokumen fully approved |
| **Rejected** | Ditolak di salah satu step, workflow berhenti |
| **Cancelled** | Requester membatalkan request sebelum approval selesai |

### Multi-Level Approval

Jika Approval Flow memiliki lebih dari satu step:

```
Step 1 (Supervisor) → Step 2 (Manager) → Step 3 (Director) → Final
```

- Setelah Anda approve di Step 1, request otomatis berpindah ke Step 2.
- Approver Step 2 akan menerima request di inbox mereka.
- Proses berlanjut hingga step terakhir atau ada rejection.

---

## 7. Approval Delegation

Jika Anda tidak bisa memproses approval (cuti, sakit, travel), Anda bisa mendelegasikan approval ke user lain.

### Setup Delegation

1. Buka menu **Approval Delegations** (`/approval-delegations`).
2. Klik **Add New**.
3. Isi form:
   - **Delegator** — User Anda (auto-selected).
   - **Delegate** — User yang akan menerima delegation.
   - **Approvable Type** — Jenis dokumen yang didelegasikan (atau All).
   - **Start Date** — Tanggal mulai delegation.
   - **End Date** — Tanggal berakhir delegation.
   - **Reason** — Alasan delegation (wajib).
4. Klik **Save**.

[Screenshot: Form Approval Delegation]

### Efek Delegation

| Efek | Penjelasan |
|------|------------|
| Request Routing | Request approval akan diroute ke delegate selama periode aktif |
| Audit Trail | Semua approval oleh delegate tercatat sebagai delegated approval |
| Notifikasi | Delegate menerima notifikasi request yang didelegasikan |

---

## 8. Tips & Best Practices

| Tips | Penjelasan |
|------|------------|
| **Periksa Detail Sebelum Approve** | Selalu klik Document ID untuk verifikasi informasi dokumen |
| **Komentar yang Jelas** | Tulis komentar spesifik saat approve/reject untuk audit trail yang baik |
| **Response Time** | Proses approval secepat mungkin untuk menghindari bottleneck workflow |
| **Delegation untuk Absence** | Setup delegation jika Anda tidak bisa memproses approval |
| **Cek Approval Monitoring** | Manager bisa monitor overdue approval via Approval Monitoring dashboard |

---

## FAQ

**Q: Apa yang terjadi jika saya approve request di step terakhir?**

Dokumen berstatus **Fully Approved** dan workflow approval selesai. Dokumen akan diproses sesuai next action yang ditentukan (misal: Purchase Order berubah ke status `Confirmed`, Supplier Bill berubah ke `Approved`).

**Q: Bisakah saya mengubah approval setelah reject?**

**Tidak.** Rejection bersifat final. Requester harus mengubah dokumen dan submit ulang request approval baru.

**Q: Bagaimana jika ada SLA auto-approve?**

Approval Flow bisa dikonfigurasi dengan **SLA (Service Level Agreement)**. Jika approver tidak merespons dalam waktu tertentu, sistem bisa auto-approve request. Setting ini dikonfigurasi per step di Approval Flow.

**Q: Apakah saya bisa approve dokumen yang bukan milik branch saya?**

Tergantung konfigurasi Approval Flow. Jika Approval Flow tidak membatasi branch, approver bisa approve dokumen dari branch lain. Jika ada filter branch, hanya approver di branch yang sama yang bisa memproses.

**Q: Bagaimana cara melihat histori approval yang sudah saya proses?**

1. Buka tab **Approved** atau **Rejected** di My Approvals.
2. Semua request yang sudah Anda proses ditampilkan di sana.
3. Untuk histori lengkap semua approval di sistem, buka **Approval Audit Trail**.

**Q: Apa yang terjadi jika requester membatalkan dokumen saat masih pending?**

Request approval berstatus **Cancelled**. Request tidak lagi muncul di inbox approver. Approver tidak bisa approve/reject dokumen yang sudah cancelled.

**Q: Bisakah satu dokumen memiliki lebih dari satu Approval Flow?**

**Ya.** Approval Flow bisa dikonfigurasi dengan **Conditions (JSON)** untuk memilih flow yang berbeda berdasarkan criteria tertentu. Contoh:
- PO amount > 10M: 3-level approval.
- PO amount < 10M: 2-level approval.

**Q: Bagaimana cara mengecek apakah saya adalah approver untuk dokumen tertentu?**

Buka **Approval Monitoring** (`/approval-monitoring`). Dashboard menampilkan summary approval dan Anda bisa filter berdasarkan approver. Jika Anda terdaftar sebagai approver, request akan muncul di My Approvals inbox.

**Q: Bagaimana cara menambahkan komentar saat approve atau reject?**

Saat Anda klik tombol Approve atau Reject, dialog konfirmasi muncul dengan field komentar. Isi komentar yang jelas — ini akan tercatat di audit trail dan membantu requester memahami keputusan Anda.

**Q: Apakah notifikasi dikirim saat ada request approval baru?**

Sistem saat ini tidak mengirim email notifikasi. Namun, Anda bisa memantau request yang menunggu di tab Pending My Approvals dan dashboard Approval Monitoring. Jadwalkan pengecekan rutin setiap hari.

**Q: Apa yang harus dilakukan jika saya tidak sengaja menolak dokumen?**

Hubungi requester untuk mengajukan ulang (resubmit) dokumen. Anda tidak bisa membatalkan rejection. Requester harus merevisi dokumen jika diperlukan dan submit ulang request approval baru.

---

> **Butuh bantuan?** Hubungi administrator sistem jika Anda tidak bisa mengakses My Approvals, memerlukan permission approval tambahan, atau mengalami issue dengan Approval Flow configuration.