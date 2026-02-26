# Module Registry

> Referensi metadata per-modul untuk testing (Pest + E2E).
> Data ini digunakan oleh agent saat membuat atau refactoring test.

---

## Klasifikasi Modul

### CRUD Simple

Modul dengan satu tabel utama, tanpa relasi FK kompleks.
- Frontend: `createSimpleEntityConfig` + `createSimpleEntityColumns`
- Kolom standar: Select, Name, Created At, Updated At, Actions
- Sortable: `Name`, `Created At`, `Updated At`
- Form: `SimpleEntityForm`
- View: `SimpleEntityViewModal` (dialog)

### CRUD Complex

Modul dengan relasi FK, filter multi-field, custom columns.
- Frontend: `createComplexEntityConfig` + custom `{Module}Columns.tsx`
- Form: custom `{Module}Form.tsx`
- View: custom `{Module}ViewModal.tsx` atau page

### Non-CRUD

Modul tanpa operasi CRUD standar (Auth, Settings, Dashboard, Reports, dll).

---

## Registry E2E — Simple CRUD

Semua modul simple CRUD memiliki konfigurasi E2E yang identik kecuali nama:

```yaml
- slug: departments
  route: /departments
  api: /api/departments
  export_api: /api/departments/export
  search_placeholder: "Search departments..."
  sortable_columns: [Name, Created At, Updated At]
  view_type: dialog
  view_dialog_title: "Department Details"
  checkbox_header: false

- slug: positions
  route: /positions
  api: /api/positions
  export_api: /api/positions/export
  search_placeholder: "Search positions..."
  sortable_columns: [Name, Created At, Updated At]
  view_type: dialog
  checkbox_header: false

- slug: branches
  route: /branches
  api: /api/branches
  export_api: /api/branches/export
  search_placeholder: "Search branches..."
  sortable_columns: [Name, Created At, Updated At]
  view_type: dialog
  checkbox_header: false

- slug: supplier-categories
  route: /supplier-categories
  api: /api/supplier-categories
  export_api: /api/supplier-categories/export
  search_placeholder: "Search supplier categories..."
  sortable_columns: [Name, Created At, Updated At]
  view_type: dialog
  checkbox_header: false

- slug: customer-categories
  route: /customer-categories
  api: /api/customer-categories
  export_api: /api/customer-categories/export
  search_placeholder: "Search customer categories..."
  sortable_columns: [Name, Created At, Updated At]
  view_type: dialog
  checkbox_header: false
```

---

## Registry E2E — Complex CRUD

