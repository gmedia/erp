# app/ — Backend Knowledge Base

## OVERVIEW

Laravel 12 backend organized by responsibility: Actions (business logic), Domain (services/filters), DTOs, Exports, Http (controllers/requests/resources), Models, and cross-cutting Traits/Services.

## STRUCTURE

```
app/
├── Actions/{Module}/        # 61 modules — business logic (index, store, update, delete, export)
│   └── Concerns/            # 19 shared action files (10 traits) — SimpleCrud actions, export filters
├── Console/Commands/        # Artisan commands (backfills, maintenance)
├── Domain/{Module}/         # 48 modules — filter services, query builders
├── DTOs/{Module}/           # 24 modules — validated input → typed object
│   └── Concerns/            # Shared DTO traits
├── Exports/                 # 71 Laravel Excel exports (declarative columns() pattern)
│   └── Concerns/            # SimpleCrudExport, AbstractReportIndexExport, InteractsWithExportFilters
├── Http/
│   ├── Controllers/         # 90 controllers (Admin/, Api/, Concerns/)
│   ├── Middleware/           # Custom middleware
│   ├── Requests/{Module}/   # 54 FormRequest dirs
│   └── Resources/{Module}/  # 55 API resource dirs
├── Imports/                 # Import classes (Excel)
├── Mail/                    # Mailable classes
├── Models/                  # 89 Eloquent models
│   └── Concerns/            # Model traits
├── Providers/               # Service providers
├── Services/                # 5 application services (financial, currency, budgets)
└── Traits/                  # 2 global traits
```

## WHERE TO LOOK

| Task | Location |
|------|----------|
| Add business logic | `Actions/{Module}/` — one action class per operation |
| Add filter/sort | `Domain/{Module}/{Module}FilterService.php` |
| Add export | `Exports/{Module}Export.php` — use `columns()` pattern |
| Add form validation | `Http/Requests/{Module}/` |
| Add API response shape | `Http/Resources/{Module}/` |
| Add model | `Models/` — with `HasFactory`, typed `$fillable`, `$casts` |
| Shared controller logic | `Http/Controllers/Concerns/` — `LoadsResourceRelations`, `StoresItemsInTransaction` |
| Shared export logic | `Exports/Concerns/InteractsWithExportFilters` |
| Shared action logic | `Actions/Concerns/` — 19 files (10 traits) |
| Artisan command | `Console/Commands/` — backfills, maintenance |
| Financial service | `Services/FinancialReportService.php`, `BudgetVarianceService.php` |
| Pipeline engine | `Models/Pipeline.php`, `PipelineState.php`, `PipelineTransition.php`, `PipelineEntityState.php` |
| Report export base | `Exports/Concerns/AbstractReportIndexExport.php` |

## CONVENTIONS

- **Actions pattern** — business logic lives in Action classes, NOT controllers. Controllers are thin (delegate to Actions).
- **Export `columns()` pattern** — all exports use declarative `columns()` returning `['Header' => fn($item) => $item->field]`. Headings/mapping auto-derived.
- **SimpleCrud base classes** — `SimpleCrudStoreRequest`, `SimpleCrudUpdateRequest`, `SimpleCrudIndexRequest`, `SimpleCrudExportRequest`, `SimpleCrudListingRequest`, `SimpleCrudNameRequest`, `SimpleCrudResource`, `SimpleCrudCollection`, `SimpleCrudExport`; actions via `SimpleCrudIndexAction` / `SimpleCrudExportAction` for name-only modules.
- **Controller `destroy()`** — use `$this->destroyModel($model)` helper. Exception: soft-cancel pattern for transactions.
- **Controller `show()`** — use `$this->loadResourceRelations($model)` via `LoadsResourceRelations` trait.
- **DTOs** — typed value objects for complex store/update operations. Created from validated request data.
- **Domain services** — `{Module}FilterService` handles search, filter, sort logic extracted from controllers.
- **Model casts** — NEVER include `'created_at' => 'datetime'` (Eloquent handles automatically).

## ANTI-PATTERNS

- ❌ Business logic in controllers (use Actions)
- ❌ Manual delete in `destroy()` without `destroyModel()` (unless soft-cancel)
- ❌ Inline query building in controllers (extract to FilterService)
- ❌ Duplicating export headings/mapping (use `columns()` pattern)
- ❌ Empty class body without `// Intentionally empty...` comment
