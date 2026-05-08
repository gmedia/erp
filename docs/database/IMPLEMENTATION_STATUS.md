# Implementation Status — Database Modules

> **Last updated:** 2026-05-02
>
> **Purpose:** Referensi cepat untuk AI agent dan developer tentang status implementasi setiap modul database. Dokumen ini adalah satu-satunya sumber kebenaran untuk status implementasi — jangan duplikasi informasi ini di file desain individual.

---

## Legend

| Icon | Status | Penjelasan |
|------|--------|------------|
| ✅ | Implemented | Migration, Model, Controller, Frontend, Factory, Tests — semua ada |
| 🔨 | Partial | Sebagian komponen sudah ada, sisanya pending |
| 📐 | Designed Only | Design doc ada, belum ada implementasi |
| 🚧 | In Progress | Sedang dikerjakan |

---

## Status Ringkas

| # | Module | Design Doc | Status | Catatan |
|---|--------|-----------|--------|---------|
| 00 | Products & Manufacturing | `00_products_design.md` | ✅ Implemented | V2 schema migrated |
| 01 | Chart of Accounts & Journal | `01_chart_of_accounts_design.md` | ✅ Implemented | |
| 10 | Pipeline (State Machine) | `10_pipeline_design.md` | ✅ Implemented | |
| 11 | Approval System | `11_approval_design.md` | ✅ Implemented | |
| 12 | Asset Management | `12_asset_management_design.md` | ✅ Implemented | |
| 13 | Purchasing (PR/PO/GR/SR) | `13_purchasing_design.md` | ✅ Implemented | |
| 14 | Inventory (Warehouse/Transfer/Opname) | `14_inventory_design.md` | ✅ Implemented | |
| 15 | Accounts Payable | `15_accounts_payable_design.md` | ✅ Implemented | PR #10 |
| 16 | Accounts Receivable | `16_accounts_receivable_design.md` | 📐 Designed Only | |
| 17 | General Ledger (Extended) | `17_general_ledger_design.md` | 📐 Designed Only | |
| 18 | Financial Reports | `18_financial_reports_design.md` | 📐 Designed Only | |

---

## Detail per Module

### ✅ Products & Manufacturing (V2 Implemented)

**Design doc:** `00_products_design.md`

| Layer | Status | Files |
|-------|--------|-------|
| Migration | ✅ V2 | V1 creates (`2026_01_29_114411` — `114422`) + V2 aligns (`2026_05_02_000001`, `2026_05_02_000002`) |
| Model | ✅ V2 | `Product`, `ProductCategory`, `Unit`, `ProductStock`, `ProductPrice`, `BillOfMaterial`, `ProductionOrder`, `ProductionOrderItem`, `ProductDependency`, `SubscriptionPlan`, `CustomerSubscription`, `SubscriptionBillingRecord` |
| Controller | ✅ | `ProductController`, `ProductCategoryController`, `UnitController` |
| Frontend | ✅ V2 | `pages/products/`, `pages/product-categories/`, `pages/units/` |
| Factory | ✅ V2 | All product-related factories aligned to V2 schema |
| Tests | ✅ V2 | 54 Pest tests passed, E2E helpers aligned |
| Seeder | ✅ V2 | `ProductCategorySeeder`, `UnitSeeder`, `ProductSampleDataSeeder` |

**Remaining gap:**
- `product_stocks.branch_id` belum diubah ke `warehouse_id` (menunggu Inventory integration)

---

### ✅ Chart of Accounts & Journal

**Design doc:** `01_chart_of_accounts_design.md`

