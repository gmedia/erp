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
   - `npx playwright test tests/e2e/{modul-names}/`
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
   - `npx playwright test tests/e2e/{modul-names}/`
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
   - `npx playwright test tests/e2e/{modul-names}/`
```

---

## Ringkasan

| Tipe | Awali dengan | Referensi skill | Referensi modul | Spec file |
|------|-------------|-----------------|-----------------|-----------|
| Simple CRUD | `/create-feature` | `feature-crud-simple` | `departments` | ❌ |
| Complex CRUD | `/create-feature` | `feature-crud-complex` | `employees`/`suppliers`/`customers` | ❌ |
| Non-CRUD | `/create-feature` | `feature-non-crud` | Tergantung spec | `docs/{MODUL}.md` |
