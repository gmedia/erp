# Rencana Refactoring Konsistensi Style Antar Modul

## Tujuan

- Menyamakan style implementasi antar modul sejenis tanpa mengubah kontrak API, route, query param, payload, permission, atau `data-testid` yang sudah dipakai test.
- Menjalankan refactor dalam wave kecil yang bisa divalidasi per tahap dan per modul.
- Memanfaatkan abstraksi yang sudah ada lebih dulu, baru merapikan outlier yang belum mengikuti pola bersama.

## Landasan Saat Ini

- Frontend sudah punya pusat konfigurasi CRUD di `resources/js/utils/entityConfigs.ts` melalui `createSimpleEntityConfig()` dan `createComplexEntityConfig()`.
- Halaman report/read-only sudah punya shell bersama di `resources/js/components/common/ReportDataTablePage.tsx`.
- Halaman financial report sudah punya shell khusus di `resources/js/components/reports/financial/FinancialReportPageShell.tsx`.
- Registrasi route SPA terpusat di `resources/js/app-routes.tsx`.
- Depwire summary menunjukkan hotspot koneksi terbesar ada di model backend dan helper E2E, jadi urutan refactor sebaiknya dimulai dari surface shared frontend, request/resource helper, dan shell halaman sebelum masuk ke hotspot model.

## Guardrail Tetap

- Jangan ubah URI route, HTTP method, query param, response key, bentuk payload submit, atau permission name.
- Jangan ubah `data-testid` yang sudah ada.
- Semua command runtime project harus lewat `./vendor/bin/sail`.
- Jika perlu rename, move, split, merge, atau delete file, jalankan Depwire lebih dulu (`get_file_context`, `impact_analysis`, `simulate_change`) sebelum edit.
- Jika keputusan refactor bergantung pada perilaku library atau framework, ambil referensi terbaru via Context7 lebih dulu.
- Satu wave hanya boleh membawa satu shape refactor. Jangan campur simple CRUD, transaction form, dan report shell dalam wave yang sama.

## Aturan Klasifikasi Eksekusi

- Masuk bucket `CRUD Simple` bila implementasi aktual memakai pola `createSimpleEntityConfig`, `SimpleEntityForm`, `SimpleEntityViewModal`, dan tabel sederhana tanpa nested workflow berat.
- Masuk bucket `CRUD Complex` bila implementasi aktual memakai `createComplexEntityConfig` atau sibling file khusus seperti `Columns.tsx`, `Filters.tsx`, `Form.tsx`, `ViewModal.tsx`, termasuk modul dengan async select, nested items, atau dynamic arrays.
- Masuk bucket `Non-CRUD` bila halamannya berupa report read-only, dashboard, settings, approval/workflow page, atau embedded component yang bukan CRUD shell standar.
- Jika nama kategori di registry dan bentuk implementasi aktual berbeda, ikuti implementation surface yang ada di codebase saat ini.

## Target Style Per Jenis Modul

| Jenis Modul  | Anchor Existing                                                                                                                                                                       | Target Konsistensi                                                                                                                                                                                                                           |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| CRUD Simple  | `resources/js/utils/entityConfigs.ts`                                                                                                                                                 | Page memakai `createSimpleEntityConfig()`, kolom sederhana reuse helper shared, form reuse `SimpleEntityForm`, view reuse `SimpleEntityViewModal`, request/resource/export backend mengikuti helper simple CRUD yang sama.                   |
| CRUD Complex | `resources/js/utils/entityConfigs.ts`, `resources/js/components/common/ItemFormDialog.tsx`, `resources/js/utils/schemas.ts`, `resources/js/components/common/ViewModalItemsTable.tsx` | Setiap modul mengikuti kontrak file yang konsisten (`Columns`, `Filters`, `Form`, `ViewModal`), reuse helper async select/item dialog/schema/error handling, dan backend memakai family pattern request/filter/resource/action yang sejenis. |
| Non-CRUD     | `resources/js/components/common/ReportDataTablePage.tsx`, `resources/js/components/reports/financial/FinancialReportPageShell.tsx`, `resources/js/layouts/app-layout.tsx`             | Read-only table/report memakai shell bersama, dashboard/settings/workflow memakai layout/loading/error state yang seragam, dan route SPA tetap diregistrasi dengan pola lazy route yang sama.                                                |

## Ukuran Wave

- Satu wave idealnya 2 sampai 3 modul atau maksimum 8 file produksi.
- Mulai dari anchor shared yang mengontrol behavior, lalu turunkan ke sibling module yang paling serupa.
- Selesaikan satu wave penuh sampai validasi selesai sebelum pindah tahap berikutnya.
- Dokumentasikan outlier yang sengaja belum dipindah agar tidak hilang di sesi berikutnya.

## Validasi Minimum Per Wave

- Frontend touched: `./vendor/bin/sail npm run types`
- PHP touched: `./vendor/bin/sail php ./vendor/bin/phpstan analyse <file...> --no-progress` bila scope cukup kecil, atau `./vendor/bin/sail test --group <module-slug>` bila ada group yang tepat
- E2E touched: `./vendor/bin/sail npm run test:e2e -- tests/e2e/<module-slug>/`
- Shared test helper atau banyak modul touched: `./vendor/bin/sail npm run test:e2e:smoke-waves`
- Handoff: update `task.md` setelah tiap wave selesai

## Tahap 0. Baseline dan Matriks Outlier

### Outcome

- Semua modul dipetakan ke bucket implementasi aktual: simple CRUD, complex CRUD, atau non-CRUD.
- Outlier per bucket terdokumentasi: mana yang belum mengikuti shell shared, helper shared, atau family request/resource yang sama.
- Urutan wave disepakati dari yang paling murah dan paling aman.

### Scope

- Audit dokumen dan implementation surface tanpa rename/move file.
- Mulai dari `docs/module-registry.md`, `resources/js/utils/entityConfigs.ts`, `resources/js/components/common/ReportDataTablePage.tsx`, `resources/js/components/reports/financial/FinancialReportPageShell.tsx`, dan `resources/js/app-routes.tsx`.

### Exit Criteria

- Ada daftar cohort nyata untuk Tahap 1 sampai Tahap 5.
- Modul yang secara domain tampak simple tetapi secara implementasi complex sudah ditandai eksplisit.
- Outlier yang butuh perubahan struktural sudah diberi catatan wajib Depwire.

### Prompt Eksekusi Tahap 0

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 0: baseline dan matriks outlier untuk konsistensi style antar modul.

Target:
- Petakan setiap modul ke bucket implementasi aktual: simple CRUD, complex CRUD, atau non-CRUD.
- Catat outlier yang belum mengikuti shell/config/helper shared.
- Jangan ubah kode produksi kecuali perlu menambah atau merapikan dokumentasi plan/handoff.

Aturan kerja:
- Mulai dari anchor shared: resources/js/utils/entityConfigs.ts, resources/js/components/common/ReportDataTablePage.tsx, resources/js/components/reports/financial/FinancialReportPageShell.tsx, resources/js/app-routes.tsx.
- Jangan lakukan broad repo exploration setelah bucket tiap modul sudah bisa ditentukan.
- Jika menemukan kandidat rename/move/split/merge untuk tahap berikutnya, catat dulu dan wajibkan Depwire pada eksekusi tahapnya.

Validasi:
- git diff --check -- docs/refactor-style-consistency-plan.md task.md

