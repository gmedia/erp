# Rekomendasi Prompt untuk Agent

## A. Membuat Fitur Baru

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
   - Backend: Model, Migration, Factory, Seeder, Controller, Requests, Resources, Actions, FilterService, Export, Routes (`routes/api/{modul-names}.php`)
   - Frontend: Page component + entityConfigs.ts entry + register route di `app-routes.tsx`
   - Testing Pest: Unit (Model, Actions, Requests, Resources, Domain) + Feature (Controller, Export)
   - Testing E2E: `tests/e2e/{modul-names}/{modul-name}.spec.ts` dengan helper functions
4. Standar testing:
   - Group annotation: `->group('{modul-names}')` di SEMUA test file
   - Feature test: gunakan `Sanctum::actingAs()` + `assertJson()`
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
   - Backend: Model (+ relationships), Migration, Factory, Seeder, Controller, Requests (4 files), Resources (2 files), Actions (2 files), FilterService (+ applyAdvancedFilters), DTO, Export, Routes (`routes/api/{modul-names}.php`)
   - Frontend: Page + Form + Filters + Columns + ViewModal + entityConfigs.ts entry + register route di `app-routes.tsx`
   - Testing Pest: Unit (Model, Actions, Requests, Resources, Domain) + Feature (Controller, Export)
   - Testing E2E: `tests/e2e/{modul-names}/{modul-name}.spec.ts` + `tests/e2e/{modul-names}/helpers.ts`
4. Standar testing:
   - Group annotation: `->group('{modul-names}')` di SEMUA test file
   - Feature test: gunakan `Sanctum::actingAs()` + `assertJson()`
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
   - Backend: Controller (API-only, return JSON), Routes (`routes/api/{modul-names}.php`), (+ model/migration jika ada tabel baru)
   - Frontend: Page component(s) + komponen custom sesuai spec + register route di `app-routes.tsx`
   - Testing Pest: Feature test (Sanctum::actingAs + assertJson) sesuai behavior
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
/create-import

Tambahkan fitur Import Excel pada modul CRUD `Employees`.

Spesifikasi:
- Format file: Excel (.xlsx, .xls) dan CSV (.csv)
- Kolom import: name (required), email (required, unique), phone, department_id (FK → departments by name), position_id (FK → positions by name), branch_id (FK → branches by name), salary (decimal), hire_date (date, format Y-m-d)
- Resolusi FK: lookup by `name` pada tabel relasi (departments, positions, branches)
  - Jika FK name tidak ditemukan → row gagal dengan error message
- Validasi per-row: sama seperti StoreEmployeeRequest (required, unique, format)
- Mode: insert only (skip existing by email) ATAU insert + update (upsert by email)
- Response: JSON summary { imported: N, skipped: N, errors: [{row, field, message}] }
```

---

## B. Refactor Modul dari Branch Lain (Inertia → SPA)

Gunakan prompt ini untuk modul yang masih menggunakan arsitektur **Inertia** dan perlu di-refactor ke **Laravel API + React Full SPA**.

### 5. Refactor Single Module

```
/refactor-module

Refactor modul `{ModulName}` dari arsitektur Inertia ke SPA + API.

Modul ini adalah {simple crud / complex crud / non-crud}.

Yang perlu dilakukan:
1. Backend:
   - Pindah routes dari `routes/{modul}.php` ke `routes/api/{modul-names}.php` (API-only)
   - Hapus semua `Inertia::render()` → controller return JSON saja
   - Hapus import `use Inertia\Inertia` dan `use Inertia\Response`
2. Frontend:
   - Ganti `import { Head } from '@inertiajs/react'` → `import { Helmet } from 'react-helmet-async'`
   - Ganti `<Head title="..." />` → `<Helmet><title>...</title></Helmet>`
   - Ganti `import { router } from '@inertiajs/react'` → gunakan `axios` / React Query mutation
   - Ganti `import { Link } from '@inertiajs/react'` → `import { Link } from 'react-router-dom'`
   - Ganti data fetching dari Inertia props → React Query hooks
   - Register lazy-loaded route di `app-routes.tsx`
3. Test:
   - Feature test: ganti `actingAs($user)` → `Sanctum::actingAs($user)`
   - Hapus `assertInertia()` → gunakan `assertJson()`, `assertJsonStructure()`
   - E2E: pastikan auth pakai Bearer token

Referensi modul yang sudah benar:
- Simple CRUD → `positions`, `departments`
- Complex CRUD → `employees`, `suppliers`, `customers`
- Non-CRUD → `users`, `permissions`

Verifikasi:
- `./vendor/bin/sail test --filter={ModulName}`
- `./vendor/bin/sail npx playwright test tests/e2e/{modul-names}/`
```

### 6. Refactor Batch (Multiple Modules)

```
/refactor-module

Refactor modul-modul berikut dari arsitektur Inertia ke SPA + API:

