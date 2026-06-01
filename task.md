# AI Handoff: ERP Active State

Last updated: 2026-06-01 12:45 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## ⚡ Quick Start for Next Session

User is switching to a new opencode session. Read this section first.

1. **Verify baseline**: `git rev-parse HEAD` → expect the latest dashboard-permission-audit commit. `git status --short` → expect empty.
2. **Aging Dashboard AR/AP shipped earlier this session.** Backend Action + Controller + Route + 2 Seeders + 13 Pest + 7 Playwright + 5 frontend components + permission gate. CI verified green.
3. **Dashboard permission audit completed this session.** Closed identical security gap on aging-dashboard, financial-dashboard, and approval-monitoring routes (3 commits). Each got a regression 403 test.
4. **P&L by Department research done.** See `docs/profit-loss-by-department-design.md`. Recommendation: defer (LARGE 5-7 day lift, no real driver yet). Pivot to Budget Management likely better value-to-effort.
5. **If user says "lanjutkan" without direction**: ASK which next option. Do NOT pick autonomously.

### Recommended next-session options (need user input)

1. **Pivot product feature**: Budget Management (Recommended), Sales/Invoicing, or Multi-currency. Avoid P&L by Department until business driver confirmed.
2. **Multi-currency cross-cutting fix** (Oracle H3 from aging-dashboard review): same blind spot in `IndexArAgingReportAction`, dashboards, reports — one ticket spanning all financial reports.
3. **Branch tenant isolation** (Oracle H2): non-admin users currently see all branches' data on dashboards.
4. **Sonar duplications refactor**: `AbstractApPaymentRequest` + `AbstractArReceiptRequest` trait extraction (-10 LOC, gate OK).
5. **Seed dev DB**: `sail artisan db:seed --class=MenuSeeder --class=PermissionSeeder` to activate financial-dashboard + aging-dashboard nav links + new aging_dashboard / approval_monitoring permissions.

## Current State

- Branch: `main`
- Working tree: clean (all changes pushed).
- This session lands a complete Aging Dashboard feature + 3 dashboard permission gates + 1 design research doc.
- Sonar Quality Gate: OK (last verified 2026-05-31, no significant changes after).
- Module registry: 79 entries (added `aging-dashboard`).

## This Session's Commits (in order)

| Commit | Subject |
|---|---|
| `331d4d15` | feat(aging-dashboard): AR/AP aging dashboard with 5 buckets + top-10 overdue |
| `956cd64e` | fix(aging-dashboard): gate route by permission + apply oracle review fixes |
| `e97ae4bb` | fix(financial-dashboard): gate route by financial_dashboard permission |
| `8ed62cd6` | docs(research): P&L by Department pre-implementation design doc |
| (this commit) | fix(approval-monitoring): gate route by approval_monitoring permission + changelog/task.md refresh |

## Aging Dashboard AR/AP — feature summary

`GET /api/aging-dashboard?as_of_date=YYYY-MM-DD&branch_id=N` returns AR + AP outstanding bucketed by overdue age (Current, 1-30, 31-60, 61-90, Over 90 days), as-of date filter (default today), branch filter (default all), plus top-10 overdue customers/suppliers.

Cross-DB safe (parameterized SUM(CASE) bindings + Carbon date math, no MySQL DATEDIFF/CURDATE).

Files:
- Backend: `app/Actions/AgingDashboard/GetAgingDashboardDataAction.php`, `app/Http/Controllers/AgingDashboardController.php`, `routes/api/aging-dashboard.php`
- Seeders: `database/seeders/PermissionSeeder.php`, `database/seeders/MenuSeeder.php` (icon `Hourglass`)
- Tests: `tests/Feature/AgingDashboard/AgingDashboardControllerTest.php` (13 cases / 107 assertions), `tests/e2e/aging-dashboard/aging-dashboard.spec.ts` (7 cases)
- Frontend: `resources/js/hooks/useAgingDashboard.ts`, `resources/js/pages/aging-dashboard/index.tsx`, 5 components in `resources/js/components/aging-dashboard/`
- Module registry: new YAML entry + Pest registry row 30

## Dashboard Permission Gate Audit — 3 routes hardened

All three previously had only `auth:sanctum` middleware (no permission check). Closed in this session:

| Route | Permission | Pest 403 case | Notes |
|---|---|---|---|
| `/api/aging-dashboard` | `aging_dashboard` | added | mirrors pipeline-dashboard.php pattern |
| `/api/financial-dashboard` | `financial_dashboard` | added | beforeEach permission corrected from `report` (was bypassing because no gate existed) |
| `/api/approval-monitoring/data` | `approval_monitoring` | added | tests refactored to use `CreatesTestUserWithPermissions` trait |

Routes already correctly gated (verified): `/api/asset-dashboard/data` (under `permission:asset,true`), `/api/pipeline-dashboard/data` (`permission:pipeline_dashboard,true`), `/api/stock-monitor` (`permission:stock_monitor,true`).

`/api/dashboard` (main home dashboard) intentionally left ungated — returns only Customer/Employee/Supplier/Asset counts (low sensitivity, no permission key defined).

## P&L by Department Research

`docs/profit-loss-by-department-design.md`. Key finding: `journal_entry_lines` has zero dimension columns (no `department_id`, `branch_id`, `cost_center_id`, `project_id`). Adding department to GL is genuinely new architecture. Three options documented:

| Option | Effort | Value |
|---|---|---|
| A. Tag manual journals only | ~1 day | Low (most lines come from system posting → null) |
| B. Doc-level + propagation through 8 posting actions | 5-7 days | High but requires business modeling decisions |
| C. Defer (Recommended) | 0 days | Pivot to Budget Management (better value-to-effort) |

## Verification State (last verified locally)

| Gate | Result |
|------|--------|
| TypeScript (`npm run types`) | clean |
| ESLint (`sail npm run lint`) | clean |
| Duster (`sail bin duster fix`) | clean (1788 files) |
| PHPStan (`sail bin phpstan analyze`) | `[OK] No errors` |
| Pest aging-dashboard | 13 passed, 107 assertions, 13.93s |
| Pest financial-dashboard | 8 passed, 58 assertions, 11.58s |
| Pest approval-monitoring | 3 passed, 25 assertions, 12.25s |
| Playwright aging-dashboard | 7 passed, 53.2s |
| Playwright financial-dashboard | 4 passed, 27.7s |

## Useful Commands

```bash
# Run focused tests
sail test --group aging-dashboard
sail test --group financial-dashboard
sail test --group approval-monitoring
PLAYWRIGHT_USE_SAIL=1 ./vendor/bin/sail npx playwright test tests/e2e/aging-dashboard/

# Activate new nav links + permissions in dev DB
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
Read task.md first. Repo on `main`. Last session shipped Aging Dashboard
AR/AP feature plus closed permission gaps on 3 dashboard routes
(aging-dashboard, financial-dashboard, approval-monitoring). All quality
gates green locally. CI verified on commits up to 8ed62cd6.

Next options need user direction:
1. Budget Management (Recommended pivot — see profit-loss-by-department
   research doc that suggests this)
2. Multi-currency cross-cutting fix (Oracle H3)
3. Branch tenant isolation (Oracle H2)
4. Sonar duplications refactor (AP/AR Request trait, -10 LOC)
5. Seed dev DB to activate nav links

If user says "lanjutkan" without direction, ASK which path.
```
