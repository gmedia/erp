<!-- scope-note: E2E registry covers 87 slugs (5 simple + 82 complex). Non-CRUD modules (58 per header) do NOT have a separate E2E section — many are lumped under "Complex CRUD" with view_type: page. Pest registry has 90 entries (5+41+44) vs header claim of 102 (5+39+58). The header counts are aspirational; the table rows are what's actually documented. -->
<!-- scope-note: "Registry E2E — Complex CRUD" section (82 slugs) includes non-CRUD modules (reports, dashboards, settings, workflows) using view_type: page/modal/embedded/component. These are NOT true CRUD but are documented here because they share DataTable-based UI patterns. -->
# Module Registry

> Referensi metadata per-modul untuk testing (Pest + E2E).
> Data ini digunakan oleh agent saat membuat atau refactoring test.
>
> Lihat juga: `docs/archive/refactor-style-consistency-plan.md` untuk matriks klasifikasi implementasi aktual dan hasil audit per tahap.

---

## Klasifikasi Modul

### CRUD Simple

Modul dengan satu tabel utama, tanpa relasi FK kompleks. **5 modul**: departments, positions, branches, customer-categories, supplier-categories. Tidak termasuk product-categories, units, asset-categories, asset-locations, asset-models — semuanya complex CRUD (lihat catatan di bawah).

- Frontend: `createSimpleEntityConfig()` + `createEntityCrudPage()` (6-line page file)
- Kolom: `createSimpleEntityColumns()` → Select, Name, Created At, Updated At, Actions
- Filters: `createSimpleEntityFilterFields()` → search only
- Form: `SimpleEntityForm` (shared)
- View: `SimpleEntityViewModal` (shared, bound via factory)
- Backend: `SimpleCrudStoreRequest`, `SimpleCrudUpdateRequest`, `SimpleCrudIndexRequest`, `SimpleCrudExportRequest`, `SimpleCrudResource`, `SimpleCrudCollection`, `SimpleCrudExport`
- Controller: `destroyModel()` via base `Controller` class

### CRUD Complex

Modul dengan relasi FK, filter multi-field, custom columns. **39 modul** termasuk master data, transaction/nested form, dan borderline-simple.

- Frontend: `createComplexEntityConfig()` + `createEntityCrudPage()` + sibling files (`Columns.tsx`, `Filters.tsx`, `Form.tsx`, `ViewModal.tsx`)
- Form: custom `{Module}Form.tsx` menggunakan `EntityForm`, `AsyncSelectField`, `zodResolver`
- View: custom `{Module}ViewModal.tsx` menggunakan `ViewModalShell`, `ViewField`
- Transaction modules: tambahan `StoresItemsInTransaction` trait, `ItemFormDialog`, `ViewModalItemsTable`
- Backend: custom Request/Resource/Export per module, `destroyModel()` atau soft-cancel pattern
- Inventory transactions (`stock-transfers`, `inventory-stocktakes`, `stock-adjustments`): `destroy()` = soft-cancel (update status → cancelled)

### Non-CRUD

Modul tanpa operasi CRUD standar. **58 modul** termasuk reports, dashboards, settings, workflows, embedded components, dan cross-cutting regression specs.

- Reports: `ReportDataTablePage` (19 modules) atau `FinancialReportPageShell` (5 modules)
- Audit trails: `AuditTrailPage` (2 modules)
- Dashboards: `AppLayout` + custom charts/cards (6 modules)
- Settings: `AdminSettingsLayout` (1 module)
- Workflows: `AppLayout` + custom domain logic (4 modules)
- Embedded: components tanpa page shell (3 modules)
- Cross-cutting regression specs (2 modules)
- Complex CRUD dengan E2E entry di section ini (16 modules)
- Backend: Action pattern via method DI, `AbstractReportIndexExport` atau `AbstractActionCollectionExport`

---

## Registry E2E — Simple CRUD

Semua modul simple CRUD memiliki konfigurasi E2E yang identik kecuali nama:

