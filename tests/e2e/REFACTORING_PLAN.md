# E2E Test Refactoring Plan

> **INSTRUKSI UNTUK AI EXECUTOR:**
> Dokumen ini adalah rencana refactoring E2E tests untuk project ERP.
> Eksekusi fase-fase di bawah secara berurutan. Tandai `[x]` setelah selesai.
> **PENTING:** Sebelum mengubah test code, pertimbangkan apakah ada perubahan
> frontend atau backend yang diperlukan agar test code bisa konsisten dan minim duplikasi.
> Contoh: jika selector test berbeda antar modul karena komponen frontend inkonsisten,
> **perbaiki frontend dulu** agar semua modul konsisten, baru tulis test.

---

## KONTEKS PROJECT

### Lokasi File Penting

| File/Folder | Path | Deskripsi |
|-------------|------|-----------|
| E2E Tests Root | `@/tests/e2e/` | Semua file E2E test |
| Shared Helpers | `@/tests/e2e/helpers.ts` | 2792 baris — helper functions untuk semua modul (PERLU DIPECAH) |
| Simple CRUD Factory | `@/tests/e2e/simple-crud-tests.ts` | Factory function yang TIDAK digunakan (PERLU DIGANTI) |
| Frontend Columns | `@/resources/js/components/{module}/{Module}Columns.tsx` | Definisi kolom DataTable per modul |
| Frontend Column Utils | `@/resources/js/utils/columns.tsx` | Shared column builders (createTextColumn, createSortingHeader, createSelectColumn, dll) |
| Entity Configs | `@/resources/js/utils/entityConfigs.ts` | Config semua entity (simple/complex classification) |
| Backend Export Actions | `@/app/Actions/{Module}/Export{Module}Action.php` | Kolom export per modul |
| Backend Filter Services | `@/app/Services/{Module}FilterService.php` | Logic sorting/filter per modul |

### Teknologi
- **Test Runner**: Playwright
- **Frontend**: React + Inertia.js + shadcn/ui
- **Backend**: Laravel
- **DataTable**: TanStack Table v8

---

## REGISTRY MODUL

Setiap modul memiliki informasi yang diperlukan untuk menulis test. Gunakan data ini sebagai referensi saat implementasi.

### Simple CRUD (menggunakan `createSimpleEntityConfig` + `createSimpleEntityColumns`)

Modul simple CRUD semuanya memiliki kolom yang sama: **Select, Name, Created At, Updated At, Actions**.
Sortable columns: `Name`, `Created At`, `Updated At`.
Frontend page: menggunakan `createEntityCrudPage()` dari `@/components/common/EntityCrudPage.tsx`.
Form: menggunakan `SimpleEntityForm` dari `@/components/common/EntityForm.tsx`.
View: menggunakan `SimpleEntityViewModal` dari `@/components/common/SimpleEntityViewModal.tsx`.

```yaml
- slug: departments
  route: /departments
  api: /api/departments
  export_api: /api/departments/export
  search_placeholder: "Search departments..."
  sortable_columns: [Name, Created At, Updated At]
  view_type: dialog
  view_dialog_title: "Department Details"  # cek SimpleEntityViewModal
  checkbox_header: false  # createSelectColumn() → header: () => null

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

### Complex CRUD (menggunakan `createComplexEntityConfig` + custom `{Module}Columns.tsx`)

```yaml
- slug: product-categories
  route: /product-categories
  api: /api/product-categories
  export_api: /api/product-categories/export
  search_placeholder: "Search product categories..."
  columns_file: "@/resources/js/components/product-categories/ProductCategoryColumns.tsx"
  sortable_columns: [Name, Created At, Updated At]
  non_sortable_columns: [Description]
  view_type: dialog
  checkbox_header: false

- slug: units
  route: /units
  api: /api/units
  export_api: /api/units/export
  search_placeholder: "Search units..."
  columns_file: "@/resources/js/components/units/UnitColumns.tsx"
  sortable_columns: [Name, Created At, Updated At]
  non_sortable_columns: [Symbol]
  view_type: dialog
  checkbox_header: false