```yaml
- slug: product-categories
  route: /product-categories
  api: /api/product-categories
  export_api: /api/product-categories/export
  search_placeholder: "Search product categories..."
  sortable_columns: [Name, Created At, Updated At]
  non_sortable_columns: [Description]
  view_type: dialog
  checkbox_header: false

- slug: units
  route: /units
  api: /api/units
  export_api: /api/units/export
  search_placeholder: "Search units..."
  sortable_columns: [Name, Created At, Updated At]
  non_sortable_columns: [Symbol]
  view_type: dialog
  checkbox_header: false

- slug: employees
  route: /employees
  api: /api/employees
  export_api: /api/employees/export
  import_api: /api/employees/import
  search_placeholder: "Search employees..."
  sortable_columns: [NIK, Name, Email, Phone, Department, Position, Branch, Salary, Status, Hire Date]
  view_type: dialog
  checkbox_header: false

- slug: customers
  route: /customers
  api: /api/customers
  export_api: /api/customers/export
  search_placeholder: "Search customers..."
  sortable_columns: [Name, Email, Phone, Branch, Category, Status]
  view_type: dialog
  checkbox_header: false

- slug: suppliers
  route: /suppliers
  api: /api/suppliers
  export_api: /api/suppliers/export
  import_api: /api/suppliers/import
  search_placeholder: "Search suppliers..."
  sortable_columns: [Name, Email, Phone, Branch, Category, Status]
  view_type: dialog
  checkbox_header: false

- slug: products
  route: /products
  api: /api/products
  export_api: /api/products/export
  search_placeholder: "Search products..."
  sortable_columns: [Code, Name, Type, Category, Cost, Price, Status]
  view_type: dialog
  checkbox_header: false

- slug: assets
  route: /assets
  api: /api/assets
  export_api: /api/assets/export
  import_api: /api/assets/import
  search_placeholder: "Search assets..."
  sortable_columns: [Code, Name, Category, Branch, Status, Cost, Purchase Date, Location, Department, Employee, Supplier]
  view_type: page  # navigasi ke /assets/{ulid}
  view_url_pattern: "/assets/\\w+"
  checkbox_header: false

- slug: asset-categories
  route: /asset-categories
  api: /api/asset-categories
  export_api: /api/asset-categories/export
  search_placeholder: "Search asset categories..."
  sortable_columns: [Code, Name, Default Useful Life (Months), Created At, Updated At]
  view_type: dialog
  checkbox_header: false

- slug: asset-models
  route: /asset-models
  api: /api/asset-models
  export_api: /api/asset-models/export
  search_placeholder: "Search asset models..."
  sortable_columns: [Model Name, Manufacturer, Category]
  non_sortable_columns: [Specs]
  view_type: dialog
  checkbox_header: false

- slug: asset-locations
  route: /asset-locations
  api: /api/asset-locations
  export_api: /api/asset-locations/export
  search_placeholder: "Search asset locations..."
  sortable_columns: [Code, Name, Branch, Parent Location]
  view_type: dialog
  checkbox_header: false

- slug: asset-movements
  route: /asset-movements
  api: /api/asset-movements
  export_api: /api/asset-movements/export
  search_placeholder: "Search movements..."
  sortable_columns: [Asset, Type, Date, Ref/Notes, PIC]
  non_sortable_columns: [Origin, Destination]
  view_type: dialog
  checkbox_header: false

- slug: asset-maintenances
  route: /asset-maintenances
  api: /api/asset-maintenances
  export_api: /api/asset-maintenances/export
  search_placeholder: "Search maintenances..."
  sortable_columns: [Asset, Type, Status, Scheduled, Performed, Supplier, Notes, Cost]
  view_type: dialog
  checkbox_header: false

- slug: asset-stocktakes
  route: /asset-stocktakes
  api: /api/asset-stocktakes
  export_api: /api/asset-stocktakes/export
  search_placeholder: "Search stocktakes..."
  sortable_columns: [Reference, Branch, Planned Date, Performed Date, Status, Created By]
  view_type: dialog
  checkbox_header: false
  note: "Memiliki custom action 'Perform' yang navigasi ke page /asset-stocktakes/{ulid}/perform. Juga memiliki endpoint nested /items untuk AssetStocktakeItem."

- slug: fiscal-years
  route: /fiscal-years
  api: /api/fiscal-years
  export_api: /api/fiscal-years/export
  search_placeholder: "Search fiscal years..."
  sortable_columns: [Name, Start Date, End Date, Status, Created At]
  view_type: dialog
  checkbox_header: false

- slug: coa-versions
  route: /coa-versions
  api: /api/coa-versions
  export_api: /api/coa-versions/export
  search_placeholder: "Search coa versions..."
  sortable_columns: [Name, Fiscal Year, Status, Created At]
  view_type: dialog
  checkbox_header: false

- slug: account-mappings
  route: /account-mappings
  api: /api/account-mappings
  export_api: /api/account-mappings/export
  search_placeholder: "Search account mappings..."
  sortable_columns: [Source Account, Target Account, Type, Created At]
  view_type: dialog
  checkbox_header: false

- slug: journal-entries
  route: /journal-entries
  api: /api/journal-entries
  export_api: /api/journal-entries/export
  search_placeholder: "Search journal entries..."
  sortable_columns: [Entry Number, Date, Description, Reference, Total Amount, Status]
  view_type: dialog
  checkbox_header: false
  note: "Actions menggunakan icon buttons (Eye, Pencil, Trash) bukan dropdown menu"

- slug: asset-depreciation-runs
  route: /asset-depreciation-runs
  api: /api/asset-depreciation-runs
  view_type: page
  note: "Non-CRUD feature for calculating and posting asset depreciation."

- slug: asset-reports/register
  route: /reports/assets/register
  api: /api/assets
  export_api: /reports/assets/register/export
  search_placeholder: "Search assets..."
  sortable_columns: [Code, Name, Category, Branch, Status, Cost, Purchase Date]
  view_type: dialog
  checkbox_header: false
  note: "Laporan Asset Register (Non-CRUD), menggunakan read-only data table dengan advanced filter."

- slug: book-value-depreciation-reports
  route: /reports/book-value-depreciation
  api: /reports/book-value-depreciation
  export_api: /reports/book-value-depreciation/export
  search_placeholder: "Search..."
  sortable_columns: [Asset Code, Asset Name, Purchase Date, Purchase Cost, Accum. Depreciation, Book Value]
  view_type: dialog
  checkbox_header: false
  note: "Laporan Book Value & Depreciation (Non-CRUD), menggunakan read-only data table dengan filter."

- slug: maintenance-cost-reports
  route: /reports/maintenance-cost
  api: /reports/maintenance-cost
  export_api: /reports/maintenance-cost/export
  search_placeholder: "Search code, name, notes..."
  sortable_columns: [Asset Code, Asset Name, Type, Status, Scheduled At, Performed At, Vendor, Cost]
  view_type: dialog
  checkbox_header: false
  note: "Laporan Biaya Perawatan/Maintenance Cost (Non-CRUD), menggunakan read-only data table dengan filter."

- slug: asset-stocktake-variances
  route: /asset-stocktake-variances
  api: /api/asset-stocktake-variances
  export_api: /api/asset-stocktake-variances/export
  search_placeholder: "Search code, name, notes..."
  sortable_columns: [Asset Code, Asset Name, Expected Location, Found Location, Result, Notes, Checked By, Checked At]
  view_type: dialog
  checkbox_header: false
  note: "Laporan Stocktake Variance (Non-CRUD), menggunakan read-only data table dengan filter."

- slug: pipelines
  route: /pipelines
  api: /api/pipelines
  export_api: /api/pipelines/export
  search_placeholder: "Search name, code, or description..."
  sortable_columns: [Name, Code, Entity, Version, Creator, Status]
  view_type: dialog
  checkbox_header: false
  note: "Memiliki embedded sub-form untuk Pipeline States dan Pipeline Transitions yang dapat diakses di dalam Edit modal."

- slug: entity-state-actions
  route: (embedded in Asset Profile)
  api: /api/entity-states/{entityType}/{entityId}
  view_type: embedded
  note: "Embedded pipeline actions component for entities like Asset."

- slug: entity-state-timeline
  route: /api/entities/{entity_type}/{id}/timeline
  api: /api/entities/{entity_type}/{id}/timeline
  view_type: modal
  note: "View only modal endpoint for timeline data."

- slug: pipeline-dashboard
  route: /pipeline-dashboard
  api: /api/pipeline-dashboard/data
  view_type: page
  note: "Pipeline monitoring dashboard with summary cards, state distribution chart, and stale entity detection."

## Testing

E2E testing uses Playwright. Tests are organized by module in `tests/e2e/`.

---

## Registry Pest — CRUD Simple

| # | Modul | Group | Feature Files | Unit Model |
|---|-------|-------|---------------|------------|
| 1 | Departments | `departments` | `DepartmentControllerTest`, `DepartmentExportTest` | `DepartmentTest` |
| 2 | Positions | `positions` | `PositionControllerTest`, `PositionExportTest` | `PositionTest` |
| 3 | Branches | `branches` | `BranchControllerTest`, `BranchExportTest` | `BranchTest` |
| 4 | Customer Categories | `customer-categories` | `CustomerCategoryControllerTest`, `CustomerCategoryExportTest` | `CustomerCategoryTest` |
| 5 | Supplier Categories | `supplier-categories` | `SupplierCategoryControllerTest`, `SupplierCategoryExportTest` | `SupplierCategoryTest` |
| 6 | Product Categories | `product-categories` | `ProductCategoryControllerTest`, `ProductCategoryExportTest` | `ProductCategoryTest` |
| 7 | Units | `units` | `UnitControllerTest`, `UnitExportTest` | `UnitTest` |
| 8 | Asset Categories | `asset-categories` | `AssetCategoryControllerTest`, `AssetCategoryExportTest` | `AssetCategoryTest` |
| 9 | Asset Locations | `asset-locations` | `AssetLocationControllerTest`, `AssetLocationExportTest` | `AssetLocationTest` |
| 10 | Asset Models | `asset-models` | `AssetModelControllerTest`, `AssetModelExportTest` | `AssetModelTest` |

---

## Registry Pest — CRUD Complex

| # | Modul | Group | Feature Files | Unit Model | Catatan |
|---|-------|-------|---------------|------------|---------|
| 1 | Employees | `employees` | `EmployeeControllerTest`, `EmployeeExportTest`, `EmployeeImportTest` | `EmployeeTest` | Ada `UpdateEmployeeDataTest` (DTO) |
| 2 | Customers | `customers` | `CustomerControllerTest`, `CustomerExportTest` | `CustomerTest` | — |
| 3 | Suppliers | `suppliers` | `SupplierControllerTest`, `SupplierExportTest` | `SupplierTest` | — |
| 4 | Products | `products` | `ProductControllerTest`, `ProductExportTest` | `ProductTest` | — |
| 5 | Accounts | `accounts` | `AccountControllerTest`, `AccountExportTest` | `AccountTest` | — |
| 6 | Account Mappings | `account-mappings` | `AccountMappingControllerTest`, `AccountMappingExportTest` | `AccountMappingTest` | — |
| 7 | Assets | `assets` | `AssetControllerTest`, `AssetExportTest`, `AssetFilteredExportTest`, `AssetProfileTest` | `AssetTest` | Paling kompleks |
| 8 | Asset Movements | `asset-movements` | `AssetMovementControllerTest`, `AssetMovementExportTest` | `AssetMovementTest` | — |
| 9 | Asset Maintenances | `asset-maintenances` | `AssetMaintenanceControllerTest`, `AssetMaintenanceExportTest` | `AssetMaintenanceTest` | — |
| 10 | COA Versions | `coa-versions` | `CoaVersionControllerTest`, `CoaVersionExportTest` | `CoaVersionTest` | — |
| 11 | Fiscal Years | `fiscal-years` | `FiscalYearControllerTest`, `FiscalYearExportTest` | `FiscalYearTest` | — |
| 12 | Journal Entries | `journal-entries` | `JournalEntryControllerTest`, `JournalEntryExportTest` | `JournalEntryTest` | — |
| 13 | Asset Stocktakes | `asset-stocktakes` | `AssetStocktakeControllerTest`, `AssetStocktakeExportTest` | `AssetStocktakeTest` | — |
| 14 | Pipelines | `pipelines` | `PipelineControllerTest` | `PipelineTest` | — |

---

## Registry Pest — Non-CRUD

| # | Modul | Group | Files | Catatan |
|---|-------|-------|-------|---------|
| 1 | Auth | `auth` | `Feature/Auth/*.php` (7 files) | Sudah di subfolder |
| 2 | Settings | `settings` | `Feature/Settings/*.php` (3 files) | Sudah di subfolder |
| 3 | Dashboard | `dashboard` | `Feature/Dashboard/DashboardTest.php` | — |
| 4 | Permissions | `permissions` | `Feature/Permissions/PermissionControllerTest.php` | — |
| 5 | Users | `users` | `Feature/Users/UserControllerTest.php` | — |
| 6 | Reports | `reports` | `Feature/Reports/*.php` (5 files) | Ada legacy + new tests |
| 7 | Posting Journals | `posting-journals` | `Feature/PostingJournals/PostingJournalTest.php` | — |
| 8 | Asset Depreciation Runs | `asset-depreciation-runs` | `Feature/AssetDepreciationRuns/*.php` | — |
| 9 | Asset Reports | `asset-reports` | `Feature/AssetRegisterTest.php` | Laporan Asset Register |
| 10 | Book Value Reports | `book-value-depreciation-reports` | `Feature/Reports/BookValueDepreciationReportTest.php` | Laporan Book Value & Depreciation |
| 11 | Stocktake Variance Reports | `asset-stocktakes` | `Feature/AssetStocktakes/AssetStocktakeVarianceControllerTest.php` | Laporan Stocktake Variance |
| 12 | Entity State Actions | `entity-state-actions` | `Feature/EntityStates/EntityStateControllerTest.php` | Pipeline actions engine per entitas |
| 13 | Entity State Timeline | `entity-state-timeline` | `Feature/EntityStates/EntityStateTimelineTest.php` | Timeline history component per entitas |

---

## Struktur Test Target

```
tests/
├── Feature/{ModuleName}/
│   ├── {Module}ControllerTest.php
│   └── {Module}ExportTest.php
├── Unit/
│   ├── Models/{Module}Test.php
│   ├── Actions/{ModuleName}/*Test.php
│   ├── Domain/{ModuleName}/*Test.php
│   ├── Requests/{ModuleName}/*Test.php
│   └── Resources/{ModuleName}/*Test.php
└── e2e/{module-slug}/
    ├── helpers.ts
    └── {module}.spec.ts
```

### Standar Group Naming

- **Format**: `->group('{module-slug}')` — kebab-case
- **Satu group saja** — tidak perlu secondary tags (`'unit'`, `'actions'`, `'export'`, dll)
- **Konsisten**: `supplier-categories` (BUKAN `supplier_categories`)

### Cara Menjalankan

```bash
# Pest — semua test untuk satu modul
./vendor/bin/sail test --group {module-slug}

# E2E — semua test untuk satu modul
npx playwright test tests/e2e/{module-slug}/
```

---

## 9 E2E Test Cases per Modul

| # | Test Case | Assertion Kunci |
|---|-----------|-----------------|
| 1 | Search | Row dengan identifier terlihat |
| 2 | Filters | Hasil tabel berubah sesuai filter |
| 3 | Add | Dialog tertutup, entity muncul |
| 4 | View | Dialog/page menampilkan data |
| 5 | Edit | Data terupdate setelah save |
| 6 | Export | Kolom Excel ⊇ kolom DataTable |
| 7 | Checkbox | Body punya checkbox, header TIDAK punya |
| 8 | Sorting | Semua sortable columns bisa diklik |
| 9 | Delete | Entity terhapus dari tabel |
| 10 | Import | Dialog import tampil dan berhasil memicu notifikasi hasil/error |

---

## Known Issues & Notes

- `journal-entries`: Actions menggunakan icon buttons (Eye, Pencil, Trash) bukan dropdown menu
- `assets`: `view_type: page` — navigasi ke `/assets/{ulid}` bukan dialog