```yaml
- slug: departments
  route: /departments
  api: /api/departments
  export_api: /api/departments/export
  search_placeholder: 'Search departments...'
  sortable_columns: [Name, Created At, Updated At]
  view_type: dialog
  view_dialog_title: 'Department Details'
  checkbox_header: false

- slug: positions
  route: /positions
  api: /api/positions
  export_api: /api/positions/export
  search_placeholder: 'Search positions...'
  sortable_columns: [Name, Created At, Updated At]
  view_type: dialog
  checkbox_header: false

- slug: branches
  route: /branches
  api: /api/branches
  export_api: /api/branches/export
  search_placeholder: 'Search branches...'
  sortable_columns: [Name, Created At, Updated At]
  view_type: dialog
  checkbox_header: false

- slug: supplier-categories
  route: /supplier-categories
  api: /api/supplier-categories
  export_api: /api/supplier-categories/export
  search_placeholder: 'Search supplier categories...'
  sortable_columns: [Name, Created At, Updated At]
  view_type: dialog
  checkbox_header: false

- slug: customer-categories
  route: /customer-categories
  api: /api/customer-categories
  export_api: /api/customer-categories/export
  search_placeholder: 'Search customer categories...'
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

- slug: warehouses
  route: /warehouses
  api: /api/warehouses
  export_api: /api/warehouses/export
  search_placeholder: "Search warehouses..."
  sortable_columns: [Code, Name, Branch]
  view_type: dialog
  view_dialog_title: "Warehouse Details"
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

- slug: stock-transfers
  route: /stock-transfers
  api: /api/stock-transfers
  export_api: /api/stock-transfers/export
  search_placeholder: "Search stock transfers..."
  sortable_columns: [Transfer Number, From Warehouse, To Warehouse, Transfer Date, Expected Arrival, Status]
  view_type: dialog
  checkbox_header: false

- slug: inventory-stocktakes
  route: /inventory-stocktakes
  api: /api/inventory-stocktakes
  export_api: /api/inventory-stocktakes/export
  search_placeholder: "Search inventory stocktakes..."
  sortable_columns: [Stocktake Number, Warehouse, Product Category, Stocktake Date, Status]
  view_type: dialog
  checkbox_header: false

- slug: stock-adjustments
  route: /stock-adjustments
  api: /api/stock-adjustments
  export_api: /api/stock-adjustments/export
  search_placeholder: "Search stock adjustments..."
  sortable_columns: [Adjustment Number, Warehouse, Adjustment Date, Adjustment Type, Status]
  view_type: dialog
  checkbox_header: false

- slug: purchase-requests
  route: /purchase-requests
  api: /api/purchase-requests
  export_api: /api/purchase-requests/export
  search_placeholder: "Search PR number, notes, or rejection reason..."
  sortable_columns: [PR Number, Branch, Department, Requester, Request Date, Required Date, Priority, Status, Estimated Amount]
  view_type: dialog
  checkbox_header: false

- slug: purchase-orders
  route: /purchase-orders
  api: /api/purchase-orders
  export_api: /api/purchase-orders/export
  search_placeholder: "Search PO number, payment terms, notes, or shipping address..."
  sortable_columns: [PO Number, Supplier, Warehouse, Order Date, Expected Delivery, Status, Grand Total]
  view_type: dialog
  checkbox_header: false

- slug: goods-receipts
  route: /goods-receipts
  api: /api/goods-receipts
  export_api: /api/goods-receipts/export
  search_placeholder: "Search GR number, supplier delivery note, or notes..."
  sortable_columns: [GR Number, PO Number, Warehouse, Receipt Date, Supplier Delivery Note, Status]
  view_type: dialog
  checkbox_header: false

- slug: supplier-returns
  route: /supplier-returns
  api: /api/supplier-returns
  export_api: /api/supplier-returns/export
  search_placeholder: "Search return number or notes..."
  sortable_columns: [Return Number, PO Number, GR Number, Supplier, Warehouse, Return Date, Reason, Status]
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

- slug: inventory-valuation-reports
  route: /reports/inventory-valuation
  api: /reports/inventory-valuation
  export_api: /reports/inventory-valuation/export
  search_placeholder: "Search product, category, warehouse, branch..."
  sortable_columns: [Product, Category, Unit, Warehouse, Qty On Hand, Avg Cost, Stock Value, Last Movement]
  view_type: dialog
  checkbox_header: false
  note: "Laporan nilai persediaan per produk per gudang (quantity × average cost) dengan filter product/warehouse/branch/category."
  tests:
    - tests/e2e/inventory-valuation-report/inventory-valuation-report.spec.ts

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

- slug: approval-flows
  route: /approval-flows
  api: /api/approval-flows
  export_api: /api/approval-flows/export
  search_placeholder: "Search by Code or Name..."
  sortable_columns: [Code, Name, Approvable Type, Status, Created At]
  view_type: dialog
  checkbox_header: false
  note: "Memiliki dynamic nested array untuk Approval Flow Steps (mengatur role/user, SLA auto-approve, dll) via react-hook-form."

- slug: approval-delegations
  route: /approval-delegations
  api: /api/approval-delegations
  export_api: /api/approval-delegations/export
  search_placeholder: "Search delegator, delegate, or reason..."
  sortable_columns: [Delegator, Delegate, Approvable Type, Start Date, End Date, Reason, Status]
  view_type: dialog
  checkbox_header: false

- slug: accounts
  route: /accounts
  api: /api/accounts
  export_api: /api/accounts/export
  search_placeholder: "Search accounts..."
  sortable_columns: [Code, Name, Type, Parent Account, Level, Status]
  view_type: page
  view_url_pattern: "/accounts/\\w+"
  checkbox_header: false
  note: "Custom tree-based page (AccountTree + AccountForm), not standard CRUD dialog."
  tests:
    - tests/e2e/accounts/add-account.spec.ts
    - tests/e2e/accounts/delete-account.spec.ts
    - tests/e2e/accounts/edit-account.spec.ts
    - tests/e2e/accounts/export-account.spec.ts
    - tests/e2e/accounts/filter-accounts.spec.ts

- slug: ap-payments
  route: /ap-payments
  api: /api/ap-payments
  export_api: /api/ap-payments/export
  sortable_columns: [Payment Number, Supplier, Branch, Payment Date, Payment Method, Status, Total Amount]
  view_type: dialog
  checkbox_header: false

- slug: ar-receipts
  route: /ar-receipts
  api: /api/ar-receipts
  export_api: /api/ar-receipts/export
  sortable_columns: [Receipt Number, Customer, Branch, Receipt Date, Payment Method, Status, Total Amount]
  view_type: dialog
  checkbox_header: false

- slug: credit-notes
  route: /credit-notes
  api: /api/credit-notes
  export_api: /api/credit-notes/export
  sortable_columns: [Credit Note Number, Customer, Branch, Credit Note Date, Reason, Status, Grand Total]
  view_type: dialog
  checkbox_header: false

- slug: customer-invoices
  route: /customer-invoices
  api: /api/customer-invoices
  export_api: /api/customer-invoices/export
  sortable_columns: [Invoice Number, Customer, Branch, Invoice Date, Due Date, Status, Grand Total, Amount Due]
  view_type: dialog
  checkbox_header: false

- slug: supplier-bills
  route: /supplier-bills
  api: /api/supplier-bills
  export_api: /api/supplier-bills/export
  sortable_columns: [Bill Number, Supplier, Branch, Bill Date, Due Date, Status, Grand Total, Amount Due]
  view_type: dialog
  checkbox_header: false

- slug: recurring-journals
  route: /recurring-journals
  api: /api/recurring-journals
  export_api: /api/recurring-journals/export
  sortable_columns: [Name, Frequency, Next Run Date, Total Amount, Auto Post, Is Active, Created At]
  view_type: dialog
  checkbox_header: false

- slug: period-closings
  route: /period-closings
  api: /api/period-closings
  sortable_columns: [Fiscal Year, Period Month, Period Year, Closing Type, Status, Net Income, Closed At]
  view_type: dialog
  checkbox_header: false

- slug: report-configurations
  route: /report-configurations
  api: /api/report-configurations
  export_api: /api/report-configurations/export
  sortable_columns: [Code, Name]
  view_type: dialog
  checkbox_header: false

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
  tests:
    - tests/e2e/pipeline-dashboard/pipeline-dashboard.spec.ts

- slug: pipeline-audit-trail
  route: /pipeline-audit-trail
  api: /pipeline-audit-trail (Accepts application/json)
  export_api: /api/pipeline-audit-trail/export
  search_placeholder: "Search entity ID, performer, comment..."
  sortable_columns: [Date, Pipeline, Entity Type, Entity ID, From State, To State, Transition, Performed By, Comment]
  view_type: dialog
  checkbox_header: false
  note: "Non-CRUD feature for viewing and exporting pipeline state logs. Read-only Data Table with Detail modal."

- slug: stock-movements
  route: /stock-movements
  api: /stock-movements (Accepts application/json)
  export_api: /api/stock-movements/export
  search_placeholder: "Search reference, notes, product, warehouse..."
  sortable_columns: [Moved At, Product, Warehouse, Type, Reference, Qty In, Qty Out, Balance, Unit Cost, Avg Cost, Created By]
  view_type: page
  checkbox_header: false
  note: "Non-CRUD kartu stok digital. Read-only Data Table dengan filter product/warehouse/type/date dan drill-down reference."
  tests:
    - tests/e2e/stock-movements/stock-movement.spec.ts

- slug: stock-movement-report
  route: /reports/stock-movement
  api: /reports/stock-movement (Accepts application/json)
  export_api: /reports/stock-movement/export
  search_placeholder: "Search product, category, warehouse, branch..."
  sortable_columns: [Product, Category, Warehouse, Total In, Total Out, Ending Balance, Last Movement]
  view_type: page
  checkbox_header: false
  note: "Non-CRUD laporan pergerakan stok per periode (total masuk, total keluar, saldo akhir) per produk per gudang."
  tests:
    - tests/e2e/stock-movement-report/stock-movement-report.spec.ts

- slug: inventory-stocktake-variance-report
  route: /reports/inventory-stocktake-variance
  api: /reports/inventory-stocktake-variance (Accepts application/json)
  export_api: /reports/inventory-stocktake-variance/export
  search_placeholder: "Search stocktake, product, category, warehouse..."
  sortable_columns: [Stocktake No., Stocktake Date, Product, Category, Warehouse, System Qty, Counted Qty, Variance, Result, Counted At]
  view_type: page
  checkbox_header: false
  note: "Non-CRUD laporan selisih stock opname inventory (surplus/deficit) per produk per gudang."
  tests:
    - tests/e2e/inventory-stocktake-variance-report/inventory-stocktake-variance-report.spec.ts

- slug: stock-adjustment-report
  route: /reports/stock-adjustment
  api: /reports/stock-adjustment (Accepts application/json)
  export_api: /reports/stock-adjustment/export
  search_placeholder: "Search number, warehouse, branch, type, status..."
  sortable_columns: [Adjustment Date, Adjustment Type, Status, Warehouse, Adjustment Count, Total Qty Adjusted, Total Adjustment Value]
  view_type: page
  checkbox_header: false
  note: "Non-CRUD laporan penyesuaian stok per tipe, periode, dan gudang dengan total nilai adjustment."
  tests:
    - tests/e2e/stock-adjustment-report/stock-adjustment-report.spec.ts

- slug: purchase-order-status-report
  route: /reports/purchase-order-status
  api: /reports/purchase-order-status (Accepts application/json)
  export_api: /reports/purchase-order-status/export
  search_placeholder: "Search PO number, supplier, warehouse, or product..."
  sortable_columns: [PO Number, Supplier, Warehouse, Status, Status Category, Ordered Qty, Received Qty, Outstanding Qty, Receipt Progress (%), Grand Total, Expected Delivery]
  view_type: page
  checkbox_header: false
  note: "Non-CRUD laporan monitoring status purchase order (outstanding, partially received, closed) dengan agregasi kuantitas penerimaan."
  tests:
    - tests/e2e/purchase-order-status-report/purchase-order-status-report.spec.ts

- slug: purchase-history-report
  route: /reports/purchase-history
  api: /reports/purchase-history (Accepts application/json)
  export_api: /reports/purchase-history/export
  search_placeholder: "Search PO number, supplier, warehouse, or product..."
  sortable_columns: [PO Number, Supplier, Product, Warehouse, Status, Ordered Qty, Received Qty, Outstanding Qty, Receipt Count, Last Receipt, Total Value, Expected Delivery]
  view_type: page
  checkbox_header: false
  note: "Non-CRUD laporan riwayat pembelian per supplier, produk, dan periode dengan agregasi penerimaan barang confirmed."
  tests:
    - tests/e2e/purchase-history-report/purchase-history-report.spec.ts

- slug: goods-receipt-report
  route: /reports/goods-receipt
  api: /reports/goods-receipt (Accepts application/json)
  export_api: /reports/goods-receipt/export
  search_placeholder: "Search GR number, PO number, supplier, warehouse, or product..."
  sortable_columns: [GR Number, PO Number, Supplier, Warehouse, Status, Item Count, Received Qty, Accepted Qty, Rejected Qty, Total Value]
  view_type: page
  checkbox_header: false
  note: "Non-CRUD laporan penerimaan barang per periode, supplier, dan gudang dengan agregasi kuantitas serta nilai penerimaan."
  tests:
    - tests/e2e/goods-receipt-report/goods-receipt-report.spec.ts

- slug: stock-monitor
  route: /stock-monitor
  api: /stock-monitor (Accepts application/json)
  export_api: /api/stock-monitor/export
  search_placeholder: "Search product, category, warehouse, branch..."
  sortable_columns: [Product, Category, Warehouse, Qty On Hand, Avg Cost, Stock Value, Last Movement]
  view_type: page
  checkbox_header: false
  note: "Non-CRUD dashboard stok per produk per gudang, dengan summary per warehouse/category/branch dan low stock threshold."
  tests:
    - tests/e2e/stock-monitor/stock-monitor.spec.ts

- slug: approval-audit-trail
  route: /approval-audit-trail
  api: /api/approval-audit-trail
  export_api: /api/approval-audit-trail/export
  search_placeholder: "Search IP, Document ID, user..."
  sortable_columns: [Date, Document Type, Document ID, Event, Actor, Step]
  view_type: dialog
  checkbox_header: false
  note: "Non-CRUD feature for viewing and exporting approval audit trail logs. Read-only Data Table with Detail modal."

- modul: Asset Dashboard
  group: asset-dashboard
  tests:
    - tests/e2e/asset-dashboard/asset-dashboard.spec.ts
  note: "Non-CRUD feature for visualizing asset distribution, condition, maintenance, and warranty alerts."

- slug: admin-settings
  route: /admin-settings
  api: PUT /admin-settings
  view_type: page
  note: "Non-CRUD settings page. Grouped key-value form (General, Regional). Requires admin_setting permission."
  tests:
    - tests/e2e/admin-settings/admin-settings.spec.ts

- slug: my-approvals
  route: /my-approvals
  api: POST /my-approvals/{id}/approve
  view_type: page
  note: "Non-CRUD approval inbox page. Shows pending, approved, rejected, and all requests for the logged-in user."
  tests:
    - tests/e2e/my-approvals/my-approvals.spec.ts

- slug: approval-history
  api: GET /api/entity-states/{entityType}/{id}/approvals
  view_type: component
  note: "Non-CRUD component embedded in entity profiles to show the timeline of approval requests and steps."
  tests:
    - tests/e2e/approval-history/approval-history.spec.ts

- slug: approval-monitoring
  route: /approval-monitoring
  api: GET /api/approval-monitoring/data
  view_type: page
  note: "Non-CRUD approval monitoring dashboard. Shows summary stats and overdue approvals."
  tests:
    - tests/e2e/approval-monitoring/approval-monitoring.spec.ts

- slug: dashboards
  route: /
  api: /api/dashboard
  view_type: page
  note: "Main application dashboard with summary cards and charts."
  tests:
    - tests/e2e/dashboards/dashboard.spec.ts

- slug: permissions
  route: /permissions
  api: /api/permissions
  view_type: page
  note: "Permission management page for role-based access control."
  tests:
    - tests/e2e/permissions/permission.spec.ts

- slug: users
  route: /users
  api: /api/users
  view_type: page
  note: "User management CRUD with role assignment."
  tests:
    - tests/e2e/users/user.spec.ts

- slug: bank-reconciliations
  route: /bank-reconciliations
  api: /api/bank-reconciliations
  export_api: /api/bank-reconciliations/export
  view_type: dialog
  checkbox_header: false
  note: "Bank reconciliation module with statement import and matching."
  tests:
    - tests/e2e/bank-reconciliations/bank-reconciliation.spec.ts

- slug: balance-sheet-report
  route: /reports/balance-sheet
  api: /api/reports/balance-sheet
  view_type: page
  note: "Financial report: Balance Sheet. Uses FinancialReportPageShell with fiscal year selector."
  tests:
    - tests/e2e/balance-sheet-report/balance-sheet-report.spec.ts

- slug: cash-flow-report
  route: /reports/cash-flow
  api: /api/reports/cash-flow
  view_type: page
  note: "Financial report: Cash Flow Statement. Uses FinancialReportPageShell."
  tests:
    - tests/e2e/cash-flow-report/cash-flow-report.spec.ts

- slug: comparative-report
  route: /reports/comparative
  api: /api/reports/comparative
  view_type: page
  note: "Financial report: Comparative Report (multi-period comparison). Uses FinancialReportPageShell."
  tests:
    - tests/e2e/comparative-report/comparative-report.spec.ts

- slug: income-statement-report
  route: /reports/income-statement
  api: /api/reports/income-statement
  view_type: page
  note: "Financial report: Income Statement (P&L). Uses FinancialReportPageShell."
  tests:
    - tests/e2e/income-statement-report/income-statement-report.spec.ts

- slug: trial-balance-report
  route: /reports/trial-balance
  api: /api/reports/trial-balance
  view_type: page
  note: "Financial report: Trial Balance. Uses FinancialReportPageShell."
  tests:
    - tests/e2e/trial-balance-report/trial-balance-report.spec.ts

- slug: trial-balance-detailed-report
  route: /reports/trial-balance-detailed
  api: /reports/trial-balance-detailed
  export_api: /reports/trial-balance-detailed/export
  view_type: page
  note: "Non-CRUD detailed trial balance with per-account transaction drill-down. Uses ReportDataTablePage."
  tests:
    - tests/e2e/trial-balance-detailed-report/trial-balance-detailed-report.spec.ts

- slug: general-ledger-report
  route: /reports/general-ledger
  api: /reports/general-ledger
  export_api: /reports/general-ledger/export
  view_type: page
  note: "Non-CRUD general ledger report with per-account journal detail. Uses ReportDataTablePage."
  tests:
    - tests/e2e/general-ledger-report/general-ledger-report.spec.ts

- slug: financial-dashboard
  route: /financial-dashboard
  api: /api/financial-dashboard
  view_type: page
  note: "Non-CRUD financial dashboard. 7 KPI cards with YoY badges (revenue, expenses, net income, assets, liabilities, equity, cash balance), monthly revenue vs expenses bar chart (12 months), cash flow summary, top expense breakdown, fiscal year selector with auto-comparison. Backed by GetFinancialDashboardDataAction + FinancialReportService::getMonthlyTrends. Requires `financial_dashboard` permission."
  tests:
    - tests/e2e/financial-dashboard/financial-dashboard.spec.ts

- slug: aging-dashboard
  route: /aging-dashboard
  api: /api/aging-dashboard
  view_type: page
  note: "Non-CRUD AR/AP aging dashboard. 4 KPI cards (Total Receivables, AR Overdue, Total Payables, AP Overdue with overdue percentage badges), 2 horizontal bar charts (AR/AP buckets: Current, 1-30, 31-60, 61-90, Over 90 days, color-scaled emerald/rose), 2 top-10 overdue tables (customers + suppliers). Filters: as_of_date (default today), branch_id (default all). Cross-DB compatible (no DATEDIFF — Carbon date math + parameterized bindings). Backed by GetAgingDashboardDataAction. Requires `aging_dashboard` permission."
  tests:
    - tests/e2e/aging-dashboard/aging-dashboard.spec.ts

- slug: fiscal-year-auto-select
  route: (cross-cutting — covers /ap-payments, /ar-receipts, /period-closings, /bank-reconciliations)
  api: /api/fiscal-years (and /api/fiscal-years?status=open)
  view_type: cross-cutting
  note: "Regression spec for the preferred fiscal year auto-select pattern. Opens each Add dialog, waits for /api/fiscal-years, asserts the FY combobox no longer renders a placeholder string. Covers both backend filter shapes (no filter for AP/AR, ?status=open for period-closings/bank-reconciliations). Guards FiscalYearCollection meta + AsyncSelectField preferredMetaKey wiring landed in wave 13."
  tests:
    - tests/e2e/fiscal-year-auto-select/fiscal-year-auto-select.spec.ts

- slug: ap-aging-report
  route: /reports/ap-aging
  api: /api/reports/ap-aging
  view_type: page
  note: "Non-CRUD laporan aging hutang (AP Aging). Menampilkan summary per supplier dengan bucket umur hutang (Current, 1-30, 31-60, 61-90, >90 hari)."
  tests:
    - tests/e2e/ap-aging-report/ap-aging-report.spec.ts

- slug: ap-outstanding-report
  route: /reports/ap-outstanding
  api: /api/reports/ap-outstanding
  view_type: page
  note: "Non-CRUD laporan outstanding hutang (AP Outstanding). Menampilkan daftar bill yang belum lunas per supplier dengan jumlah outstanding."
  tests:
    - tests/e2e/ap-outstanding-report/ap-outstanding-report.spec.ts

- slug: ap-payment-history-report
  route: /reports/ap-payment-history
  api: /api/reports/ap-payment-history
  view_type: page
  note: "Non-CRUD laporan riwayat pembayaran hutang (AP Payment History). Menampilkan daftar pembayaran yang telah dilakukan per supplier."
  tests:
    - tests/e2e/ap-payment-history-report/ap-payment-history-report.spec.ts

- slug: ar-aging-report
  route: /reports/ar-aging
  api: /api/reports/ar-aging
  view_type: page
  note: "Non-CRUD laporan aging piutang (AR Aging). Menampilkan summary per customer dengan bucket umur piutang (Current, 1-30, 31-60, 61-90, >90 hari)."
  tests:
    - tests/e2e/ar-aging-report/ar-aging-report.spec.ts

- slug: ar-outstanding-report
  route: /reports/ar-outstanding
  api: /api/reports/ar-outstanding
  view_type: page
  note: "Non-CRUD laporan outstanding piutang (AR Outstanding). Menampilkan daftar invoice yang belum lunas per customer dengan jumlah outstanding."
  tests:
    - tests/e2e/ar-outstanding-report/ar-outstanding-report.spec.ts

- slug: customer-statement-report
  route: /reports/customer-statement
  api: /api/reports/customer-statement
  view_type: page
  note: "Non-CRUD laporan statement pelanggan (Customer Statement). Menampilkan ringkasan transaksi per customer dalam periode tertentu."
  tests:
    - tests/e2e/customer-statement-report/customer-statement-report.spec.ts

- slug: per-branch-financial-reports
  route: (cross-cutting — covers /reports/balance-sheet, /reports/trial-balance, /reports/cash-flow, /reports/income-statement, /reports/comparative)
  api: /api/reports/ (balance-sheet, trial-balance, cash-flow, income-statement, comparative)
  view_type: cross-cutting
  note: "Non-CRUD regression spec untuk 5 financial report pages dengan filter branch. Memverifikasi setiap report dapat dimuat dengan branch filter yang berfungsi."
  tests:
    - tests/e2e/per-branch-financial-reports/per-branch-financial-reports.spec.ts

- slug: posting-journals
  route: /posting-journals
  api: POST /api/posting-journals
  view_type: page
  note: "Non-CRUD fitur bulk posting journal entries. Halaman untuk memposting banyak journal entry sekaligus dengan filter periode."
  tests:
    - tests/e2e/posting-journals/post-journal.spec.ts

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

> **Catatan**: Product Categories, Units, Asset Categories, Asset Locations, dan Asset Models secara implementasi menggunakan `createComplexEntityConfig` (bukan simple) karena memiliki kolom/relasi tambahan. Mereka tercatat di Registry Pest — CRUD Complex di bawah meskipun domain-nya relatif sederhana.

---

## Registry Pest — CRUD Complex

| # | Modul | Group | Feature Files | Unit Model | Catatan |
|---|-------|-------|---------------|------------|---------|
| 1 | Employees | `employees` | `EmployeeControllerTest`, `EmployeeExportTest`, `EmployeeImportTest` | `EmployeeTest` | Ada `UpdateEmployeeDataTest` (DTO) |
| 2 | Customers | `customers` | `CustomerControllerTest`, `CustomerExportTest` | `CustomerTest` | — |
| 3 | Suppliers | `suppliers` | `SupplierControllerTest`, `SupplierExportTest` | `SupplierTest` | — |
| 4 | Products | `products` | `ProductControllerTest`, `ProductExportTest` | `ProductTest` | — |
| 5 | Accounts | `accounts` | `AccountControllerTest`, `AccountExportTest` | `AccountTest` | Custom tree-based page (AccountTree + AccountForm), not standard CRUD |
| 6 | Account Mappings | `account-mappings` | `AccountMappingControllerTest`, `AccountMappingExportTest` | `AccountMappingTest` | — |
| 7 | Assets | `assets` | `AssetControllerTest`, `AssetProfileTest` | `AssetTest` | Paling kompleks |
| 8 | Asset Movements | `asset-movements` | `AssetMovementControllerTest`, `AssetMovementExportTest` | `AssetMovementTest` | — |
| 9 | Asset Maintenances | `asset-maintenances` | `AssetMaintenanceControllerTest`, `AssetMaintenanceExportTest` | `AssetMaintenanceTest` | — |
| 10 | COA Versions | `coa-versions` | `CoaVersionControllerTest`, `CoaVersionExportTest` | `CoaVersionTest` | — |
| 11 | Fiscal Years | `fiscal-years` | `FiscalYearControllerTest`, `FiscalYearExportTest` | `FiscalYearTest` | — |
| 12 | Journal Entries | `journal-entries` | `JournalEntryControllerTest`, `JournalEntryExportTest` | `JournalEntryTest` | — |
| 13 | Asset Stocktakes | `asset-stocktakes` | `AssetStocktakeControllerTest`, `AssetStocktakeExportTest` | `AssetStocktakeTest` | — |
| 14 | Pipelines | `pipelines` | `PipelineControllerTest` | `PipelineTest` | — |
| 15 | Approval Flows | `approval-flows` | `ApprovalFlowControllerTest` | `ApprovalFlowTest`, `ApprovalFlowStepTest` | Memiliki `ApprovalFlowFilterServiceTest` |
| 16 | Approval Delegations | `approval-delegations` | `ApprovalDelegationControllerTest`, `ApprovalDelegationExportTest` | `ApprovalDelegationTest` | Memiliki `ApprovalDelegationFilterServiceTest` |
| 17 | Stock Transfers | `stock-transfers` | `StockTransferControllerTest`, `StockTransferExportTest` | `StockTransferTest` | — |
| 18 | Inventory Stocktakes | `inventory-stocktakes` | `InventoryStocktakeControllerTest`, `InventoryStocktakeExportTest` | `InventoryStocktakeTest` | — |
| 19 | Stock Adjustments | `stock-adjustments` | `StockAdjustmentControllerTest`, `StockAdjustmentExportTest` | `StockAdjustmentTest` | — |
| 20 | Purchase Requests | `purchase-requests` | `PurchaseRequestControllerTest`, `PurchaseRequestExportTest` | `PurchaseRequestTest` | Memiliki FilterService, DTO, Resource, Request, Action tests |
| 21 | Purchase Orders | `purchase-orders` | `PurchaseOrderControllerTest`, `PurchaseOrderExportTest` | `PurchaseOrderTest` | Memiliki FilterService, DTO, Resource, Request, Action tests |
| 22 | Goods Receipts | `goods-receipts` | `GoodsReceiptControllerTest`, `GoodsReceiptExportTest` | `GoodsReceiptTest` | Memiliki FilterService, DTO, Resource, Request, Action tests |
| 23 | Supplier Returns | `supplier-returns` | `SupplierReturnControllerTest`, `SupplierReturnExportTest` | `SupplierReturnTest` | Memiliki FilterService, DTO, Resource, Request, Action tests |
| 24 | Product Categories | `product-categories` | `ProductCategoryControllerTest`, `ProductCategoryExportTest` | `ProductCategoryTest` | Menggunakan `createComplexEntityConfig` |
| 25 | Units | `units` | `UnitControllerTest`, `UnitExportTest` | `UnitTest` | Menggunakan `createComplexEntityConfig` |
| 26 | Asset Categories | `asset-categories` | `AssetCategoryControllerTest`, `AssetCategoryExportTest` | `AssetCategoryTest` | Menggunakan `createComplexEntityConfig` |
| 27 | Asset Locations | `asset-locations` | `AssetLocationControllerTest`, `AssetLocationExportTest` | `AssetLocationTest` | Menggunakan `createComplexEntityConfig` |
| 28 | Asset Models | `asset-models` | `AssetModelControllerTest`, `AssetModelExportTest` | `AssetModelTest` | Menggunakan `createComplexEntityConfig` |
| 29 | Report Configurations | `report-configurations` | `ReportConfigurationControllerTest`, `ReportConfigurationExportTest` | `ReportConfigurationTest` | — |
| 30 | Budgets | `budgets` | `BudgetControllerTest`, `BudgetExportTest` | `BudgetTest` | Memiliki `BudgetVarianceReportControllerTest` |
| 31 | Supplier Bills | `supplier-bills` | `SupplierBillControllerTest`, `SupplierBillExportTest` | `SupplierBillTest` | Memiliki FilterService, DTO, Resource, Request, Action tests |
| 32 | AP Payments | `ap-payments` | `ApPaymentControllerTest`, `ApPaymentExportTest` | `ApPaymentTest` | Memiliki FilterService, DTO, Resource, Request, Action tests |
| 33 | Customer Invoices | `customer-invoices` | `CustomerInvoiceControllerTest`, `CustomerInvoiceExportTest` | `CustomerInvoiceTest` | Memiliki FilterService, DTO, Resource, Request, Action tests |
| 34 | AR Receipts | `ar-receipts` | `ArReceiptControllerTest`, `ArReceiptExportTest` | `ArReceiptTest` | Memiliki FilterService, DTO, Resource, Request, Action tests |
| 35 | Credit Notes | `credit-notes` | `CreditNoteControllerTest`, `CreditNoteExportTest` | `CreditNoteTest` | Memiliki FilterService, DTO, Resource, Request, Action tests |
| 36 | Period Closings | `period-closings` | `PeriodClosingControllerTest` | `PeriodClosingTest` | — |
| 37 | Bank Reconciliations | `bank-reconciliations` | `BankReconciliationControllerTest`, `BankReconciliationExportTest` | `BankReconciliationTest` | — |
| 38 | Recurring Journals | `recurring-journals` | `RecurringJournalControllerTest`, `RecurringJournalExportTest` | `RecurringJournalTest` | — |
| 39 | Warehouses | `warehouses` | `WarehouseControllerTest`, `WarehouseExportTest` | `WarehouseTest` | Memiliki FilterService, Resource, Collection tests |

---

## Registry Pest — Non-CRUD

| # | Modul | Group | Files | Catatan |
|---|-------|-------|-------|---------|
| 1 | Auth | `auth` | `Feature/Auth/*.php` (3 files) | Sudah di subfolder |
| 2 | Settings | `settings` | — | Direktori belum ada, test Settings tidak ditemukan |
| 3 | Dashboard | `dashboard` | `Feature/Dashboard/DashboardTest.php` | — |
| 4 | Permissions | `permissions` | `Feature/Permissions/PermissionControllerTest.php` | — |
| 5 | Users | `users` | `Feature/Users/UserControllerTest.php` | — |
| 6 | Reports | `reports` | `Feature/Reports/*.php` (25 files) | Ada legacy + new tests |
| 7 | Posting Journals | `posting-journals` | `Feature/PostingJournals/PostingJournalTest.php` | — |
| 8 | Asset Depreciation Runs | `asset-depreciation-runs` | `Feature/AssetDepreciationRuns/*.php` | — |
| 9 | Asset Reports | `asset-reports` | `Feature/Assets/AssetRegisterTest.php` | Laporan Asset Register |
| 10 | Book Value Reports | `book-value-depreciation-reports` | `Feature/Reports/BookValueDepreciationReportTest.php` | Laporan Book Value & Depreciation |
| 11 | Stocktake Variance Reports | `asset-stocktakes` | `Feature/AssetStocktakes/AssetStocktakeVarianceControllerTest.php` | Laporan Stocktake Variance |
| 12 | Entity State Actions | `entity-state-actions` | `Feature/EntityStates/EntityStateControllerTest.php` | Pipeline actions engine per entitas |
| 13 | Entity State Timeline | `entity-state-timeline` | `Feature/EntityStates/EntityStateTimelineTest.php` | Timeline history component per entitas |
| 14 | Pipeline Audit Trail | `pipeline-audit-trail` | `Feature/PipelineAuditTrail/PipelineAuditTrailControllerTest.php` | Log seluruh transisi state pipeline |
| 15 | Approval Audit Trail | `approval-audit-trail` | `Feature/ApprovalAuditTrail/ApprovalAuditTrailControllerTest.php` | Log seluruh histori approval |
| 16 | Asset Dashboard | `asset-dashboard` | `Feature/AssetDashboard/AssetDashboardControllerTest.php` | Dashboard Visualisasi Aset |
| 17 | Admin Settings | `admin-settings` | `Feature/AdminSettings/AdminSettingControllerTest.php`, `Unit/Models/SettingTest.php` | Non-CRUD settings page (General, Regional) |
| 18 | My Approvals | `my-approvals` | `Feature/MyApprovalControllerTest.php` | Inbox for user to approve/reject documents |
| 19 | Approval History | `approval-history` | `Feature/EntityApprovalHistoryControllerTest.php` | Component showing timeline history of approvals per entity |
| 20 | Approval Monitoring | `approval-monitoring` | `Feature/ApprovalMonitoring/ApprovalMonitoringControllerTest.php` | Monitoring dashboard connecting to GetApprovalMonitoringDataAction |
| 21 | Stock Movements | `stock-movements` | `Feature/StockMovements/*.php` (2 files) | Kartu stok (read-only) + export |
| 22 | Stock Monitor | `stock-monitor` | `Feature/StockMonitor/StockMonitorControllerTest.php` | Dashboard stok per produk & gudang dengan low stock threshold |
| 23 | Inventory Valuation Report | `inventory-valuation-report` | `Feature/Reports/InventoryValuationReportTest.php` | Laporan nilai persediaan per produk per gudang + export |
| 24 | Stock Movement Report | `stock-movement-report` | `Feature/Reports/StockMovementReportTest.php` | Laporan pergerakan stok per periode + export |
| 25 | Inventory Stocktake Variance Report | `inventory-stocktake-variance-report` | `Feature/Reports/InventoryStocktakeVarianceReportTest.php` | Laporan variance stock opname inventory + export |
| 26 | Stock Adjustment Report | `stock-adjustment-report` | `Feature/Reports/StockAdjustmentReportTest.php` | Laporan penyesuaian stok per tipe/periode/gudang + export |
| 27 | Purchase Order Status Report | `purchase-order-status-report` | `Feature/Reports/PurchaseOrderStatusReportTest.php` | Laporan monitoring status PO (outstanding, partially received, closed) + export |
| 28 | Purchase History Report | `purchase-history-report` | `Feature/Reports/PurchaseHistoryReportTest.php` | Laporan riwayat pembelian per supplier/produk/periode + export |
| 29 | Goods Receipt Report | `goods-receipt-report` | `Feature/Reports/GoodsReceiptReportTest.php` | Laporan penerimaan barang per periode/supplier/gudang + export |
| 30 | Aging Dashboard | `aging-dashboard` | `Feature/AgingDashboard/AgingDashboardControllerTest.php` | AR/AP aging dashboard with 5 buckets + top 10 overdue customers/suppliers + branch filter |

---

## Struktur Test Target

```

tests/
├── Feature/{ModuleName}/
│ ├── {Module}ControllerTest.php
│ └── {Module}ExportTest.php
├── Unit/
│ ├── Models/{Module}Test.php
│ ├── Actions/{ModuleName}/*Test.php
│ ├── Domain/{ModuleName}/*Test.php
│ ├── Requests/{ModuleName}/*Test.php
│ └── Resources/{ModuleName}/*Test.php
└── e2e/{module-slug}/
├── helpers.ts
└── {module}.spec.ts

````

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
````

---

## 9 E2E Test Cases per Modul

| #   | Test Case | Assertion Kunci                                                 |
| --- | --------- | --------------------------------------------------------------- |
| 1   | Search    | Row dengan identifier terlihat                                  |
| 2   | Filters   | Hasil tabel berubah sesuai filter                               |
| 3   | Add       | Dialog tertutup, entity muncul                                  |
| 4   | View      | Dialog/page menampilkan data                                    |
| 5   | Edit      | Data terupdate setelah save                                     |
| 6   | Export    | Kolom Excel ⊇ kolom DataTable                                   |
| 7   | Checkbox  | Body punya checkbox, header TIDAK punya                         |
| 8   | Sorting   | Semua sortable columns bisa diklik                              |
| 9   | Delete    | Entity terhapus dari tabel                                      |
| 10  | Import    | Dialog import tampil dan berhasil memicu notifikasi hasil/error |

---

## Known Issues & Notes

- `journal-entries`: Actions menggunakan icon buttons (Eye, Pencil, Trash) bukan dropdown menu
- `assets`: `view_type: page` — navigasi ke `/assets/{ulid}` bukan dialog
