---
name: Refactor E2E Tests
description: Panduan refactor E2E test per modul menggunakan shared test factories, termasuk pertimbangan refactoring frontend/backend agar testing konsisten.
---

# Refactor E2E Tests

Gunakan skill ini untuk refactoring E2E test satu modul pada satu waktu.
Baca modul existing yang sejenis sebagai referensi pattern sebelum refactoring.

## 🔌 MCP Tools yang Digunakan

| Tool | Kapan Digunakan |
|------|-----------------|
| `mcp_laravel-boost_browser-logs` | Debug E2E frontend errors |
| `mcp_laravel-boost_last-error` | Debug backend errors saat test |
| `mcp_laravel-boost_database-schema` | Verify kolom export match database |
| `mcp_laravel-boost_list-routes` | Verify API routes per modul |
| `mcp_filesystem_read_file` | Baca file referensi existing |

---

## 🎯 WORKFLOW PER MODUL

### Langkah 1: Identifikasi Modul

Cari modul yang akan direfactor. Catat informasi berikut dari kode existing:
- `slug` — nama folder modul (kebab-case)
- `route` — URL frontend
- `api` — API endpoint
- `export_api` — API export endpoint
- `sortable_columns` — kolom yang harus ditest sorting (dari Columns.tsx)
- `view_type` — 'dialog' atau 'page'
- `checkbox_header` — apakah header checkbox ada (harusnya `false`)

Referensi modul yang sudah direfactor:
```
mcp_filesystem_read_file(path: "tests/e2e/departments/helpers.ts")
mcp_filesystem_read_file(path: "tests/e2e/departments/department.spec.ts")
```

### Langkah 2: Periksa Frontend (SEBELUM Menulis Test)

Baca file Columns modul:
```
mcp_filesystem_read_file(path: "resources/js/components/{module}/{Module}Columns.tsx")
```

**Periksa dan perbaiki jika perlu:**

1. **Select Column** — Harus menggunakan `createSelectColumn()`, BUKAN custom implementation
   - ✅ Benar: `createSelectColumn<Entity>()`
   - ❌ Salah: `{ id: 'select', header: ({ table }) => <Checkbox ... /> }`
   - Referensi: `@/resources/js/utils/columns.tsx` → function `createSelectColumn()`

2. **Actions Column** — Harus menggunakan `createActionsColumn()` dengan dropdown pattern
   - ✅ Benar: `createActionsColumn<Entity>()`
   - ❌ Salah: Custom icon buttons tanpa dropdown
   - Exception: Jika modul memang butuh custom actions (misal conditional rendering), tulis catatan di helpers

3. **Sortable Columns** — Pastikan sesuai REGISTRY MODUL
   - Kolom yang pakai `createSortingHeader()` atau `createTextColumn()` (default sortable) = sortable
   - Kolom yang pakai `enableSorting: false` = non-sortable
   - Template referensi: `resources/Columns.tsx.template` di skill ini

### Langkah 3: Periksa Backend (SEBELUM Menulis Test)

```
mcp_filesystem_read_file(path: "app/Http/Requests/{Module}/Index{Entity}Request.php")
```

**Periksa:**

1. **Sort validation** — Pastikan SEMUA `sortable_columns` dari Columns.tsx ada di `sort_by` validation rule
   - Jika missing: tambahkan ke `sort_by` validation
   - Cek juga sorting logic di `FilterService` (`app/Domain/{Module}/`) atau `Action`

2. **Export columns** — Baca Export Action:
   ```
   mcp_filesystem_read_file(path: "app/Actions/{Module}/Export{Module}Action.php")
   ```
   - Pastikan semua kolom DataTable diexport
   - Jika missing: tambahkan ke Export Action dan Export class

### Langkah 4: Cek Shared Test Factories

```
mcp_filesystem_read_file(path: "tests/e2e/shared-test-factories.ts")
```

Jika file **belum ada**, buat dulu menggunakan template:
```
mcp_filesystem_read_file(path: ".github/skills/refactor-e2e/resources/shared-test-factories.ts.template")
```

### Langkah 5: Buat/Update Module Helpers

Buat file `tests/e2e/{module}/helpers.ts` jika belum ada.

**Wajib export:**
- `create{Entity}(page)` — buat entity baru, return identifier
- `search{Entity}(page, identifier)` — cari entity di search