- slug: employees
  route: /employees
  api: /api/employees
  export_api: /api/employees/export
  search_placeholder: "Search employees..."
  columns_file: "@/resources/js/components/employees/EmployeeColumns.tsx"
  sortable_columns: [Name, Email, Phone, Department, Position, Branch, Salary, Hire Date]
  view_type: dialog
  checkbox_header: false

- slug: customers
  route: /customers
  api: /api/customers
  export_api: /api/customers/export
  search_placeholder: "Search customers..."
  columns_file: "@/resources/js/components/customers/CustomerColumns.tsx"
  sortable_columns: [Name, Email, Phone, Branch, Category, Status]
  view_type: dialog
  checkbox_header: false

- slug: suppliers
  route: /suppliers
  api: /api/suppliers
  export_api: /api/suppliers/export
  search_placeholder: "Search suppliers..."
  columns_file: "@/resources/js/components/suppliers/SupplierColumns.tsx"
  sortable_columns: [Name, Email, Phone, Branch, Category, Status]
  view_type: dialog
  checkbox_header: false

- slug: products
  route: /products
  api: /api/products
  export_api: /api/products/export
  search_placeholder: "Search products..."
  columns_file: "@/resources/js/components/products/ProductColumns.tsx"
  sortable_columns: [Code, Name, Type, Category, Cost, Price, Status]
  view_type: dialog
  checkbox_header: false

- slug: assets
  route: /assets
  api: /api/assets
  export_api: /api/assets/export
  search_placeholder: "Search assets..."
  columns_file: "@/resources/js/components/assets/AssetColumns.tsx"
  sortable_columns: [Code, Name, Category, Branch, Status, Cost, Purchase Date]
  view_type: page  # navigasi ke /assets/{ulid}
  view_url_pattern: "/assets/\\w+"
  checkbox_header: false

- slug: asset-categories
  route: /asset-categories
  api: /api/asset-categories
  export_api: /api/asset-categories/export
  search_placeholder: "Search asset categories..."
  columns_file: "@/resources/js/components/asset-categories/AssetCategoryColumns.tsx"
  sortable_columns: [Code, Name, Default Useful Life (Months), Created At, Updated At]
  view_type: dialog
  checkbox_header: false

- slug: asset-models
  route: /asset-models
  api: /api/asset-models
  export_api: /api/asset-models/export
  search_placeholder: "Search asset models..."
  columns_file: "@/resources/js/components/asset-models/AssetModelColumns.tsx"
  sortable_columns: [Model Name, Manufacturer, Category]
  non_sortable_columns: [Specs]
  view_type: dialog
  checkbox_header: false

- slug: asset-locations
  route: /asset-locations
  api: /api/asset-locations
  export_api: /api/asset-locations/export
  search_placeholder: "Search asset locations..."
  columns_file: "@/resources/js/components/asset-locations/AssetLocationColumns.tsx"
  sortable_columns: [Code, Name, Branch, Parent Location]
  view_type: dialog
  checkbox_header: false

- slug: asset-movements
  route: /asset-movements
  api: /api/asset-movements
  export_api: /api/asset-movements/export
  search_placeholder: "Search movements..."
  columns_file: "@/resources/js/components/asset-movements/AssetMovementColumns.tsx"
  sortable_columns: [Asset, Type, Date, Ref/Notes, PIC]
  non_sortable_columns: [Origin, Destination]
  view_type: dialog
  checkbox_header: true  # ⚠️ BUG: HARUS false — lihat INSTRUKSI FIX FRONTEND di bawah

- slug: fiscal-years
  route: /fiscal-years
  api: /api/fiscal-years
  export_api: /api/fiscal-years/export
  search_placeholder: "Search fiscal years..."
  columns_file: "@/resources/js/components/fiscal-years/FiscalYearColumns.tsx"
  sortable_columns: [Name, Start Date, End Date, Status, Created At]
  view_type: dialog
  checkbox_header: false

- slug: coa-versions
  route: /coa-versions
  api: /api/coa-versions
  export_api: /api/coa-versions/export
  search_placeholder: "Search coa versions..."
  columns_file: "@/resources/js/components/coa-versions/CoaVersionColumns.tsx"
  sortable_columns: [Name, Fiscal Year, Status, Created At]
  view_type: dialog
  checkbox_header: false