Handoff:
- Update task.md dengan hasil klasifikasi, outlier utama, dan next wave yang direkomendasikan.
```

### Hasil Tahap 0 — Matriks Klasifikasi Implementasi Aktual

Tanggal: 2026-04-30

#### Bucket A: CRUD Simple (menggunakan `createSimpleEntityConfig` + `createEntityCrudPage`)

| # | Modul | Config Factory | Page Shell | Outlier? |
|---|-------|---------------|------------|----------|
| 1 | departments | `createSimpleEntityConfig` | `createEntityCrudPage` | ✅ Konform |
| 2 | positions | `createSimpleEntityConfig` | `createEntityCrudPage` | ✅ Konform |
| 3 | branches | `createSimpleEntityConfig` | `createEntityCrudPage` | ✅ Konform |
| 4 | supplier-categories | `createSimpleEntityConfig` | `createEntityCrudPage` | ✅ Konform |
| 5 | customer-categories | `createSimpleEntityConfig` | `createEntityCrudPage` | ✅ Konform |

**Catatan**: Semua 5 modul simple CRUD sudah sepenuhnya konform. Tidak ada outlier di bucket ini.

#### Bucket B: CRUD Complex (menggunakan `createComplexEntityConfig` + `createEntityCrudPage`)

| # | Modul | Config Factory | Page Shell | Outlier? | Catatan |
|---|-------|---------------|------------|----------|---------|
| 1 | warehouses | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | — |
| 2 | employees | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Import support |
| 3 | customers | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | — |
| 4 | suppliers | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Import support |
| 5 | products | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | — |
| 6 | product-categories | `createComplexEntityConfig` | `createEntityCrudPage` | ⚠️ Borderline | Domain simple, impl complex (extra Description col) |
| 7 | units | `createComplexEntityConfig` | `createEntityCrudPage` | ⚠️ Borderline | Domain simple, impl complex (extra Symbol col) |
| 8 | asset-categories | `createComplexEntityConfig` | `createEntityCrudPage` | ⚠️ Borderline | Domain simple, impl complex (extra Useful Life col) |
| 9 | asset-models | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | FK ke asset-categories |
| 10 | asset-locations | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | FK ke branches, self-ref parent |
| 11 | assets | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Paling kompleks, view_type: page |
| 12 | asset-movements | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | — |
| 13 | asset-maintenances | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | — |
| 14 | asset-stocktakes | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Custom perform page |
| 15 | fiscal-years | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | — |
| 16 | coa-versions | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | — |
| 17 | account-mappings | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | — |
| 18 | journal-entries | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Icon buttons action |
| 19 | pipelines | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Nested sub-form |
| 20 | approval-flows | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Dynamic nested array |
| 21 | approval-delegations | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | — |
| 22 | stock-transfers | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Transaction/nested items |
| 23 | inventory-stocktakes | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Transaction/nested items |
| 24 | stock-adjustments | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Transaction/nested items |
| 25 | purchase-requests | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Transaction/nested items |
| 26 | purchase-orders | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Transaction/nested items |
| 27 | goods-receipts | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Transaction/nested items |
| 28 | supplier-returns | `createComplexEntityConfig` | `createEntityCrudPage` | ✅ Konform | Transaction/nested items |

**Outlier CRUD Complex**:

| # | Modul | Masalah | Wajib Depwire? |
|---|-------|---------|----------------|
| 1 | **accounts** | Tidak ada di `entityConfigs.ts`, tidak pakai `createEntityCrudPage`. Custom tree-based UI dengan `AccountTree` component, manual state management (`useState`/`useCallback`), dan `axios` langsung. | Ya — jika ingin migrasi ke shared shell perlu simulate_change karena UI tree tidak cocok dengan DataTable |
| 2 | **product-categories** | Domain simple (hanya Name + Description) tapi pakai `createComplexEntityConfig` karena ada kolom Description tambahan. | Tidak — bisa downgrade ke simple config + optional extra column helper |
| 3 | **units** | Domain simple (hanya Name + Symbol) tapi pakai `createComplexEntityConfig` karena ada kolom Symbol tambahan. | Tidak — bisa downgrade ke simple config + optional extra column helper |
| 4 | **asset-categories** | Domain simple (hanya Name + Useful Life) tapi pakai `createComplexEntityConfig` karena ada kolom Useful Life tambahan. | Tidak — bisa downgrade ke simple config + optional extra column helper |

#### Bucket C: Non-CRUD

| # | Modul | Shell Aktual | Konform? | Catatan |
|---|-------|-------------|----------|---------|
| **C1. Report Data Table (menggunakan `ReportDataTablePage`)** | | | | |
| 1 | inventory-valuation-report | `ReportDataTablePage` | ✅ Konform | — |
| 2 | stock-movement-report | `ReportDataTablePage` | ✅ Konform | — |
| 3 | inventory-stocktake-variance-report | `ReportDataTablePage` | ✅ Konform | — |
| 4 | stock-adjustment-report | `ReportDataTablePage` | ✅ Konform | — |
| 5 | purchase-order-status-report | `ReportDataTablePage` | ✅ Konform | — |
| 6 | purchase-history-report | `ReportDataTablePage` | ✅ Konform | — |
| 7 | goods-receipt-report | `ReportDataTablePage` | ✅ Konform | — |
| 8 | asset-reports/register | `ReportDataTablePage` | ✅ Konform | Reuse AssetColumns/Filters |
| 9 | book-value-depreciation-reports | `ReportDataTablePage` | ✅ Konform | — |
| 10 | maintenance-cost-reports | `ReportDataTablePage` | ✅ Konform | — |
| 11 | asset-stocktake-variances | `ReportDataTablePage` | ✅ Konform | — |
| **C2. Financial Report (menggunakan `FinancialReportPageShell`)** | | | | |
| 12 | balance-sheet | `FinancialReportPageShell` | ✅ Konform | Comparison mode |
| 13 | income-statement | `FinancialReportPageShell` | ✅ Konform | Comparison mode |
| 14 | comparative | `FinancialReportPageShell` | ✅ Konform | Comparison mode |
| 15 | cash-flow | `SingleYearFinancialReportPageShell` | ✅ Konform | Single year variant |
| 16 | trial-balance | `SingleYearFinancialReportPageShell` | ✅ Konform | Single year variant |
| **C3. Audit Trail (menggunakan `AuditTrailPage`)** | | | | |
| 17 | pipeline-audit-trail | `AuditTrailPage` | ✅ Konform | — |
| 18 | approval-audit-trail | `AuditTrailPage` | ✅ Konform | — |
| **C4. Read-Only Data Table (menggunakan `DataTablePage` — bukan `ReportDataTablePage`)** | | | | |
| 19 | stock-movements | `DataTablePage` | ⚠️ Outlier | Pakai `DataTablePage` bukan `ReportDataTablePage`; manual `useCrudFilters`/`useCrudQuery` |
| 20 | stock-monitor | `DataTablePage` | ⚠️ Outlier | Pakai `DataTablePage` + custom summary cards; manual hooks |
| **C5. Standalone Table (menggunakan `StandaloneTablePage`)** | | | | |
| 21 | posting-journals | `StandaloneTablePage` | ⚠️ Outlier | Custom batch-post workflow, manual table, tidak pakai shared report/CRUD shell |
| 22 | asset-depreciation-runs | `StandaloneTablePage` | ⚠️ Outlier | Custom calculate/post workflow, manual table |
| **C6. Dashboard (custom page, `AppLayout` langsung)** | | | | |
| 23 | asset-dashboard | `AppLayout` langsung | ⚠️ Outlier | Tidak ada shared dashboard shell |
| 24 | pipeline-dashboard | `AppLayout` langsung | ⚠️ Outlier | Tidak ada shared dashboard shell |
| 25 | approval-monitoring | `AppLayout` langsung | ⚠️ Outlier | Tidak ada shared dashboard shell |
| **C7. Settings/Workflow (custom page)** | | | | |
| 26 | admin-settings | `AdminSettingsLayout` | ✅ Konform | Punya layout sendiri yang konsisten |
| 27 | my-approvals | `AppLayout` langsung | ⚠️ Outlier | Custom tabs + approval dialog, 406 lines |
| 28 | users | `AppLayout` langsung | ⚠️ Outlier | Custom employee-user linking page |
| 29 | permissions | `AppLayout` langsung | ⚠️ Outlier | Custom permission matrix page |

#### Ringkasan Outlier Utama

| # | Modul | Bucket Registry | Bucket Aktual | Masalah Utama | Kandidat Refactor |
|---|-------|----------------|---------------|---------------|-------------------|
| 1 | accounts | Complex CRUD | **Non-CRUD (custom tree)** | Tidak pakai entityConfigs/createEntityCrudPage sama sekali | Biarkan — UI tree tidak cocok DataTable |
| 2 | product-categories | Complex CRUD | Complex CRUD (borderline simple) | Pakai `createComplexEntityConfig` padahal domain simple | Downgrade ke extended simple config |
| 3 | units | Complex CRUD | Complex CRUD (borderline simple) | Pakai `createComplexEntityConfig` padahal domain simple | Downgrade ke extended simple config |
| 4 | asset-categories | Complex CRUD | Complex CRUD (borderline simple) | Pakai `createComplexEntityConfig` padahal domain simple | Downgrade ke extended simple config |
| 5 | stock-movements | Non-CRUD | Non-CRUD (read-only table) | Pakai `DataTablePage` bukan `ReportDataTablePage` | Migrasi ke `ReportDataTablePage` |
| 6 | stock-monitor | Non-CRUD | Non-CRUD (read-only table + dashboard) | Pakai `DataTablePage` + custom summary | Migrasi ke `ReportDataTablePage` + summary slot |
| 7 | posting-journals | Non-CRUD | Non-CRUD (batch workflow) | `StandaloneTablePage` custom, 389 lines | Biarkan — workflow unik |
| 8 | asset-depreciation-runs | Non-CRUD | Non-CRUD (batch workflow) | `StandaloneTablePage` custom, 250 lines | Biarkan — workflow unik |
| 9 | asset-dashboard | Non-CRUD | Non-CRUD (dashboard) | Tidak ada shared dashboard shell | Kandidat shared dashboard shell |
| 10 | pipeline-dashboard | Non-CRUD | Non-CRUD (dashboard) | Tidak ada shared dashboard shell | Kandidat shared dashboard shell |
| 11 | approval-monitoring | Non-CRUD | Non-CRUD (dashboard) | Tidak ada shared dashboard shell | Kandidat shared dashboard shell |
| 12 | my-approvals | Non-CRUD | Non-CRUD (workflow) | Custom tabs + dialog, 406 lines | Biarkan — workflow unik |
| 13 | users | Non-CRUD | Non-CRUD (admin) | Custom employee-user linking | Biarkan — domain unik |
| 14 | permissions | Non-CRUD | Non-CRUD (admin) | Custom permission matrix | Biarkan — domain unik |

#### Kandidat Perubahan Struktural (Wajib Depwire Saat Eksekusi)

| # | Kandidat | Tipe | Alasan Depwire |
|---|----------|------|----------------|
| 1 | Extend `createSimpleEntityConfig` untuk menerima extra columns opsional | Split/extend helper | Blast radius ke semua 5 simple CRUD consumer |
| 2 | Migrasi `stock-movements` dari `DataTablePage` ke `ReportDataTablePage` | Move shell | Perlu cek dependents `DataTablePage` dan impact ke E2E |
| 3 | Migrasi `stock-monitor` dari `DataTablePage` ke `ReportDataTablePage` + summary slot | Move shell + extend | Perlu cek apakah `ReportDataTablePage` bisa menerima children/summary |
| 4 | Buat shared `DashboardPageShell` untuk 3 dashboard pages | New shared component | Perlu impact analysis pada 3 consumer pages |

#### Cohort Wave yang Direkomendasikan

**Tahap 1 (CRUD Simple)** — Sudah konform, tidak perlu refactor kecuali:
- Wave 1C opsional: downgrade `product-categories`, `units`, `asset-categories` ke extended simple config (perlu extend `createSimpleEntityConfig` dulu).

**Tahap 2 (CRUD Complex Master)** — Mayoritas sudah konform via `createComplexEntityConfig` + `createEntityCrudPage`. Fokus:
- Wave 2A: `warehouses`, `customers`, `suppliers` — cek konsistensi backend request/resource/filter pattern.
- Wave 2B: `employees`, `products`, `fiscal-years`, `coa-versions` — cek konsistensi backend.
- Wave 2C: `account-mappings`, `journal-entries`, `asset-models`, `asset-locations`, `approval-delegations`, `pipelines` — cek konsistensi backend.

**Tahap 3 (CRUD Complex Transaction)** — Semua sudah pakai `createEntityCrudPage`, fokus pada:
- Wave 3A: `purchase-requests`, `purchase-orders`, `goods-receipts`, `supplier-returns` — deduplikasi item dialog/table boilerplate.
- Wave 3B: `stock-transfers`, `inventory-stocktakes`, `stock-adjustments` — deduplikasi item dialog/table boilerplate.
- Wave 3C: `assets`, `asset-movements`, `asset-maintenances`, `asset-stocktakes`, `approval-flows` — deduplikasi nested form boilerplate.

**Tahap 4 (Non-CRUD Report)** — Mayoritas sudah konform. Fokus:
- Wave 4A: Migrasi `stock-movements` dan `stock-monitor` ke `ReportDataTablePage` (wajib Depwire).
- Wave 4B–4D: Cek drift minor pada report pages yang sudah konform.

**Tahap 5 (Non-CRUD Workflow/Dashboard/Settings)** — Fokus:
- Wave 5A: `admin-settings`, `my-approvals`, `approval-monitoring` — cek apakah shared dashboard shell bisa dibuat.
- Wave 5B: `pipeline-dashboard`, `asset-dashboard`, `asset-depreciation-runs` — kandidat shared dashboard shell.
- Wave 5C: `approval-history`, `entity-state-actions`, `entity-state-timeline` — embedded components, cek konsistensi.

#### Modul yang Sengaja Dikecualikan dari Refactor

| # | Modul | Alasan |
|---|-------|--------|
| 1 | accounts | UI tree-based, tidak cocok DataTable shell |
| 2 | posting-journals | Batch workflow unik (select + post) |
| 3 | asset-depreciation-runs | Batch workflow unik (calculate + post) |
| 4 | users | Employee-user linking, domain unik |
| 5 | permissions | Permission matrix, domain unik |
| 6 | my-approvals | Approval inbox dengan tabs + action dialog, domain unik |

## Tahap 1. Konvergensi CRUD Simple

### Outcome

- Modul simple CRUD memakai shell frontend dan backend yang benar-benar seragam.
- Perbedaan antar modul simple hanya tinggal metadata nama entitas, placeholder, dan permission yang memang spesifik.

### Wave Prioritas

- Wave 1A: `departments`, `positions`, `branches`
- Wave 1B: `customer-categories`, `supplier-categories`
- Wave 1C: modul lain yang setelah Tahap 0 terbukti masih memenuhi kontrak simple CRUD secara implementasi aktual

### Anchor

- `resources/js/utils/entityConfigs.ts`
- `resources/js/components/common/SimpleEntityForm.tsx`
- `resources/js/components/common/SimpleEntityViewModal.tsx`
- helper kolom/action/select shared yang dipakai tabel simple CRUD
- request/resource/export helper backend simple CRUD yang sudah ada di family request dan export

### Exit Criteria

- Page simple CRUD tidak lagi menyimpan custom shell yang bisa direduksi ke factory shared.
- Search placeholder, kolom select, action column, form, dan view modal konsisten antar sibling.
- Request/resource/export pair simple CRUD tidak lagi berbeda hanya karena boilerplate.

### Prompt Eksekusi Tahap 1

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 1: konvergensi CRUD Simple.

Batas wave:
- Maksimum 3 modul atau 8 file produksi.
- Jangan campur modul simple CRUD dengan complex/non-CRUD dalam wave yang sama.

Target wave ini:
- Samakan modul ke pola createSimpleEntityConfig() + SimpleEntityForm + SimpleEntityViewModal.
- Rapikan kolom select/actions/search/filter agar mengikuti helper shared yang sama.
- Rapikan request/resource/export/helper backend simple CRUD bila masih menyimpan boilerplate antar sibling.
- Jangan ubah route, endpoint, payload, query param, permission, atau data-testid.

Langkah wajib:
1. Mulai dari anchor shared, lalu turunkan ke sibling module wave ini.
2. Jika perlu perubahan struktural file, jalankan Depwire file context + impact analysis + simulate change sebelum edit.
3. Jika keputusan dipengaruhi behavior library, pakai Context7 dulu.
4. Setelah edit pertama, langsung jalankan validasi terfokus sebelum patch lain.

Validasi:
- ./vendor/bin/sail npm run types
- ./vendor/bin/sail test --group {module-slug}
- Jika frontend/E2E touched: ./vendor/bin/sail npm run test:e2e -- tests/e2e/{module-slug}/

Handoff:
- Update task.md dengan modul yang sudah konvergen, validasi yang lulus, dan simple CRUD outlier yang tersisa.
```

