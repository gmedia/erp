# AI Handoff: Preferred Fiscal Year Auto-Select for Financial Reports

Last updated: 2026-05-27 01:07 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current State

- Branch: `main`
- HEAD: `420b7c7b feat(reports): auto-select preferred fiscal year with posted entries`
- Working tree: clean.
- Remote: pushed.
- CI E2E is **required gate** (no `continue-on-error`).
- Latest verified-green CI run: `26480230470` (HEAD `4d9584f4`, 78-module subset).
  - `Quality checks via Sail`: `success`
  - `Playwright E2E via Sail`: `success`
  - `Test suite via Sail`: `success`
- Current CI E2E subset: **78 modules**.
- Coverage: 78 of 80 directories under `tests/e2e/` are in the required gate. Remaining 2 are not real modules:
  - `misc/` (catch-all utilities)
  - `test-results/` (Playwright output)

### Preferred Fiscal Year Auto-Select (this session)

- Problem: financial report pages defaulted to "first open FY" or "first FY by start_date desc", which could be an empty FY without posted journal entries. Users saw empty tables on first load.
- Solution: `GetPreferredFiscalYearAction` resolves the default FY with priority:
  1. Latest FY (by `start_date` desc) that has ≥1 posted `JournalEntry`
  2. Fallback: first open FY
  3. Fallback: first FY in collection
- Wired into `InteractsWithFinancialReportRequest::resolveFiscalYearContext()` trait, which feeds `selectedYearId` to all 5 financial reports (income-statement, balance-sheet, comparative, cash-flow, trial-balance). **Zero frontend change** — frontend already consumes `data?.selectedYearId`.
- Files changed:
  - NEW: `app/Actions/FiscalYears/GetPreferredFiscalYearAction.php`
  - MODIFIED: `app/Http/Controllers/Concerns/InteractsWithFinancialReportRequest.php` (1 line: replace old default logic with Action call)
  - NEW: `tests/Unit/Actions/FiscalYears/GetPreferredFiscalYearActionTest.php` (6 test cases)
- Verification:
  - Unit: 6 passed (GetPreferredFiscalYearActionTest)
  - Pest `--group reports`: 31 passed (76.6s)
  - PHPStan: 0 errors
  - Duster: PASS
  - Playwright (income-statement + trial-balance + balance-sheet): 10 passed (43s)
