# AI Handoff: ERP Active State

Last updated: 2026-06-14 (post-merge) UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## ⚡ Quick Start for Next Session

User is switching to a new opencode session. Read this section first.

1. **Verify baseline**: `git rev-parse HEAD` → expect `6b78c29c`. `git status --short` → expect empty.
2. **PR #16 MERGED** (Wave 0 H3 multi-currency lock):
   - Merge commit: `6b78c29c`
   - Feature commits: `a85a457e` (lock), `96cf4e19` (refactor)
   - Sonar QG: PASSED (duplication 0.0%, was 8.5% threshold 3%)
   - All CI checks green
3. **Earlier session work** (still relevant):
   - Branch tenant isolation SHIPPED (`5f2cb816`): 4 dashboards scoped, `ResolvesBranchScope` trait + `view_all_branches` permission
   - Budget Management module SHIPPED (`f0c8e3c0`)
   - Permission seeded: admin emp has `view_all_branches`
4. **If user says "lanjutkan" without direction**: ASK which path. Do NOT pick autonomously.

### Active Work: H3 Multi-Currency — Wave 0 SHIPPED, Wave 1 BLOCKED

**Wave 0 SHIPPED via PR #16** (transactional currency lock + dedup refactor):

Lock pieces:
- New config: `app.base_currency = 'IDR'`, `app.supported_transaction_currencies = ['IDR']` (config/app.php).
- New trait: `app/Http/Requests/Concerns/HasSupportedCurrencyRules.php` (sibling to `HasTransactionAmountRules`).
- Applied to 6 AbstractRequest write classes: PurchaseOrders, SupplierBills, CustomerInvoices, ApPayments, ArReceipts, Assets.
- Applied to `app/Imports/AssetImport.php` (closes Excel-import bypass discovered by Oracle security review).
- 3 regression tests: `PurchaseOrderControllerTest::store rejects unsupported currency`, `SupplierBillControllerTest::store rejects unsupported currency`, `AssetImportTest::rejects rows with unsupported currency` (all expect 422 / `imported=0,skipped=1`).

Dedup refactor pieces (commit `96cf4e19`, response to Sonar QG):
- New trait: `app/Http/Requests/Concerns/HasBankPaymentRules.php` (parametrized by date field + payment method enum). Used by AP Payment + AR Receipt.
- New trait: `app/Http/Requests/Concerns/HasInvoiceLikeRules.php` (header + items.* common subset). Used by Customer Invoice + Supplier Bill.
- Net -44 lines in 4 AbstractRequest classes.

Verification (final):
- PHPStan: clean
- Duster: clean
- Pest full suite: 1854 passed, 8308 assertions
- Pest 6 affected groups: 98 passed
- Sonar QG: OK (all 6 conditions, duplication 0.0%, coverage 100%, ratings A)

**Wave 1 BLOCKED on user decisions**. Oracle verdict: execute as **Wave 0+1 hybrid lock+guard, ~1.25d remaining**. NOT full FX subsystem (defer to Wave 2 = first non-IDR customer signed).