### Hasil Tahap 1 — Konvergensi CRUD Simple

Tanggal: 2026-04-30

#### Audit Summary

**Frontend (Wave 1A + 1B)**: Semua 5 modul sudah 100% konform.
- Setiap page file hanya 6 baris: `createEntityCrudPage(xxxConfig)`.
- Semua config menggunakan `createSimpleEntityConfig()` dari `entityConfigs.ts`.
- Form: `SimpleEntityForm` (shared).
- View: `SimpleEntityViewModal` (shared, bound via factory).
- Columns: `createSimpleEntityColumns()` (shared).
- Filters: `createSimpleEntityFilterFields()` (shared).

**Backend (Wave 1A + 1B)**: Semua 5 modul sudah 100% konform setelah fix minor.
- Controller: Semua identik (80 lines), menggunakan `Index{Plural}Action` + `Export{Plural}Action`.
- Requests: Semua extend shared base (`SimpleCrudStoreRequest`, `SimpleCrudUpdateRequest`, `SimpleCrudIndexRequest`, `SimpleCrudExportRequest`) — body kosong.
- Resources: Semua extend shared base (`SimpleCrudResource`, `SimpleCrudCollection`) — body kosong.
- Exports: Semua extend `SimpleCrudExport` — hanya override `getModelClass()`.
- Models: Semua identik setelah fix docblock.

#### Fix yang Diterapkan

| # | File | Perubahan | Alasan |
|---|------|-----------|--------|
| 1 | `app/Models/CustomerCategory.php` | Tambah `@var list<string>` docblock di atas `$fillable` | Konsistensi dengan Department/Position/Branch |
| 2 | `app/Models/SupplierCategory.php` | Tambah `@var list<string>` docblock di atas `$fillable` | Konsistensi dengan Department/Position/Branch |

#### Validasi

- `./vendor/bin/sail npm run types` → clean (no errors)
- `./vendor/bin/sail test --group departments` → 28 passed
- `./vendor/bin/sail test --group positions` → 27 passed
- `./vendor/bin/sail test --group branches` → 26 passed
- `./vendor/bin/sail test --group customer-categories` → 26 passed
- `./vendor/bin/sail test --group supplier-categories` → 26 passed
- `./vendor/bin/sail php ./vendor/bin/phpstan analyse app/Models/CustomerCategory.php app/Models/SupplierCategory.php` → No errors
- `git diff --check` → clean

#### Kesimpulan Tahap 1

Semua 5 modul simple CRUD sudah **sepenuhnya konvergen**. Tidak ada outlier tersisa di bucket simple CRUD. Perbedaan antar modul hanya tinggal metadata domain (nama entitas, placeholder, permission) yang memang harus berbeda.

**Wave 1C (opsional)**: Downgrade `product-categories`, `units`, `asset-categories` dari `createComplexEntityConfig` ke extended simple config masih bisa dilakukan di tahap berikutnya jika `createSimpleEntityConfig` diperluas untuk menerima extra columns. Ini memerlukan Depwire karena blast radius ke semua 5 simple CRUD consumer.

## Tahap 2. Konvergensi CRUD Complex Master dan Detail Dasar

### Outcome

- Modul complex CRUD non-transaction mengikuti kontrak file dan helper yang sama.
- Variasi hanya tinggal field bisnis, filter khusus, dan view detail yang memang domain-specific.

### Wave Prioritas

- Wave 2A: `warehouses`, `customers`, `suppliers`
- Wave 2B: `employees`, `products`, `fiscal-years`, `coa-versions`
- Wave 2C: `account-mappings`, `journal-entries`, `asset-categories`, `asset-models`, `asset-locations`, `approval-delegations`, `pipelines`

### Anchor

- `resources/js/utils/entityConfigs.ts`
- `resources/js/utils/schemas.ts`
- `resources/js/utils/errorHandling.ts`
- `resources/js/components/common/ViewModalItemsTable.tsx`
- `resources/js/components/common` helper untuk async select, columns, modal detail, dan error mapping

### Exit Criteria

- Setiap modul complex dasar punya struktur sibling file yang seragam dan tidak lagi menyimpan boilerplate `Columns/Filters/Form/ViewModal` yang bisa diangkat ke helper shared.
- Family request, filter service, action, dan resource backend sudah konsisten per cohort.
- `createComplexEntityConfig()` menjadi titik masuk utama, bukan fallback terakhir.

### Prompt Eksekusi Tahap 2

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 2: konvergensi CRUD Complex master dan detail dasar.

Batas wave:
- Maksimum 2 sampai 3 modul yang benar-benar sekeluarga.
- Jangan campur modul transaction/nested-item ke wave ini.

Target wave ini:
- Pastikan modul memakai createComplexEntityConfig() dan struktur file sibling yang konsisten.
- Reuse helper shared untuk schema, async select, error handling, dan modal/view detail bila perilakunya sama.
- Rapikan pair request/filter service/resource/action backend yang masih berbeda hanya karena boilerplate.
- Jangan ubah route, payload, query param, atau data-testid.

Langkah wajib:
1. Mulai dari helper shared yang mengontrol behavior.
2. Jika perlu rename/move/split/merge, jalankan Depwire file context + impact analysis + simulate change dulu.
3. Jika behavior React, React Query, react-router-dom, zod, atau library lain perlu dipastikan, pakai Context7 sebelum edit.
4. Setelah edit pertama, langsung jalankan validasi terfokus.

Validasi:
- ./vendor/bin/sail npm run types
- ./vendor/bin/sail test --group {module-slug}
- Jika frontend/E2E touched: ./vendor/bin/sail npm run test:e2e -- tests/e2e/{module-slug}/