- slug: account-mappings
  route: /account-mappings
  api: /api/account-mappings
  export_api: /api/account-mappings/export
  search_placeholder: "Search account mappings..."
  columns_file: "@/resources/js/components/account-mappings/AccountMappingColumns.tsx"
  sortable_columns: [Source Account, Target Account, Type, Created At]
  view_type: dialog
  checkbox_header: false
  has_local_helpers: true  # sudah punya tests/e2e/account-mappings/helpers.ts

- slug: journal-entries
  route: /journal-entries
  api: /api/journal-entries
  export_api: /api/journal-entries/export
  search_placeholder: "Search journal entries..."
  columns_file: "@/resources/js/components/journal-entries/JournalEntryColumns.tsx"
  sortable_columns: [Entry Number, Date, Description, Reference, Total Amount, Status]
  view_type: dialog
  checkbox_header: false
  note: "Actions menggunakan icon buttons (Eye, Pencil, Trash) bukan dropdown menu"
```

---

## 9 TEST CASES YANG WAJIB ADA PER MODUL

Setiap modul CRUD harus memiliki test untuk:

| # | Test Case | Deskripsi | Assertion Kunci |
|---|-----------|-----------|-----------------|
| 1 | **Search** | Buat entity → search by identifier → verify row visible | Row dengan identifier terlihat di tabel |
| 2 | **Filters** | Buka filter dialog → pilih filter → verify hasil terfilter | Hasil tabel berubah sesuai filter |
| 3 | **Add** | Klik Add → isi form → submit → verify entity terbuat | Dialog tertutup, entity muncul di tabel |
| 4 | **View** | Klik Actions → View → verify detail ditampilkan | Dialog/page menampilkan data entity |
| 5 | **Edit** | Klik Actions → Edit → ubah data → submit → verify perubahan | Dialog tertutup, data terupdate |
| 6 | **Export** | Klik Export → download file → verify SEMUA kolom DataTable ada di Excel | Kolom Excel ⊇ kolom DataTable |
| 7 | **Checkbox** | Verify: row body PUNYA checkbox, row head TIDAK punya checkbox | `thead checkbox count = 0`, `tbody checkbox visible = true` |
| 8 | **Sorting** | Untuk SETIAP kolom sortable: klik header → verify tidak crash, URL/response berubah | Semua `sortable_columns` dari registry di atas harus ditest |
| 9 | **Delete** | Klik Actions → Delete → confirm → verify entity terhapus | Row entity tidak terlihat lagi |

---

## FASE IMPLEMENTASI

### Fase 0: Pertimbangkan Refactoring Frontend & Backend

> **SEBELUM menulis test code, periksa dan perbaiki inkonsistensi di frontend/backend yang menyebabkan test tidak bisa ditulis secara konsisten.**

#### 0.1 Frontend: Konsistenkan Checkbox Column

- [ ] **Periksa** `@/resources/js/components/asset-movements/AssetMovementColumns.tsx`
  - Modul ini menggunakan custom select column dengan HEADER CHECKBOX
  - Semua modul lain menggunakan `createSelectColumn()` yang TIDAK punya header checkbox
- [ ] **Perbaiki** — ganti custom select column menjadi `createSelectColumn()` agar konsisten  
  - File: `@/resources/js/components/asset-movements/AssetMovementColumns.tsx`
  - Ganti block custom select (baris `id: 'select'` dengan `header: ({ table }) =>`) menjadi `createSelectColumn<AssetMovement>()`

#### 0.2 Frontend: Konsistenkan Actions Column

- [ ] **Periksa** `@/resources/js/components/journal-entries/JournalEntryColumns.tsx`
  - Modul ini menggunakan custom actions column dengan icon buttons (Eye, Pencil, Trash) dan conditional rendering
  - Semua modul lain menggunakan `createActionsColumn()` yang render dropdown menu
- [ ] **Pertimbangkan** apakah perlu diganti ke `createActionsColumn()` agar E2E test bisa menggunakan selector yang sama (Actions dropdown → menu item pattern)
  - Jika TIDAK diganti: test `journal-entries` perlu custom logic di helpers untuk view/edit/delete
  - Jika DIGANTI: semua modul konsisten, test factory bisa dipakai seragam

#### 0.3 Backend: Verifikasi Sorting Support

- [ ] Untuk setiap modul, periksa apakah SEMUA `sortable_columns` di registry di atas didukung oleh backend
  - Cek validation rules di `@/app/Http/Requests/{Module}/Index{Module}Request.php` → field `sort_by`
  - Cek sorting logic di `@/app/Services/{Module}FilterService.php` atau `@/app/Actions/{Module}/Index{Module}Action.php`
- [ ] **Jika ada kolom sortable di frontend tapi TIDAK didukung backend:**
  - Opsi A: Tambah support sorting di backend (update validation + query)
  - Opsi B: Hapus sorting di frontend (set `enableSorting: false` di kolom tersebut)

#### 0.4 Backend: Verifikasi Export Columns

- [ ] Untuk setiap modul, periksa bahwa file `@/app/Actions/{Module}/Export{Module}Action.php` mengexport SEMUA kolom yang tampil di DataTable
  - Kolom export MINIMAL harus mencakup kolom-kolom DataTable
  - Boleh punya kolom tambahan (misal ID, foreign key details)
- [ ] **Jika ada kolom DataTable yang TIDAK diexport:**
  - Tambahkan kolom tersebut ke Export Action

---

### Fase 1: Buat `shared-test-factories.ts`

- [ ] **Hapus** file lama: `@/tests/e2e/simple-crud-tests.ts`
- [ ] **Buat** file baru: `@/tests/e2e/shared-test-factories.ts`
- [ ] Implementasi `ModuleTestConfig` interface:

```typescript
import { test, expect, Page } from '@playwright/test';
import { login } from './helpers';
import * as fs from 'fs';
import * as path from 'path';
import ExcelJS from 'exceljs';

