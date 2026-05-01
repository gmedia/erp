# Development Patterns Guide

> Referensi pola implementasi standar untuk developer yang membuat modul baru atau memodifikasi modul existing.
> Semua pattern di bawah sudah divalidasi dan konsisten di seluruh codebase.

---

## Frontend: CRUD Module

### Page File

Setiap CRUD module hanya butuh 1 page file (6 lines):

```tsx
// resources/js/pages/{module-slug}/index.tsx
'use client';

import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import { xxxConfig } from '@/utils/entityConfigs';

export default createEntityCrudPage(xxxConfig);
```

Jika module punya import feature, tambahkan `toolbarActions`:

```tsx
const config = {
    ...xxxConfig,
    toolbarActions: (
        <ImportDialog title="Import Xxx" importRoute="/api/xxx/import" templateHeaders={[...]} />
    ),
};
export default createEntityCrudPage(config);
```

### Entity Config

Registrasi di `resources/js/utils/entityConfigs.ts`:

**Simple CRUD** (hanya name field):
```tsx
export const xxxConfig = createSimpleEntityConfig({
    entityName: 'Xxx',
    entityNamePlural: 'Xxxs',
    apiBase: 'xxxs',
    filterPlaceholder: 'Search xxxs...',
});
```

**Complex CRUD** (custom columns, filters, form, view modal):
```tsx
export const xxxConfig = createComplexEntityConfig<Xxx>({
    entityName: 'Xxx',
    entityNamePlural: 'Xxxs',
    apiEndpoint: '/api/xxxs',
    exportEndpoint: '/api/xxxs/export',
    queryKey: ['xxxs'],
    breadcrumbs: [{ title: 'Xxxs', href: '/xxxs' }],
    initialFilters: { search: '', status: '' },
    columns: xxxColumns,
    filterFields: createXxxFilterFields(),
    formComponent: XxxForm,
    formType: 'complex',
    entityNameForSearch: 'xxx',
    viewModalComponent: XxxViewModal,
    getDeleteMessage: (item) => `...delete ${item.name}...`,
});
```

### Form Component (menggunakan `useEntityForm` hook)

```tsx
// resources/js/components/{module}/XxxForm.tsx
import { useEntityForm } from '@/hooks/useEntityForm';
import EntityForm from '@/components/common/EntityForm';
import { xxxFormSchema, type XxxFormData } from '@/utils/schemas';

interface XxxFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: Xxx | null;          // ← selalu gunakan 'entity' sebagai prop name
    onSubmit: (data: XxxFormData) => void;
    isLoading?: boolean;
}

const getDefaults = (entity?: Xxx | null): XxxFormData => ({
    name: entity?.name || '',
    // ... field defaults
});

export const XxxForm = memo<XxxFormProps>(function XxxForm({
    open, onOpenChange, entity, onSubmit, isLoading = false,
}) {
    const form = useEntityForm<XxxFormData, Xxx>({
        schema: xxxFormSchema,
        getDefaults,
        entity,
    });

    return (
        <EntityForm form={form} open={open} onOpenChange={onOpenChange}
            title={entity ? 'Edit Xxx' : 'Add New Xxx'}
            onSubmit={onSubmit} isLoading={isLoading}
        >
            {/* form fields here */}
        </EntityForm>
    );
});
```

### ViewModal Component

```tsx
// resources/js/components/{module}/XxxViewModal.tsx
import { memo } from 'react';
import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { useTranslation } from '@/contexts/i18n-context';

export const XxxViewModal = memo<XxxViewModalProps>(
    function XxxViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <ViewModalShell open={open} onClose={onClose}
                title="View Xxx" description={t('common.view_details')}
            >
                <div className="space-y-4 py-4">
                    <ViewField label="Name" value={item.name} />
                    <ViewField label="Status" value={<Badge>...</Badge>} />
                    {/* more fields */}
                </div>
            </ViewModalShell>
        );
    },
);
```

### Sibling File Structure

Setiap complex CRUD module punya 4 sibling files:
```
resources/js/components/{module}/
├── XxxColumns.tsx      # Column definitions using createTextColumn, createSelectColumn, etc.
├── XxxFilters.tsx      # Filter field definitions using createTextFilterField, createAsyncSelectFilterField
├── XxxForm.tsx         # Form component using useEntityForm hook
└── XxxViewModal.tsx    # View modal using ViewField + ViewModalShell + memo
```

---

## Frontend: Report Page