**Opsional export:**
- `edit{Entity}(page, identifier, updates)` — edit entity
- `delete{Entity}(page, identifier)` — hapus entity

Referensi pattern helpers:
```
# Simple CRUD:
mcp_filesystem_read_file(path: "tests/e2e/departments/helpers.ts")

# Complex CRUD:
mcp_filesystem_read_file(path: "tests/e2e/employees/helpers.ts")

# Complex dengan async select:
mcp_filesystem_read_file(path: "tests/e2e/account-mappings/helpers.ts")
```

### Langkah 6: Buat Spec File

Buat file `tests/e2e/{module}/{entity}.spec.ts` menggunakan `generateModuleTests()`.

Gunakan template:
```
mcp_filesystem_read_file(path: ".github/skills/refactor-e2e/resources/module.spec.ts.template")
```

### Langkah 7: Verifikasi

```bash
// turbo-all
npx playwright test tests/e2e/{module}/ --reporter=line
```

Jika gagal:
```
mcp_laravel-boost_browser-logs(entries: 20)
mcp_laravel-boost_last-error()
```

---

## 📏 STANDAR YANG WAJIB DIIKUTI

### Wait Strategy (KONSISTEN untuk semua modul)
```typescript
// ✅ BENAR — gunakan waitForResponse
await page.waitForResponse(
  r => r.url().includes('/api/{module}') && r.status() < 400
).catch(() => null);

// ❌ SALAH — jangan gunakan waitForTimeout
await page.waitForTimeout(1000);

// ❌ SALAH — jangan gunakan waitForLoadState saja
await page.waitForLoadState('networkidle');
```

### Checkbox Selector (KONSISTEN untuk semua modul)
```typescript
// Header checkbox — harus TIDAK ADA
const headerCheckboxes = page.locator('thead').locator('button[role="checkbox"]');
await expect(headerCheckboxes).toHaveCount(0);

// Body checkbox — harus ADA
const bodyCheckbox = page.locator('tbody tr').first().locator('button[role="checkbox"]');
await expect(bodyCheckbox).toBeVisible();
```

### Sorting Pattern (KONSISTEN untuk semua modul)
```typescript
for (const column of sortableColumns) {
  const sortButton = page.getByRole('button', { name: column, exact: true });
  await expect(sortButton).toBeVisible();
  
  // Test ASC
  await sortButton.click();
  await page.waitForResponse(
    r => r.url().includes('/api/{module}') && r.status() < 400
  ).catch(() => null);
  
  // Test DESC
  await sortButton.click();
  await page.waitForResponse(
    r => r.url().includes('/api/{module}') && r.status() < 400
  ).catch(() => null);
}
```

### Export Pattern (KONSISTEN untuk semua modul)
```typescript
const downloadPromise = page.waitForEvent('download');
await page.getByRole('button', { name: /export/i }).click();
const download = await downloadPromise;

// Verify kolom menggunakan ExcelJS
const filePath = `/tmp/test-export-${Date.now()}.xlsx`;
await download.saveAs(filePath);
const workbook = new ExcelJS.Workbook();
await workbook.xlsx.readFile(filePath);
const worksheet = workbook.getWorksheet(1);
const headerRow = worksheet.getRow(1);
const headers = headerRow.values as string[];

for (const col of expectedExportColumns) {
  expect(headers).toContain(col);
}
```

### Actions Dropdown Pattern (KONSISTEN untuk semua modul)
```typescript
// Buka actions menu
await page.locator('tbody tr').first().getByRole('button').last().click();

// Klik View
await page.getByRole('menuitem', { name: 'View' }).click();

// Klik Edit
await page.getByRole('menuitem', { name: 'Edit' }).click();

// Klik Delete
await page.getByRole('menuitem', { name: 'Delete' }).click();
```

---

## 📋 CHECKLIST PER MODUL

- [ ] Identifikasi data modul dari kode existing (Columns, Routes, API)
- [ ] Periksa frontend: Columns.tsx konsisten (select, actions, sorting)
- [ ] Periksa backend: sorting validation, export columns
- [ ] Buat/update `{module}/helpers.ts`
- [ ] Buat `{module}/{entity}.spec.ts` menggunakan `generateModuleTests()`
- [ ] Hapus file spec lama (9 file) setelah spec baru pass
- [ ] Cleanup: hapus module-specific functions dari `tests/e2e/helpers.ts` shared
- [ ] Verifikasi: `npx playwright test tests/e2e/{module}/`