- Commit: `420b7c7b feat(reports): auto-select preferred fiscal year with posted entries`
- CI verification: pending (run triggered by push).
- Note: Trial Balance Detailed and General Ledger use `ReportDataTablePage` with a filter field fetching `/api/fiscal-years` — they do NOT go through this trait. They have no server-side default selection; user must pick FY from filter. This is acceptable for now (data-table pattern doesn't have `selectedYearId` injection).

### Pipeline Dashboard Smoke Spec (this session)

- New file: `tests/e2e/pipeline-dashboard/pipeline-dashboard.spec.ts`.
- Pattern mirrors `tests/e2e/asset-dashboard/asset-dashboard.spec.ts`:
  - Open Admin menu → click Pipeline Dashboard.
  - Wait for `/api/pipeline-dashboard/data` response.
  - Assert heading `Pipeline Dashboard`, filter labels `Select Pipeline` + `Stale Threshold`, at least one `[data-slot="card"]`, and the chart card title `State Distribution` + table card title `Stale Entities`.
- Locator choices accommodate the empty-data branch on a fresh seeded DB (no pipeline-state activity), since the page still renders the chart and table card headers in that branch.
- CI subset bump: `tests/e2e/pipeline-dashboard/` added to `.github/workflows/tests.yml` (77 → 78 modules).
- Local verification:

```bash
PLAYWRIGHT_USE_SAIL=1 PLAYWRIGHT_BASE_URL=http://localhost:82 PLAYWRIGHT_SKIP_BUILD=1 \
  npx playwright test tests/e2e/pipeline-dashboard/ --reporter=list
# → 1 passed (21.1s)
```

- TypeScript verification: `npm run types` → no errors.
- Commit: `ca1ae199 test(e2e): add pipeline-dashboard smoke spec + include in required CI subset`.
- CI verification: run `26480230470` green for HEAD `4d9584f4` (which carries `ca1ae199`'s workflow change) with the 78-module subset.

### Fiscal Years Re-Inclusion (previous session)

- Old blocker: when fiscal-years E2E created an extra FY without a CoA version, financial report exports (income-statement, trial-balance-detailed) returned 500 with `Undefined array key "comparison_revenue"`.
- Root cause #1 (backend): `FinancialReportService::emptyIncomeStatementReport()` and `emptyBalanceSheetReport()` returned only base totals; consumers (xlsx exports) accessed `comparison_*` and `change_*` keys without `??`.
- Root cause #2 (frontend): `DataTableToolbar` Export button was disabled on `!hasData`. With CoA-less FY default-selected by FE, no rows -> Export permanently disabled -> `tests/e2e/trial-balance-detailed-report` saw the disabled state and the test stayed in `toBeEnabled` waiting.
- Fixes:
  - `4e036b23 fix(reports): emit full totals shape from empty financial reports` — fills empty templates with all `comparison_*`/`change_*` keys at zero. Pest regression tests added in `IncomeStatementReportTest` + `BalanceSheetReportTest` (FY without CoA → 200, valid xlsx).
  - `4f13056a fix(reports): allow exporting empty data tables once filters are applied` — drops `!hasData` from Export button disable condition; only `exporting` blocks reclicks. Backend already returns header-only xlsx for empty data.
  - `a51d043f fix(reports): remove now-unused hasData prop from DataTableToolbar` — follow-up to satisfy ESLint after the prop was no longer used.
- Local verification: 29 passed (2.0m) on fiscal-years/ + 6 financial reports.
- Pest verification: 31 passed (74.8s) on `--group reports`.
- CI verification: run `26467825310` green for HEAD `a51d043f` with the 77-module subset.

### Wave 8 Failure & Fix

- First wave-8 push (`3059cc5e`) failed CI run `26455179022` with **480 passed, 1 failed**:
  - `tests/e2e/comparative-report/comparative-report.spec.ts:84:5 › can export comparative report`
  - Strict-mode violation: `getByRole('button', { name: 'Export' })` resolved to 2 elements.
  - Cause: `accounts/add-account.spec.ts` seeds an account named `Export Test Account` (CODE66342). The account row in `/accounts` exposes a button accessible name `CODE66342 Export Test Account`, which Playwright treats as a substring match for `Export`.
- Fix (`186f1652`): pinned the Export button locator with `{ exact: true }` in three vulnerable specs:
  - `tests/e2e/comparative-report/comparative-report.spec.ts` (the failing one)
  - `tests/e2e/cash-flow-report/cash-flow-report.spec.ts` (same shape, pre-emptive)
  - `tests/e2e/asset-stocktake-variances/index.spec.ts` (same shape, pre-emptive)
- Local verification (Sail): 13 passed (1.2m) on accounts + the 3 reports.
- CI run `26458724971`: all 3 jobs green.

## Current Objective

Two features shipped this session:
1. Pipeline-dashboard smoke spec (CI subset 77 → 78, verified green).
2. Preferred fiscal year auto-select for 5 financial reports (zero frontend change).

CI verification for commit `420b7c7b` is pending. Once green, both features are fully validated.

## Recommended Next Steps

Bias: shift to product features. Items below are optional.

1. **Optional: extend preferred-FY to Trial Balance Detailed + General Ledger**
   - These 2 reports use `ReportDataTablePage` filter pattern (no `selectedYearId` injection).
   - Could add `preferred_fiscal_year_id` as meta in `/api/fiscal-years` response, then frontend filter field auto-selects it.
   - Scope: ~1 hr. Backend: modify `FiscalYearCollection` to include meta. Frontend: modify 2 filter field configs to consume it.

2. **Optional UX polish: show indicator when default FY was auto-selected**
   - Small frontend badge/tooltip on the FY selector showing "Auto-selected: has posted entries".
   - Pure cosmetic, low priority.

3. **Optional: defensive locator audit** (same as before)

4. **Optional: update `task.changelog.md`** with both milestones.

## Useful Commands

```bash
# Status
git status --short
git log --oneline -12

# Run current CI subset locally if needed (long)
PLAYWRIGHT_USE_SAIL=1 PLAYWRIGHT_BASE_URL=http://localhost:82 PLAYWRIGHT_SKIP_BUILD=1 \
  npx playwright test \
  tests/e2e/account-mappings/ tests/e2e/asset-categories/ tests/e2e/asset-locations/ \
  tests/e2e/asset-maintenances/ tests/e2e/asset-models/ tests/e2e/asset-movements/ \
  tests/e2e/asset-stocktakes/ tests/e2e/balance-sheet-report/ tests/e2e/bank-reconciliations/ \
  tests/e2e/branches/ tests/e2e/cash-flow-report/ tests/e2e/coa-versions/ \
  tests/e2e/comparative-report/ tests/e2e/customer-categories/ tests/e2e/customers/ \
  tests/e2e/departments/ tests/e2e/employees/ tests/e2e/goods-receipts/ \
  tests/e2e/income-statement-report/ tests/e2e/inventory-stocktakes/ \
  tests/e2e/positions/ tests/e2e/product-categories/ tests/e2e/products/ \
  tests/e2e/purchase-orders/ tests/e2e/purchase-requests/ \
  tests/e2e/stock-adjustments/ tests/e2e/stock-transfers/ \
  tests/e2e/supplier-categories/ tests/e2e/supplier-returns/ tests/e2e/suppliers/ \
  tests/e2e/trial-balance-detailed-report/ tests/e2e/trial-balance-report/ \
  tests/e2e/units/ tests/e2e/warehouses/ --reporter=line

# Monitor latest CI
gh run list --branch main --limit 3
gh run view <run_id> --json status,conclusion,jobs
```

## Continuation Prompt

```text
Read task.md first. Repo should be on `main` at `420b7c7b` or newer.
Working tree should be clean.

CI E2E is required. Latest verified-green run: `26480230470` on HEAD
4d9584f4 with the 78-module subset. Newer commit 420b7c7b adds the
preferred-FY feature (backend only, no workflow change); CI run for
this commit is pending/should be green by now.

Two features shipped this session:
1. tests/e2e/pipeline-dashboard/ smoke spec (CI subset 77 -> 78).
2. GetPreferredFiscalYearAction: financial reports now default to the
   latest FY with posted journal entries instead of just "first open FY".
   Affects 5 reports via InteractsWithFinancialReportRequest trait.
   Zero frontend change.

Coverage: 78 of 80 directories under tests/e2e/ in required gate.
Remaining: misc/ (catch-all), test-results/ (Playwright output).

Recommended next work (all optional, pick zero or one):
1. Extend preferred-FY to Trial Balance Detailed + General Ledger
   (add preferred_fiscal_year_id meta to /api/fiscal-years response +
   frontend filter auto-select). ~1 hr.
2. Defensive locator audit (getByRole('link'), getByText without exact).
3. Update task.changelog.md with both milestones.
4. New product feature (user provides scope).
```