| Layer | Status | Files |
|-------|--------|-------|
| Migration | ✅ | `2026_01_30_000000_create_coa_tables.php` |
| Model | ✅ | `FiscalYear`, `CoaVersion`, `Account`, `AccountMapping`, `JournalEntry`, `JournalEntryLine` |
| Controller | ✅ | `FiscalYearController`, `CoaVersionController`, `AccountController`, `AccountMappingController`, `JournalEntryController`, `PostingJournalController` |
| Frontend | ✅ | `pages/fiscal-years/`, `pages/coa-versions/`, `pages/accounts/`, `pages/account-mappings/`, `pages/journal-entries/`, `pages/posting-journals/` |
| Factory | ✅ | `FiscalYearFactory`, `CoaVersionFactory`, `AccountFactory`, `JournalEntryFactory`, `JournalEntryLineFactory` |
| Tests | ✅ | Feature, Unit, E2E directories exist |
| Seeder | ✅ | `CoaSeeder` |

**Known gaps vs design:**
- Kolom `journal_type` dan `source_type/source_id` (dari `17_general_ledger_design.md`) belum ditambahkan

---

### ✅ Pipeline (State Machine)

**Design doc:** `10_pipeline_design.md`

| Layer | Status | Files |
|-------|--------|-------|
| Migration | ✅ | `2026_02_23_110100` — `2026_02_25_072653` (6 files) |
| Model | ✅ | `Pipeline`, `PipelineState`, `PipelineTransition`, `PipelineTransitionAction`, `PipelineEntityState`, `PipelineStateLog` |
| Controller | ✅ | `PipelineController`, `PipelineStateController`, `PipelineTransitionController`, `PipelineDashboardController`, `PipelineAuditTrailController`, `EntityStateController` |
| Frontend | ✅ | `pages/pipelines/`, `pages/pipeline-dashboard/`, `pages/pipeline-audit-trail/`, `pages/entity-state-actions/`, `pages/entity-state-timeline/` |
| Factory | ✅ | All pipeline-related factories exist |
| Tests | ✅ | Feature, Unit, E2E directories exist |
| Seeder | ✅ | `PipelineSampleDataSeeder` |

**Known gaps vs design:** None identified

---

### ✅ Approval System

**Design doc:** `11_approval_design.md`

| Layer | Status | Files |
|-------|--------|-------|
| Migration | ✅ | `2026_02_27_035612`, `2026_02_28_104540`, `2026_03_01_134121` — `2026_03_01_134127` |
| Model | ✅ | `ApprovalFlow`, `ApprovalFlowStep`, `ApprovalRequest`, `ApprovalRequestStep`, `ApprovalDelegation`, `ApprovalAuditLog` |
| Controller | ✅ | `ApprovalFlowController`, `ApprovalDelegationController`, `MyApprovalController`, `ApprovalMonitoringController`, `ApprovalAuditTrailController`, `EntityApprovalHistoryController` |
| Frontend | ✅ | `pages/approval-flows/`, `pages/approval-delegations/`, `pages/my-approvals/`, `pages/approval-monitoring/`, `pages/approval-audit-trail/`, `pages/approval-history/` |
| Factory | ✅ | All approval-related factories exist |
| Tests | ✅ | Feature, Unit, E2E directories exist |
| Seeder | ✅ | `ApprovalFlowSampleDataSeeder`, `ApprovalDelegationSampleDataSeeder` |

**Known gaps vs design:** None identified

---

### ✅ Asset Management

**Design doc:** `12_asset_management_design.md`

| Layer | Status | Files |
|-------|--------|-------|
| Migration | ✅ | `2026_02_03_000000_create_asset_management_tables.php`, `2026_02_08_061342`, `2026_02_21_072846` |
| Model | ✅ | `AssetCategory`, `AssetModel`, `AssetLocation`, `Asset`, `AssetMovement`, `AssetMaintenance`, `AssetStocktake`, `AssetStocktakeItem`, `AssetDepreciationRun`, `AssetDepreciationLine` |
| Controller | ✅ | `AssetCategoryController`, `AssetModelController`, `AssetLocationController`, `AssetController`, `AssetMovementController`, `AssetMaintenanceController`, `AssetStocktakeController`, `AssetStocktakeItemController`, `AssetDepreciationRunController`, `AssetDashboardController`, `AssetReportController`, `AssetStocktakeVarianceController`, `BookValueDepreciationReportController`, `MaintenanceCostReportController` |
| Frontend | ✅ | `pages/asset-categories/`, `pages/asset-models/`, `pages/asset-locations/`, `pages/assets/`, `pages/asset-movements/`, `pages/asset-maintenances/`, `pages/asset-stocktakes/`, `pages/asset-depreciation-runs/`, `pages/asset-dashboard/`, `pages/asset-reports/` |
| Factory | ✅ | All asset-related factories exist |
| Tests | ✅ | Feature, Unit, E2E directories exist |
| Seeder | ✅ | `AssetCategorySeeder`, `AssetSampleDataSeeder` |