Handoff:
- Update task.md dengan cohort complex CRUD yang sudah seragam dan outlier yang masih perlu helper baru.
```

### Hasil Tahap 2 Wave 2A — Konvergensi CRUD Complex (warehouses, customers, suppliers)

Tanggal: 2026-04-30

#### Audit Summary

**Frontend**: Semua 3 modul sudah konform.
- Page: `createEntityCrudPage(xxxConfig)` (6 lines untuk warehouses/customers, 26 lines untuk suppliers karena import dialog).
- Config: `createComplexEntityConfig()` dari `entityConfigs.ts`.
- Sibling files: konsisten 4-file structure (`Columns.tsx`, `Filters.tsx`, `Form.tsx`, `ViewModal.tsx`).
- Semua menggunakan shared helpers: `createSelectColumn`, `createActionsColumn`, `createTextColumn`, `createSortingHeader`, `AsyncSelectField`, `EntityForm`, `ViewModalShell`, `ViewField`.

**Frontend Variasi yang Disengaja (domain-specific)**:
- Customers/Suppliers punya lebih banyak kolom (email, phone, branch, category, status) vs. Warehouses (code, branch).
- Suppliers punya import dialog toolbar action.
- ViewModal layout berbeda: Warehouse (simple space-y), Customer (ViewField list), Supplier (2-col grid).
- Status badge variant: Customer `destructive` untuk inactive, Supplier `secondary` untuk inactive — minor style choice.

**Backend**: Ditemukan beberapa boilerplate inconsistencies yang sudah diperbaiki.

#### Fix yang Diterapkan

| # | File | Perubahan | Alasan |
|---|------|-----------|--------|
| 1 | `app/Models/Supplier.php` | Tambah `@use HasFactory<...>` annotation | Konsistensi dengan Warehouse/Customer |
| 2 | `app/Models/Supplier.php` | Tambah `@var list<string>` docblock di `$fillable` | Konsistensi dengan Warehouse/Customer |
| 3 | `app/Models/Supplier.php` | Tambah `@var array<string, string>` docblock di `$casts` | Konsistensi dengan Warehouse |
| 4 | `app/Models/Supplier.php` | Hapus redundant `'created_at' => 'datetime'` dan `'updated_at' => 'datetime'` casts | Eloquent sudah cast ini secara default |
| 5 | `app/Models/Customer.php` | Tambah `$casts` property (`branch_id`, `category_id` → integer) | Konsistensi dengan Warehouse/Supplier |
| 6 | `app/Exports/SupplierExport.php` | Tambah `ShouldAutoSize` interface | Konsistensi dengan Warehouse/Customer exports |
| 7 | `app/Exports/SupplierExport.php` | Tambah `'category'` ke eager load | Fix N+1 query bug (columns references category relation) |
| 8 | `app/Http/Controllers/SupplierController.php` | Ganti `destroy()` dari `response()->noContent()` ke `$this->destroyModel($supplier)` | Konsistensi dengan base Controller pattern |
| 9 | `app/Http/Controllers/SupplierController.php` | Tambah `$supplier->load(['branch', 'category'])` di `show()` | Konsistensi dengan Customer (yang sudah load relations) |
| 10 | `app/Http/Controllers/CustomerController.php` | Ganti `destroy()` dari manual delete ke `$this->destroyModel($customer)` | Konsistensi dengan base Controller pattern |
| 11 | `app/Http/Controllers/CustomerController.php` | Tambah `'category'` ke `show()` relation load | Fix: sebelumnya hanya load `'branch'` |

#### Outlier yang Dicatat (Tidak Diperbaiki di Wave Ini)

Berikut adalah perbedaan arsitektural yang lebih dalam dan memerlukan analisis lebih lanjut atau Depwire sebelum diubah:

| # | Modul | Masalah | Alasan Ditunda |
|---|-------|---------|----------------|
| 1 | Warehouse | Index Action pakai `handleSearchOrPrimaryIndexRequest` (search XOR filter) vs Customer/Supplier pakai `handleIndexRequest` (search AND filter) | Behavioral difference — perlu validasi apakah ini intentional |
| 2 | Warehouse | Store/Update request pakai trait pattern (`HasWarehouseRules`) vs Customer/Supplier pakai abstract class pattern (`usesSometimes()`) | Structural divergence — perlu Depwire untuk unify |
| 3 | Warehouse | Resource/Collection extend `SimpleCrud*` base vs Customer/Supplier extend raw Laravel classes + `BuildsPartyResourceData` trait | Structural divergence — perlu Depwire |
| 4 | Warehouse | Export pakai manual inline pattern vs Customer/Supplier pakai `columns()` + helper methods | Structural divergence — perlu Depwire |
| 5 | Customer | `update()` pakai DTO (`UpdateCustomerData`) vs Warehouse/Supplier pakai `$request->validated()` langsung | Architectural choice — perlu keputusan apakah semua harus pakai DTO |
| 6 | Supplier | FilterService punya monolithic `apply()` method yang mungkin dead code | Perlu investigasi apakah method ini dipanggil dari tempat lain |
| 7 | Warehouse | Satu-satunya yang pakai `LoadsResourceRelations` trait di controller | Structural divergence — perlu Depwire untuk unify |

#### Validasi

- `./vendor/bin/sail php ./vendor/bin/phpstan analyse` (5 files) → No errors
- `./vendor/bin/sail test --group=warehouses` → 35 passed (187 assertions)
- `./vendor/bin/sail test --group=customers` → 54 passed (354 assertions)
- `./vendor/bin/sail test --group=suppliers` → 45 passed (146 assertions)
- `./vendor/bin/sail npm run types` → clean (no errors)
- `git diff --check` → clean

#### Kesimpulan Wave 2A

Boilerplate inconsistencies yang aman diperbaiki sudah ditangani (5 production files, 11 perubahan). Perbedaan arsitektural yang lebih dalam (7 items) dicatat sebagai outlier untuk wave berikutnya yang memerlukan Depwire.

**Rekomendasi Wave 2B**: Audit `employees`, `products`, `fiscal-years`, `coa-versions` — fokus pada konsistensi backend request/resource/filter pattern yang sama.

### Hasil Tahap 2 Wave 2B — Konvergensi CRUD Complex (employees, products, fiscal-years, coa-versions)

Tanggal: 2026-04-30

#### Audit Summary

**Frontend**: Semua 4 modul sudah konform.
- `fiscal-years`, `coa-versions`: standard 6-line `createEntityCrudPage(xxxConfig)`.
- `employees`: 30 lines (import dialog toolbar action — intentional domain feature).
- `products`: 10 lines (wraps in function component — minor style variant, no behavioral difference).

**Backend**: Ditemukan boilerplate inconsistencies yang sudah diperbaiki.

#### Fix yang Diterapkan

| # | File | Perubahan | Alasan |
|---|------|-----------|--------|
| 1 | `app/Http/Controllers/EmployeeController.php` | `destroy()` → `$this->destroyModel($employee)` | Konsistensi dengan base Controller pattern |
| 2 | `app/Http/Controllers/ProductController.php` | `destroy()` → `$this->destroyModel($product)` | Konsistensi dengan base Controller pattern |
| 3 | `app/Http/Controllers/FiscalYearController.php` | `destroy()` → `$this->destroyModel($fiscalYear)` | Konsistensi dengan base Controller pattern |
| 4 | `app/Http/Controllers/CoaVersionController.php` | `destroy()` → `$this->destroyModel($coaVersion)` | Konsistensi dengan base Controller pattern |
| 5 | `app/Models/FiscalYear.php` | +`@use HasFactory<...>`, +`@var list<string>`, +`@var array<string, string>` | Konsistensi dengan reference pattern |
| 6 | `app/Models/CoaVersion.php` | +`@use HasFactory<...>`, +`@var list<string>`, import relation types (replace inline FQCN) | Konsistensi dengan reference pattern |
| 7 | `app/Models/Product.php` | +`@use HasFactory<...>` annotation | Konsistensi dengan reference pattern |
| 8 | `app/Exports/EmployeeExport.php` | `protected $filters` → `protected array $filters` | Konsistensi: typed property |

#### Outlier yang Dicatat (Tidak Diperbaiki di Wave Ini)

| # | Modul | Masalah | Alasan Ditunda |
|---|-------|---------|----------------|
| 1 | Employee, Product | Controller `show()` tidak load relations | Perlu cek apakah Resource sudah handle lazy loading |
| 2 | Employee, Product | Controller uses manual `new Action(app(...))` instead of method injection | Structural — perlu Depwire untuk unify |
| 3 | Product | `IndexProductRequest` defines pagination rules inline | Structural — perlu cek base class compatibility |
| 4 | Product | `ProductCollection` overrides `toArray()` differently from siblings | Structural — perlu cek apakah ini intentional |
| 5 | Employee | Export uses manual date formatting instead of `$this->formatIso8601()` | Behavioral — date format change could break exports |
| 6 | FiscalYear, CoaVersion | Resources extend `SimpleCrudResource` (different from Employee/Product which extend `JsonResource`) | Architectural split — intentional |

#### Validasi

- `./vendor/bin/sail php ./vendor/bin/phpstan analyse` (8 files) → No errors
- `./vendor/bin/sail test --group=employees` → 122 passed (637 assertions)
- `./vendor/bin/sail test --group=products` → 54 passed (251 assertions)
- `./vendor/bin/sail test --group=fiscal-years` → 34 passed (214 assertions)
- `./vendor/bin/sail test --group=coa-versions` → 48 passed (281 assertions)
- `./vendor/bin/sail npm run types` → clean (no errors)
- `git diff --check` → clean

#### Kesimpulan Wave 2B

Boilerplate inconsistencies yang aman diperbaiki sudah ditangani (8 production files). Perbedaan arsitektural yang lebih dalam (6 items) dicatat sebagai outlier. Total Pest tests passed across Wave 2B: **258 passed (1383 assertions)**.

**Rekomendasi Wave 2C**: Audit `account-mappings`, `journal-entries`, `asset-models`, `asset-locations`, `approval-delegations`, `pipelines` — fokus pada konsistensi backend yang sama.

### Hasil Tahap 2 Wave 2C — Konvergensi CRUD Complex (account-mappings, journal-entries, asset-models, asset-locations, approval-delegations, pipelines)

Tanggal: 2026-04-30

#### Audit Summary

**Frontend**: Semua 6 modul sudah konform (4-6 line `createEntityCrudPage` pattern).

**Backend**: Ditemukan boilerplate inconsistencies yang sudah diperbaiki.

#### Fix yang Diterapkan

| # | File | Perubahan | Alasan |
|---|------|-----------|--------|
| 1 | `app/Http/Controllers/AccountMappingController.php` | `destroy()` → `$this->destroyModel()` | Konsistensi dengan base Controller pattern |
| 2 | `app/Http/Controllers/AssetModelController.php` | `destroy()` → `$this->destroyModel()` | Konsistensi dengan base Controller pattern |
| 3 | `app/Http/Controllers/ApprovalDelegationController.php` | `destroy()` → `$this->destroyModel()` | Konsistensi dengan base Controller pattern |
| 4 | `app/Http/Controllers/PipelineController.php` | `destroy()` → `$this->destroyModel()` | Konsistensi dengan base Controller pattern |
| 5 | `app/Models/AccountMapping.php` | +`@var list<string>` docblock di `$fillable` | Konsistensi dengan reference pattern |
| 6 | `app/Models/JournalEntry.php` | +`@use HasFactory<...>`, +`@var list<string>`, +`@var array<string, string>` | Konsistensi dengan reference pattern |
| 7 | `app/Models/Pipeline.php` | +`@use HasFactory<...>`, +`@var list<string>`, +`@var array<string, string>`, hapus redundant `created_at`/`updated_at` casts | Konsistensi + remove redundancy |

#### Modul yang Sudah Konform Tanpa Perubahan

- `asset-locations`: Controller sudah pakai `$this->destroyModel()`, `show()` sudah load relations.

#### Outlier yang Dicatat (Tidak Diperbaiki di Wave Ini)

| # | Modul | Masalah | Alasan Ditunda |
|---|-------|---------|----------------|
| 1 | journal-entries | `destroy()` punya domain logic (guard posted + cascade delete lines) — tidak bisa pakai `destroyModel()` | Intentional domain behavior |
| 2 | account-mappings | Export (via SimpleCrudExport) tidak eager-load `sourceAccount.coaVersion`, `targetAccount.coaVersion` | Perlu cek apakah Export Action sudah handle ini |
| 3 | journal-entries | Export tidak eager-load `lines` (used in `map()` for sum) | N+1 potential — perlu cek volume impact |
| 4 | asset-models, asset-locations, pipelines | Export `$filters` untyped (in standalone export classes) | Low priority — cosmetic |
| 5 | asset-models, approval-delegations, pipelines | Model missing `@use HasFactory` (no factory file exists or factory name differs) | Perlu cek factory existence first |
| 6 | approval-delegations, pipelines | Model has redundant `created_at`/`updated_at` casts | Pipeline fixed; approval-delegations deferred (needs separate read) |

#### Validasi

- `./vendor/bin/sail php ./vendor/bin/phpstan analyse` (7 files) → No errors
- `./vendor/bin/sail test --group=account-mappings` → 27 passed (109 assertions)
- `./vendor/bin/sail test --group=journal-entries` → 29 passed (194 assertions)
- `./vendor/bin/sail test --group=asset-models` → 34 passed (106 assertions)
- `./vendor/bin/sail test --group=asset-locations` → 39 passed (109 assertions)
- `./vendor/bin/sail test --group=approval-delegations` → 34 passed (129 assertions)
- `./vendor/bin/sail test --group=pipelines` → 42 passed (125 assertions)
- `./vendor/bin/sail npm run types` → clean
- `git diff --check` → clean

#### Kesimpulan Wave 2C

Boilerplate inconsistencies yang aman diperbaiki sudah ditangani (7 production files). Total Pest tests passed across Wave 2C: **205 passed (772 assertions)**.

#### Kesimpulan Keseluruhan Tahap 2

Tahap 2 selesai (Wave 2A + 2B + 2C). Total:
- **13 modul complex CRUD** diaudit: warehouses, customers, suppliers, employees, products, fiscal-years, coa-versions, account-mappings, journal-entries, asset-models, asset-locations, approval-delegations, pipelines.
- **20 production files** diperbaiki di Tahap 2.
- **597 Pest tests passed** (total across all 3 waves).
- **13+ architectural outliers** dicatat untuk future Depwire-assisted unification.

**Rekomendasi selanjutnya**: Tahap 3 (transaction/nested form modules) atau langsung ke Tahap 4 (report shell consistency) tergantung prioritas.

## Tahap 3. Konvergensi CRUD Complex Transaction dan Nested Form

### Outcome

- Modul complex yang punya item dialog, nested rows, atau dynamic array berbagi helper form yang sama.
- Payload transaction dan nested resource tetap stabil, tetapi boilerplate UI dan request validation turun drastis.

### Wave Prioritas

- Wave 3A: `purchase-requests`, `purchase-orders`, `goods-receipts`, `supplier-returns`
- Wave 3B: `stock-transfers`, `inventory-stocktakes`, `stock-adjustments`
- Wave 3C: `assets`, `asset-movements`, `asset-maintenances`, `asset-stocktakes`, `approval-flows`

### Anchor

- `resources/js/components/common/ItemFormDialog.tsx`
- `resources/js/components/common/ViewModalItemsTable.tsx`
- `resources/js/utils/schemas.ts`
- helper modal detail dan section header/item action shared di `resources/js/components/common`
- action/resource/request backend untuk item collection dan nested payload

### Exit Criteria

- Boilerplate item dialog, item table, modal detail, dan nested schema sudah dipindah ke helper shared bila contract-nya sejenis.
- Tidak ada lagi clone block besar antar transaction form sibling hanya untuk field item, empty row, action buttons, atau error mapping.
- Nested payload dan export/import behavior tetap sama.

### Prompt Eksekusi Tahap 3

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 3: konvergensi CRUD Complex transaction dan nested form.

Batas wave:
- Maksimum 2 modul transaction sekeluarga atau 1 family helper shared dalam satu wave.
- Jangan campur report/read-only page ke wave ini.

Target wave ini:
- Reuse helper item dialog, item table, modal detail, schema shared, dan error handling shared untuk modul transaction/nested form.
- Rapikan request/resource/action backend yang mengelola item collection bila masih menyimpan mapping/validation boilerplate antar sibling.
- Pertahankan shape payload nested, upload/import/export flow, dan data-testid.

Langkah wajib:
1. Mulai dari helper shared atau satu transaction family yang paling serupa.
2. Jika ada perubahan struktural file, jalankan Depwire lebih dulu.
3. Jika ada behavior library yang belum pasti, pakai Context7.
4. Setelah edit pertama, jalankan validasi yang sama sebelum patch tambahan.

Validasi:
- ./vendor/bin/sail npm run types
- ./vendor/bin/sail test --group {module-slug}
- Jika frontend/E2E touched: ./vendor/bin/sail npm run test:e2e -- tests/e2e/{module-slug}/

Handoff:
- Update task.md dengan family transaction yang sudah dipusatkan ke helper shared dan nested outlier yang masih tersisa.
```

