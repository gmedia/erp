# Rencana Deduplikasi Style Antar Modul Sejenis — 2 Hari

> Lanjutan dari refactor style consistency (Tahap 0–6 + polish).
> Fokus: mengurangi duplikasi kode aktual antar modul sejenis, bukan hanya cosmetic consistency.

Status: ✅ **SELESAI** — dieksekusi 2026-05-01
Tanggal mulai: 2026-05-01
Deadline: 2026-05-02 (selesai hari pertama)

---

## Konteks

Refactor sebelumnya sudah menangani:
- Docblocks, destroy patterns, prop naming, ViewModal migration (cosmetic)
- 160 E2E smoke tests green, 1,247+ Pest tests validated

Yang **belum dikerjakan** — duplikasi kode aktual:
1. Frontend form boilerplate (~10 lines identik per form × 10 forms)
2. Backend export manual `headings()`/`map()` vs declarative `columns()` pattern

---

## Hari 1: Extract `useEntityForm` Hook + Migrate Forms

### Problem

Setiap complex CRUD form mengulang pattern yang sama:

```tsx
const defaultValues = useMemo(() => getDefaults(entity), [entity]);
const form = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues,
});
useEffect(() => { form.reset(defaultValues); }, [form, defaultValues]);
```

Ini 7-10 lines boilerplate yang identik di setiap form.

### Solution

Buat shared hook:

```tsx
// resources/js/hooks/useEntityForm.ts
export function useEntityForm<TFormData, TEntity>({
    schema,
    getDefaults,
    entity,
}: {
    schema: ZodSchema;
    getDefaults: (entity?: TEntity | null) => TFormData;
    entity?: TEntity | null;
}) {
    const defaultValues = useMemo(() => getDefaults(entity), [entity, getDefaults]);
    const form = useForm<TFormData>({
        resolver: zodResolver(schema),
        defaultValues,
    });
    useEffect(() => { form.reset(defaultValues); }, [form, defaultValues]);
    return form;
}
```

### Target Forms (5 simplest first)

| # | Form | Lines Saved | Complexity |
|---|------|-------------|------------|
| 1 | `WarehouseForm` | ~8 | Low — 3 fields |
| 2 | `ProductCategoryForm` | ~8 | Low — 2 fields |
| 3 | `UnitForm` | ~8 | Low — 2 fields |
| 4 | `CustomerForm` | ~8 | Low — 8 fields |
| 5 | `SupplierForm` | ~8 | Low — 7 fields |

### Langkah

1. Buat `resources/js/hooks/useEntityForm.ts`
2. Validasi TypeScript
3. Migrasi `WarehouseForm` sebagai pilot
4. Validasi E2E `warehouses`
5. Migrasi 4 form lainnya
6. Validasi E2E per module
7. Commit + push

### Validasi Akhir Hari 1

```bash
./vendor/bin/sail npm run types
./vendor/bin/sail npm run test:e2e -- tests/e2e/warehouses/ tests/e2e/customers/ tests/e2e/suppliers/ tests/e2e/units/ tests/e2e/product-categories/
```

---

## Hari 2: Migrate Standalone Exports ke `columns()` Pattern

### Problem

Beberapa exports masih pakai manual pattern:

```php
public function headings(): array {
    return ['ID', 'Name', 'Email', ...]; // Manual array
}

public function map($model): array {
    return [$model->id, $model->name, ...]; // Manual mapping
}
```

Sementara yang sudah modern pakai declarative pattern:

```php
protected function columns(): array {
    return [
        'ID' => fn ($model) => $model->id,
        'Name' => fn ($model) => $model->name,
        ...
    ];
}

public function headings(): array { return $this->exportHeadings($this->columns()); }
public function map($model): array { return $this->mapExportRow($model, $this->columns()); }
```

Pattern declarative lebih maintainable: tambah/hapus kolom = 1 line change.

### Target Exports

| # | Export | Current Pattern | Lines Saved |
|---|--------|----------------|-------------|
| 1 | `WarehouseExport` | Manual headings + map + inline query | ~15 |
| 2 | `GoodsReceiptExport` | Manual headings + map | ~15 |
| 3 | `PurchaseRequestExport` | Manual headings + map | ~15 |
| 4 | `SupplierReturnExport` | Manual headings + map | ~15 |

### Langkah

1. Baca `InteractsWithExportFilters` trait untuk understand `columns()` + `exportHeadings()` + `mapExportRow()` API
2. Migrasi `WarehouseExport` sebagai pilot
3. Validasi: PHPStan + Pest `warehouses`
4. Migrasi 3 export lainnya
5. Validasi per module
6. Commit + push

### Validasi Akhir Hari 2

```bash
./vendor/bin/sail php ./vendor/bin/phpstan analyse app/Exports/WarehouseExport.php app/Exports/GoodsReceiptExport.php app/Exports/PurchaseRequestExport.php app/Exports/SupplierReturnExport.php
./vendor/bin/sail test --group=warehouses --group=goods-receipts --group=purchase-requests --group=supplier-returns
```

---

## Guardrails

- Jangan ubah API response shape, route, endpoint, query param, atau data-testid
- Hook baru harus backward-compatible (forms yang belum migrasi tetap bekerja)
- Export migrasi harus menghasilkan output Excel yang identik (same headings, same data)
- Commit per batch (max 8 files per commit)
- Validasi segera setelah edit pertama
- Jika perlu perubahan struktural file (rename/move/split), jalankan Depwire dulu

---

## Definition of Done (Akhir Hari 2)

- [x] `useEntityForm` hook dibuat dan dipakai oleh 5 forms — `8d8a7d44`
- [x] 4 standalone exports dimigrasi ke `columns()` pattern — `ea25834b`
- [x] `npm run types` clean
- [x] Pest 95 passed (warehouses + goods-receipts + purchase-requests + supplier-returns)
- [x] E2E 47 passed (warehouses + product-categories + units + customers + suppliers)
- [x] Semua perubahan pushed ke remote

### Commits (Initial Plan)

| # | Hash | Description | Impact |
|---|------|-------------|--------|
| 1 | `8d8a7d44` | Extract useEntityForm hook + migrate 5 forms | -150 lines frontend |
| 2 | `ea25834b` | Migrate 4 exports to columns() pattern | -14 lines backend, better maintainability |

### Commits (Continuation Sessions)

| # | Hash | Description | Impact |
|---|------|-------------|--------|
| 3 | `03f559df` | Migrate 3 more forms to useEntityForm (AssetLocation, AssetCategory, FiscalYear) | -89 lines frontend |
| 4 | `6b7a1880` | Migrate 3 more exports to columns() (Employee, JournalEntry, AssetMovement) | -20 lines + N+1 fix |
| 5 | `6dd37f55` | Migrate final 8 standalone exports to columns() pattern | +42 lines (more declarative, but single source of truth) |

### Final Totals

| Metric | Value |
|--------|-------|
| `useEntityForm` adopters | 8 forms |
| `columns()` pattern exports | 15 exports (all standalone) |
| Total lines reduced (forms) | ~239 lines |
| Pest tests validated | 589 passed |
| E2E tests validated | 74 passed |

---

## Fallback

Jika Hari 1 (useEntityForm) memakan waktu lebih dari expected:
- Prioritaskan pilot (1 form) + validasi, lalu lanjut ke Hari 2 (exports)
- Form migration bisa dilanjutkan di sprint berikutnya
- Export migration lebih mechanical dan lower-risk
