# AI Handoff: ERP Active State

Last updated: 2026-05-30 07:38 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current State

- Branch: `main`
- HEAD: `8da4f872 docs(task): update handoff — financial dashboard v2 with monthly trends complete`
- Working tree: clean.
- Remote: pushed (up to date).
- CI E2E is **required gate** (no `continue-on-error`).
- Sonar: Quality Gate **OK** (overall coverage 91.4%, 0 HIGH/BLOCKER issues).
- Module registry: 76 entries + financial-dashboard (added this session).

### CI Note — Autofix Supersede Pattern (read this before reacting to red runs)

`tests.yml` runs Duster + Prettier + ESLint on every push. When those tools change files, the workflow auto-commits them with `style: apply CI autofixes` and forces the final `Test suite via Sail` job to `exit 1` (workflow lines 124-168, 473-476). The intent: that run is treated as failed so the **follow-up run on the autofix commit** is the authoritative verdict.

Effect on `gh run list` for recent commits:
- `08f400e5` (feat): FAIL — autofix triggered, ran `0e30c869`.
- `0e30c869` (autofix): superseded by `8da4f872` push (concurrency cancels).
- `8da4f872` (docs): authoritative. Quality job already success. E2E in progress as of last check.

**Do NOT panic at the red runs above** — they are not test failures. Verify via the latest run on HEAD only.

### Financial Dashboard (fully implemented previous session)

| Component | Status |
|-----------|--------|
| Backend Action (`GetFinancialDashboardDataAction`) | done |
| Backend Service (`getMonthlyTrends` in FinancialReportService) | done |
| Controller (`FinancialDashboardController`) | done |
| Route (`GET /api/financial-dashboard`) | done |
| Frontend hook (`useFinancialDashboard`) | done |
| 6 components (SummaryCards, CashFlowSummary, ExpenseBreakdown, FiscalYearSelector, MonthlyTrendChart, page) | done |
| Route in `app-routes.tsx` | done |
| Sidebar nav link (Accounting → Financial Dashboard) | done |
| Permission (`financial_dashboard`) | done |
| Pest tests (7 pass, 57 assertions) | done |
| E2E tests (4 cases) | done |
| PHPStan / TypeScript / ESLint | clean |
| Module registry entry | done (added this session) |

### Dashboard features

- 7 KPI cards with YoY change badges (revenue, expenses, net income, assets, liabilities, equity, cash balance).
- Monthly revenue vs expenses vertical grouped bar chart (12 months, respects fiscal year start month).
- Cash flow summary (inflow/outflow/net CSS bars).
- Top expenses breakdown (top 8 expense categories).
- Fiscal year selector with auto-comparison (previous year default).
- URL state for fiscal year params.

### What changed this session

1. Verified financial-dashboard implementation against `task.md` claims (files present, CI failures explained as autofix-supersede).
2. Added `financial-dashboard` entry to `docs/module-registry.md`.
3. Added Pest feature test for `ApplyCreditNoteAction` via `POST /api/credit-notes/{id}/apply` (lift coverage on previously 0%-covered domain action).
4. Added Pest feature test for `InventoryStocktakeItemController::getItems` + `syncItems` and indirectly covers `HandlesNestedItemsResponse` trait (was 0% covered).
5. Synced `task.md` semantics (HEAD pointer, CI autofix-supersede explanation).

## Recommended Next Steps (AI-autonomous)

| # | Task | Effort | Value | Notes |
|---|------|--------|-------|-------|
| 1 | Seed dev DB | Low | Medium | `sail artisan db:seed --class=MenuSeeder --class=PermissionSeeder` to activate financial-dashboard nav link. |
| 2 | Re-verify CI green on HEAD | Low | High | Watch run for `8da4f872` (or successor). |
| 3 | Backfill remaining 0% coverage files | Medium | Medium | Auth controllers (LoginRequest, NewPasswordController, AuthenticatedSessionController) still 0% — pure boilerplate but tests are cheap. |

**Product features** (require user scope):

| # | Feature | Effort | Value | Notes |
|---|---------|--------|-------|-------|
| 4 | Profit & Loss by Department | Medium | Medium | Extends income statement with department dimension. |
| 5 | Supplier/Customer Aging Dashboard | Medium | Medium | Visualizes existing AP/AR aging report data. |
| 6 | Budget Management module | High | High | New domain (models, CRUD, variance reports). |
| 7 | Sales/Invoicing module | High | High | New domain. |

## Useful Commands

```bash
# Seed nav link + permission for financial-dashboard
sail artisan db:seed --class=MenuSeeder
sail artisan db:seed --class=PermissionSeeder

# Run focused tests
sail test --group financial-dashboard
sail test --group credit-notes
sail test --group inventory-stocktakes
npx playwright test tests/e2e/financial-dashboard/

# Monitor CI
gh run list --branch main --limit 3
```

## Continuation Prompt

```text
Read task.md first. Repo on `main` at `8da4f872` (or later). Working tree clean.

Financial Dashboard complete (v2 with monthly trends). 7 KPI cards with YoY,
monthly revenue vs expenses chart, cash flow summary, expense breakdown,
fiscal year selector. Pest 7/7 (57 assertions). E2E 4 cases. PHPStan/TS/ESLint clean.

Coverage backfill in progress: ApplyCreditNoteAction and
InventoryStocktakeItemController now have Pest tests (previously 0%).

Before reacting to red CI runs: read the "CI Note — Autofix Supersede Pattern"
section in task.md. Most recent reds are by-design supersedes, not test failures.

Next options: seed dev DB to activate nav link, backfill remaining 0%-covered
auth controllers, or pick a product feature (Profit & Loss by Department,
Aging Dashboard, Budget Management, Sales/Invoicing).
```
