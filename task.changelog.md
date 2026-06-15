# Changelog Tugas

Terakhir diperbarui: 2026-06-15

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

### Hardening Keamanan Multi-Currency (Oracle H3 Wave 0) — PR #16

- [x] Tutup celah silent-corruption multi-currency pada agregasi laporan (aging, AR/AP, dashboard). Sebelum fix, API menerima `currency=USD` di 6 endpoint transaksional + Excel import asset tanpa validasi whitelist. Single POST ill-typed bisa menyebabkan `SUM(amount_due)` mencampur IDR dan USD sebagai angka mentah.
- [x] Konfigurasi baru `config/app.php`: `base_currency='IDR'`, `supported_transaction_currencies=['IDR']` sebagai single source of truth whitelist. Wave 2 (full FX subsystem) akan memperluas list ini saat customer non-IDR pertama tanda tangan.
- [x] Trait baru `HasSupportedCurrencyRules` (sibling `HasTransactionAmountRules`) untuk validasi field `currency` di FormRequest. Diaplikasikan ke 6 AbstractRequest write (Purchase Order, Supplier Bill, Customer Invoice, AP Payment, AR Receipt, Asset) — cover Store + Update via inheritance.
- [x] `AssetImport` Excel uploader juga pakai trait (menutup bypass `/api/assets/import` yang ditemukan oleh review keamanan Oracle, sebelum push).
- [x] 3 regression test memastikan rejection USD: `PurchaseOrderControllerTest::store rejects unsupported currency`, `SupplierBillControllerTest::store rejects unsupported currency`, `AssetImportTest::rejects rows with unsupported currency` (422 / `imported=0,skipped=1`).
- [x] Refactor follow-up untuk Sonar quality gate (commit `96cf4e19`): ekstrak 2 trait baru `HasBankPaymentRules` (parametrized date field + payment method enum, dipakai AP Payment + AR Receipt) dan `HasInvoiceLikeRules` (header + items.* common, dipakai Customer Invoice + Supplier Bill). Net `-44` baris. Duplicated lines density: `8.5%` → `0.0%`.
- [x] Verifikasi: PHPStan clean, Duster clean, Pest full suite 1854 pass (8308 assertions), Sonar Quality Gate OK (semua 6 kondisi pass, coverage 100%, ratings A).

### Hardening Keamanan Multi-Currency (Oracle H3 Wave 1) — PR #17

- [x] Wave 1 melengkapi Wave 0: tambah defense-in-depth pada lapisan agregasi laporan agar mixed-currency mustahil lolos walaupun ada raw DB write atau future importer yang bypass FormRequest. Setelah Wave 1, blind spot Oracle H3 ditutup penuh untuk skema saat ini.
- [x] Service baru `app/Services/Currency/CurrencyGuard.php` dengan dua method: `assertHomogeneousQuery(Builder, context, column='currency')` untuk live DB query dan `assertHomogeneousRows(iterable, context, column='currency')` untuk in-memory iterable.
- [x] Exception baru `app/Exceptions/Currency/MixedCurrencyException` extends `HttpResponseException`. Mengembalikan HTTP 422 + JSON validation error pada field `currency` (Oracle decision #3 picked: 422, bukan 500+Sentry).
- [x] Trait baru `app/Actions/Concerns/AssertsSingleCurrency` (sibling pattern `ResolvesBranchScope`) wrapping the guard.
- [x] Diaplikasikan ke `GetAgingDashboardDataAction`: pre-flight homogeneity check pada `customer_invoices` + `supplier_bills` (filter status + branch_id) sebelum `SUM(amount_due)`. Dua regression test memastikan rejection sat AR atau AP punya mixed-currency.
- [x] `AdminSettingRequest` membaca whitelist dari `config('app.supported_transaction_currencies')` (sebelumnya hardcoded 9 mata uang). Eliminasi desync whitelist yang ditemukan oleh Oracle security review (decision #2 picked: lock to IDR via config).
- [x] 6 form frontend menyembunyikan field `<InputField name="currency">` (Purchase Order, Supplier Bill, Customer Invoice, AP Payment, AR Receipt, Asset). Form state tetap submit `currency: 'IDR'` dari getDefaults — react-hook-form preserve default values walaupun field tidak dirender. Cleanup juga 2 dead `currencyOptions` array. Decision #1 picked: hide entirely.
- [x] Halaman `/admin-settings` dropdown `CURRENCY_OPTIONS` dipersempit ke IDR-only agar konsisten dengan backend whitelist. E2E test "can update regional settings" diperbarui untuk toggle `hide_decimal` (writable regional setting) sat ini, bukan pilih USD lagi.
- [x] Doc baru `docs/user-guide-multi-currency.md` menjelaskan status sat ini, apa yang berubah, FAQ, dan roadmap Wave 2 (decision #4 picked: yes release note).
- [x] Naming aligned dengan precedent `ResolvesBranchScope`: trait `AssertsSingleCurrency` + service `CurrencyGuard` (decision #5 picked).
- [x] Verifikasi: PHPStan clean, Duster clean, Pest full suite 1865 pass (8335 assertions, was 1854 + 11 new tests), Sonar Quality Gate OK (semua 6 kondisi pass, coverage 100%, ratings A).
- [x] Test baru: 7 unit (CurrencyGuard + Exception), 2 feature (Aging mixed-currency rejection AR + AP), 1 admin-settings (USD reject), 1 admin-settings (regression update IDR).
- [x] Scope intentionally tidak dicover (verified by code investigation): `FinancialDashboard` baca `journal_entries` yang tidak punya kolom `currency`; AP/AR aging report + ApPaymentHistoryReport adalah listing per-row bukan agregasi; Budget actions baca journal_entry_lines saja. Semua menunggu Wave 2 (full FX subsystem).

## Dokumen Terkait

- Status handoff aktif: `task.md`
- Arsip historis handoff E2E: `task.handoff-archive.md`
- Pre-implementation research: `docs/profit-loss-by-department-design.md`
