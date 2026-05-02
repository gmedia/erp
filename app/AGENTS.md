# app/ — Backend Knowledge Base

## OVERVIEW

Laravel 12 backend organized by responsibility: Actions (business logic), Domain (services/filters), DTOs, Exports, Http (controllers/requests/resources), Models, and cross-cutting Traits/Services.

## STRUCTURE

```
app/
├── Actions/{Module}/        # Business logic per module (index, store, update, delete, export)
│   └── Concerns/            # Shared action traits (InteractsWithExportFilters, etc.)
├── Domain/{Module}/         # Filter services, query builders
├── DTOs/{Module}/           # Data transfer objects (validated input → typed object)
│   └── Concerns/            # Shared DTO traits
├── Exports/                 # Laravel Excel exports (declarative columns() pattern)
│   └── Concerns/            # InteractsWithExportFilters trait
├── Http/
│   ├── Controllers/         # 69 controllers (Admin/, Api/, Auth/, Concerns/, Settings/)
│   ├── Middleware/           # Custom middleware
│   ├── Requests/{Module}/   # Form requests per module
│   └── Resources/{Module}/  # API resources per module
├── Imports/                 # Import classes (Excel)
├── Mail/                    # Mailable classes
├── Models/                  # 68 Eloquent models
│   └── Concerns/            # Model traits
├── Providers/               # Service providers
├── Services/                # Application services
└── Traits/                  # Global traits
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
| Shared action logic | `Actions/Concerns/` — 16 shared traits |

## CONVENTIONS

- **Actions pattern** — business logic lives in Action classes, NOT controllers. Controllers are thin (delegate to Actions).
- **Export `columns()` pattern** — all exports use declarative `columns()` returning `['Header' => fn($item) => $item->field]`. Headings/mapping auto-derived.
- **SimpleCrud base classes** — `SimpleCrudStoreRequest`, `SimpleCrudUpdateRequest`, `SimpleCrudIndexRequest`, `SimpleCrudExportRequest`, `SimpleCrudResource`, `SimpleCrudCollection`, `SimpleCrudExport` for name-only modules.
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
