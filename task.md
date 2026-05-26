# AI Handoff: CI E2E Required Gate Expanded to 55 Modules

Last updated: 2026-05-26 13:40 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current State

- Branch: `main`
- HEAD: `fc55e70e ci: nudge after Actions degraded recovery (2nd retry)`
- Working tree: clean (this update staged for commit).
- Remote: pushed.
- CI E2E is **required gate** (no `continue-on-error`).
- Latest CI run: `26449377161` → overall `success`
  - `Quality checks via Sail`: `success`
  - `Playwright E2E via Sail`: `success`
  - `Test suite via Sail`: `success`
- Current CI E2E subset: **55 modules** (wave 7 added 7 non-CRUD workflow modules).

### Notes on Recovery

- GitHub Actions had a `major_outage` from 10:57 UTC (incident `gnftqj9htp0g`).
- Pushes at 11:55 UTC (`f721361f`), 12:01 UTC (`28212829`), and 12:10 UTC (`179b12de`) did not trigger any CI run.
- A 4th nudge empty commit at 12:58 UTC (`fc55e70e`) finally triggered run `26449377161` after Actions moved from `major_outage` to `degraded_performance`.
- Workflow content is unchanged across the wave 7 commits; the green run validates the 55-module subset present at HEAD.

## Current Objective

Keep expanding `tests/e2e/**` into the required CI subset in safe waves,
while fixing known data-coupling/export hardening issues before adding
risky modules.

## What Changed Since Previous Handoff

### 1. Root cause for 41 CRUD export failures fixed

- CI full E2E run `26384727117` had `41 failed` export specs.
- Failure signature:
  - `Console Error text: "Export failed: x"`
  - `Error: Can't find end of central directory : is this a zip file ?`
  - failure location: `tests/e2e/shared-test-factories.ts:208`
- Root cause: fresh CI runner did not have `public/storage` symlink.
  - Backend stores exports in `storage/app/public/exports/*.xlsx`.
  - Backend returns `Storage::disk('public')->url(...)` → `/storage/exports/...`.
  - Without symlink, browser downloads HTML 404 as `.xlsx`; ExcelJS fails.
- Fix committed:
  - `34fad961 ci(e2e): run storage:link in global-setup to unblock CRUD export specs`
  - `tests/e2e/global-setup.ts` now runs `artisan storage:link --force` after `migrate:fresh` + `db:seed`.
- Validated on CI through CRUD export specs (`branches`, `departments`, later many more).

### 2. E2E promoted to required

- Commit: `f09c9002 ci(e2e): promote E2E to required + expand with 3 more simple-CRUD modules`
- Changes:
  - Removed `continue-on-error: true` from the e2e job in `.github/workflows/tests.yml`.
  - Reflect step now checks both:
    - `needs.quality.result == success`
    - `needs.e2e.result == success`
- CI run `26392779224`: all green with E2E required.

### 3. CI E2E subset expanded in waves

Current subset in `.github/workflows/tests.yml`:

```text
tests/e2e/account-mappings/
tests/e2e/admin-settings/
tests/e2e/approval-audit-trail/
tests/e2e/approval-delegations/
tests/e2e/approval-flows/
tests/e2e/approval-history/
tests/e2e/approval-monitoring/
tests/e2e/asset-categories/
tests/e2e/asset-dashboard/
tests/e2e/asset-locations/
tests/e2e/asset-maintenances/
tests/e2e/asset-models/
tests/e2e/asset-movements/
tests/e2e/asset-stocktakes/
tests/e2e/assets/
tests/e2e/balance-sheet-report/
tests/e2e/bank-reconciliations/
tests/e2e/branches/
tests/e2e/cash-flow-report/
tests/e2e/coa-versions/
tests/e2e/comparative-report/
tests/e2e/customer-categories/
tests/e2e/customers/
tests/e2e/departments/
tests/e2e/employees/
tests/e2e/goods-receipt-report/
tests/e2e/goods-receipts/
tests/e2e/income-statement-report/
tests/e2e/inventory-stocktake-variance-report/
tests/e2e/inventory-stocktakes/
tests/e2e/inventory-valuation-report/
tests/e2e/journal-entries/
tests/e2e/my-approvals/
tests/e2e/pipeline-audit-trail/
tests/e2e/pipelines/
tests/e2e/positions/
tests/e2e/product-categories/
tests/e2e/products/
tests/e2e/purchase-history-report/
tests/e2e/purchase-order-status-report/
tests/e2e/purchase-orders/
tests/e2e/purchase-requests/
tests/e2e/stock-adjustment-report/
tests/e2e/stock-adjustments/
tests/e2e/stock-monitor/
tests/e2e/stock-movement-report/
tests/e2e/stock-movements/
tests/e2e/stock-transfers/
tests/e2e/supplier-categories/
tests/e2e/supplier-returns/
tests/e2e/suppliers/
tests/e2e/trial-balance-detailed-report/
tests/e2e/trial-balance-report/
tests/e2e/units/
tests/e2e/warehouses/
```

