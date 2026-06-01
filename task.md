# AI Handoff: ERP Active State

Last updated: 2026-06-01 05:15 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## ⚡ Quick Start for Next Session

User is switching to a new opencode session. Read this section first.

1. **Verify baseline**: `git rev-parse HEAD` → expect the latest "feat(aging-dashboard)" commit. `git status --short` → expect empty.
2. **Aging Dashboard AR/AP just landed.** New feature complete: backend Action + Controller + Route + Seeders + 7 Pest tests + frontend page with 5 components + 5 E2E cases. All quality gates green locally.
3. **CI not yet verified** for the aging-dashboard commit at handoff time. Check `gh run list --branch main --limit 3` to confirm green before assuming production-ready.
4. **If user says "lanjutkan" without direction**: ASK which next option (Sonar dup refactor, P&L by Department, Budget Management, Seed dev DB).

### Recommended next-session options (need user input)

1. **Pivot to next product feature**: P&L by Department, Budget Management, or Sales/Invoicing. Highest user value. Aging Dashboard pattern proven and ready to fork.
2. **Sonar duplications refactor candidate**: `AbstractApPaymentRequest` + `AbstractArReceiptRequest` share ~20 lines transaction header validation. Trait extraction = -10 LOC net but modifies validation surface. Sub-threshold metric (gate OK at 0.7%). Needs explicit go-ahead.
3. **Seed dev DB**: `sail artisan db:seed --class=MenuSeeder --class=PermissionSeeder` to activate financial-dashboard + aging-dashboard nav links. Mutates state, low risk but should ask first.
4. **4 deferred typescript:S3358 ternaries in BankReconciliationWorkspace**: deeply nested JSX cells, refactor risk explicitly judged > value. Skip unless user wants Sonar at zero OPEN.

## Current State

- Branch: `main`
- HEAD: latest commit lands the Aging Dashboard AR/AP feature (single commit; backend + frontend + E2E + docs).
- Working tree: clean.
- Sonar Quality Gate: OK (last verified 2026-05-31 17:12 UTC).
- Module registry: 79 entries (added `aging-dashboard` after `financial-dashboard`).

## Aging Dashboard AR/AP — feature summary

`GET /api/aging-dashboard?as_of_date=YYYY-MM-DD&branch_id=N` returns AR + AP outstanding bucketed by overdue age (Current, 1-30, 31-60, 61-90, Over 90 days), as-of date filter (default today), branch filter (default all), plus top-10 overdue customers/suppliers.

### Architecture

| Layer | Files | Notes |
|-------|-------|-------|
| Backend Action | `app/Actions/AgingDashboard/GetAgingDashboardDataAction.php` | Pure aggregation. Uses `DB::table()` + `selectRaw` with parameterized bindings. NO DATEDIFF/CURDATE — cross-DB safe. |
| Backend Controller | `app/Http/Controllers/AgingDashboardController.php` | Invokable. Carbon parses `as_of_date` (fallback today on InvalidFormat). Branches list returned for dropdown. |
| Route | `routes/api/aging-dashboard.php` | Single GET, auto-included via `routes/api.php`. |
| Permission | `database/seeders/PermissionSeeder.php` | New `aging_dashboard` entry, no children. |
| Menu | `database/seeders/MenuSeeder.php` | Child of accounting group, icon `Hourglass`, url `aging-dashboard`. |
| Pest tests | `tests/Feature/AgingDashboard/AgingDashboardControllerTest.php` | 7 cases / 85 assertions. Group `aging-dashboard`. |
| Frontend hook | `resources/js/hooks/useAgingDashboard.ts` | TanStack Query, 60s staleTime, exports all interfaces. |
| Frontend page | `resources/js/pages/aging-dashboard/index.tsx` | URL params (as_of_date, branch_id), AppLayout, Helmet, error alert, refresh button. |
| Frontend components | `resources/js/components/aging-dashboard/` | 5 components: AgingFilters, AgingSummaryCards (4 KPI), AgingBucketChart (5 bars, emerald/rose intensity scale), TopOverdueCustomers, TopOverdueSuppliers. |
| Route registration | `resources/js/app-routes.tsx` | Lazy import + `<Route path="/aging-dashboard">`. |
| E2E spec | `tests/e2e/aging-dashboard/aging-dashboard.spec.ts` | 5 cases / all pass in ~30s. |
| Module registry | `docs/module-registry.md` | New YAML entry + Pest registry row 30. |

### Verification (all green at handoff)

| Gate | Result |
|------|--------|
| TypeScript (`npm run types`) | clean |
| ESLint (`sail npm run lint`) | clean |
| Duster (`sail bin duster fix`) | clean (1788 files) |
| PHPStan (`sail bin phpstan analyze`) | `[OK] No errors` (1049 files) |
| Pest (`sail test --group aging-dashboard`) | 7 passed, 85 assertions, 13.31s |
| Playwright E2E (`tests/e2e/aging-dashboard/`) | 5 passed, 29.9s |