### Hasil Tahap 3 Wave 3A — Konvergensi CRUD Complex Transaction (purchase-requests, purchase-orders, goods-receipts, supplier-returns)

Tanggal: 2026-04-30

#### Audit Summary

**Frontend**: Semua 4 modul sudah konform.
- Semua pakai `createComplexEntityConfig` + `createEntityCrudPage` + `StoresItemsInTransaction` trait.
- Shared helpers digunakan: `ItemFormDialog`, `ViewModalItemsTable`, `ItemProductUnitFields`, `ItemNotesField`.
- Form pattern konsisten: extract items → create parent → assign doc number → sync items.

**Backend**: Semua 4 modul menggunakan pattern yang konsisten:
- Controller: `StoresItemsInTransaction` trait dengan `storeWithSyncedItems()` / `updateWithSyncedItems()`.
- PurchaseOrder + GoodsReceipt: `LoadsResourceRelations` trait (PO) atau inline load (GR).
- Actions: `SyncXxxItemsAction` per module.
- DTOs: `UpdateXxxData` per module.

#### Fix yang Diterapkan

| # | File | Perubahan | Alasan |
|---|------|-----------|--------|
| 1 | `app/Http/Controllers/PurchaseOrderController.php` | `destroy()` → `$this->destroyModel()` | Konsistensi |
| 2 | `app/Http/Controllers/GoodsReceiptController.php` | `destroy()` → `$this->destroyModel()` | Konsistensi |
| 3 | `app/Http/Controllers/PurchaseRequestController.php` | `destroy()` → `$this->destroyModel()` | Konsistensi |
| 4 | `app/Http/Controllers/SupplierReturnController.php` | `destroy()` → `$this->destroyModel()` | Konsistensi |
| 5 | `app/Models/PurchaseOrder.php` | +`@use HasFactory`, +`@var list<string>`, hapus redundant `created_at`/`updated_at` dari `datetimeCasts()` | Konsistensi + remove redundancy |
| 6 | `app/Models/GoodsReceipt.php` | +`@use HasFactory`, +`@var list<string>`, +`@var array<string, string>`, hapus redundant `created_at`/`updated_at` | Konsistensi + remove redundancy |
| 7 | `app/Models/PurchaseRequest.php` | +`@use HasFactory`, +`@var list<string>`, +`@var array<string, string>`, hapus redundant `created_at`/`updated_at` | Konsistensi + remove redundancy |
| 8 | `app/Models/SupplierReturn.php` | +`@use HasFactory`, +`@var list<string>`, +`@var array<string, string>`, hapus redundant `created_at`/`updated_at` | Konsistensi + remove redundancy |