Wave history:

| Commit | Result |
|--------|--------|
| `865130d9` | Narrowed to known-green subset: bank-reconciliations + 6 financial reports |
| `ecfc83e3` | Corrected subset to include `branches`, `departments`; exclude `fiscal-years` |
| `f09c9002` | Required gate + add `positions`, `customer-categories`, `supplier-categories` |
| `c036f09c` | Add `asset-categories`, `asset-locations`, `asset-models`, `product-categories`, `units`, `warehouses` |
| `04d47b33` | Add `employees`, `customers`, `suppliers`, `products`, `asset-movements`, `asset-maintenances`, `asset-stocktakes`, `coa-versions`, `account-mappings` |
| `f2555ae9` | Add `goods-receipts`, `inventory-stocktakes`, `purchase-orders`, `purchase-requests`, `stock-adjustments`, `stock-transfers`, `supplier-returns` (transaction wave 4) |
| `f7278be5` | Add `goods-receipt-report`, `inventory-stocktake-variance-report`, `inventory-valuation-report`, `purchase-history-report`, `purchase-order-status-report`, `stock-adjustment-report`, `stock-monitor`, `stock-movement-report`, `stock-movements` (stock/report wave 5) |
| `0655495d` | Add `approval-delegations`, `approval-flows`, `assets`, `journal-entries`, `pipelines` (CRUD wave 6) |
| `f721361f` | Add `admin-settings`, `approval-audit-trail`, `approval-history`, `approval-monitoring`, `asset-dashboard`, `my-approvals`, `pipeline-audit-trail` (non-CRUD workflow wave 7) |
| `28212829` | First retrigger empty commit (didn't fire CI; Actions outage) |
| `179b12de` | docs(task): handoff update for wave 7 + outage |
| `fc55e70e` | 2nd retrigger empty commit; finally triggered CI run `26449377161` (green) |

Local verification before `f721361f` (non-CRUD workflow wave 7):

```bash
PLAYWRIGHT_USE_SAIL=1 PLAYWRIGHT_BASE_URL=http://localhost:82 PLAYWRIGHT_SKIP_BUILD=1 \
  npx playwright test \
  tests/e2e/my-approvals/ tests/e2e/approval-monitoring/ \
  tests/e2e/approval-audit-trail/ tests/e2e/pipeline-audit-trail/ \
  tests/e2e/asset-dashboard/ tests/e2e/admin-settings/ \
  tests/e2e/approval-history/ --reporter=list
# → 19 passed (1.4m)
```

Local verification before `0655495d` (CRUD wave 6):

```bash
PLAYWRIGHT_USE_SAIL=1 PLAYWRIGHT_BASE_URL=http://localhost:82 PLAYWRIGHT_SKIP_BUILD=1 \
  npx playwright test \
  tests/e2e/journal-entries/ tests/e2e/assets/ \
  tests/e2e/approval-flows/ tests/e2e/approval-delegations/ \
  tests/e2e/pipelines/ --reporter=list
# → 58 passed (6.4m)
```

Local verification before `f7278be5` (stock/report wave 5):

```bash
PLAYWRIGHT_USE_SAIL=1 PLAYWRIGHT_BASE_URL=http://localhost:82 PLAYWRIGHT_SKIP_BUILD=1 \
  npx playwright test \
  tests/e2e/stock-movements/ tests/e2e/stock-monitor/ \
  tests/e2e/stock-movement-report/ tests/e2e/stock-adjustment-report/ \
  tests/e2e/inventory-valuation-report/ \
  tests/e2e/inventory-stocktake-variance-report/ \
  tests/e2e/goods-receipt-report/ tests/e2e/purchase-order-status-report/ \
  tests/e2e/purchase-history-report/ --reporter=list
# → 9 passed (50.3s)
```

Local verification before `f2555ae9` (transaction wave 4):

```bash
PLAYWRIGHT_USE_SAIL=1 PLAYWRIGHT_BASE_URL=http://localhost:82 PLAYWRIGHT_SKIP_BUILD=1 \
  npx playwright test \
  tests/e2e/stock-transfers/ tests/e2e/inventory-stocktakes/ \
  tests/e2e/stock-adjustments/ tests/e2e/purchase-requests/ \
  tests/e2e/purchase-orders/ tests/e2e/goods-receipts/ \
  tests/e2e/supplier-returns/ --reporter=list
# → 75 passed (8.0m)
```

Local verification before `04d47b33`:

```bash
# Wave 2
npx playwright test tests/e2e/employees/ tests/e2e/customers/ tests/e2e/suppliers/ tests/e2e/products/ --reporter=list
# → 40 passed (5.6m)

# Wave 3
npx playwright test tests/e2e/asset-movements/ tests/e2e/asset-maintenances/ tests/e2e/asset-stocktakes/ tests/e2e/coa-versions/ tests/e2e/account-mappings/ --reporter=list
# → 46 passed (5.9m)
```

## Known Issues / Blockers

### fiscal-years is excluded

- Commit `4080dec7` tried adding `fiscal-years/`.
- CI run `26389923397` showed failures after `fiscal-years/` ran:
  - `income-statement-report can export income statement report`
    - `/api/reports/income-statement/export` returned `500`
    - browser log: `Console Error text: "Export failed: x"`
  - `trial-balance-detailed-report can export trial balance detailed report`
    - export button stayed disabled / no data loaded, depending on attempt.
- Reproduced locally by running:

```bash
PLAYWRIGHT_USE_SAIL=1 PLAYWRIGHT_BASE_URL=http://localhost:82 \
  npx playwright test tests/e2e/fiscal-years/ tests/e2e/income-statement-report/ --reporter=list
# → income-statement export timeout / backend 500
```

- Investigation findings:
  - `fiscal-years/` creates extra test years like `FY 1779...`.
  - Report pages can auto-select or operate against a fiscal year with no report data.
  - Report export actions can 500 instead of returning an empty `.xlsx` for an empty fiscal year.
- Current decision: keep `fiscal-years/` excluded until report export actions are hardened.

### Backend report export hardening needed

- Report export endpoints should return valid empty `.xlsx` instead of 500 when fiscal year has no data/config.
- Likely targets:
  - `app/Actions/Reports/ExportIncomeStatementReportAction.php`
  - trial-balance-detailed export action / route path (find exact file before editing)
  - possibly other financial report export actions.

### Full E2E suite still not enabled

- Remaining modules include transaction/procurement/inventory modules and various reports.
- Need local verification per wave before adding to required CI subset.

## Recommended Next Steps

1. **Wave 8: Remaining smaller modules**
   - Candidates (mix of CRUD/non-CRUD/reports):
     - `tests/e2e/accounts/`
     - `tests/e2e/posting-journals/`
     - `tests/e2e/recurring-journals/`
     - `tests/e2e/period-closings/`
     - `tests/e2e/customer-invoices/`
     - `tests/e2e/supplier-bills/`
     - `tests/e2e/ar-receipts/`
     - `tests/e2e/ap-payments/`
     - `tests/e2e/credit-notes/`
     - `tests/e2e/general-ledger-report/`
     - `tests/e2e/maintenance-cost-reports/`
     - `tests/e2e/book-value-depreciation-reports/`
     - `tests/e2e/asset-stocktake-variances/`
     - `tests/e2e/asset-depreciation-runs/`
     - `tests/e2e/asset-reports/`
     - `tests/e2e/pipeline-dashboard/`
     - `tests/e2e/entity-state-actions/`
     - `tests/e2e/entity-state-timeline/`
     - `tests/e2e/users/`
     - `tests/e2e/permissions/`
     - `tests/e2e/dashboards/`
     - `tests/e2e/report-configurations/`
   - Run locally first via Sail in smaller batches; consolidate green ones into the workflow.

2. **Separate task: harden report exports for fiscal-years re-inclusion**
   - Make empty-data fiscal years export a valid empty workbook, not a 500.
   - Targets:
     - `app/Actions/Reports/ExportIncomeStatementReportAction.php`
     - trial-balance-detailed export action / route path (find exact file before editing)
     - possibly other financial report export actions.
   - Then re-add `tests/e2e/fiscal-years/` to subset.

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
Read task.md first. Repo should be on `main` at `fc55e70e` or newer.
Working tree should be clean.

CI E2E is required and green. Latest known green run: `26449377161`
on HEAD fc55e70e with the 55-module subset.

Next recommended work: Wave 8 — remaining smaller modules (accounts,
posting-journals, recurring-journals, period-closings, customer-invoices,
supplier-bills, ar-receipts, ap-payments, credit-notes,
general-ledger-report, maintenance-cost-reports, book-value-depreciation-reports,
asset-stocktake-variances, asset-depreciation-runs, asset-reports,
pipeline-dashboard, entity-state-actions, entity-state-timeline, users,
permissions, dashboards, report-configurations).

Run locally first via Sail in smaller batches before adding paths to
.github/workflows/tests.yml.

Keep `fiscal-years/` excluded until report export actions are hardened.

Note: GitHub Actions had a major outage 2026-05-26 10:57-12:58 UTC. If
future pushes silently don't trigger CI, the workaround is an empty
commit nudge.
```