### Implementation notes

- Bucket boundaries are cross-DB safe (Carbon date math + parameterized SUM(CASE) bindings, NOT MySQL DATEDIFF). Aging dashboard works on MySQL/MariaDB/PostgreSQL/SQLite.
- Outstanding filter: AR `status IN ('sent','partially_paid','overdue')`, AP `status IN ('confirmed','partially_paid','overdue')`. Excludes draft, paid, cancelled, void.
- Outstanding amount sourced directly from `amount_due` column (always current — maintained by `SyncArReceiptAllocationsAction` / `SyncApPaymentAllocationsAction`).
- All monetary values cast `round((float) $val, 2)` so JSON serializes as numbers.
- Test fixtures use `Carbon::setTestNow('2026-06-01')` for deterministic bucket assertions.
- Frontend chart bars use Tailwind intensity scale (emerald-200 → emerald-900 for AR, rose-200 → rose-900 for AP) — no chart library dependency.
- `as_of_date` URL param has `'2026-06-01'` fallback string for TS strict mode safety (browser always returns YYYY-MM-DD; fallback only protects from impossible undefined branch).

## Recent History (pre-aging-dashboard, last verified state)

- 2026-05-31 18:08Z: CI verified GREEN on `107104ae` via run `26719382228` (Quality + E2E + Test suite all ✅).
- Sonar OPEN code smells: 4 (deferred typescript:S3358 in BankReconciliationWorkspace, explicit skip with rationale).
- Sonar Security Hotspots: 0 TO_REVIEW.
- 11 commits in 2026-05-31 session covered Sonar cleanup waves 18-25 + dead code removal + FY auto-select E2E. See `task.handoff-archive.md` if archived, or earlier task.md history for details.

## Recommended Next Steps

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 1 | Wait for CI to verify the aging-dashboard commit | Passive | `gh run list --branch main --limit 3` |
| 2 | Pivot to next product feature: P&L by Department / Budget / Sales | High | Highest user value. Pattern proven via aging-dashboard. |
| 3 | Sonar duplications refactor (AP/AR Request trait extraction) | Low | -10 LOC. Sub-threshold metric. Needs explicit go-ahead. |
| 4 | Seed dev DB to activate dashboard nav links | Low | `sail artisan db:seed --class=MenuSeeder --class=PermissionSeeder`. |

## Useful Commands

```bash
# Run focused tests for aging-dashboard
sail test --group aging-dashboard
PLAYWRIGHT_USE_SAIL=1 ./vendor/bin/sail npx playwright test tests/e2e/aging-dashboard/

# Activate the new nav link in dev DB
sail artisan db:seed --class=MenuSeeder
sail artisan db:seed --class=PermissionSeeder

# Quality gates
sail bin phpstan analyze
sail bin duster fix
npm run types
sail npm run lint

# Monitor CI
gh run list --branch main --limit 5
```

## Continuation Prompt

```text
Read task.md first. Repo on `main`. Latest feature: Aging Dashboard AR/AP
(landed 2026-06-01). New endpoint /api/aging-dashboard returns AR+AP
outstanding bucketed by overdue age (Current, 1-30, 31-60, 61-90, 90+),
plus top-10 overdue customers/suppliers, filterable by as_of_date and
branch_id.

All quality gates green locally:
- TypeScript clean, ESLint clean, Duster clean, PHPStan clean
- Pest: 7/7 (85 assertions), group aging-dashboard
- Playwright E2E: 5/5

Files added (8 backend, 7 frontend, 1 E2E, 2 doc updates):
- app/Actions/AgingDashboard/GetAgingDashboardDataAction.php
- app/Http/Controllers/AgingDashboardController.php
- routes/api/aging-dashboard.php (auto-included via routes/api.php)
- tests/Feature/AgingDashboard/AgingDashboardControllerTest.php
- resources/js/hooks/useAgingDashboard.ts
- resources/js/pages/aging-dashboard/index.tsx
- resources/js/components/aging-dashboard/{AgingFilters,AgingSummaryCards,AgingBucketChart,TopOverdueCustomers,TopOverdueSuppliers}.tsx
- tests/e2e/aging-dashboard/aging-dashboard.spec.ts
- docs/module-registry.md (new entry + Pest registry row 30)

Files modified:
- routes/api.php (require new file)
- database/seeders/PermissionSeeder.php (aging_dashboard entry)
- database/seeders/MenuSeeder.php (accounting child entry, icon Hourglass)
- resources/js/app-routes.tsx (lazy import + Route)

Next options (NEED user direction):
1. Pivot next product feature (P&L by Department, Budget, Sales/Invoicing)
2. Sonar duplications refactor (AP/AR Request trait, -10 LOC, gate OK)
3. Seed dev DB to activate nav links

If user says "lanjutkan" without direction, ASK which path to take.
```