export interface FilterTestConfig {
  filterName: string;      // Label filter di UI
  filterType: 'combobox' | 'select' | 'text';
  filterValue: string;     // Nilai yang dipilih/diisi
  expectedText: string;    // Text yang diharapkan muncul di hasil
}

export interface ModuleTestConfig {
  // Identitas
  entityName: string;
  entityNamePlural: string;
  route: string;
  apiPath: string;

  // Callbacks
  createEntity: (page: Page) => Promise<string>;  // return identifier
  searchEntity: (page: Page, identifier: string) => Promise<void>;
  editEntity?: (page: Page, identifier: string, updates: Record<string, string>) => Promise<void>;
  editUpdates?: Record<string, string>;

  // DataTable
  sortableColumns: string[];

  // View
  viewType: 'dialog' | 'page';
  viewDialogTitle?: string;    // required jika viewType = 'dialog'
  viewUrlPattern?: RegExp;     // required jika viewType = 'page'

  // Export
  exportApiPath: string;
  expectedExportColumns: string[];

  // Filter (opsional)
  filterTests?: FilterTestConfig[];
}
```

- [ ] Implementasi `generateModuleTests(config)` yang menghasilkan 9 test cases
- [ ] Standarisasi semua test pattern:
  - **Wait strategy**: `page.waitForResponse(r => r.url().includes(config.apiPath) && r.status() < 400).catch(() => null)`
  - **Checkbox selector**: `page.locator('thead').locator('button[role="checkbox"], input[type="checkbox"]')` untuk header, `page.locator('tbody tr').first().locator('button[role="checkbox"]')` untuk body
  - **Export**: `page.waitForEvent('download')` → save → ExcelJS verify kolom
  - **Sorting**: Iterasi `config.sortableColumns`, `page.getByRole('button', { name: col, exact: true })`, klik 2x

---

### Fase 2: Pecah `helpers.ts` Per Modul

- [ ] **Refactor** `@/tests/e2e/helpers.ts` — pertahankan HANYA shared utilities:
  - `login(page, email?, password?)`
  - `createEntity(page, config, overrides?)` (generic entity creation)
  - `fillAsyncSelect(page, ...)` (shared async select interaction)
  - Utility functions lainnya yang dipakai lintas modul
- [ ] **Buat** `@/tests/e2e/{module}/helpers.ts` untuk SETIAP modul:
  - Pindahkan module-specific functions dari `helpers.ts` ke file per-modul
  - Setiap file harus export minimal: `create{Entity}`, `search{Entity}`
  - Opsional export: `edit{Entity}`, `delete{Entity}` jika logiknya custom

Daftar file yang harus dibuat (skip jika sudah ada):

```
tests/e2e/departments/helpers.ts
tests/e2e/positions/helpers.ts
tests/e2e/branches/helpers.ts
tests/e2e/supplier-categories/helpers.ts
tests/e2e/customer-categories/helpers.ts
tests/e2e/product-categories/helpers.ts
tests/e2e/units/helpers.ts
tests/e2e/employees/helpers.ts
tests/e2e/customers/helpers.ts
tests/e2e/suppliers/helpers.ts
tests/e2e/products/helpers.ts
tests/e2e/assets/helpers.ts
tests/e2e/asset-categories/helpers.ts
tests/e2e/asset-models/helpers.ts
tests/e2e/asset-locations/helpers.ts
tests/e2e/asset-movements/helpers.ts
tests/e2e/fiscal-years/helpers.ts
tests/e2e/coa-versions/helpers.ts
tests/e2e/account-mappings/helpers.ts  # ✅ SUDAH ADA — jangan timpa
tests/e2e/journal-entries/helpers.ts
```

- [ ] **Update** semua import paths di spec files yang sudah ada

---

### Fase 3: Migrasi Simple CRUD (Pilot)

Untuk setiap modul simple CRUD, buat 1 file spec menggunakan `generateModuleTests()`:

- [ ] `tests/e2e/departments/department.spec.ts`
- [ ] `tests/e2e/positions/position.spec.ts`
- [ ] `tests/e2e/branches/branch.spec.ts`
- [ ] `tests/e2e/supplier-categories/supplier-category.spec.ts`
- [ ] `tests/e2e/customer-categories/customer-category.spec.ts`

Setelah migrasi berhasil dan semua test pass:
- [ ] **Hapus** 9 file spec lama per modul (add-*.spec.ts, edit-*.spec.ts, dll)
- [ ] **Verifikasi**: `npx playwright test tests/e2e/departments/ tests/e2e/positions/ tests/e2e/branches/ tests/e2e/supplier-categories/ tests/e2e/customer-categories/`

---

### Fase 4: Migrasi Complex CRUD

Urutkan dari yang paling simpel ke yang paling kompleks:

**Batch 1 — Form sederhana (mirip simple CRUD):**
- [ ] product-categories
- [ ] units
- [ ] asset-categories

**Batch 2 — Form dengan AsyncSelect (relasi FK):**
- [ ] employees
- [ ] customers
- [ ] suppliers
- [ ] products

**Batch 3 — Form khusus / actions non-standar:**
- [ ] assets (view type = page, bukan dialog)
- [ ] asset-models
- [ ] asset-locations
- [ ] asset-movements (perlu fix checkbox dulu — Fase 0)
- [ ] fiscal-years
- [ ] coa-versions
- [ ] account-mappings (sudah punya local helpers)
- [ ] journal-entries (actions pakai icon buttons, bukan dropdown)

Untuk modul yang punya kebutuhan test custom di luar 9 test standar:
- Tulis test tambahan di bawah `generateModuleTests()` call
- Contoh: `assets` mungkin perlu test navigasi ke profile page

- [ ] **Verifikasi per batch**: `npx playwright test tests/e2e/{module}/`

---

### Fase 5: Final Verification

- [ ] Jalankan SEMUA E2E tests: `npx playwright test tests/e2e/`
- [ ] Verifikasi jumlah test case = 20 modul × 9 tests = minimal 180 test cases
- [ ] Pastikan TIDAK ada file spec lama yang tertinggal (yang sudah digantikan)
- [ ] Pastikan `helpers.ts` shared sudah ramping (target: < 300 baris)
- [ ] Review: setiap modul punya `{module}/helpers.ts` + `{module}/{entity}.spec.ts`
