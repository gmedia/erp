# Rencana Polish Style Consistency — 3 Hari

> Lanjutan dari refactor Tahap 0–6 yang sudah selesai.
> Fokus: mechanical consistency fixes yang zero-risk, meningkatkan DX untuk development fitur baru setelahnya.

Status: ✅ **SELESAI** — dieksekusi 2026-04-30 (lebih cepat dari jadwal)
Tanggal rencana awal: 2026-05-01 – 2026-05-03
Tanggal eksekusi aktual: 2026-04-30 (single session)

---

## Konteks

Refactor Tahap 0–6 sudah menangani:
- 50 production files modified
- 1,247+ Pest tests validated
- 160 E2E smoke tests green
- Semua backlog items resolved

Yang **belum tersentuh** secara menyeluruh:
1. Model docblocks — hanya 24 dari ~50+ models yang sudah di-fix
2. Export date formatting — masih ada 3 style berbeda
3. Controller `show()` relation loading — beberapa masih tidak load relations
4. Frontend Form/ViewModal prop naming — inconsistent antar sibling

---

## Hari 1: Sweep Model Docblocks + Redundant Casts (Seluruh Codebase)

### Target

Audit **semua** model yang belum tersentuh di Tahap 1–5, termasuk:
- Item models: `PurchaseOrderItem`, `PurchaseRequestItem`, `GoodsReceiptItem`, `SupplierReturnItem`, `StockTransferItem`, `InventoryStocktakeItem`, `StockAdjustmentItem`
- Pivot/support models: `PipelineState`, `PipelineEntityState`, `ApprovalFlowStep`, `ProductStock`, `ProductPrice`, `BillOfMaterial`, `ProductDependency`, `JournalEntryLine`, `AssetStocktakeItem`
- Other models: `Employee`, `AssetMaintenance`, `AssetLocation`, `ApprovalDelegation`, `Account`, `Setting`, `User`

### Checklist per Model

- [ ] `/** @use HasFactory<\Database\Factories\XxxFactory> */` annotation (jika pakai HasFactory)
- [ ] `@var list<string>` docblock di atas `$fillable`
- [ ] `@var array<string, string>` docblock di atas `$casts` (jika property-style)
- [ ] Hapus redundant `'created_at' => 'datetime'` dan `'updated_at' => 'datetime'`
- [ ] Import relation return types (bukan inline FQCN)

### Aturan

- Jangan ubah logic, hanya docblocks dan redundant casts
- Batch per family (item models bersama, support models bersama)
- Validasi: `./vendor/bin/sail php ./vendor/bin/phpstan analyse <files>` per batch
- Commit per batch (max 2-3 commits di hari ini)

### Validasi Akhir Hari 1

```bash
./vendor/bin/sail npm run types
./vendor/bin/sail test --group=purchase-orders --group=goods-receipts --group=purchase-requests --group=supplier-returns
```

---

## Hari 2: Export Date Formatting + Controller `show()` Relation Loading

### Target A: Standardize Export Date Formatting

Audit semua Export classes untuk date formatting consistency:

| Pattern Saat Ini | Target |
|-----------------|--------|
| `->format('Y-m-d')` | `$this->formatIso8601()` atau `$this->formatDateValue()` |
| `->toIso8601String()` | OK (keep) |
| `$this->formatIso8601()` | OK (keep) |
| `$this->formatDateValue($date, 'Y-m-d H:i:s')` | Ganti ke `$this->formatIso8601()` untuk consistency |

Files yang perlu dicek:
- `app/Exports/EmployeeExport.php` (manual `->format('Y-m-d')`)
- `app/Exports/CustomerExport.php` (`formatDateValue(..., 'Y-m-d H:i:s')`)
- `app/Exports/WarehouseExport.php` (`->toIso8601String()`)
- Semua export lain yang belum pakai helper

### Target B: Controller `show()` Relation Loading

Controllers yang belum load relations di `show()`:
- `EmployeeController::show()` — tambah `$employee->load(['department', 'position', 'branch'])`
- `ProductController::show()` — tambah `$product->load(['category', 'unit', 'branch'])`
- `FiscalYearController::show()` — tidak perlu (no FK relations to display)

### Aturan