**Known gaps vs design:** None identified

---

### ✅ Purchasing (PR/PO/GR/SR)

**Design doc:** `13_purchasing_design.md`

| Layer | Status | Files |
|-------|--------|-------|
| Migration | ✅ | `2026_03_05_150000` — `2026_03_06_010100` (8 files) |
| Model | ✅ | `PurchaseRequest`, `PurchaseRequestItem`, `PurchaseOrder`, `PurchaseOrderItem`, `GoodsReceipt`, `GoodsReceiptItem`, `SupplierReturn`, `SupplierReturnItem` |
| Controller | ✅ | `PurchaseRequestController`, `PurchaseOrderController`, `GoodsReceiptController`, `SupplierReturnController`, `PurchaseOrderStatusReportController`, `PurchaseHistoryReportController`, `GoodsReceiptReportController` |
| Frontend | ✅ | `pages/purchase-requests/`, `pages/purchase-orders/`, `pages/goods-receipts/`, `pages/supplier-returns/` |
| Factory | ✅ | All purchasing-related factories exist |
| Tests | ✅ | Feature, Unit, E2E directories exist |
| Seeder | ✅ | `PurchasingSampleDataSeeder` |

**Known gaps vs design:**
- `document_sequences` table belum dibuat sebagai tabel terpisah (penomoran mungkin di-handle di application level)
- Integrasi akuntansi (`journal_entry_id` di GR/SR) — Decision Required di design doc

---

### ✅ Inventory (Warehouse/Transfer/Opname/Adjustment)

**Design doc:** `14_inventory_design.md`

| Layer | Status | Files |
|-------|--------|-------|
| Migration | ✅ | `2026_02_25_000000` (warehouses), `2026_02_25_010000-010001` (transfers), `2026_02_26_000000-000001` (stocktakes), `2026_02_27_050000-050001` (adjustments), `2026_03_03_000000` (stock_movements), `2026_02_27_042222` (warehouse branch/code) |
| Model | ✅ | `Warehouse`, `StockTransfer`, `StockTransferItem`, `InventoryStocktake`, `InventoryStocktakeItem`, `StockAdjustment`, `StockAdjustmentItem`, `StockMovement` |
| Controller | ✅ | `WarehouseController`, `StockTransferController`, `StockTransferItemController`, `InventoryStocktakeController`, `InventoryStocktakeItemController`, `StockAdjustmentController`, `StockAdjustmentItemController`, `StockMovementController`, `StockMonitorController`, `InventoryValuationReportController`, `StockMovementReportController`, `InventoryStocktakeVarianceReportController`, `StockAdjustmentReportController` |
| Frontend | ✅ | `pages/warehouses/`, `pages/stock-transfers/`, `pages/inventory-stocktakes/`, `pages/stock-adjustments/`, `pages/stock-movements/`, `pages/stock-monitor/` |
| Factory | ✅ | All inventory-related factories exist |
| Tests | ✅ | Feature, Unit, E2E directories exist |
| Seeder | ✅ | `WarehouseSeeder`, `StockTransferSampleDataSeeder`, `InventoryStocktakeSampleDataSeeder`, `StockAdjustmentSampleDataSeeder`, `StockMovementSampleDataSeeder` |