```tsx
// resources/js/pages/reports/{report-slug}/index.tsx
import { createEmptyReportFilters, createReportBreadcrumbs, ReportDataTablePage } from '@/components/common/ReportDataTablePage';
import { xxxColumns } from '@/components/reports/{report-slug}/Columns';
import { createXxxFilterFields } from '@/components/reports/{report-slug}/Filters';

export default function XxxReportPage() {
    return (
        <ReportDataTablePage<XxxItem>
            title="Xxx Report"
            breadcrumbs={createReportBreadcrumbs('Xxx', '/reports/xxx')}
            columns={xxxColumns}
            filterFields={createXxxFilterFields()}
            initialFilters={createEmptyReportFilters(['field1', 'field2'] as const)}
            endpoint="/reports/xxx"
            queryKey={['xxx-report']}
            entityName="Xxx"
            exportEndpoint="/reports/xxx/export"
        />
    );
}
```

---

## Backend: Model

```php
class Xxx extends Model
{
    /** @use HasFactory<\Database\Factories\XxxFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [...];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        // JANGAN include 'created_at' => 'datetime' atau 'updated_at' => 'datetime'
        // Eloquent sudah handle ini secara otomatis
    ];
}
```

---

## Backend: Controller

```php
class XxxController extends Controller
{
    use LoadsResourceRelations;  // jika perlu load relations di show/store/update

    public function show(Xxx $xxx): JsonResponse
    {
        return (new XxxResource($this->loadResourceRelations($xxx)))->response();
    }

    public function destroy(Xxx $xxx): JsonResponse
    {
        return $this->destroyModel($xxx);  // ← gunakan base helper, bukan manual delete
    }

    protected function resourceRelations(): array
    {
        return ['relation1', 'relation2'];
    }
}
```

**Exception**: Jika `destroy()` punya domain logic (guard, cascade, soft-cancel), tulis manual.

---

## Backend: Export (Declarative `columns()` Pattern)

```php
class XxxExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Xxx::query()->with([...]);
        // apply filters + sorting
        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($item): array
    {
        return $this->mapExportRow($item, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (Xxx $x): mixed => $x->id,
            'Name' => fn (Xxx $x): mixed => $x->name,
            'Relation' => fn (Xxx $x): mixed => $this->relatedAttribute($x, 'relation', 'name'),
            'Date' => fn (Xxx $x): mixed => $this->formatDateValue($x->some_date, 'Y-m-d'),
            'Created At' => fn (Xxx $x): mixed => $this->formatIso8601($x->created_at),
        ];
    }
}
```

**Benefit**: Tambah/hapus kolom = 1 line change. Headings dan mapping selalu sinkron.

---

## Checklist: Membuat Module Baru

### Frontend
- [ ] Config di `entityConfigs.ts` (simple atau complex)
- [ ] Page file di `pages/{module-slug}/index.tsx`
- [ ] Route di `app-routes.tsx`
- [ ] Sibling files: `Columns.tsx`, `Filters.tsx`, `Form.tsx` (pakai `useEntityForm`), `ViewModal.tsx` (pakai `ViewField` + `memo`)
- [ ] Form prop: selalu gunakan `entity` (bukan nama spesifik)
- [ ] `npm run types` clean

### Backend
- [ ] Model: `@use HasFactory`, `@var list<string>`, `@var array<string, string>`, no redundant timestamp casts
- [ ] Controller: `destroyModel()` di `destroy()`, `loadResourceRelations()` di `show()`
- [ ] Export: `ShouldAutoSize`, typed `$filters`, `columns()` pattern
- [ ] Request/Resource/Collection sesuai family (SimpleCrud* atau custom)
- [ ] PHPStan clean
- [ ] Pest tests: Controller + Export + Model

### Testing
- [ ] Pest group: `->group('{module-slug}')` (kebab-case)
- [ ] E2E: `tests/e2e/{module-slug}/{module}.spec.ts`

---

## Helper Reference

| Helper | Location | Purpose |
|--------|----------|---------|
| `useEntityForm` | `hooks/useEntityForm.ts` | Eliminates form setup boilerplate |
| `createSimpleEntityConfig` | `utils/entityConfigs.ts` | Config for name-only entities |
| `createComplexEntityConfig` | `utils/entityConfigs.ts` | Config for multi-field entities |
| `createEntityCrudPage` | `components/common/EntityCrudPage.tsx` | Page factory from config |
| `ReportDataTablePage` | `components/common/ReportDataTablePage.tsx` | Report page shell |
| `ViewField` | `components/common/ViewField.tsx` | Consistent field display |
| `ViewModalShell` | `components/common/ViewModalShell.tsx` | Modal wrapper |
| `EntityForm` | `components/common/EntityForm.tsx` | Form dialog wrapper |
| `InteractsWithExportFilters` | `Exports/Concerns/InteractsWithExportFilters.php` | Export helpers (columns, filters, dates) |
| `LoadsResourceRelations` | `Controllers/Concerns/LoadsResourceRelations.php` | Relation loading trait |
| `StoresItemsInTransaction` | `Controllers/Concerns/StoresItemsInTransaction.php` | Transaction form pattern |
| `destroyModel()` | `Controller.php` (base) | Standard delete response |
