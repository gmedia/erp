# AI Handoff: ERP Active State

Last updated: 2026-05-28 10:46 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current State

- Branch: `main`
- HEAD: `f657812d test(e2e): add retry to User Management tests for CI timing resilience`
- Working tree: clean.
- Remote: pushed.
- CI E2E is **required gate** (no `continue-on-error`).
- Latest verified-green CI run: `26565168691` (HEAD `f657812d`).
  - `Quality checks via Sail`: `success`
  - `Playwright E2E via Sail`: `success`
  - `Test suite via Sail`: `success`
- Current CI E2E subset: **78 modules**.
- Coverage: 78 of 80 directories under `tests/e2e/` are in the required gate. Remaining 2 are not real modules:
  - `misc/` (catch-all utilities)
  - `test-results/` (Playwright output)
- Sonar metrics (last scan): 91.2% coverage, 0.7% duplication, 91k ncloc.

## Recommended Next Steps

Bias: shift to product features. Codebase is healthy. All CI green.

1. **New product feature** (user provides scope).
2. **Verify Sonar re-scan** shows 0 open issues after CI triggers analysis.

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
Read task.md first. Repo should be on `main` at `f657812d` or newer.
Working tree should be clean. CI green (run 26565168691, 78-module subset).

Codebase healthy: 91.2% coverage, 0.7% duplication, 0 Sonar blockers.
All historical context archived in task.handoff-archive.md (2026-05-28 entry).

Ready for new product feature or Sonar verification.
```
