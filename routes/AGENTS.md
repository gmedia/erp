# routes/ — API Route Registration

## OVERVIEW

63 module files under `routes/api/`, auto-required inside the `auth:sanctum` group in `routes/api.php`. 406 route definitions; `routes/web.php` is the SPA catch-all only.

## STRUCTURE

```
routes/
├── api.php            # Auth routes + protected group; requires every api/*.php
├── api/{module}.php   # One kebab-case file per module
├── console.php        # Artisan scheduling
└── web.php            # SPA catch-all — DO NOT ADD ROUTES
```

## WHERE TO LOOK

| Task | Location |
|------|----------|
| Add module routes | `routes/api/{module-slug}.php` + one `require` in `api.php` |
| Public auth routes | `routes/api.php` (before the protected group) |
| Permission middleware | Module file wrapper + per-action route middleware |
| SPA fallback | `routes/web.php` (read-only) |

## CONVENTIONS

- **One file per module** — kebab-case filename matches the URL prefix (`routes/api/purchase-orders.php` → `/api/purchase-orders`).
- **Registration** — add `require __DIR__ . '/api/{module-slug}.php';` inside the existing `auth:sanctum` group in `api.php`. The list is mostly alphabetical but grouped by domain in places — place related modules next to each other.
- **Permission wrapper** — wrap the module in `Route::middleware('permission:{resource},true')->group(function () { ... });`.
- **Action permissions** — `permission:{resource}.create`, `.edit`, `.delete` on the matching verbs.
- **Thin controllers** — `[Controller::class, 'method']` array callables; delegate logic to `app/Actions`.
- **Model binding** — default `{department}` binding; use `{id}` only when binding is intentionally bypassed.

## ANTI-PATTERNS

- ❌ Adding routes to `routes/web.php`.
- ❌ Creating a top-level route file that `api.php` never requires.
- ❌ snake_case/camelCase route filenames.
- ❌ Prefixing `api/` inside module files — Laravel already applies it.
- ❌ Inline validation or business logic — use FormRequest + Action.
- ❌ Repeating the module-level `permission:` middleware on every route.

## COMMANDS

```bash
sail artisan route:list --path=api
sail artisan route:list --path=api/{module-slug}
```
