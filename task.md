# AI Handoff: ERP Active State

Last updated: 2026-05-30 07:00 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current State

- Branch: `main`
- HEAD: `08f400e5 feat(financial-dashboard): add monthly revenue vs expenses trend chart`
- Working tree: clean.
- Remote: pushed (up to date).
- CI E2E is **required gate** (no `continue-on-error`).
- Sonar: Quality Gate OK (pending new scan for latest commits).
- Module registry: 76 entries + 1 new (financial-dashboard).

### Financial Dashboard (fully implemented this session)

| Component | Status |
|-----------|--------|
| Backend Action (`GetFinancialDashboardDataAction`) | ✅ |
| Backend Service (`getMonthlyTrends` in FinancialReportService) | ✅ |
| Controller (`FinancialDashboardController`) | ✅ |
| Route (`GET /api/financial-dashboard`) | ✅ |
| Frontend hook (`useFinancialDashboard`) | ✅ |
| 6 components (SummaryCards, CashFlowSummary, ExpenseBreakdown, FiscalYearSelector, MonthlyTrendChart, page) | ✅ |
| Route in `app-routes.tsx` | ✅ |
| Sidebar nav link (Accounting → Financial Dashboard) | ✅ |
| Permission (`financial_dashboard`) | ✅ |
| Pest tests (7 pass, 57 assertions) | ✅ |
| E2E tests (4 cases) | ✅ |
| PHPStan clean | ✅ |
| TypeScript clean | ✅ |
| ESLint clean | ✅ |

### Dashboard features:
- 7 KPI cards with YoY change badges (revenue, expenses, net income, assets, liabilities, equity, cash balance)
- Monthly revenue vs expenses vertical grouped bar chart (12 months, respects fiscal year start month)
- Cash flow summary (inflow/outflow/net CSS bars)
- Top expenses breakdown (top 8 expense categories)
- Fiscal year selector with auto-comparison (previous year default)
- URL state for fiscal year params

### What changed this session

1. Implemented Financial Dashboard v1: KPI cards + cash flow + expense breakdown.
2. Added sidebar nav link under Accounting + permission seeder.
3. Implemented Monthly Trend Chart (v2): vertical grouped bars, 12 months, fiscal year order.
4. Backend `getMonthlyTrends()` queries journal entries GROUP BY month with COA version guard.
5. Fixed dynamic Tailwind class issue (JIT requires complete strings).
6. All pushed to remote (4 feature commits + 1 docs + 1 CI autofix).

## Recommended Next Steps (AI-autonomous)

| # | Task | Effort | Value | Notes |
|---|------|--------|-------|-------|
| 1 | Seed dev DB | Low | Medium | `sail artisan db:seed --class=MenuSeeder --class=PermissionSeeder` to activate nav link. |
| 2 | Verify CI green | Low | High | Check latest run after push. |
| 3 | Update module-registry.md | Low | Low | Add financial-dashboard entry. |

**Product features** (require user scope):

| # | Feature | Effort | Value |
|---|---------|--------|-------|
| 4 | Profit & Loss by Department | Medium | Medium | Extends income statement with department dimension |
| 5 | Supplier/Customer Aging Dashboard | Medium | Medium | Data from AP/AR aging reports → visualization |
| 6 | Budget Management module | High | High | New domain (models, CRUD, variance reports) |
| 7 | Sales/Invoicing module | High | High | New domain |

## Useful Commands

```bash
# Seed nav link + permission
sail artisan db:seed --class=MenuSeeder
sail artisan db:seed --class=PermissionSeeder

# Run financial dashboard tests
sail test --group financial-dashboard
npx playwright test tests/e2e/financial-dashboard/

# Monitor CI
gh run list --branch main --limit 3
```

## Continuation Prompt

```text
Read task.md first. Repo on `main` at `08f400e5`. Working tree clean, remote up to date.

Financial Dashboard complete (v2 with monthly trends):
- 7 KPI cards with YoY badges, monthly revenue vs expenses chart (12 months),
  cash flow summary, expense breakdown, fiscal year selector with auto-comparison.
- Backend: GetFinancialDashboardDataAction + getMonthlyTrends in FinancialReportService.
- Pest 7/7 (57 assertions). E2E 4 cases. PHPStan/TS/ESLint clean.
- Nav link added to Accounting section (needs db:seed to activate).

Next options: Profit & Loss by Department (medium), Aging Dashboard (medium),
Budget Management (high), Sales/Invoicing (high).
```
