# PROJECT KNOWLEDGE BASE

**Generated:** 2025-05-02
**Commit:** ddc886a7
**Branch:** main

## OVERVIEW

ERP system (gmedia) — Laravel 12 JSON API backend + React 19 full SPA frontend. Manages finance, assets, inventory, purchasing, approvals, and pipelines across 60+ modules.

## STRUCTURE

```
erp/
├── app/                # Backend: Actions, Domain, DTOs, Exports, Http, Models, Services
├── resources/js/       # Frontend SPA: React + TypeScript + Shadcn UI + TanStack Query
├── routes/api/         # 48 modular route files (auto-included by routes/api.php)
├── database/           # Migrations (67), Factories (67), Seeders (24)
├── tests/              # Pest (Feature/Unit) + Playwright E2E
├── docs/               # Design docs, module registry, refactor progress
├── .github/            # Skills, prompts, workflows, agent configs
├── scripts/            # Sync scripts, E2E lock helper
├── docker/             # Docker configs
└── .kilo/              # Kilo sync configs
```

## WHERE TO LOOK

| Task | Location | Notes |
|------|----------|-------|
| Add new module (backend) | `app/Actions/{Module}/`, `app/Http/Controllers/`, `routes/api/` | Follow `docs/development-patterns.md` |
| Add new module (frontend) | `resources/js/pages/{slug}/`, `resources/js/components/{slug}/` | Use `createEntityCrudPage` pattern |
| Register route (frontend) | `resources/js/app-routes.tsx` | Lazy-loaded `<Route>` |
| Register route (backend) | `routes/api/{module-slug}.php` | Auto-included |
| Add export | `app/Exports/` | Declarative `columns()` pattern |
| Add report | `app/Actions/Reports/`, `resources/js/pages/reports/` | `ReportDataTablePage` shell |
| Shared UI components | `resources/js/components/common/`, `resources/js/components/ui/` | Shadcn + custom |
| Shared backend traits | `app/Actions/Concerns/`, `app/Http/Controllers/Concerns/` | `InteractsWithExportFilters`, `LoadsResourceRelations` |
| Entity configs | `resources/js/utils/entityConfigs.ts` | Simple vs Complex config |
| Form schemas | `resources/js/utils/schemas.ts` (or per-module) | Zod validation |
| Type definitions | `resources/js/types/` | 36 type files |
| Hooks | `resources/js/hooks/` | `useEntityForm`, `useCrudQuery`, `useCrudMutations` |
| Module metadata | `docs/module-registry.md` | Full E2E + Pest registry |
| Dev patterns | `docs/development-patterns.md` | Canonical implementation guide |
| Session handoff | `task.md` | Active status + next action |
| Skills/templates | `.github/skills/` | Scaffolding for new features |

## CONVENTIONS

- **NO Inertia.js** — pure API + SPA. Never import `@inertiajs/react`.
- **Sanctum Bearer Token** — stateless auth, not session/cookies.
- **`routes/web.php`** — catch-all SPA only. NEVER add routes here.
- **Sail required** — all runtime commands via `./vendor/bin/sail <cmd>`.
- **Empty wrapper classes** — must have `// Intentionally empty...` comment body.
- **Import hygiene** — always `use` at top, never FQCN with `\` in body.
- **Duster fix** — run `sail bin duster fix` after PHP changes.
- **Module naming** — kebab-case for routes/slugs, PascalCase for classes.
- **Form prop** — always `entity` (never module-specific name).
- **Test groups** — `->group('{module-slug}')` kebab-case, single group only.
- **react-helmet-async** — aliased to local shim (`resources/js/lib/`), never re-add upstream dep.

## ANTI-PATTERNS (THIS PROJECT)

- ❌ `Inertia::render()`, `@inertiajs/react` imports
- ❌ `actingAs($user)` in tests → use `Sanctum::actingAs($user)`
- ❌ `assertInertia()` → use `assertJson()`, `assertJsonStructure()`
- ❌ Routes in `routes/web.php`
- ❌ FQCN with leading `\` in PHP body code
- ❌ Empty class bodies without intent comment
- ❌ Redundant `'created_at' => 'datetime'` casts in models
- ❌ Running commands without `sail` prefix

## ARCHITECTURE PATTERNS

| Pattern | Backend | Frontend |
|---------|---------|----------|
| Simple CRUD | `SimpleCrud*` base classes | `createSimpleEntityConfig()` |
| Complex CRUD | Custom Request/Resource/Export | `createComplexEntityConfig()` + sibling files |
| Reports | `AbstractReportIndexExport` + Action | `ReportDataTablePage` |
| Transactions | `StoresItemsInTransaction` trait | `ItemFormDialog` + nested form |
| Exports | `columns()` declarative + `InteractsWithExportFilters` | Export button in toolbar |
| Pipelines | `EntityState` engine | `EntityStateActions` component |
| Approvals | `ApprovalFlow` + `ApprovalFlowStep` | `MyApprovals` inbox |

## COMMANDS

```bash
# Development
composer dev                          # Server + queue + pail + vite (concurrent)
sail npm run dev                      # Vite only

# Testing
sail test --group {module-slug}       # Pest by module
sail test                             # All Pest tests
npm run test:e2e                      # All Playwright E2E
npx playwright test tests/e2e/{slug}/ # E2E by module

# Quality
sail bin phpstan analyze              # Static analysis
sail bin duster fix                   # Code style (TLint + Pint + CS Fixer)
sail npm run lint                     # ESLint
sail npm run format                   # Prettier
npm run types                         # TypeScript check

# Utilities
sail artisan ide-helper:generate      # Facade autocomplete
sail artisan ide-helper:models -RW    # Model PHPDoc
```

## NOTES

- **11,983 files** total (1,594 PHP, 377 TSX, 194 TS) — large codebase.
- **48 API route files** — one per module, auto-included.
- **68 models**, **67 migrations**, **67 factories** — near 1:1 ratio.
- **SonarCloud** integrated — quality gate, duplication, coverage tracked.
- **Sentry** for error monitoring.
- **Octane** for performance (production).
- **Scramble** for auto-generated API docs.
- **Zustand** used alongside React Query for client state.
- **`task.md`** is the handoff document — read it first when continuing work.
- **`.github/skills/`** has scaffolding templates for new modules.
- Typo in codebase: `ApprovalDelegationss` (double 's') exists — legacy, don't rename without impact analysis.
