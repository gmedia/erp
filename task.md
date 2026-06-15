# AI Handoff: ERP Active State

Last updated: 2026-06-15 (post-Wave-1-merge) UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## ⚡ Quick Start for Next Session

User is switching to a new opencode session. Read this section first.

1. **Verify baseline**: `git rev-parse HEAD` → expect `4df36b76`. `git status --short` → expect empty.
2. **PR #17 MERGED** (Wave 1 H3 multi-currency aggregation guard):
   - Merge commit: `4df36b76`
   - Feature commits: `4c61d95d` (Wave 1 main), `907edf38` (admin-settings UI/E2E fix)
   - Sonar QG: PASSED (duplication 0.0%, coverage 100%, ratings A)
   - All CI checks green
3. **Earlier session work** (still relevant):
   - PR #16 MERGED Wave 0 H3 (`6b78c29c`): currency lock + dedup refactor
   - Branch tenant isolation SHIPPED (`5f2cb816`)
   - Budget Management module SHIPPED (`f0c8e3c0`)
4. **If user says "lanjutkan" without direction**: ASK which path. Do NOT pick autonomously.

### Status H3 Multi-Currency — COMPLETE for Wave 0+1

Both Wave 0 (write path lock) + Wave 1 (aggregation guard) shipped. The blind spot Oracle flagged is fully closed for current schema.

**Wave 0 (PR #16, merge `6b78c29c`)**:
- `config/app.supported_transaction_currencies = ['IDR']` whitelist
- `HasSupportedCurrencyRules` trait on 6 AbstractRequest write classes (PO, SB, CI, AP, AR, Asset) + AssetImport Excel uploader
- Dedup refactor: `HasBankPaymentRules` + `HasInvoiceLikeRules` traits to drive Sonar duplication density to 0%

**Wave 1 (PR #17, merge `4df36b76`)**:
- `app/Services/Currency/CurrencyGuard` service (`assertHomogeneousQuery`, `assertHomogeneousRows`)
- `app/Exceptions/Currency/MixedCurrencyException` (HTTP 422 + JSON validation error)
- `app/Actions/Concerns/AssertsSingleCurrency` trait (sibling to `ResolvesBranchScope`)
- Applied to `GetAgingDashboardDataAction` (AR + AP pre-flight homogeneity check)
- `AdminSettingRequest` reads currency whitelist from config
- 6 frontend forms: `<InputField name="currency">` removed; admin-settings `CURRENCY_OPTIONS` narrowed to IDR
- New doc: `docs/user-guide-multi-currency.md`

**5 Decision points all RESOLVED** (Oracle picks adopted):
1. UI lock scope: hide entirely
2. Admin display setting: lock to IDR via config
3. Mixed-currency response code: 422 user-correctable
4. Frontend release note: yes
5. Naming: `AssertsSingleCurrency` + `CurrencyGuard`

### Wave 2 (deferred until first non-IDR customer signs)

Full FX subsystem:
- `currency_rates` table (date, base, target, rate)
- `exchange_rate` columns on transaction tables (default 1.0 for IDR)
- `ConvertsCurrency` trait
- `journal_entries.currency` schema change (currently no currency col)
- Widen `config('app.supported_transaction_currencies')` whitelist
- Re-audit AdminSettingRequest, AssetImport, all aggregators

Pull only when first non-IDR customer is signed.

### Other deferred options (if user reroutes)

| # | Item | Effort | Severity |
|---|---|---|---|
| 1 | Timezone drift (Oracle M3) | TBD (needs Oracle deepdive first) | Medium |
| 2 | Financial dashboard branch scoping | 3-5d (needs `branch_id` on `journal_entries`) | High |
| 3 | Pipeline/Approval dashboard branch scoping | TBD (polymorphic resolution) | Medium |
| 4 | Other Oracle finding | TBD | TBD |

## Current State

- Branch: `main`
- HEAD: `4df36b76` (merge of PR #17)
- Working tree: clean
- CI on `main`: green
- Sonar Quality Gate: OK (all conditions pass)
- Module registry: 80 entries (Budget Management added earlier)
- Permission seeded: admin emp has `view_all_branches`

## Recent Commits On Main

| Commit | Subject |
|---|---|
| `4df36b76` | Merge pull request #17 from gmedia/feat/h3-wave1-currency-guard |
| `907edf38` | fix(admin-settings): narrow currency dropdown to IDR + update E2E |
| `4c61d95d` | feat(security): add multi-currency aggregation guard (H3 Wave 1) |
| `1e45f4fa` | docs(handoff): record H3 Wave 0 ship + PR #16 merge |
| `6b78c29c` | Merge pull request #16 from gmedia/feat/h3-wave0-currency-lock |
| `96cf4e19` | refactor: extract shared FormRequest traits to reduce duplication |
| `a85a457e` | feat(security): lock transactional currency to IDR (H3 Wave 0) |

## Branch Isolation — Scoping Policy (still active from earlier work)

| User Type | Behavior |
|---|---|
| Has `view_all_branches` permission | Honor requested `branch_id` (null = all) |
| Employee with `branch_id` set | Forced to own branch (request ignored) |
| Employee with `branch_id` null | Unscoped (backward compat, legacy admin) |

### Scoped Endpoints

| Endpoint | Status |
|---|---|
| `/api/dashboard` | ✅ Scoped |
| `/api/aging-dashboard` | ✅ Scoped + currency-guarded |
| `/api/asset-dashboard/data` | ✅ Scoped |
| `/api/stock-monitor` | ✅ Scoped |
| `/api/financial-dashboard` | ⏳ DEFERRED (journal_entries lacks branch_id + currency) |
| `/api/pipeline-dashboard/data` | ⏳ DEFERRED (polymorphic) |
| `/api/approval-monitoring/data` | ⏳ DEFERRED (polymorphic) |

## Useful Commands

```bash
# Run focused tests
sail test --group currency-guard
sail test --group aging-dashboard
sail test --group admin-settings
sail test --group purchase-orders --group supplier-bills

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
Read task.md first. Repo on `main`, HEAD `4df36b76`, working tree clean.
PR #17 (H3 Wave 1 currency aggregation guard) MERGED. Sonar QG OK.
Full Pest suite 1865 pass.

H3 multi-currency is now fully shipped (Wave 0 + Wave 1). Next action
needs USER DIRECTION (do NOT auto-pick):

1. Timezone drift (Oracle M3) — Medium severity, needs Oracle deepdive
2. Financial dashboard branch scoping — High severity, schema change
   (3-5d): add branch_id to journal_entries
3. Pipeline/Approval dashboard branch scoping — Medium, polymorphic
4. Other Oracle finding (request fresh audit)

If user says "lanjutkan" without direction, ASK which path.
```