#### Outlier yang Dicatat (Tidak Diperbaiki di Wave Ini)

| # | Modul | Masalah | Alasan Ditunda |
|---|-------|---------|----------------|
| 1 | goods-receipts | Controller tidak pakai `LoadsResourceRelations` trait — inline load di 3 tempat (store/show/update) | Structural — perlu Depwire untuk extract ke trait |
| 2 | goods-receipts | Export pakai manual `headings()`/`map()` bukan `columns()` pattern | Structural — perlu Depwire |
| 3 | goods-receipts | Model missing class-level `@property` docblock | Low priority — cosmetic |
| 4 | All 4 | Export `$filters` pakai `private readonly` constructor promotion bukan `protected array` | Stylistic — `private readonly` arguably stricter |

#### Validasi

- `./vendor/bin/sail php ./vendor/bin/phpstan analyse` (8 files) → No errors
- `./vendor/bin/sail test --group=purchase-orders` → 17 passed (71 assertions)
- `./vendor/bin/sail test --group=goods-receipts` → 22 passed (65 assertions)
- `./vendor/bin/sail test --group=purchase-requests` → 19 passed (79 assertions)
- `./vendor/bin/sail test --group=supplier-returns` → 19 passed (59 assertions)
- `./vendor/bin/sail npm run types` → clean
- `git diff --check` → clean

#### Kesimpulan Wave 3A

Purchasing family (4 modules) sudah konvergen pada level boilerplate. Total Pest tests passed: **77 passed (274 assertions)**. Shared transaction helpers (`StoresItemsInTransaction`, `ItemFormDialog`, `ViewModalItemsTable`) sudah digunakan secara konsisten. Perbedaan yang tersisa adalah structural (GR inline load vs trait) dan stylistic (export column pattern).

**Rekomendasi Wave 3B**: Audit `stock-transfers`, `inventory-stocktakes`, `stock-adjustments` — inventory transaction family.

### Hasil Tahap 3 Wave 3B — Konvergensi CRUD Complex Transaction (stock-transfers, inventory-stocktakes, stock-adjustments)

Tanggal: 2026-04-30

#### Audit Summary

**Frontend**: Semua 3 modul sudah konform.
- Semua pakai `createComplexEntityConfig` + `createEntityCrudPage` + `StoresItemsInTransaction` trait.
- Shared helpers digunakan: `ItemFormDialog`, `ViewModalItemsTable`.

**Backend**: Models sudah punya `@use HasFactory`, `@var list<string>`, `@var array<string, string>` — hanya redundant casts dan missing `ShouldAutoSize` yang perlu diperbaiki.

**Controller `destroy()` pattern**: Ketiga modul menggunakan **soft-cancel** (`update status → cancelled`) bukan hard delete — ini intentional domain behavior untuk inventory transactions dan TIDAK diubah ke `destroyModel()`.

#### Fix yang Diterapkan

| # | File | Perubahan | Alasan |
|---|------|-----------|--------|
| 1 | `app/Models/StockTransfer.php` | Hapus redundant `'created_at' => 'datetime'`, `'updated_at' => 'datetime'` | Eloquent auto-casts timestamps |
| 2 | `app/Models/InventoryStocktake.php` | Hapus redundant `'created_at' => 'datetime'`, `'updated_at' => 'datetime'` | Eloquent auto-casts timestamps |
| 3 | `app/Models/StockAdjustment.php` | Hapus redundant `'created_at' => 'datetime'`, `'updated_at' => 'datetime'` | Eloquent auto-casts timestamps |
| 4 | `app/Exports/StockTransferExport.php` | Tambah `ShouldAutoSize` interface | Konsistensi dengan sibling exports |
| 5 | `app/Exports/InventoryStocktakeExport.php` | Tambah `ShouldAutoSize` interface | Konsistensi dengan sibling exports |
| 6 | `app/Exports/StockAdjustmentExport.php` | Tambah `ShouldAutoSize` interface | Konsistensi dengan sibling exports |

#### Intentional Domain Differences (Tidak Diubah)

- Controller `destroy()` menggunakan soft-cancel (`update status → cancelled`) bukan hard delete — ini benar untuk inventory transactions yang tidak boleh dihapus permanen.

#### Validasi

- `./vendor/bin/sail php ./vendor/bin/phpstan analyse` (6 files) → No errors
- `./vendor/bin/sail test --group=stock-transfers` → 35 passed (150 assertions)
- `./vendor/bin/sail test --group=inventory-stocktakes` → 35 passed (153 assertions)
- `./vendor/bin/sail test --group=stock-adjustments` → 35 passed (166 assertions)
- `./vendor/bin/sail npm run types` → clean
- `git diff --check` → clean

#### Kesimpulan Wave 3B

Inventory transaction family (3 modules) sudah konvergen. Total Pest tests passed: **105 passed (469 assertions)**. Models sudah punya docblocks yang benar, exports sekarang konsisten dengan `ShouldAutoSize`.

**Rekomendasi Wave 3C**: Audit `assets`, `asset-movements`, `asset-maintenances`, `asset-stocktakes`, `approval-flows` — asset/workflow nested form family.

### Hasil Tahap 3 Wave 3C — Konvergensi CRUD Complex Transaction (assets, asset-movements, asset-maintenances, asset-stocktakes, approval-flows)

Tanggal: 2026-04-30

#### Audit Summary

**Frontend**: Semua 5 modul sudah konform (`createComplexEntityConfig` + `createEntityCrudPage`).

**Backend**: Models dan exports sudah cukup konsisten. Fixes fokus pada docblocks, redundant casts, dan FQCN imports.

**Controller `destroy()` pattern**:
- `asset-stocktakes`, `approval-flows`: standard `json(null, 204)` → diubah ke `destroyModel()`.
- `assets`, `asset-maintenances`: return custom message `{'message': '...'}` → intentional, tidak diubah (would change API contract).
- `asset-movements`: domain logic (DB transaction + revert asset location) → intentional, tidak diubah.

#### Fix yang Diterapkan

| # | File | Perubahan | Alasan |
|---|------|-----------|--------|
| 1 | `app/Http/Controllers/AssetStocktakeController.php` | `destroy()` → `$this->destroyModel()` | Konsistensi |
| 2 | `app/Http/Controllers/ApprovalFlowController.php` | `destroy()` → `$this->destroyModel()` | Konsistensi |
| 3 | `app/Models/ApprovalFlow.php` | +`@var list<string>`, +`@var array<string, string>`, hapus redundant `created_at`/`updated_at`, import `BelongsTo`/`HasMany` (replace FQCN) | Konsistensi + remove redundancy |
| 4 | `app/Models/AssetStocktake.php` | +`@use HasFactory<...>`, +`@var list<string>`, +`@var array<string, string>` | Konsistensi |
| 5 | `app/Models/AssetMovement.php` | +`@use HasFactory<...>`, +`@var list<string>`, +`@var array<string, string>` | Konsistensi |

#### Intentional Domain Differences (Tidak Diubah)

- `AssetController::destroy()` — returns custom message `{'message': 'Asset deleted successfully'}` (different API contract)
- `AssetMaintenanceController::destroy()` — returns custom message `{'message': 'Asset maintenance deleted successfully'}`
- `AssetMovementController::destroy()` — DB transaction with asset location revert logic

#### Validasi

- `./vendor/bin/sail php ./vendor/bin/phpstan analyse` (5 files) → No errors
- `./vendor/bin/sail test --group=assets` → 42 passed (148 assertions)
- `./vendor/bin/sail test --group=asset-movements` → 30 passed (89 assertions)
- `./vendor/bin/sail test --group=asset-maintenances` → 33 passed (92 assertions)
- `./vendor/bin/sail test --group=asset-stocktakes` → 43 passed (118 assertions)
- `./vendor/bin/sail test --group=approval-flows` → 46 passed (240 assertions)
- `./vendor/bin/sail npm run types` → clean
- `git diff --check` → clean

#### Kesimpulan Keseluruhan Tahap 3

Tahap 3 selesai (Wave 3A + 3B + 3C). Total:
- **12 modul transaction/nested form** diaudit: purchase-requests, purchase-orders, goods-receipts, supplier-returns, stock-transfers, inventory-stocktakes, stock-adjustments, assets, asset-movements, asset-maintenances, asset-stocktakes, approval-flows.
- **19 production files** diperbaiki di Tahap 3.
- **376 Pest tests passed** (total across all 3 waves).
- Shared transaction helpers (`StoresItemsInTransaction`, `ItemFormDialog`, `ViewModalItemsTable`) sudah digunakan secara konsisten di semua purchasing dan inventory modules.

**Rekomendasi selanjutnya**: Tahap 4 (report/read-only shell consistency) atau Tahap 5 (workflow/dashboard/settings).

## Tahap 4. Konvergensi Non-CRUD Report dan Read-Only Table

### Outcome

- Halaman report/read-only memakai shell, filter, kolom, dan export flow yang konsisten.
- Perbedaan antar report tinggal query business logic dan presentasi domain-specific yang memang harus berbeda.

### Wave Prioritas

- Wave 4A: `stock-movements`, `stock-monitor`, `inventory-valuation-report`, `stock-movement-report`, `stock-adjustment-report`
- Wave 4B: `purchase-order-status-report`, `purchase-history-report`, `goods-receipt-report`
- Wave 4C: `asset-reports/register`, `book-value-depreciation-reports`, `maintenance-cost-reports`, `asset-stocktake-variances`, `pipeline-audit-trail`, `approval-audit-trail`
- Wave 4D: financial report pages (`balance-sheet`, `comparative`, `income-statement`, `cash-flow`, `trial-balance`) bila masih ada drift terhadap shell shared

### Anchor

- `resources/js/components/common/ReportDataTablePage.tsx`
- `resources/js/components/common/ReportColumns.tsx`
- `resources/js/components/reports/financial/FinancialReportPageShell.tsx`
- helper filter report shared di `resources/js/components/common` atau `resources/js/components/reports`
- request/resource/export/action backend family report

### Exit Criteria

