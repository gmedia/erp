# Test Refactoring Plan — Feature & Unit Tests

> **Goal**: Reorganisasi file test Feature & Unit agar konsisten per-modul, mudah dijalankan via `--group`, dan mudah di-maintain oleh AI atau developer.

---

## Daftar Isi

1. [Status Saat Ini (Masalah)](#status-saat-ini)
2. [Struktur Target](#struktur-target)
3. [Standar Group Naming](#standar-group-naming)
4. [Registry Modul](#registry-modul)
5. [Instruksi Refactoring Per-Modul](#instruksi-refactoring-per-modul)
6. [Prompt Refactoring Per-Modul](#prompt-refactoring-per-modul)

---

## Status Saat Ini

### Masalah Utama

| # | Masalah | Dampak |
|---|---------|--------|
| 1 | **File Feature test flat** di root `tests/Feature/` | Tidak bisa navigasi per-modul |
| 2 | **File Unit model test flat** di root `tests/Unit/` | Inkonsisten dengan Requests/Actions/Resources yang sudah di subfolder |
| 3 | **Duplikasi**: `AssetControllerTest.php` ada di root DAN `Assets/` | Bingung mana yang aktif |
| 4 | **Group tag inkonsisten**: beberapa pakai `('assets', 'asset-feature')`, beberapa hanya `('departments')` | Tidak bisa filter satu modul secara bersih |
| 5 | **Extra group tags** seperti `'unit'`, `'models'`, `'actions'`, `'requests'`, `'resources'`, `'domain'` | Noise, tidak diperlukan |
| 6 | **Permission setup inkonsisten**: sebagian pakai `createTestUserWithPermissions()`, sebagian manual `Permission::firstOrCreate` | Copy-paste error prone |
| 7 | **File tanpa group**: `UpdateEmployeeDataTest.php`, `ExampleTest.php` | Tidak bisa difilter |
| 8 | **Typo group**: `supplier_categories` (underscore) vs `supplier-categories` (kebab-case) | Group mismatch |
| 9 | **AssetModelTest.php** pakai `->group()` PER-TEST bukan file-level `uses()` | Inkonsisten pattern |
| 10 | **AssetModelTest.php** pakai `uses(TestCase::class, RefreshDatabase::class)->in('Unit')` yang redundan dengan `Pest.php` | Override global config |

---

## Struktur Target

```
tests/
├── Feature/
│   ├── Auth/                      # (tetap, non-CRUD)
│   │   ├── AuthenticationTest.php
│   │   ├── ...
│   ├── Settings/                  # (tetap, non-CRUD)
│   │   ├── PasswordUpdateTest.php
│   │   ├── ...
│   ├── Accounts/
│   │   ├── AccountControllerTest.php
│   │   └── AccountExportTest.php
│   ├── AccountMappings/
│   │   ├── AccountMappingControllerTest.php
│   │   └── AccountMappingExportTest.php
│   ├── Assets/
│   │   ├── AssetControllerTest.php      # ← merge/pilih satu
│   │   ├── AssetExportTest.php
│   │   ├── AssetFilteredExportTest.php
│   │   └── AssetProfileTest.php
│   ├── AssetCategories/
│   │   ├── AssetCategoryControllerTest.php
│   │   └── AssetCategoryExportTest.php
│   ├── AssetLocations/
│   │   ├── AssetLocationControllerTest.php
│   │   └── AssetLocationExportTest.php
│   ├── AssetModels/
│   │   ├── AssetModelControllerTest.php
│   │   └── AssetModelExportTest.php
│   ├── AssetMovements/
│   │   ├── AssetMovementControllerTest.php
│   │   └── AssetMovementExportTest.php
│   ├── Branches/
│   │   ├── BranchControllerTest.php
│   │   └── BranchExportTest.php
│   ├── CoaVersions/
│   │   ├── CoaVersionControllerTest.php
│   │   └── CoaVersionExportTest.php
│   ├── CustomerCategories/
│   │   ├── CustomerCategoryControllerTest.php
│   │   └── CustomerCategoryExportTest.php
│   ├── Customers/
│   │   ├── CustomerControllerTest.php
│   │   └── CustomerExportTest.php
│   ├── Dashboard/
│   │   └── DashboardTest.php
│   ├── Departments/
│   │   ├── DepartmentControllerTest.php
│   │   └── DepartmentExportTest.php
│   ├── Employees/
│   │   ├── EmployeeControllerTest.php
│   │   └── EmployeeExportTest.php
│   ├── FiscalYears/
│   │   ├── FiscalYearControllerTest.php
│   │   └── FiscalYearExportTest.php
│   ├── JournalEntries/
│   │   ├── JournalEntryControllerTest.php
│   │   └── JournalEntryExportTest.php
│   ├── Permissions/
│   │   └── PermissionControllerTest.php
│   ├── Positions/
│   │   ├── PositionControllerTest.php
│   │   └── PositionExportTest.php
│   ├── PostingJournals/
│   │   └── PostingJournalTest.php
│   ├── ProductCategories/
│   │   ├── ProductCategoryControllerTest.php
│   │   └── ProductCategoryExportTest.php
│   ├── Products/
│   │   ├── ProductControllerTest.php
│   │   └── ProductExportTest.php
│   ├── Reports/
│   │   ├── FinancialReportsTest.php
│   │   ├── BalanceSheetReportTest.php
│   │   ├── IncomeStatementReportTest.php
│   │   ├── TrialBalanceReportTest.php
│   │   └── ComparativeReportTest.php
│   ├── SupplierCategories/
│   │   ├── SupplierCategoryControllerTest.php
│   │   └── SupplierCategoryExportTest.php
│   ├── Suppliers/
│   │   ├── SupplierControllerTest.php
│   │   └── SupplierExportTest.php
│   ├── Units/
│   │   ├── UnitControllerTest.php
│   │   └── UnitExportTest.php
│   └── Users/
│       └── UserControllerTest.php
│
├── Unit/
│   ├── Models/                   # ← pindahkan semua *Test.php flat ke sini
│   │   ├── AccountTest.php
│   │   ├── AccountMappingTest.php
│   │   ├── AssetTest.php
│   │   ├── AssetCategoryTest.php
│   │   ├── AssetLocationTest.php
│   │   ├── AssetModelTest.php
│   │   ├── AssetMovementTest.php
│   │   ├── BranchTest.php
│   │   ├── CoaVersionTest.php
│   │   ├── CustomerTest.php
│   │   ├── CustomerCategoryTest.php
│   │   ├── DepartmentTest.php
│   │   ├── EmployeeTest.php
│   │   ├── FiscalYearTest.php
│   │   ├── JournalEntryTest.php
│   │   ├── PositionTest.php
│   │   ├── ProductTest.php
│   │   ├── ProductCategoryTest.php
│   │   ├── SupplierTest.php
│   │   ├── SupplierCategoryTest.php
│   │   └── UnitTest.php
│   ├── DTOs/
│   │   └── Employees/
│   │       └── UpdateEmployeeDataTest.php
│   ├── Actions/                  # (tetap, sudah terstruktur)
│   │   ├── Accounts/
│   │   ├── AccountMappings/
│   │   ├── ...
│   ├── Requests/                 # (tetap, sudah terstruktur)
│   │   ├── Accounts/
│   │   ├── AccountMappings/
│   │   ├── ...
│   ├── Resources/                # (tetap, sudah terstruktur)
│   │   ├── Accounts/
│   │   ├── AccountMappings/
│   │   ├── ...
│   ├── Domain/                   # (tetap, sudah terstruktur)
│   │   ├── Accounts/
│   │   ├── AccountMappings/
│   │   ├── ...
│   └── Commands/                 # (tetap, sudah terstruktur)
│
├── Pest.php                      # update: add `uses()->in('Unit/Models')` if needed
└── TestCase.php
```

---

## Standar Group Naming

### Aturan

1. **Satu primary group** = nama modul (kebab-case), selalu menjadi argumen **pertama**
2. **Tidak perlu** secondary tags (`'unit'`, `'models'`, `'actions'`, `'requests'`, `'resources'`, `'domain'`, `'export'`, `'asset-unit'`, `'asset-feature'`)
3. **Format**: `uses(RefreshDatabase::class)->group('{module-name}');`
4. **Konsisten kebab-case**: `supplier-categories` (BUKAN `supplier_categories`)

### Contoh

```php
// ✅ BENAR — Feature
uses(RefreshDatabase::class)->group('departments');

// ✅ BENAR — Unit Model
uses(RefreshDatabase::class)->group('departments');

// ✅ BENAR — Unit Action
uses(RefreshDatabase::class)->group('departments');

// ✅ BENAR — Unit Request (tanpa RefreshDatabase jika tidak perlu DB)
uses()->group('departments');

// ❌ SALAH
uses(RefreshDatabase::class)->group('departments', 'actions');     // extra tag
uses(RefreshDatabase::class)->group('assets', 'asset-unit');       // extra tag
uses(RefreshDatabase::class)->group('supplier_categories');         // underscore
```

### Cara Menjalankan Test Per Modul

```bash
# Semua test (Feature + Unit) untuk satu modul:
./vendor/bin/sail test --group departments

# Hanya Feature test untuk satu modul:
./vendor/bin/sail test --group departments tests/Feature/Departments/

# Hanya Unit test untuk satu modul:
./vendor/bin/sail test --group departments tests/Unit/
```

---

## Registry Modul

### CRUD Simple

Tipe ini memiliki satu tabel utama tanpa relasi FK kompleks.

| # | Modul | Group Name | Feature Files | Unit Files (flat) |
|---|-------|------------|---------------|-------------------|
| 1 | Departments | `departments` | `DepartmentControllerTest.php`, `DepartmentExportTest.php` | `DepartmentTest.php` |
| 2 | Positions | `positions` | `PositionControllerTest.php`, `PositionExportTest.php` | `PositionTest.php` |
| 3 | Branches | `branches` | `BranchControllerTest.php`, `BranchExportTest.php` | `BranchTest.php` |
| 4 | Customer Categories | `customer-categories` | `CustomerCategoryControllerTest.php`, `CustomerCategoryExportTest.php` | `CustomerCategoryTest.php` |
| 5 | Supplier Categories | `supplier-categories` | `SupplierCategoryControllerTest.php`, `SupplierCategoryExportTest.php` | `SupplierCategoryTest.php` |
| 6 | Product Categories | `product-categories` | `ProductCategoryControllerTest.php`, `ProductCategoryExportTest.php` | `ProductCategoryTest.php` |
| 7 | Units | `units` | `UnitControllerTest.php`, `UnitExportTest.php` | `UnitTest.php` |
| 8 | Asset Categories | `asset-categories` | `AssetCategoryControllerTest.php`, `AssetCategoryExportTest.php` | `AssetCategoryTest.php` |
| 9 | Asset Locations | `asset-locations` | `AssetLocationControllerTest.php`, `AssetLocationExportTest.php` | `AssetLocationTest.php` |
| 10 | Asset Models | `asset-models` | `AssetModelControllerTest.php`, `AssetModelExportTest.php` | `AssetModelTest.php` |

---

### CRUD Complex

Tipe ini memiliki relasi FK, filter multi-field, dan logic lebih rumit.

| # | Modul | Group Name | Feature Files | Unit Files (flat) | Catatan Khusus |
|---|-------|------------|---------------|-------------------|----------------|
| 1 | Employees | `employees` | `EmployeeControllerTest.php`, `EmployeeExportTest.php` | `EmployeeTest.php` | Ada `UpdateEmployeeDataTest.php` (DTO) → pindah ke `Unit/DTOs/Employees/` |
| 2 | Customers | `customers` | `CustomerControllerTest.php`, `CustomerExportTest.php` | `CustomerTest.php` | — |
| 3 | Suppliers | `suppliers` | `SupplierControllerTest.php`, `SupplierExportTest.php` | `SupplierTest.php` | — |
| 4 | Products | `products` | `ProductControllerTest.php`, `ProductExportTest.php` | `ProductTest.php` | — |
| 5 | Accounts | `accounts` | `AccountControllerTest.php`, `AccountExportTest.php` | `AccountTest.php` | — |
| 6 | Account Mappings | `account-mappings` | `AccountMappingControllerTest.php`, `AccountMappingExportTest.php` | `AccountMappingTest.php` | — |
| 7 | Assets | `assets` | ⚠️ **DUPLIKAT**: root `AssetControllerTest.php` + `Assets/AssetControllerTest.php`; `AssetExportTest.php`, `Assets/AssetFilteredExportTest.php`, `AssetProfileTest.php` | `AssetTest.php` | Harus merge/pilih satu, hapus duplikat |
| 8 | Asset Movements | `asset-movements` | `AssetMovementControllerTest.php`, `AssetMovementExportTest.php` | `AssetMovementTest.php` | — |
| 9 | COA Versions | `coa-versions` | `CoaVersionControllerTest.php`, `CoaVersionExportTest.php` | `CoaVersionTest.php` | — |
| 10 | Fiscal Years | `fiscal-years` | `FiscalYearControllerTest.php`, `FiscalYearExportTest.php` | `FiscalYearTest.php` | — |
| 11 | Journal Entries | `journal-entries` | `JournalEntries/JournalEntryControllerTest.php`, `JournalEntries/JournalEntryExportTest.php` | `JournalEntryTest.php` | Sudah di subfolder (Feature) |

---

### Non-CRUD

| # | Modul | Group Name | Files | Catatan |
|---|-------|------------|-------|---------|
| 1 | Auth | `auth` | `Feature/Auth/*.php` (7 files) | Sudah di subfolder, cek group annotation |
| 2 | Settings | `settings` | `Feature/Settings/*.php` (3 files) | Sudah di subfolder, cek group annotation |
| 3 | Dashboard | `dashboard` | `Feature/DashboardTest.php` | Pindahkan ke `Feature/Dashboard/` |
| 4 | Permissions | `permissions` | `Feature/PermissionControllerTest.php` | Pindahkan ke `Feature/Permissions/` |
| 5 | Users | `users` | `Feature/UserControllerTest.php` | Pindahkan ke `Feature/Users/` |
| 6 | Reports | `reports` | `Feature/FinancialReportsTest.php`, `Feature/Reports/*.php` (4 files) | ⚠️ Duplikat: legacy test di root + new tests di `Reports/` |
| 7 | Posting Journals | `posting-journals` | `Feature/PostingJournalTest.php` | Pindahkan ke `Feature/PostingJournals/` |

---

### Files to Delete/Cleanup

| File | Alasan |
|------|--------|
| `tests/Unit/ExampleTest.php` | Boilerplate, tidak berguna |
| `tests/Feature/AssetControllerTest.php` (root) | Duplikat dengan `tests/Feature/Assets/AssetControllerTest.php` — pilih yang lebih lengkap |
| `tests/Feature/AssetExportTest.php` (root) | Pindah ke `tests/Feature/Assets/` |
| `tests/Feature/FinancialReportsTest.php` (root) | Pindah ke `tests/Feature/Reports/` |

---

## Instruksi Refactoring Per-Modul

### Langkah Umum (Berlaku untuk SEMUA modul)

1. **Buat subfolder** Feature: `tests/Feature/{ModuleName}/`
2. **Pindahkan** file Feature dari root ke subfolder
3. **Pindahkan** file Unit model dari `tests/Unit/` ke `tests/Unit/Models/`
4. **Standarisasi group annotation**: ubah ke hanya `->group('{module-name}')`
5. **Standarisasi permission setup**: gunakan `createTestUserWithPermissions([...])` (bukan manual)
6. **Hapus** namespace yang tidak perlu (Pest tidak memerlukan namespace)
7. **Hapus** `uses(TestCase::class, ...)` yang redundan dengan `Pest.php`
8. **Verifikasi**: `./vendor/bin/sail test --group {module-name}`

### Urutan Eksekusi yang Direkomendasikan

Mulai dari yang paling sederhana untuk membangun confidence:

```
Phase 1 — CRUD Simple (low risk, simple moves):
  1. departments
  2. positions
  3. branches
  4. units
  5. customer-categories
  6. supplier-categories
  7. product-categories
  8. asset-categories
  9. asset-locations
  10. asset-models

Phase 2 — CRUD Complex (more files, need careful merge):
  11. employees
  12. customers
  13. suppliers
  14. products
  15. accounts
  16. account-mappings
  17. coa-versions
  18. fiscal-years
  19. journal-entries
  20. asset-movements
  21. assets (⚠️ paling kompleks, ada duplikat)

Phase 3 — Non-CRUD:
  22. dashboard
  23. permissions
  24. users
  25. posting-journals
  26. reports (⚠️ ada duplikat + legacy)
  27. auth
  28. settings

Phase 4 — Cleanup:
  29. Hapus ExampleTest.php
  30. Verifikasi keseluruhan: ./vendor/bin/sail test
```

---

## Prompt Refactoring Per-Modul

### Template Prompt: CRUD Simple

```
Refactor testing modul `{module-name}` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Simple

Lakukan:
1. Buat folder `tests/Feature/{ModuleName}/`
2. Pindahkan `tests/Feature/{Module}ControllerTest.php` → `tests/Feature/{ModuleName}/{Module}ControllerTest.php`
3. Pindahkan `tests/Feature/{Module}ExportTest.php` → `tests/Feature/{ModuleName}/{Module}ExportTest.php`
4. Pindahkan `tests/Unit/{Module}Test.php` → `tests/Unit/Models/{Module}Test.php`
5. Di SEMUA file yang dipindah + file di `tests/Unit/Actions/{ModuleName}/`, `tests/Unit/Requests/{ModuleName}/`, `tests/Unit/Resources/{ModuleName}/`, `tests/Unit/Domain/{ModuleName}/`:
   - Standarisasi group: `uses(RefreshDatabase::class)->group('{module-name}');` (tanpa extra tags)
   - Hapus namespace jika ada
   - Pastikan permission setup pakai `createTestUserWithPermissions([...])`
6. Verifikasi: `./vendor/bin/sail test --group {module-name}`

Pastikan SEMUA test pass sebelum dan sesudah refactoring.
```

---

### Prompt Per-Modul: CRUD Simple

#### 1. `departments`

```
Refactor testing modul `departments` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Simple

Lakukan:
1. Buat folder `tests/Feature/Departments/`
2. Pindahkan `tests/Feature/DepartmentControllerTest.php` → `tests/Feature/Departments/DepartmentControllerTest.php`
3. Pindahkan `tests/Feature/DepartmentExportTest.php` → `tests/Feature/Departments/DepartmentExportTest.php`
4. Pindahkan `tests/Unit/DepartmentTest.php` → `tests/Unit/Models/DepartmentTest.php`
5. Standarisasi group annotation di SEMUA file terkait:
   - `tests/Feature/Departments/DepartmentControllerTest.php` → `->group('departments')`
   - `tests/Feature/Departments/DepartmentExportTest.php` → `->group('departments')`
   - `tests/Unit/Models/DepartmentTest.php` → `->group('departments')`
   - `tests/Unit/Actions/Departments/*.php` → `->group('departments')` (hapus tag 'actions')
   - `tests/Unit/Requests/Departments/*.php` → `->group('departments')` (hapus tag 'requests')
   - `tests/Unit/Resources/Departments/*.php` → `->group('departments')` (hapus tag 'resources')
   - `tests/Unit/Domain/Departments/*.php` → `->group('departments')` (hapus tag 'domain')
6. Verifikasi: `./vendor/bin/sail test --group departments`
```

#### 2. `positions`

```
Refactor testing modul `positions` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Simple

Lakukan:
1. Buat folder `tests/Feature/Positions/`
2. Pindahkan `tests/Feature/PositionControllerTest.php` → `tests/Feature/Positions/PositionControllerTest.php`
3. Pindahkan `tests/Feature/PositionExportTest.php` → `tests/Feature/Positions/PositionExportTest.php`
4. Pindahkan `tests/Unit/PositionTest.php` → `tests/Unit/Models/PositionTest.php`
5. Standarisasi group annotation di SEMUA file terkait:
   - `tests/Unit/Actions/Positions/*.php` → `->group('positions')` (hapus tag 'actions')
   - `tests/Unit/Requests/Positions/*.php` → `->group('positions')` (hapus tag 'requests', jika ada)
   - `tests/Unit/Resources/Positions/*.php` → `->group('positions')` (hapus tag 'resources', jika ada)
   - `tests/Unit/Domain/Positions/*.php` → `->group('positions')` (hapus tag 'domain')
6. Verifikasi: `./vendor/bin/sail test --group positions`
```

#### 3. `branches`

```
Refactor testing modul `branches` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Simple

Lakukan:
1. Buat folder `tests/Feature/Branches/`
2. Pindahkan `tests/Feature/BranchControllerTest.php` → `tests/Feature/Branches/BranchControllerTest.php`
3. Pindahkan `tests/Feature/BranchExportTest.php` → `tests/Feature/Branches/BranchExportTest.php`
4. Pindahkan `tests/Unit/BranchTest.php` → `tests/Unit/Models/BranchTest.php`
5. Standarisasi group annotation di SEMUA file terkait (hapus extra tags 'actions', 'domain', 'resources'):
   - `tests/Unit/Actions/Branches/*.php` → `->group('branches')`
   - `tests/Unit/Requests/Branches/*.php` → `->group('branches')`
   - `tests/Unit/Resources/Branches/*.php` → `->group('branches')`
   - `tests/Unit/Domain/Branches/*.php` → `->group('branches')`
6. Verifikasi: `./vendor/bin/sail test --group branches`
```

#### 4. `units`

```
Refactor testing modul `units` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Simple

Lakukan:
1. Buat folder `tests/Feature/Units/`
2. Pindahkan `tests/Feature/UnitControllerTest.php` → `tests/Feature/Units/UnitControllerTest.php`
3. Pindahkan `tests/Feature/UnitExportTest.php` → `tests/Feature/Units/UnitExportTest.php`
4. Pindahkan `tests/Unit/UnitTest.php` → `tests/Unit/Models/UnitTest.php`
5. Standarisasi semua group annotation (hapus extra tags).
6. Verifikasi: `./vendor/bin/sail test --group units`
```

#### 5. `customer-categories`

```
Refactor testing modul `customer-categories` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Simple

Lakukan:
1. Buat folder `tests/Feature/CustomerCategories/`
2. Pindahkan `tests/Feature/CustomerCategoryControllerTest.php` → `tests/Feature/CustomerCategories/CustomerCategoryControllerTest.php`
3. Pindahkan `tests/Feature/CustomerCategoryExportTest.php` → `tests/Feature/CustomerCategories/CustomerCategoryExportTest.php`
4. Pindahkan `tests/Unit/CustomerCategoryTest.php` → `tests/Unit/Models/CustomerCategoryTest.php`
5. Standarisasi semua group annotation (hapus extra tags).
6. Verifikasi: `./vendor/bin/sail test --group customer-categories`
```

#### 6. `supplier-categories`

```
Refactor testing modul `supplier-categories` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Simple

⚠️ PERHATIAN: Ada typo group `supplier_categories` (underscore) pada `tests/Unit/SupplierCategoryTest.php`. Perbaiki menjadi `supplier-categories` (kebab-case).

Lakukan:
1. Buat folder `tests/Feature/SupplierCategories/`
2. Pindahkan `tests/Feature/SupplierCategoryControllerTest.php` → `tests/Feature/SupplierCategories/SupplierCategoryControllerTest.php`
3. Pindahkan `tests/Feature/SupplierCategoryExportTest.php` → `tests/Feature/SupplierCategories/SupplierCategoryExportTest.php`
4. Pindahkan `tests/Unit/SupplierCategoryTest.php` → `tests/Unit/Models/SupplierCategoryTest.php`
5. Standarisasi semua group annotation:
   - FIX typo: `supplier_categories` → `supplier-categories`
   - Hapus extra tags ('requests', 'domain', 'resources', 'actions')
6. Verifikasi: `./vendor/bin/sail test --group supplier-categories`
```

#### 7. `product-categories`

```
Refactor testing modul `product-categories` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Simple

Lakukan:
1. Buat folder `tests/Feature/ProductCategories/`
2. Pindahkan `tests/Feature/ProductCategoryControllerTest.php` → `tests/Feature/ProductCategories/ProductCategoryControllerTest.php`
3. Pindahkan `tests/Feature/ProductCategoryExportTest.php` → `tests/Feature/ProductCategories/ProductCategoryExportTest.php`
4. Pindahkan `tests/Unit/ProductCategoryTest.php` → `tests/Unit/Models/ProductCategoryTest.php`
5. Standarisasi semua group annotation (hapus extra tags).
6. Verifikasi: `./vendor/bin/sail test --group product-categories`
```

#### 8. `asset-categories`

```
Refactor testing modul `asset-categories` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Simple

⚠️ PERHATIAN: Beberapa file punya extra tags ('unit', 'models', 'actions', 'requests', 'resources', 'domain').

Lakukan:
1. Buat folder `tests/Feature/AssetCategories/`
2. Pindahkan `tests/Feature/AssetCategoryControllerTest.php` → `tests/Feature/AssetCategories/AssetCategoryControllerTest.php`
3. Pindahkan `tests/Feature/AssetCategoryExportTest.php` → `tests/Feature/AssetCategories/AssetCategoryExportTest.php`
4. Pindahkan `tests/Unit/AssetCategoryTest.php` → `tests/Unit/Models/AssetCategoryTest.php`
5. Standarisasi semua group annotation: `->group('asset-categories')` SAJA.
6. Verifikasi: `./vendor/bin/sail test --group asset-categories`
```

#### 9. `asset-locations`

```
Refactor testing modul `asset-locations` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Simple

Lakukan:
1. Buat folder `tests/Feature/AssetLocations/`
2. Pindahkan `tests/Feature/AssetLocationControllerTest.php` → `tests/Feature/AssetLocations/AssetLocationControllerTest.php`
3. Pindahkan `tests/Feature/AssetLocationExportTest.php` → `tests/Feature/AssetLocations/AssetLocationExportTest.php`
4. Pindahkan `tests/Unit/AssetLocationTest.php` → `tests/Unit/Models/AssetLocationTest.php`
5. Standarisasi semua group annotation (hapus extra tags).
6. Verifikasi: `./vendor/bin/sail test --group asset-locations`
```

#### 10. `asset-models`

```
Refactor testing modul `asset-models` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Simple

⚠️ PERHATIAN: `tests/Unit/AssetModelTest.php` punya masalah khusus:
- Menggunakan `uses(TestCase::class, RefreshDatabase::class)->in('Unit')` yang REDUNDAN
- Group annotation per-test (`->group('asset-models')` pada setiap test) bukan file-level

Lakukan:
1. Buat folder `tests/Feature/AssetModels/`
2. Pindahkan `tests/Feature/AssetModelControllerTest.php` → `tests/Feature/AssetModels/AssetModelControllerTest.php`
3. Pindahkan `tests/Feature/AssetModelExportTest.php` → `tests/Feature/AssetModels/AssetModelExportTest.php`
4. Pindahkan `tests/Unit/AssetModelTest.php` → `tests/Unit/Models/AssetModelTest.php`
5. PERBAIKI `tests/Unit/Models/AssetModelTest.php`:
   - Hapus baris `uses(TestCase::class, RefreshDatabase::class)->in('Unit');`
   - Tambahkan file-level: `uses(RefreshDatabase::class)->group('asset-models');`
   - Hapus `->group('asset-models')` dari setiap test individual
6. Standarisasi semua group annotation di file terkait lainnya.
7. Verifikasi: `./vendor/bin/sail test --group asset-models`
```

---

### Prompt Per-Modul: CRUD Complex

#### 11. `employees`

```
Refactor testing modul `employees` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Complex

Lakukan:
1. Buat folder `tests/Feature/Employees/`
2. Pindahkan `tests/Feature/EmployeeControllerTest.php` → `tests/Feature/Employees/EmployeeControllerTest.php`
3. Pindahkan `tests/Feature/EmployeeExportTest.php` → `tests/Feature/Employees/EmployeeExportTest.php`
4. Pindahkan `tests/Unit/EmployeeTest.php` → `tests/Unit/Models/EmployeeTest.php`
5. Buat folder `tests/Unit/DTOs/Employees/`
6. Pindahkan `tests/Unit/UpdateEmployeeDataTest.php` → `tests/Unit/DTOs/Employees/UpdateEmployeeDataTest.php`
   - Tambahkan group annotation: `uses()->group('employees');`
7. Standarisasi semua group annotation (hapus extra tags).
8. Verifikasi: `./vendor/bin/sail test --group employees`
```

#### 12. `customers`

```
Refactor testing modul `customers` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Complex

Lakukan:
1. Buat folder `tests/Feature/Customers/`
2. Pindahkan `tests/Feature/CustomerControllerTest.php` → `tests/Feature/Customers/CustomerControllerTest.php`
3. Pindahkan `tests/Feature/CustomerExportTest.php` → `tests/Feature/Customers/CustomerExportTest.php`
4. Pindahkan `tests/Unit/CustomerTest.php` → `tests/Unit/Models/CustomerTest.php`
5. Standarisasi semua group annotation.
6. Verifikasi: `./vendor/bin/sail test --group customers`
```

#### 13. `suppliers`

```
Refactor testing modul `suppliers` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Complex

Lakukan:
1. Buat folder `tests/Feature/Suppliers/`
2. Pindahkan `tests/Feature/SupplierControllerTest.php` → `tests/Feature/Suppliers/SupplierControllerTest.php`
3. Pindahkan `tests/Feature/SupplierExportTest.php` → `tests/Feature/Suppliers/SupplierExportTest.php`
4. Pindahkan `tests/Unit/SupplierTest.php` → `tests/Unit/Models/SupplierTest.php`
5. Standarisasi semua group annotation.
6. Verifikasi: `./vendor/bin/sail test --group suppliers`
```

#### 14. `products`

```
Refactor testing modul `products` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Complex

Lakukan:
1. Buat folder `tests/Feature/Products/`
2. Pindahkan `tests/Feature/ProductControllerTest.php` → `tests/Feature/Products/ProductControllerTest.php`
3. Pindahkan `tests/Feature/ProductExportTest.php` → `tests/Feature/Products/ProductExportTest.php`
4. Pindahkan `tests/Unit/ProductTest.php` → `tests/Unit/Models/ProductTest.php`
5. Standarisasi semua group annotation.
6. Verifikasi: `./vendor/bin/sail test --group products`
```

#### 15. `accounts`

```
Refactor testing modul `accounts` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Complex

Lakukan:
1. Buat folder `tests/Feature/Accounts/`
2. Pindahkan `tests/Feature/AccountControllerTest.php` → `tests/Feature/Accounts/AccountControllerTest.php`
3. Pindahkan `tests/Feature/AccountExportTest.php` → `tests/Feature/Accounts/AccountExportTest.php`
4. Pindahkan `tests/Unit/AccountTest.php` → `tests/Unit/Models/AccountTest.php`
5. Standarisasi group: hapus `'export'` tag dari `AccountExportTest.php`.
6. Verifikasi: `./vendor/bin/sail test --group accounts`
```

#### 16. `account-mappings`

```
Refactor testing modul `account-mappings` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Complex

Lakukan:
1. Buat folder `tests/Feature/AccountMappings/`
2. Pindahkan `tests/Feature/AccountMappingControllerTest.php` → `tests/Feature/AccountMappings/AccountMappingControllerTest.php`
3. Pindahkan `tests/Feature/AccountMappingExportTest.php` → `tests/Feature/AccountMappings/AccountMappingExportTest.php`
4. Pindahkan `tests/Unit/AccountMappingTest.php` → `tests/Unit/Models/AccountMappingTest.php`
5. Standarisasi group: hapus `'export'`, `'actions'` tags.
6. Verifikasi: `./vendor/bin/sail test --group account-mappings`
```

#### 17. `coa-versions`

```
Refactor testing modul `coa-versions` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Complex

Lakukan:
1. Buat folder `tests/Feature/CoaVersions/`
2. Pindahkan `tests/Feature/CoaVersionControllerTest.php` → `tests/Feature/CoaVersions/CoaVersionControllerTest.php`
3. Pindahkan `tests/Feature/CoaVersionExportTest.php` → `tests/Feature/CoaVersions/CoaVersionExportTest.php`
4. Pindahkan `tests/Unit/CoaVersionTest.php` → `tests/Unit/Models/CoaVersionTest.php`
5. Standarisasi semua group annotation.
6. Verifikasi: `./vendor/bin/sail test --group coa-versions`
```

#### 18. `fiscal-years`

```
Refactor testing modul `fiscal-years` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Complex

Lakukan:
1. Buat folder `tests/Feature/FiscalYears/`
2. Pindahkan `tests/Feature/FiscalYearControllerTest.php` → `tests/Feature/FiscalYears/FiscalYearControllerTest.php`
3. Pindahkan `tests/Feature/FiscalYearExportTest.php` → `tests/Feature/FiscalYears/FiscalYearExportTest.php`
4. Pindahkan `tests/Unit/FiscalYearTest.php` → `tests/Unit/Models/FiscalYearTest.php`
5. Standarisasi semua group annotation.
6. Verifikasi: `./vendor/bin/sail test --group fiscal-years`
```

#### 19. `journal-entries`

```
Refactor testing modul `journal-entries` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Complex

Feature test sudah di subfolder `tests/Feature/JournalEntries/`.

Lakukan:
1. Feature test sudah di subfolder — TIDAK perlu dipindah.
2. Pindahkan `tests/Unit/JournalEntryTest.php` → `tests/Unit/Models/JournalEntryTest.php`
3. Standarisasi semua group annotation.
4. Verifikasi: `./vendor/bin/sail test --group journal-entries`
```

#### 20. `asset-movements`

```
Refactor testing modul `asset-movements` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Complex

Lakukan:
1. Buat folder `tests/Feature/AssetMovements/`
2. Pindahkan `tests/Feature/AssetMovementControllerTest.php` → `tests/Feature/AssetMovements/AssetMovementControllerTest.php`
3. Pindahkan `tests/Feature/AssetMovementExportTest.php` → `tests/Feature/AssetMovements/AssetMovementExportTest.php`
4. Pindahkan `tests/Unit/AssetMovementTest.php` → `tests/Unit/Models/AssetMovementTest.php`
5. Standarisasi semua group annotation.
6. Verifikasi: `./vendor/bin/sail test --group asset-movements`
```

#### 21. `assets` ⚠️ KOMPLEKS

```
Refactor testing modul `assets` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: CRUD Complex — ⚠️ PALING KOMPLEKS

⚠️ MASALAH:
- `tests/Feature/AssetControllerTest.php` (root): 97 lines, pakai manual permission, namespace, pakai `$this->` style
- `tests/Feature/Assets/AssetControllerTest.php` (subfolder): 139 lines, pakai manual permission, lebih lengkap (ada movement sync test)
- `tests/Feature/AssetExportTest.php` (root)
- `tests/Feature/Assets/AssetFilteredExportTest.php` (subfolder)
- `tests/Feature/AssetProfileTest.php` (root)

Lakukan:
1. ANALISIS dulu kedua `AssetControllerTest.php`:
   - `Assets/AssetControllerTest.php` lebih lengkap (5 test vs 5 test, tapi ada movement sync tests) → GUNAKAN INI
   - Merge unique tests dari root ke subfolder jika ada
   - HAPUS `tests/Feature/AssetControllerTest.php` (root)
2. Pindahkan `tests/Feature/AssetExportTest.php` → `tests/Feature/Assets/AssetExportTest.php`
3. Pindahkan `tests/Feature/AssetProfileTest.php` → `tests/Feature/Assets/AssetProfileTest.php`
4. `tests/Feature/Assets/AssetFilteredExportTest.php` — sudah di tempat yang benar
5. Pindahkan `tests/Unit/AssetTest.php` → `tests/Unit/Models/AssetTest.php`
6. Standarisasi SEMUA group: `->group('assets')` SAJA.
   - Hapus `'asset-feature'`, `'asset-unit'`, `'asset-profile'`
   - Perbaiki permission setup: gunakan `createTestUserWithPermissions([...])`, bukan manual
7. Verifikasi: `./vendor/bin/sail test --group assets`
```

---

### Prompt Per-Modul: Non-CRUD

#### 22. `dashboard`

```
Refactor testing modul `dashboard` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: Non-CRUD

Lakukan:
1. Buat folder `tests/Feature/Dashboard/`
2. Pindahkan `tests/Feature/DashboardTest.php` → `tests/Feature/Dashboard/DashboardTest.php`
3. Pastikan group: `->group('dashboard')`
4. Verifikasi: `./vendor/bin/sail test --group dashboard`
```

#### 23. `permissions`

```
Refactor testing modul `permissions` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: Non-CRUD

Lakukan:
1. Buat folder `tests/Feature/Permissions/`
2. Pindahkan `tests/Feature/PermissionControllerTest.php` → `tests/Feature/Permissions/PermissionControllerTest.php`
3. Pastikan group: `->group('permissions')`
4. Verifikasi: `./vendor/bin/sail test --group permissions`
```

#### 24. `users`

```
Refactor testing modul `users` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: Non-CRUD

Lakukan:
1. Buat folder `tests/Feature/Users/`
2. Pindahkan `tests/Feature/UserControllerTest.php` → `tests/Feature/Users/UserControllerTest.php`
3. Pastikan group: `->group('users')`
4. Cek Unit test: `tests/Unit/Requests/Users/UpdateUserRequestTest.php` → pastikan group `'users'`
5. Verifikasi: `./vendor/bin/sail test --group users`
```

#### 25. `posting-journals`

```
Refactor testing modul `posting-journals` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: Non-CRUD

Lakukan:
1. Buat folder `tests/Feature/PostingJournals/`
2. Pindahkan `tests/Feature/PostingJournalTest.php` → `tests/Feature/PostingJournals/PostingJournalTest.php`
3. Pastikan group: `->group('posting-journals')`
4. Verifikasi: `./vendor/bin/sail test --group posting-journals`
```

#### 26. `reports` ⚠️ KOMPLEKS

```
Refactor testing modul `reports` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: Non-CRUD — ⚠️ Ada legacy file + subfolder

⚠️ MASALAH:
- `tests/Feature/FinancialReportsTest.php` (root): Legacy, group `'reports', 'reports-legacy'`
- `tests/Feature/Reports/BalanceSheetReportTest.php`: group `'reports', 'reports-balance-sheet'`
- `tests/Feature/Reports/IncomeStatementReportTest.php`: group `'reports', 'reports-income-statement'`
- `tests/Feature/Reports/TrialBalanceReportTest.php`: group `'reports', 'reports-trial-balance'`
- `tests/Feature/Reports/ComparativeReportTest.php`: group `'reports'`

Lakukan:
1. `tests/Feature/Reports/` sudah ada subfolder — BAIK.
2. Pindahkan `tests/Feature/FinancialReportsTest.php` → `tests/Feature/Reports/FinancialReportsTest.php`
3. Standarisasi SEMUA group: `->group('reports')` SAJA.
   - Hapus `'reports-legacy'`, `'reports-balance-sheet'`, `'reports-income-statement'`, `'reports-trial-balance'`
4. Verifikasi: `./vendor/bin/sail test --group reports`
```

#### 27. `auth`

```
Refactor testing modul `auth` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: Non-CRUD

`tests/Feature/Auth/` sudah di subfolder. Cek dan standarisasi group annotation.

Lakukan:
1. Cek group annotation di semua file `tests/Feature/Auth/*.php`
2. Standarisasi: `->group('auth')` di SEMUA file
3. Verifikasi: `./vendor/bin/sail test --group auth`
```

#### 28. `settings`

```
Refactor testing modul `settings` sesuai `tests/REFACTORING_PLAN.md`.

Tipe modul: Non-CRUD

`tests/Feature/Settings/` sudah di subfolder. Cek dan standarisasi group annotation.

Lakukan:
1. Cek group annotation di semua file `tests/Feature/Settings/*.php`
2. Standarisasi: `->group('settings')` di SEMUA file
3. Verifikasi: `./vendor/bin/sail test --group settings`
```

---

### Prompt Phase 4: Cleanup

#### 29. Cleanup

```
Cleanup sesuai `tests/REFACTORING_PLAN.md`.

Lakukan:
1. Hapus `tests/Unit/ExampleTest.php`
2. Pastikan TIDAK ADA file test di root `tests/Feature/` (semua harus di subfolder)
3. Pastikan TIDAK ADA file `*Test.php` lang di root `tests/Unit/` (semua model test harus di `tests/Unit/Models/`)
4. Verifikasi keseluruhan: `./vendor/bin/sail test`
```

---

## Verifikasi Akhir

Setelah SEMUA modul selesai:

```bash
# Test keseluruhan
./vendor/bin/sail test

# Test per-modul (contoh beberapa)
./vendor/bin/sail test --group departments
./vendor/bin/sail test --group employees
./vendor/bin/sail test --group assets
./vendor/bin/sail test --group reports
./vendor/bin/sail test --group auth

# Pastikan tidak ada file orphan di root
ls tests/Feature/*.php    # harus kosong (kecuali ExampleTest.php jika ada)
ls tests/Unit/*.php        # harus kosong (kecuali ExampleTest.php)
```
