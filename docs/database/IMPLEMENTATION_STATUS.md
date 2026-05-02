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
| 15 | Accounts Payable | `15_accounts_payable_design.md` | 📐 Designed Only | |
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

### 📐 Accounts Payable

**Design doc:** `15_accounts_payable_design.md`

| Layer | Status |
|-------|--------|
| Migration | ❌ Not created |
| Model | ❌ Not created |
| Controller | ❌ Not created |
| Frontend | ❌ Not created |
| Factory | ❌ Not created |
| Tests | ❌ Not created |

**Tables to create:** `supplier_bills`, `supplier_bill_items`, `ap_payments`, `ap_payment_allocations`

**Dependencies:** Requires COA (✅), Purchasing (✅), Suppliers (✅)

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

## Urutan Implementasi yang Direkomendasikan (Modul Belum Implemented)

```
1. Accounts Payable (15)     ← depends on: COA ✅, Purchasing ✅
2. Accounts Receivable (16)  ← depends on: COA ✅, Customers ✅
3. General Ledger Ext (17)   ← depends on: COA ✅, AP, AR
4. Financial Reports (18)    ← depends on: GL Extended, AP, AR
```

---

## Pending Decisions

| # | Decision | Context | Impact |
|---|----------|---------|--------|
| 1 | `product_stocks.branch_id → warehouse_id` | `14_inventory_design.md` Section 8 | Products V2 migration + Inventory integration |
| 2 | `journal_entry_id` di GR/SR | `13_purchasing_design.md` Section 6 | Purchasing ↔ Accounting integration |
| 3 | `journal_entry_id` di stock adjustments | `14_inventory_design.md` Section 7 | Inventory ↔ Accounting integration |
| 4 | ~~Products V1→V2 migration timing~~ | `archive/migration_plan_v1_to_v2.md` | ✅ Resolved — V2 migration complete |