- Report table wrapper tipis memakai `ReportDataTablePage` bila pola interaction-nya sama.
- Financial pages yang sejenis memakai `FinancialReportPageShell` atau helper shared terkait.
- Kolom/filter/export/report resource tidak lagi menyimpan clone block yang seharusnya menjadi helper shared.

### Prompt Eksekusi Tahap 4

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 4: konvergensi Non-CRUD report dan read-only table.

Batas wave:
- Maksimum 2 sampai 3 report sibling yang memakai shell interaksi yang sama.
- Jangan campur dashboard/settings/workflow page ke wave ini.

Target wave ini:
- Samakan page wrapper ke ReportDataTablePage atau FinancialReportPageShell bila cocok.
- Reuse helper report columns, filters, export flow, dan resource/request pair backend bila kontraknya sejenis.
- Pertahankan endpoint, query param filter, response key, dan route report yang ada.

Langkah wajib:
1. Mulai dari shell report shared, baru turunkan ke sibling report.
2. Jika ada perubahan struktural file, jalankan Depwire terlebih dahulu.
3. Jika behavior package/report helper perlu dipastikan, pakai Context7 sebelum edit.
4. Jalankan validasi terfokus segera setelah edit pertama.

Validasi:
- ./vendor/bin/sail npm run types
- ./vendor/bin/sail test --group reports
- Jika frontend/E2E touched: ./vendor/bin/sail npm run test:e2e -- tests/e2e/{module-slug}/

Handoff:
- Update task.md dengan report family yang sudah seragam dan report outlier yang tetap butuh shell khusus.
```

### Hasil Tahap 4 — Konvergensi Non-CRUD Report dan Read-Only Table

Tanggal: 2026-04-30

#### Audit Summary

**Frontend (Wave 4A–4D)**:
- 11 report pages menggunakan `ReportDataTablePage` → ✅ semua konform.
- 5 financial report pages menggunakan `FinancialReportPageShell` / `SingleYearFinancialReportPageShell` → ✅ semua konform.
- 2 audit trail pages menggunakan `AuditTrailPage` → ✅ semua konform.
- 2 outlier pages (`stock-movements`, `stock-monitor`) masih menggunakan `DataTablePage` → **wajib Depwire** untuk migrasi ke `ReportDataTablePage`.

**Backend (semua report controllers + exports)**:
- 9 report controllers: **100% konsisten** — semua extend `Controller`, pakai Action pattern (Index + Export) via method DI, return typed responses.
- 9 report exports: **100% konsisten** — semua implement `ShouldAutoSize` (via base class), `$filters` typed, proper eager loading.
- 2 base export classes digunakan: `AbstractReportIndexExport` (6 pure reports) dan `AbstractActionCollectionExport` (3 collection-based reports).
- Tidak ada boilerplate fix yang diperlukan — backend report family sudah seragam.

#### Tidak Ada Fix yang Diterapkan

Tahap 4 tidak memerlukan perubahan kode produksi. Semua report pages dan backend sudah mengikuti shared shell dan pattern yang konsisten.

#### Outlier yang Tersisa (Wajib Depwire)

| # | Modul | Masalah | Tindakan |
|---|-------|---------|----------|
| 1 | `stock-movements` | Frontend pakai `DataTablePage` bukan `ReportDataTablePage`; manual `useCrudFilters`/`useCrudQuery` | Migrasi ke `ReportDataTablePage` — wajib Depwire (cek dependents `DataTablePage`) |
| 2 | `stock-monitor` | Frontend pakai `DataTablePage` + custom summary cards; manual hooks | Migrasi ke `ReportDataTablePage` + summary slot — wajib Depwire |

#### Catatan Arsitektural

- `InventoryStocktakeVarianceExport` menggunakan `AbstractActionCollectionExport` (bukan `AbstractReportIndexExport` seperti 6 report lainnya) — ini intentional karena action returns non-paginated collection.
- `StockMonitorController` destructures action result (`$result['stocks']`, `$result['summary']`) — intentional karena endpoint returns summary + data.

#### Validasi

- `./vendor/bin/sail test --group=reports` → 13 passed (87 assertions)
- `./vendor/bin/sail test --group=stock-movements` → 14 passed (84 assertions)
- `./vendor/bin/sail test --group=stock-monitor` → 11 passed (70 assertions)
- `./vendor/bin/sail test --group=inventory-valuation-report` → 4 passed (45 assertions)
- `./vendor/bin/sail test --group=stock-movement-report` → 5 passed (47 assertions)
- `./vendor/bin/sail test --group=purchase-history-report` → 5 passed (47 assertions)
- `./vendor/bin/sail test --group=purchase-order-status-report` → 5 passed (30 assertions)
- `./vendor/bin/sail test --group=goods-receipt-report` → 5 passed (54 assertions)
- `./vendor/bin/sail npm run types` → clean
- `git diff --check` → clean (no production files modified)

#### Kesimpulan Tahap 4

Report family sudah **sepenuhnya konvergen** tanpa perlu perubahan kode. Total 62 Pest tests validated (464 assertions). Satu-satunya outlier yang tersisa (`stock-movements`, `stock-monitor`) memerlukan Depwire untuk structural migration dan dicatat untuk future wave.

## Tahap 5. Konvergensi Non-CRUD Workflow, Dashboard, dan Settings

### Outcome

- Halaman workflow/dashboard/settings memakai pola layout, loading, empty-state, error mapping, dan route registration yang konsisten.
- Embedded component tetap mempertahankan contract-nya, tetapi tidak lagi mengulang boilerplate page shell.

### Wave Prioritas

- Wave 5A: `admin-settings`, `my-approvals`, `approval-monitoring`
- Wave 5B: `pipeline-dashboard`, `asset-dashboard`, `asset-depreciation-runs`
- Wave 5C: `approval-history`, `entity-state-actions`, `entity-state-timeline`

### Anchor

- `resources/js/layouts/app-layout.tsx`
- `resources/js/app-routes.tsx`
- `resources/js/utils/errorHandling.ts`
- shared cards/filter/summary components di `resources/js/components/common`, `resources/js/components/approval-monitoring`, dan family dashboard terkait

### Exit Criteria

- Workflow/dashboard/settings pages tidak lagi punya boilerplate error handling, loading, atau layout registration yang berbeda tanpa alasan bisnis.
- Route lazy registration tetap konsisten di `app-routes.tsx`.
- Embedded approval/entity state components tetap kompatibel dengan caller yang ada.

### Prompt Eksekusi Tahap 5

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 5: konvergensi Non-CRUD workflow, dashboard, dan settings.

Batas wave:
- Maksimum 2 sampai 3 page/component sibling.
- Jangan campur report/read-only table ke wave ini.

Target wave ini:
- Samakan layout, loading, empty-state, error handling, dan route registration untuk page workflow/dashboard/settings.
- Reuse helper shared yang sudah ada di AppLayout, errorHandling, dan summary/filter component.
- Pertahankan permission gate, endpoint, payload, dan kontrak embedded component.

Langkah wajib:
1. Mulai dari shared layout/helper yang sudah mengontrol behavior.
2. Jika perlu perubahan struktural, jalankan Depwire file context + impact analysis + simulate change sebelum edit.
3. Jika behavior library perlu dikonfirmasi, pakai Context7.
4. Jalankan validasi terfokus segera setelah edit pertama.

Validasi:
- ./vendor/bin/sail npm run types
- ./vendor/bin/sail test --group {module-slug}
- Jika frontend/E2E touched: ./vendor/bin/sail npm run test:e2e -- tests/e2e/{module-slug}/

Handoff:
- Update task.md dengan page workflow/dashboard/settings yang sudah seragam dan exception yang masih disengaja.
```

### Hasil Tahap 5 — Konvergensi Non-CRUD Workflow, Dashboard, dan Settings

Tanggal: 2026-04-30

#### Audit Summary

**Frontend (Wave 5A–5C)**:
- `admin-settings`: punya `AdminSettingsLayout` sendiri → ✅ konform (intentional dedicated layout).
- `my-approvals`: custom tabs + approval dialog (406 lines) → intentional domain complexity, tidak perlu shared shell.
- `approval-monitoring`: `AppLayout` + custom summary cards + filter → intentional dashboard pattern.
- `pipeline-dashboard`: `AppLayout` + custom charts/cards → intentional dashboard pattern.
- `asset-dashboard`: `AppLayout` + custom charts/cards → intentional dashboard pattern.
- `asset-depreciation-runs`: `StandaloneTablePage` + custom calculate/post workflow → intentional batch workflow.
- `approval-history`, `entity-state-actions`, `entity-state-timeline`: embedded components → intentional, tidak perlu page shell.

**Kesimpulan Frontend**: Semua non-CRUD workflow/dashboard/settings pages sudah menggunakan `AppLayout` secara konsisten. Perbedaan antar page adalah domain-specific (dashboard charts, approval tabs, batch workflow) yang memang harus berbeda. Tidak ada shared "DashboardPageShell" yang bisa dibuat tanpa over-abstraction.

**Backend (Wave 5A–5C)**:
- 6 controllers ditemukan: semua pakai Action pattern (kecuali `MyApprovalController` yang pakai direct query — intentional).
- Tidak ada `destroy()` method di controller non-CRUD ini.
- 5 models ditemukan dengan missing docblocks → diperbaiki.

#### Fix yang Diterapkan

| # | File | Perubahan | Alasan |
|---|------|-----------|--------|
| 1 | `app/Models/AssetDepreciationRun.php` | +`@use HasFactory<...>`, +`@var list<string>`, +`@var array<string, string>` | Konsistensi |
| 2 | `app/Models/PipelineTransition.php` | +`@use HasFactory<...>`, +`@var list<string>`, +`@var array<string, string>` | Konsistensi |
| 3 | `app/Models/ApprovalRequest.php` | +`@var list<string>`, +`@var array<string, string>` | Konsistensi |
| 4 | `app/Models/ApprovalRequestStep.php` | +`@var list<string>`, +`@var array<string, string>` | Konsistensi |
| 5 | `app/Models/ApprovalAuditLog.php` | +`@var list<string>`, +`@var array<string, string>` | Konsistensi |