Simple CRUD: `{ModulA}`, `{ModulB}`
Complex CRUD: `{ModulC}`
Non-CRUD: `{ModulD}`

Kerjakan SATU MODUL PER SATU, verifikasi sebelum lanjut ke modul berikutnya.

Untuk setiap modul, lakukan:
1. Backend: pindah routes ke `routes/api/`, controller return JSON only
2. Frontend: ganti @inertiajs/react → react-helmet-async + react-router-dom + React Query
3. Register route di `app-routes.tsx`
4. Test: update auth ke `Sanctum::actingAs()` + `assertJson()`
5. Verifikasi: `./vendor/bin/sail test --filter={Modul}`

Referensi: positions (simple), employees (complex), users (non-crud).
```

---

## C. Test & Refactor Existing Code

### 7. Buat Test untuk Fitur Existing

```
/create-tests

Buatkan test untuk modul `{ModulName}` yang sudah ada.

Tipe modul: {simple crud / complex crud / non-crud}
Tipe test yang dibutuhkan: {feature test / unit test / e2e / semua}

Referensi: lihat test dari modul `{referensi}` sebagai template.
```

### 8. Refactor E2E Test Existing

```
Refactor E2E test modul `{ModulName}` agar menggunakan shared test factories.

Gunakan skill `refactor-e2e`. Migrasi ke `generateModuleTests()` dari `shared-test-factories.ts`.
Helper functions harus di `tests/e2e/{modul-names}/helpers.ts`.
```

---

## D. Refactor Berbasis SonarQube MCP (Duplikasi)

### 9. Refactor Sonar (Baseline + Wave Semi-Besar Terkontrol)

```
/refactor-sonar

Ambil dan analisa data dari SonarQube MCP untuk menurunkan duplikasi dan menjaga konsistensi style kode antar modul.

Project key: `gmedia_erp`
Target: turunkan `duplicated_lines`, `duplicated_blocks`, dan `new_duplicated_lines_density` tanpa mengubah API contract.

Instruksi:
1. Ambil baseline Sonar MCP (WAJIB):
   - quality gate
   - duplicated_lines
   - duplicated_blocks
   - duplicated_lines_density
   - ncloc
   - coverage
2. Ambil shortlist cluster duplikasi prioritas, lalu pilih 1 wave semi-besar terkontrol (4-8 file) dengan pola refactor yang sama.
3. Refactor hanya internal (shared concern/trait/helper), tanpa ubah route, payload, response keys, query param.
4. Terapkan pola style/struktur yang sama ke sibling module setara pada wave yang sama.
5. Jalankan test terfokus modul terdampak via Sail.
6. Update `docs/refactor-sonar-progress.md`:
   - baseline/delta metrik
   - ringkasan perubahan wave
   - evidence test
   - snapshot Sonar terbaru
7. Jika `coverage` Sonar anomali (contoh `0.0`) tapi test lokal lulus, catat sebagai anomali pipeline dan verifikasi ulang pada snapshot berikutnya.
```

### 10. Lanjutan Wave Sonar (Setelah Push)

```
/refactor-sonar

Lanjutkan batch aktif di `docs/refactor-sonar-progress.md` dengan pendekatan semi-besar terkontrol.

Konteks:
- Fokus batch: `{BatchName}`
- Wave sebelumnya: `{commit-hash}`

Langkah:
1. Tarik ulang quality gate + metrik inti Sonar MCP.
2. Jika snapshot belum berubah, lanjut 1 wave refactor 4-8 file dengan pola yang sama dan risiko rendah.
3. Jalankan test terfokus hanya untuk modul terdampak.
4. Commit + push.
5. Tarik ulang Sonar MCP dan update delta + log di `docs/refactor-sonar-progress.md`.

Output wajib:
- Daftar file yang direfactor
- Hasil test
- Delta metrik sebelum/sesudah
- Risiko residual (jika ada)
```

---

## Ringkasan

| Skenario | Awali dengan | Skill | Referensi modul |
|----------|-------------|-------|-----------------|
| Simple CRUD baru | `/create-feature` | `feature-crud-simple` | `departments` |
| Complex CRUD baru | `/create-feature` | `feature-crud-complex` | `employees` / `suppliers` / `customers` |
| Non-CRUD baru | `/create-feature` | `feature-non-crud` | `users` / `permissions` |
| Import Excel | `/create-import` | `feature-import` | `employees` / `suppliers` |
| Refactor Inertia → SPA | `/refactor-module` | `refactor-backend` + `refactor-frontend` | sesuai tipe CRUD |
| Refactor berbasis Sonar MCP | `/refactor-sonar` | workflow `refactor-sonar` + `refactor-backend` | `docs/refactor-sonar-progress.md` |
| Buat test baru | `/create-tests` | `testing-strategy` | sesuai tipe CRUD |
| Refactor E2E test | — | `refactor-e2e` | `shared-test-factories.ts` |
