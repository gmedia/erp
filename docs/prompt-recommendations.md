# Rekomendasi Prompt untuk Membuat Fitur Baru

## Prompt Templates

### 1. CRUD Simple

```
/create-feature

Buat fitur/modul CRUD Simple `{ModulNames}` (contoh: `Warehouses`).

Spesifikasi:
- Tabel: `{modul_names}` dengan kolom: `name` (required, unique)
- Tipe: Simple CRUD (1 tabel, tanpa FK, filter search saja)

Instruksi:
1. Gunakan skill `feature-crud-simple` → baca SKILL.md
2. Referensi modul existing: `departments` sebagai template
3. Output yang diharapkan:
   - Backend: Model, Migration, Factory, Seeder, Controller, Requests, Resources, Actions, FilterService, Export, Routes
   - Frontend: Page component + entityConfigs.ts entry
   - Testing Pest: Unit (Model, Actions, Requests, Resources, Domain) + Feature (Controller, Export)
   - Testing E2E: `tests/e2e/{modul-names}/{modul-name}.spec.ts` dengan helper functions
4. Standar testing:
   - Group annotation: `->group('{modul-names}')` di SEMUA test file
   - E2E menggunakan `generateModuleTests()` dari `shared-test-factories.ts`
5. Verifikasi:
   - `./vendor/bin/sail test --group {modul-names}`
   - `./vendor/bin/sail npx playwright test tests/e2e/{modul-names}/`
```

### 2. CRUD Complex

```
/create-feature

Buat fitur/modul CRUD Complex `{ModulNames}` (contoh: `PurchaseOrders`).

Spesifikasi:
- Tabel: `{modul_names}` dengan kolom: [daftar kolom lengkap]
- Relasi FK: [daftar relasi, contoh: `supplier_id → suppliers`, `branch_id → branches`]
- Filter: [daftar filter, contoh: search, status dropdown, date range]
- Sortable columns: [daftar kolom yang bisa di-sort di DataTable]

Instruksi:
1. Gunakan skill `feature-crud-complex` → baca SKILL.md
2. Referensi pattern yang paling mirip:
   - Jika ada 3+ FK + range filter → ikuti `employees`
   - Jika ada 2 FK + status enum → ikuti `suppliers` / `customers`
3. Output yang diharapkan:
   - Backend: Model (+ relationships), Migration, Factory, Seeder, Controller, Requests (4 files), Resources (2 files), Actions (2 files), FilterService (+ applyAdvancedFilters), DTO, Export, Routes
   - Frontend: Page + Form + Filters + Columns + ViewModal + entityConfigs.ts entry
   - Testing Pest: Unit (Model, Actions, Requests, Resources, Domain) + Feature (Controller, Export)
   - Testing E2E: `tests/e2e/{modul-names}/{modul-name}.spec.ts` + `tests/e2e/{modul-names}/helpers.ts`
4. Standar testing:
   - Group annotation: `->group('{modul-names}')` di SEMUA test file
   - E2E menggunakan `generateModuleTests()` dari `shared-test-factories.ts`
   - E2E helper di file terpisah: `tests/e2e/{modul-names}/helpers.ts`
5. Verifikasi:
   - `./vendor/bin/sail test --group {modul-names}`
   - `./vendor/bin/sail npx playwright test tests/e2e/{modul-names}/`
```

### 3. Non-CRUD

```
/create-feature

Buat fitur/modul Non-CRUD `{ModulNames}` dengan spesifikasi @/docs/{MODUL_NAMES}.md

Spesifikasi:
- Tipe: Non-CRUD (custom UI, mungkin menggunakan model existing)
- Baca spec lengkap di: `docs/{MODUL_NAMES}.md`

Instruksi:
1. Gunakan skill `feature-non-crud` → baca SKILL.md
2. Baca spec file `docs/{MODUL_NAMES}.md` untuk memahami requirement
3. Output yang diharapkan:
   - Backend: Controller, Routes, (+ model/migration jika ada tabel baru)
   - Frontend: Page component(s), komponen custom sesuai spec
   - Testing Pest: Feature test sesuai behavior yang didefinisikan di spec
   - Testing E2E: Test custom sesuai user journey di spec
4. Standar testing:
   - Group annotation: `->group('{modul-names}')` di SEMUA test file
   - E2E helper (jika ada interaksi CRUD) di `tests/e2e/{modul-names}/helpers.ts`
5. Verifikasi:
   - `./vendor/bin/sail test --group {modul-names}`
   - `./vendor/bin/sail npx playwright test tests/e2e/{modul-names}/`
```