**Plan**:
- ✅ Wave 0 (DONE, PR #16 merged): Rule whitelist + 3 regression tests + dedup refactor.
- ⏸ Wave 1a (0.5d): `app/Services/Currency/CurrencyGuard.php` + `MixedCurrencyException` + `app/Actions/Concerns/AssertsSingleCurrency.php` trait.
- ⏸ Wave 1b (0.5d): apply trait to AgingDashboard + AR/AP aging actions.
- ⏸ Wave 1c (0.25d, REDUCED): apply to FinancialDashboard + ApPaymentHistoryReport. Budget EXCLUDED (BudgetVarianceService reads journal_entry_lines only; journal_entries has no currency col so blast radius zero until Wave 2 schema change).
- ⏸ Wave 1d: SKIPPED — already done as part of Wave 0.

**5 Decision points still pending user**:
1. Lock UI scope: hide currency field on 6 frontend forms entirely OR keep visible+disabled with tooltip?
2. Admin display setting (`AdminSettingRequest` whitelist 9 currencies): lock to IDR too OR keep accepting USD/EUR/etc as cosmetic preference (creates inconsistency)?
3. Mixed currency response code (Wave 1b/1c): 422 (user-correctable) OR 500+Sentry alert? (Oracle pick: 422)
4. Frontend release note: ship one-liner in user-guide saying "multi-currency display setting cosmetic until v.next"?
5. Naming for Wave 1: trait `AssertsSingleCurrency` + service `CurrencyGuard` (Oracle pick, matches `ResolvesBranchScope` precedent) — confirm?

### Other deferred options (if user reroutes)

1. **Timezone drift** (Oracle M3) — Medium severity, lower priority than H3
2. **Financial dashboard branch scoping** (DEFERRED): requires `branch_id` on `journal_entries` table (schema change, 3-5 day lift)
3. **Pipeline/Approval dashboard branch scoping** (DEFERRED): polymorphic resolution needed

## Current State

- Branch: `main`
- HEAD: `6b78c29c` (merge of PR #16)
- Working tree: clean
- CI: green on `main` after merge
- Sonar Quality Gate: OK (all conditions pass)
- Module registry: 80 entries (Budget Management added earlier)

## Recent Commits On Main

| Commit | Subject |
|---|---|
| `6b78c29c` | Merge pull request #16 from gmedia/feat/h3-wave0-currency-lock |
| `96cf4e19` | refactor: extract shared FormRequest traits to reduce duplication |
| `a85a457e` | feat(security): lock transactional currency to IDR (H3 Wave 0) |
| `423f97ab` | docs(handoff): record branch isolation completion + update next options |
| `5f2cb816` | fix(security): enforce branch tenant isolation on 4 dashboard endpoints |

## Branch Isolation — Scoping Policy (still active)

| User Type | Behavior |
|---|---|
| Has `view_all_branches` permission | Honor requested `branch_id` (null = all) |
| Employee with `branch_id` set | Forced to own branch (request ignored) |
| Employee with `branch_id` null | Unscoped (backward compat, legacy admin) |

### Scoped Endpoints

| Endpoint | Status |
|---|---|
| `/api/dashboard` | ✅ Scoped |
| `/api/aging-dashboard` | ✅ Scoped |
| `/api/asset-dashboard/data` | ✅ Scoped |
| `/api/stock-monitor` | ✅ Scoped |
| `/api/financial-dashboard` | ⏳ DEFERRED (journal_entries lacks branch_id) |
| `/api/pipeline-dashboard/data` | ⏳ DEFERRED (polymorphic) |
| `/api/approval-monitoring/data` | ⏳ DEFERRED (polymorphic) |

## Useful Commands

```bash
# Run focused tests
sail test --group purchase-orders
sail test --group supplier-bills
sail test --group customer-invoices
sail test --group ap-payments
sail test --group ar-receipts
sail test --group assets

# All quality gates
sail bin phpstan analyze
sail bin duster fix
npm run types
sail npm run lint

# Activate new permissions in dev DB (already done)
sail artisan db:seed --class=PermissionSeeder

# Monitor CI
gh run list --branch main --limit 5
gh pr view <num> --json statusCheckRollup
```

## Continuation Prompt

```text
Read task.md first. Repo on `main`, HEAD `6b78c29c`, working tree clean.
PR #16 (H3 Wave 0 multi-currency lock) MERGED. Sonar QG OK (duplication 0.0%).
Full suite 1854 pass.

This session shipped via PR #16:
- Wave 0 currency lock: HasSupportedCurrencyRules trait, 6 AbstractRequest
  + AssetImport, 3 regression tests, config keys
- Dedup refactor: HasBankPaymentRules + HasInvoiceLikeRules traits

Next action needs USER DIRECTION (do NOT auto-pick):
1. Wave 1 multi-currency aggregation guard (~1.25d remaining)
   — needs answers to 5 decision points (see task.md "Active Work" section)
2. Timezone drift (Oracle M3)
3. Financial dashboard branch scoping (journal_entries.branch_id schema change)
4. Other Oracle finding

If user says "lanjutkan" without direction, ASK which path.
```