**Known gaps vs design:**
- `product_stocks.branch_id → warehouse_id` migration belum dilakukan (Section 8 design doc)
- Integrasi akuntansi (`journal_entry_id` di adjustments) — Decision Required di design doc

---

### ✅ Accounts Payable

**Design doc:** `15_accounts_payable_design.md`

| Layer | Status | Files |
|-------|--------|-------|
| Migration | ✅ | `2026_05_10_000000` — `2026_05_10_000300` (4 files) |
| Model | ✅ | `SupplierBill`, `SupplierBillItem`, `ApPayment`, `ApPaymentAllocation` |
| Controller | ✅ | `SupplierBillController`, `ApPaymentController`, `ApAgingReportController`, `ApOutstandingReportController`, `ApPaymentHistoryReportController` |
| Frontend | ✅ | `pages/supplier-bills/`, `pages/ap-payments/`, `pages/reports/ap-aging-report/`, `pages/reports/ap-outstanding-report/`, `pages/reports/ap-payment-history-report/` |
| Factory | ✅ | `SupplierBillFactory`, `SupplierBillItemFactory`, `ApPaymentFactory`, `ApPaymentAllocationFactory` |
| Tests | ✅ | 31 Pest tests passed (180 assertions) |
| Seeder | ✅ | Pipeline: `supplier_bill_lifecycle` (7 states, 10 transitions), `ap_payment_lifecycle` (6 states, 7 transitions) |

**Dependencies:** COA (✅), Purchasing (✅), Suppliers (✅)

**Known gaps vs design:**
- Journal auto-posting on bill confirm / payment confirm belum diimplementasi (akan ditambahkan saat GL Extended selesai)
- Three-way matching (PO ↔ GR ↔ Bill) belum diimplementasi (opsional per design doc)
- Overdue detection menggunakan computed on-the-fly (bukan scheduler)

---

### 📐 Accounts Receivable

**Design doc:** `16_accounts_receivable_design.md`

| Layer | Status |
|-------|--------|
| Migration | ❌ Not created |
| Model | ❌ Not created |
| Controller | ❌ Not created |
| Frontend | ❌ Not created |
| Factory | ❌ Not created |
| Tests | ❌ Not created |

**Tables to create:** `customer_invoices`, `customer_invoice_items`, `ar_receipts`, `ar_receipt_allocations`, `credit_notes`, `credit_note_items`

**Dependencies:** Requires COA (✅), Customers (✅), Products (✅)

---

### 📐 General Ledger (Extended)

**Design doc:** `17_general_ledger_design.md`

| Layer | Status |
|-------|--------|
| Migration | ❌ Not created |
| Model | ❌ Not created |
| Controller | ❌ Not created |
| Frontend | ❌ Not created |
| Factory | ❌ Not created |
| Tests | ❌ Not created |

**Tables to create:** `account_balances`, `recurring_journals`, `recurring_journal_lines`, `bank_reconciliations`, `bank_reconciliation_items`, `period_closings`

**Dependencies:** Requires COA (✅), AP (📐), AR (📐)

**Note:** Sebagian fitur GL dasar (journal entries, posting) sudah ada di COA module. Dokumen ini menambahkan fitur lanjutan.

---

### 📐 Financial Reports

**Design doc:** `18_financial_reports_design.md`

| Layer | Status |
|-------|--------|
| Migration | ❌ Not created |
| Model | ❌ Not created |
| Controller | 🔨 Partial (`ReportController` exists but scope unclear) |
| Frontend | 🔨 Partial (`pages/reports/` exists) |
| Factory | ❌ Not created |
| Tests | ❌ Not created |

**Tables to create:** `report_configurations`, `report_sections`

**Dependencies:** Requires GL Extended (📐), AP (📐), AR (📐)

---

## Supporting Modules (Tidak Ada Design Doc Terpisah)

Modul-modul berikut sudah diimplementasi sebagai master data pendukung:

| Module | Migration | Model | Controller | Frontend | Tests |
|--------|-----------|-------|------------|----------|-------|
| Branches | ✅ | ✅ | ✅ | ✅ | ✅ |
| Departments | ✅ | ✅ | ✅ | ✅ | ✅ |
| Positions | ✅ | ✅ | ✅ | ✅ | ✅ |
| Employees | ✅ | ✅ | ✅ | ✅ | ✅ |
| Customers | ✅ | ✅ | ✅ | ✅ | ✅ |
| Customer Categories | ✅ | ✅ | ✅ | ✅ | ✅ |
| Suppliers | ✅ | ✅ | ✅ | ✅ | ✅ |
| Supplier Categories | ✅ | ✅ | ✅ | ✅ | ✅ |
| Permissions & Menus | ✅ | ✅ | ✅ | ✅ | ✅ |
| Users | ✅ | ✅ | ✅ | ✅ | ✅ |
| Settings | ✅ | ✅ | ✅ | ✅ | — |

---

## Roadmap Implementasi

### Fase 1 — Accounts Payable & Receivable (paralel)

Kedua modul ini bisa dikerjakan paralel karena tidak saling bergantung.

**1A. Accounts Payable (15) — ✅ COMPLETE**

Implemented in PR #10 (`feature/accounts-payable`). 110 files, +7303 lines, 31 Pest tests (180 assertions).