### 4. Import Feature (untuk modul CRUD existing)

```
Tambahkan fitur Import Excel pada modul CRUD `Employees`.

Spesifikasi:
- Format file: Excel (.xlsx, .xls) dan CSV (.csv)
- Kolom import: name (required), email (required, unique), phone, department_id (FK → departments by name), position_id (FK → positions by name), branch_id (FK → branches by name), salary (decimal), hire_date (date, format Y-m-d)
- Resolusi FK: lookup by `name` pada tabel relasi (departments, positions, branches)
  - Jika FK name tidak ditemukan → row gagal dengan error message
- Validasi per-row: sama seperti StoreEmployeeRequest (required, unique, format)
- Mode: insert only (skip existing by email) ATAU insert + update (upsert by email)
- Response: JSON summary { imported: N, skipped: N, errors: [{row, field, message}] }

Instruksi:
1. Pelajari pattern existing:
   - Export: `app/Exports/EmployeeExport.php` dan `ExportEmployeesAction.php` sebagai referensi Maatwebsite/Excel concern
   - Controller: `EmployeeController.php` → method `export()` sebagai referensi flow
   - Route: `routes/employee.php` → `POST employees/export`
   - Request Validation: `StoreEmployeeRequest.php` → reuse aturan validasi
2. Backend — buat file baru:
   - `app/Imports/EmployeeImport.php` — implement `ToCollection`, `WithHeadingRow`, `WithValidation`, `SkipsOnFailure`, `SkipsFailures`
   - `app/Actions/Employees/ImportEmployeesAction.php` — orchestrate import + return summary
   - `app/Http/Requests/Employees/ImportEmployeeRequest.php` — validate file upload (mimes, max size)
3. Backend — modifikasi file:
   - `EmployeeController.php` → tambah method `import(ImportEmployeeRequest $request)`
   - `routes/employee.php` → tambah `Route::post('employees/import', ...)->middleware('permission:employee.create,true')`
4. Frontend — buat/modifikasi:
   - Buat komponen `ImportDialog.tsx` (generic, reusable) atau `EmployeeImportDialog.tsx`
     - Upload file input (accept .xlsx, .xls, .csv)
     - Download template button (kolom header sesuai format)
     - Progress indicator + result summary (imported/skipped/errors)
   - Modifikasi halaman `employees/index` → tambah tombol "Import" di samping tombol "Export"
5. Testing Pest:
   - `tests/Feature/Employees/EmployeeImportTest.php`
     - Test upload valid file → assert imported count
     - Test upload file with invalid rows → assert error responses
     - Test upload file with unknown FK names → assert row errors
     - Test upload non-Excel file → assert 422 validation error
     - Test upload empty file → assert appropriate response
     - Test duplicate email handling (skip/upsert)
   - Group annotation: `->group('employees')`
6. Verifikasi:
   - `./vendor/bin/sail test --group employees`
   - Manual: upload file Excel di browser → cek data masuk ke tabel employees

Catatan Penting:
- Gunakan `Maatwebsite\Excel` (sudah terinstall, dipakai untuk export)
- Heading row di Excel harus match: name, email, phone, department, position, branch, salary, hire_date
- FK resolution: kolom "department" di Excel berisi NAMA department, bukan ID
- Template download: buat Excel template kosong dengan header yang benar
```

---

## Ringkasan

| Tipe | Awali dengan | Referensi skill | Referensi modul | Spec file |
|------|-------------|-----------------|-----------------|-----------|
| Simple CRUD | `/create-feature` | `feature-crud-simple` | `departments` | ❌ |
| Complex CRUD | `/create-feature` | `feature-crud-complex` | `employees`/`suppliers`/`customers` | ❌ |
| Non-CRUD | `/create-feature` | `feature-non-crud` | Tergantung spec | `docs/{MODUL}.md` |
| Import Feature | — | — | `employees` (export pattern) | ❌ |
