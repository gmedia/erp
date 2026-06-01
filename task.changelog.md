# Changelog Tugas

Terakhir diperbarui: 2026-06-01

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

### Modul Financial Reports (Laporan Keuangan)

- [x] Refactor config-driven: tabel `report_configurations` dan `report_sections` (hierarchical `parent_id`, enum `section_type`, enum `sign_convention`, optional `formula`).
- [x] Seeder default untuk 4 laporan built-in: Balance Sheet (15 sections), Income Statement (16), Cash Flow (13), Trial Balance (2).
- [x] Admin UI Report Configuration di `/report-configurations` dengan nested section editor (`useFieldArray`).
- [x] API `GET /api/reports/{balance-sheet,income-statement,cash-flow,trial-balance}` menambahkan key `configuration` (additive, non-breaking) tanpa mengubah struktur `report` lama.
- [x] Menu seeder menambahkan entri "Report Configuration" di grup Accounting; Permission seeder menambahkan `report_configuration` + CRUD children.
- [x] Cakupan tes: 36 Pest financial-reports, 12 Pest reports, 7 E2E Playwright report-configurations.
- [x] Auto-select preferred fiscal year (FY dengan posted journal entries) untuk 5 laporan keuangan utama (Income Statement, Balance Sheet, Comparative, Cash Flow, Trial Balance) via `GetPreferredFiscalYearAction` + `InteractsWithFinancialReportRequest` trait. Zero frontend change.
- [x] Extend preferred FY auto-select ke Trial Balance Detailed + General Ledger via `AsyncSelect.preferredMetaKey` prop + `FiscalYearCollection` meta response. Zero per-page edits.
- [x] Extend preferred FY auto-select ke 8 form transaksi keuangan (`ApPayment`, `ArReceipt`, `CustomerInvoice`, `SupplierBill`, `CreditNote`, `PeriodClosing`, `BankReconciliation`, `AssetDepreciationRun`). Backend `FiscalYearCollection` sekarang status-filter aware (mendukung `?status=open`); frontend `AsyncSelectField` wrapper meneruskan prop `preferredMetaKey` ke `AsyncSelect`. Single backend tweak + satu prop wrapper menyebar ke 8 form tanpa duplikasi logic per-form.
- [x] Fix empty financial report export (FY tanpa CoA → 500) dengan mengisi template `comparison_*`/`change_*` keys.
- [x] Fix Export button disabled saat data kosong — hapus guard `!hasData` agar user tetap bisa export header-only xlsx.

### Modul Pipeline Dashboard

- [x] E2E smoke spec: `tests/e2e/pipeline-dashboard/pipeline-dashboard.spec.ts` — verifikasi heading, filter labels, card slots, chart/table titles.
- [x] CI subset bump 77 → 78 modules.

### Modul Accounts Payable (Hutang Usaha)

- [x] Auto-posting jurnal saat Supplier Bill di-confirm: Debit akun per-item (grouped by `account_id`), Credit Accounts Payable (`code=21100`).
- [x] Auto-posting jurnal saat AP Payment di-confirm: Debit Accounts Payable, Credit `bank_account_id`.
- [x] Idempoten — re-confirm atau update setelah confirmed tidak menduplikasi jurnal.
- [x] `JournalEntry.source_type/source_id` di-set ke dokumen sumber (`App\Models\SupplierBill` / `App\Models\ApPayment`) untuk audit trail.
- [x] Cakupan tes: 13 Pest ap-journal-posting (action-level + controller PUT integration), regresi clean pada supplier-bills, ap-payments, journal-entries, dan asset-depreciation-runs.

### Modul Accounts Receivable (Piutang Usaha)

- [x] Auto-posting jurnal saat Customer Invoice transisi ke `sent`: Debit Accounts Receivable (`code=11200`), Credit akun revenue per-item (grouped by `account_id`).
- [x] Auto-posting jurnal saat AR Receipt di-confirm: Debit `bank_account_id`, Credit Accounts Receivable.
- [x] Idempoten — re-save setelah `sent`/`confirmed` tidak menduplikasi jurnal.
- [x] `JournalEntry.source_type/source_id` di-set ke dokumen sumber (`App\Models\CustomerInvoice` / `App\Models\ArReceipt`) untuk audit trail.
- [x] Cakupan tes: 13 Pest ar-journal-posting (action-level + controller PUT integration), regresi clean pada customer-invoices, ar-receipts, credit-notes, dan ap-journal-posting.

### Modul Aging Dashboard (Dashboard Umur Piutang/Hutang)

- [x] Endpoint baru `GET /api/aging-dashboard` mengembalikan ringkasan AR + AP dengan 5 bucket umur (Current, 1-30, 31-60, 61-90, Over 90 days), Top-10 customer/supplier paling overdue, dan filter `as_of_date` + `branch_id`.
- [x] Backend `GetAgingDashboardDataAction` murni agregasi dengan `selectRaw` + parameterized bindings (tanpa `DATEDIFF`/`CURDATE`) sehingga lintas-DB (MySQL/MariaDB/PostgreSQL/SQLite). Carbon date math untuk semua cutoff bucket.
- [x] Filter status outstanding: AR (`sent`, `partially_paid`, `overdue`), AP (`confirmed`, `partially_paid`, `overdue`). `amount_due` dibaca langsung dari kolom (selalu sinkron via `SyncArReceiptAllocationsAction` / `SyncApPaymentAllocationsAction`).
- [x] Permission baru `aging_dashboard` + entry menu "Aging Dashboard" di grup Accounting (icon `Hourglass`, url `aging-dashboard`).
- [x] Halaman frontend `/aging-dashboard` dengan 4 KPI card (Total Receivables, AR Overdue, Total Payables, AP Overdue dengan badge persentase overdue), 2 chart bucket horizontal (intensitas warna emerald untuk AR, rose untuk AP), 2 tabel Top-10 overdue (customers + suppliers). Tanpa dependensi chart library — bar visual murni Tailwind.
- [x] Cakupan tes: 13 Pest aging-dashboard (107 assertions termasuk invariant jumlah 5 bucket = total outstanding, kasus boundary date inklusif untuk semua 8 edge, custom `as_of_date`, fallback `as_of_date` invalid, guard zero-percentage), 7 E2E Playwright (navigasi, KPI cards, bucket charts, tabel top overdue, filter `as_of_date`, filter branch, refresh, lima label bucket).

### Hardening Keamanan Dashboard (Permission Gate)

- [x] Tutup gap permission middleware di route dashboard yang sebelumnya hanya gated oleh `auth:sanctum`. Sebelum fix, user authenticated apapun (tanpa permission yang relevan) bisa membaca data sensitif.
- [x] `/api/aging-dashboard` di-wrap `permission:aging_dashboard,true` (mirror pola `pipeline-dashboard.php`). Regression test 403 ditambahkan.
- [x] `/api/financial-dashboard` di-wrap `permission:financial_dashboard,true`. Pest beforeEach diperbaiki dari permission `report` (yang bypass karena gate belum ada) menjadi `financial_dashboard`. Regression test 403 ditambahkan.
- [x] `/api/approval-monitoring/data` di-wrap `permission:approval_monitoring,true`. Test refactor pakai trait `CreatesTestUserWithPermissions`. Regression test 403 ditambahkan.

## Dokumen Terkait

- Status handoff aktif: `task.md`
- Arsip historis handoff E2E: `task.handoff-archive.md`
- Pre-implementation research: `docs/profit-loss-by-department-design.md`