Dependencies: COA (✅), Purchasing (✅), Suppliers (✅)
Design doc: `15_accounts_payable_design.md`
Pending decision: `journal_entry_id` di GR/SR (lihat Pending Decisions #2)

**1B. Accounts Receivable (16)**

| Step | Scope | Skill | Estimasi |
|------|-------|-------|----------|
| 1 | Migration: `customer_invoices`, `customer_invoice_items`, `ar_receipts`, `ar_receipt_allocations`, `credit_notes`, `credit_note_items` | `database-migration` | 30m |
| 2 | Models + Relations | `refactor-backend` | 1h |
| 3 | Backend: Controllers, Requests, Resources, Actions, Exports | `feature-crud-complex` | 3h |
| 4 | Frontend: Pages, Forms, Columns, Filters, ViewModals | `feature-crud-complex` | 3h |
| 5 | Factories, Seeders | `testing-strategy` | 30m |
| 6 | Tests: Feature, Unit, E2E | `testing-strategy` | 2h |

Dependencies: COA (✅), Customers (✅), Products (✅)
Design doc: `16_accounts_receivable_design.md`

### Fase 2 — General Ledger Extended (17)

Harus menunggu AP + AR selesai karena GL Extended mengintegrasikan journal entries dari kedua modul.

| Step | Scope | Skill | Estimasi |
|------|-------|-------|----------|
| 1 | Migration: `account_balances`, `recurring_journals`, `recurring_journal_lines`, `bank_reconciliations`, `bank_reconciliation_items`, `period_closings` | `database-migration` | 30m |
| 2 | Extend existing JournalEntry model: tambah `journal_type`, `source_type/source_id` | `refactor-backend` | 1h |
| 3 | Backend: Controllers, Requests, Resources, Actions | `feature-crud-complex` + `feature-non-crud` | 4h |
| 4 | Frontend: Pages (recurring journals, bank recon, period closing) | `feature-crud-complex` + `feature-non-crud` | 4h |
| 5 | Factories, Seeders, Tests | `testing-strategy` | 3h |

Dependencies: COA (✅), AP (Fase 1A), AR (Fase 1B)
Design doc: `17_general_ledger_design.md`
Note: Sebagian fitur GL dasar (journal entries, posting) sudah ada di COA module. Fase ini menambahkan fitur lanjutan.

### Fase 3 — Financial Reports (18)

Harus menunggu GL Extended selesai karena laporan keuangan membutuhkan data dari semua modul akuntansi.

| Step | Scope | Skill | Estimasi |
|------|-------|-------|----------|
| 1 | Migration: `report_configurations`, `report_sections` | `database-migration` | 15m |
| 2 | Models + Backend | `feature-non-crud` | 2h |
| 3 | Frontend: Report pages (balance sheet, income statement, cash flow, dll) | `feature-non-crud` | 4h |
| 4 | Tests | `testing-strategy` | 2h |

Dependencies: GL Extended (Fase 2), AP (Fase 1A), AR (Fase 1B)
Design doc: `18_financial_reports_design.md`
Note: Partial implementation sudah ada (`ReportController`, `pages/reports/`). Fase ini memperluas dengan konfigurasi report dan section yang dinamis.

### Fase 4 — Integration & Remaining Gaps

Task-task ini bisa dikerjakan kapan saja, tidak harus menunggu Fase 1-3.

| # | Task | Context | Blocked By | Estimasi |
|---|------|---------|------------|----------|
| 1 | `product_stocks.branch_id → warehouse_id` | `14_inventory_design.md` Section 8 | Pending Decision #1 | 2h |
| 2 | `journal_entry_id` di GR/SR | `13_purchasing_design.md` Section 6 | AP (Fase 1A) | 1h |
| 3 | `journal_entry_id` di stock adjustments | `14_inventory_design.md` Section 7 | GL Extended (Fase 2) | 1h |
| 4 | Subscription frontend (CRUD pages) | `00_products_design.md` Section 5.D | — | 4h |

---

## Pending Decisions

| # | Decision | Context | Impact | Status |
|---|----------|---------|--------|--------|
| 1 | `product_stocks.branch_id → warehouse_id` | `14_inventory_design.md` Section 8 | Products + Inventory integration | ⏳ Open |
| 2 | `journal_entry_id` di GR/SR | `13_purchasing_design.md` Section 6 | Purchasing ↔ Accounting integration | ⏳ Open |
| 3 | `journal_entry_id` di stock adjustments | `14_inventory_design.md` Section 7 | Inventory ↔ Accounting integration | ⏳ Open |
| 4 | ~~Products V1→V2 migration timing~~ | `archive/migration_plan_v1_to_v2.md` | — | ✅ Resolved |

---

## Handoff Notes

> Section ini berisi context penting untuk agent atau developer yang melanjutkan pekerjaan.

### Arsitektur & Konvensi

- **Stack**: Laravel API + React Full SPA (BUKAN Inertia). Lihat `.github/copilot-instructions.md`.
- **Auth**: Sanctum Bearer Token (stateless). Feature tests wajib `Sanctum::actingAs()`.
- **Pattern**: Controller tipis → Action classes → FilterService. Export pakai `columns()` pattern.
- **Skill docs**: `.github/skills/` berisi template dan instruksi per tipe task.
- **Prompt docs**: `.github/prompts/` berisi workflow reusable (continue, checkpoint, create-feature, dll).

### Baseline Test

| Suite | Count | Command |
|-------|-------|---------|
| Pest (all) | ~1,247 | `./vendor/bin/sail test` |
| Pest (products) | 54 | `./vendor/bin/sail test --group products` |
| E2E smoke | 160 | `./vendor/bin/sail npm run test:e2e:smoke-waves` |

### Completed Milestones

| Date | Milestone |
|------|-----------|
| 2026-05-10 | Accounts Payable module complete (PR #10) |
| 2026-05-02 | Products V1→V2 migration complete (Fase 1-8) |
| 2026-05-01 | Style deduplication refactor complete |
| 2026-04-30 | Style consistency polish complete (Tahap 0-6) |
| 2026-04-13 | Sonar refactor: 0% duplication, 89.3% coverage |

### Cara Melanjutkan

1. Baca `task.md` untuk status handoff aktif.
2. Baca `.github/prompts/continue-progress.prompt.md` untuk workflow standar.
3. Pilih task dari Roadmap di atas berdasarkan prioritas bisnis.
4. Gunakan skill yang sesuai dari `.github/skills/` (lihat kolom Skill di roadmap).
5. Setelah selesai, update dokumen ini dan `task.md`.