- Jangan ubah response shape — hanya ensure relations loaded
- Export date format change: pastikan output tetap ISO 8601 string
- Validasi per module setelah edit

### Validasi Akhir Hari 2

```bash
./vendor/bin/sail php ./vendor/bin/phpstan analyse app/Exports/ app/Http/Controllers/EmployeeController.php app/Http/Controllers/ProductController.php
./vendor/bin/sail test --group=employees --group=products --group=customers --group=warehouses
```

---

## Hari 3: Frontend Form/ViewModal Prop Naming Consistency

### Target

Audit dan standardize prop naming di custom Form dan ViewModal components:

#### Form Props

| Pattern Saat Ini | Target Standard |
|-----------------|-----------------|
| `customer?: Customer \| null` | `entity?: Customer \| null` |
| `supplier?: Supplier \| null` | `entity?: Supplier \| null` |
| `unit?: Unit \| null` + `entity?: Unit \| null` (dual prop) | `entity?: Unit \| null` (single) |

Standard: semua Form components menerima `entity` prop (yang sudah di-pass oleh `EntityCrudPage` factory).

#### ViewModal Style

| Pattern Saat Ini | Target Standard |
|-----------------|-----------------|
| Inline HTML (`<span className="font-bold">`) | `ViewField` component |
| Mixed `memo()` vs plain function | `memo()` untuk semua ViewModal |
| Inconsistent `contentClassName` | Standardize ke `sm:max-w-[500px]` untuk simple, `max-w-2xl` untuk complex |

### Scope (max 8 files per commit)

Wave 3A forms (paling serupa):
- `SupplierForm.tsx` — rename `supplier` prop ke `entity`
- `CustomerForm.tsx` — rename `customer` prop ke `entity`
- `UnitForm.tsx` — hapus dual prop, keep `entity` only

Wave 3A view modals:
- `AssetCategoryViewModal.tsx` — migrasi dari inline HTML ke `ViewField`

### Aturan

- Jangan ubah `data-testid` atau behavior
- Prop rename harus backward-compatible (EntityCrudPage passes `entity` prop)
- Validasi TypeScript setelah setiap rename
- E2E test per module yang di-touch

### Validasi Akhir Hari 3

```bash
./vendor/bin/sail npm run types
./vendor/bin/sail npm run test:e2e:smoke-waves
```

---

## Guardrails

- Jangan ubah route, endpoint, payload, query param, permission, atau `data-testid`
- Jangan ubah API response shape
- Commit per batch (max 8 files per commit)
- Validasi segera setelah edit pertama
- Jika ada perubahan struktural file (rename/move/split), jalankan Depwire dulu
- Jika ragu tentang behavior library, pakai Context7

---

## Definition of Done (Akhir Hari 3)

- [x] Semua model di codebase punya docblocks yang konsisten — `010e8c37` (36 files)
- [x] ~~Semua export pakai date formatting helper yang sama~~ — Audited: differences are intentional (date-only vs datetime vs ISO 8601). No changes needed.
- [x] Controller `show()` load relations yang dibutuhkan Resource — `3a678300` (Employee + Product)
- [x] Frontend Form props konsisten (`entity` naming) — `a30b7511` (Supplier, Customer, Unit)
- [ ] Frontend ViewModal pakai `ViewField` + `memo()` pattern — Deferred (AssetCategoryViewModal only, low priority)
- [x] `npm run types` clean
- [x] `test:e2e:smoke-waves` green (160 passed) — validated pre-commit
- [x] Semua perubahan pushed ke remote

### Commits

| # | Hash | Description | Files |
|---|------|-------------|-------|
| 1 | `010e8c37` | Model docblock sweep (Hari 1) | 36 |
| 2 | `3a678300` | Controller show() relation loading (Hari 2) | 2 |
| 3 | `a30b7511` | Frontend form prop naming (Hari 3) | 3 |

---

## Fallback

Jika Hari 3 (frontend) terlalu risky atau memakan waktu lebih dari expected:
- Prioritaskan Hari 1 + Hari 2 (backend-only, zero frontend risk)
- Hari 3 bisa di-skip atau dilakukan sebagian saja
- Frontend prop rename bisa ditunda ke sprint berikutnya tanpa blocking fitur baru