#### Intentional Domain Differences (Tidak Diubah)

| # | Modul | Alasan |
|---|-------|--------|
| 1 | `admin-settings` | Punya `AdminSettingsLayout` sendiri — grouped key-value form yang unik |
| 2 | `my-approvals` | Custom tabs + approval action dialog — domain-specific workflow |
| 3 | `approval-monitoring` | Dashboard dengan summary cards + overdue list — domain-specific |
| 4 | `pipeline-dashboard` | Dashboard dengan charts + stale entity detection — domain-specific |
| 5 | `asset-dashboard` | Dashboard dengan distribution charts + warranty alerts — domain-specific |
| 6 | `asset-depreciation-runs` | Batch calculate/post workflow — domain-specific |
| 7 | `MyApprovalController` | Direct query pattern (bukan Action) — intentional untuk approval inbox |

#### Validasi

- `./vendor/bin/sail php ./vendor/bin/phpstan analyse` (5 files) → No errors
- `./vendor/bin/sail test --group=asset-depreciation-runs` → 12 passed (34 assertions)
- `./vendor/bin/sail test --group=pipelines` → 42 passed (125 assertions)
- `./vendor/bin/sail test --group=my-approvals` → 7 passed (18 assertions)
- `./vendor/bin/sail test --group=approval-monitoring` → 2 passed (24 assertions)
- `./vendor/bin/sail test --group=approval-history` → 4 passed (6 assertions)
- `./vendor/bin/sail test --group=approval-audit-trail` → 12 passed (36 assertions)
- `./vendor/bin/sail npm run types` → clean
- `git diff --check` → clean

#### Kesimpulan Tahap 5

Non-CRUD workflow/dashboard/settings family sudah diaudit. 5 model docblock fixes diterapkan. Semua page-level differences adalah **intentional domain complexity** — tidak ada shared shell yang bisa dibuat tanpa over-abstraction. Total 79 Pest tests validated (243 assertions).

## Tahap 6. Hardening Test, Registry, dan Handoff

### Outcome

- Registry test, shared factory, dan handoff selaras dengan bentuk implementasi akhir.
- Sisa outlier tercatat jelas sebagai intentional exception atau backlog lanjutan.

### Scope

- `docs/module-registry.md`
- `tests/e2e/shared-test-factories.ts` dan helper test terkait
- `task.md`
- `docs/refactor-sonar-progress.md` bila wave memang memakai tracking Sonar

### Exit Criteria

- Registry modul mencerminkan implementation surface terbaru.
- Shared test helper mendukung style final tiap cohort.
- Handoff sudah cukup untuk melanjutkan wave berikutnya tanpa re-triage besar.

### Prompt Eksekusi Tahap 6

```text
Baca task.md, docs/module-registry.md, dan docs/refactor-style-consistency-plan.md.

Kerjakan Tahap 6: hardening test, registry, dan handoff setelah wave refactor style.

Target:
- Selaraskan docs/module-registry.md dengan implementation surface terbaru.
- Rapikan shared E2E/Pest helper bila ada perubahan pola yang sekarang sudah menjadi standar.
- Update task.md dan, jika relevan untuk wave Sonar, docs/refactor-sonar-progress.md.
- Jangan membuka scope baru ke modul produksi kecuali diperlukan untuk menutup mismatch dokumentasi atau helper test.

Langkah wajib:
1. Mulai dari helper test dan dokumen registry, bukan dari broad repo search.
2. Jika perubahan file structure helper diperlukan, jalankan Depwire dulu.
3. Setelah edit pertama, jalankan validasi terfokus yang paling kecil.

Validasi:
- ./vendor/bin/sail npm run types
- ./vendor/bin/sail test --group {module-slug}
- Jika shared frontend/test helper berubah lintas banyak modul: ./vendor/bin/sail npm run test:e2e:smoke-waves

Handoff:
- Tutup sesi dengan update task.md yang memuat wave terakhir, validasi, blocker, dan next step paling kecil.
```

### Hasil Tahap 6 — Hardening Test, Registry, dan Handoff

Tanggal: 2026-04-30

#### Perubahan yang Diterapkan

| # | File | Perubahan | Alasan |
|---|------|-----------|--------|
| 1 | `docs/module-registry.md` | Update Klasifikasi Modul section: tambah backend pattern info, jumlah modul per bucket, shared base class references | Selaraskan dengan implementation surface terbaru dari Tahap 0-5 |
| 2 | `docs/module-registry.md` | Fix Registry Pest — CRUD Simple: hapus 5 modul yang sebenarnya complex (product-categories, units, asset-categories, asset-locations, asset-models), tambah catatan penjelasan | Selaraskan dengan actual `createComplexEntityConfig` usage |

#### Shared Test Helpers — Tidak Perlu Perubahan

Refactor Tahap 1-5 hanya menyentuh:
- Model docblocks (`@use HasFactory`, `@var list<string>`, `@var array<string, string>`)
- Controller `destroy()` pattern (→ `$this->destroyModel()`)
- Export `ShouldAutoSize` interface
- Redundant `created_at`/`updated_at` cast removal

Tidak ada perubahan pada:
- API response shape/payload
- Route/endpoint
- Permission names
- `data-testid` attributes
- Query parameters

Oleh karena itu, **shared E2E/Pest test helpers tidak perlu diubah** — semua test tetap valid karena kontrak API tidak berubah.

#### Validasi

- `git diff --check` → clean
- `./vendor/bin/sail npm run types` → clean
- Tidak perlu re-run test karena hanya documentation files yang diubah di Tahap 6

#### Ringkasan Keseluruhan Refactor Style Consistency (Tahap 0-6)

| Tahap | Scope | Files Modified | Tests Validated |
|-------|-------|---------------|-----------------|
| 0 | Baseline & matriks outlier | 0 (docs only) | — |
| 1 | CRUD Simple (5 modules) | 2 | 133 passed |
| 2 | CRUD Complex Master (13 modules) | 20 | 597 passed |
| 3 | CRUD Complex Transaction (12 modules) | 19 | 376 passed |
| 4 | Non-CRUD Report (18 modules) | 0 (already konform) | 62 passed |
| 5 | Non-CRUD Workflow/Dashboard (7 modules) | 5 | 79 passed |
| 6 | Registry & handoff | 1 (docs only) | — |
| **Total** | **55+ modules** | **47 production files** | **1,247 tests passed** |

#### Remaining Backlog (Requires Depwire)

| # | Item | Type | Effort | Status |
|---|------|------|--------|--------|
| 1 | ~~Migrate `stock-movements` from `DataTablePage` to `ReportDataTablePage`~~ | Structural frontend | Medium | ✅ Done |
| 2 | `stock-monitor` tetap pakai `DataTablePage` — intentional (custom query + summary cards) | Exception | — | Intentional |
| 3 | Unify Index Action methods (`handleSearchOrPrimaryIndexRequest` vs `handleIndexRequest`) | Behavioral backend | High | Backlog |
| 4 | Unify request patterns (trait vs abstract class) across Warehouse vs Customer/Supplier | Structural backend | High | Backlog |
| 5 | Unify resource base classes (SimpleCrud* vs raw JsonResource + trait) | Structural backend | Medium | Backlog |
| 6 | Decide on DTO usage (Customer/AssetModel/Pipeline use DTOs, others don't) | Architectural | High | Backlog |
| 7 | ~~Extend `createSimpleEntityConfig` for borderline-simple modules~~ | Structural frontend | Medium | ❌ Rejected — cost of new abstraction outweighs benefit (see rationale below) |
| 8 | ~~Standardize GoodsReceipt controller to use `LoadsResourceRelations` trait~~ | Structural backend | Low | ✅ Done |

#### Rationale: Backlog #7 Rejected

Analisis pada 2026-04-30 menunjukkan bahwa membuat `createExtendedSimpleEntityConfig` tidak cost-effective:

- `product-categories` (1 extra field: description textarea) dan `units` (1 extra field: symbol input) masing-masing hanya punya 3 small files (30-93 lines) yang sudah bekerja.
- `asset-categories` punya 3 fields (code, name, useful_life_months_default) dengan number type — terlalu complex untuk "extended simple".
- Membuat abstraction baru memerlukan: extend `createSimpleEntityConfig` options, extend `SimpleEntityForm` untuk accept children/extra fields, extend `SimpleEntityViewModal` untuk extra view fields, extend `createSimpleEntityColumns` untuk extra columns.
- Benefit: hapus ~6 small files. Cost: 1 new abstraction layer yang menambah cognitive load untuk 5 existing simple consumers.
- Keputusan: biarkan sebagai `createComplexEntityConfig` — pattern ini sudah benar dan working.

## Urutan Eksekusi yang Direkomendasikan

1. ~~Tahap 0 untuk memetakan bucket implementasi aktual dan outlier.~~ ✅ Selesai
2. ~~Tahap 1 untuk membersihkan simple CRUD karena paling murah dan paling deterministik.~~ ✅ Selesai
3. ~~Tahap 2 untuk complex CRUD dasar yang belum punya nested transaction weight tinggi.~~ ✅ Selesai
4. ~~Tahap 3 untuk family transaction dan nested form yang paling rawan duplikasi.~~ ✅ Selesai
5. ~~Tahap 4 untuk report/read-only shell.~~ ✅ Selesai
6. ~~Tahap 5 untuk workflow, dashboard, dan settings.~~ ✅ Selesai
7. ~~Tahap 6 untuk registry, shared tests, dan handoff.~~ ✅ Selesai

## Definition of Done Keseluruhan

- ✅ Modul sejenis memakai anchor shared yang sama dan hanya berbeda pada metadata/domain logic.
- ✅ Tidak ada perbedaan style yang tersisa hanya karena historis copy-paste (kecuali 7 items di Remaining Backlog yang memerlukan Depwire).
- ✅ Semua perubahan tervalidasi per wave dengan command terfokus.
- ✅ `task.md` selalu cukup untuk melanjutkan wave berikutnya tanpa mengulang triage besar.
