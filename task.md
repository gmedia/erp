# AI Handoff: ERP Active State

Last updated: 2026-06-15 (post-quick-wins-merge) UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## ⚡ Quick Start for Next Session

User is switching to a new opencode session. Read this section first.

1. **Verify baseline**: `git rev-parse HEAD` → expect `96879e3d`. `git status --short` → expect empty.
2. **PR #18 MERGED** (Oracle audit polish quick wins):
   - Merge commit: `96879e3d`
   - Feature commit: `ae3cd085` (4 quick wins bundle)
   - Sonar QG: PASSED, all CI checks green
3. **PR #17 MERGED** (Wave 1 H3 multi-currency aggregation guard, `4df36b76`)
4. **PR #16 MERGED** (Wave 0 H3 currency lock + dedup refactor, `6b78c29c`)
5. **Earlier session work** (still relevant):
   - Branch tenant isolation SHIPPED (`5f2cb816`)
   - Budget Management module SHIPPED (`f0c8e3c0`)
6. **If user says "lanjutkan" without direction**: ASK which path. Do NOT pick autonomously.

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

### Open findings from Oracle post-H3 audit

| # | Item | Severity | Effort | Notes |
|---|---|---|---|---|
| Finding #1 | Legacy AR/AP aging reports use raw `CURDATE()`/`DATEDIFF()` (4 actions) | HIGH | 1-2d | **Closes M3 timezone too**. Files: `IndexArAgingReportAction`, `IndexApAgingReportAction`, `IndexArOutstandingReportAction`, `IndexApOutstandingReportAction`. Port pattern from `GetAgingDashboardDataAction` (already cross-DB Carbon). |
| Finding #3 | `BankReconciliationController` 218 lines, no DB::transaction wrap on update+recreate items | MEDIUM | 3-5h | Race window where rec has zero items. Extract 5 actions, add FormRequests. |
| Finding #4 | `branch_id` parsing boilerplate 3× across dashboard controllers | LOW | 45 min | Add `resolveBranchFromRequest(Request)` to `ResolvesBranchScope` trait. Do this BEFORE pulling Pipeline/Approval polymorphic dashboard scoping. |
| M3 timezone (standalone) | KILLED — folded into Finding #1 | n/a | n/a | Verified: `regional.timezone` setting was orphan UI (closed via QW#1). All Carbon usage is UTC. |
| Financial dashboard branch scoping | DEFERRED | 3-5d | High | Needs `branch_id` on `journal_entries` table (schema change). |
| Pipeline/Approval dashboard branch scoping | DEFERRED | TBD | Medium | Polymorphic resolution. Do Finding #4 FIRST. |

### Polish Quick Wins SHIPPED (PR #18, merge `96879e3d`)

Oracle post-H3 audit recommended 4 quick wins. All landed:
- **QW#1**: killed orphan `regional.timezone` setting (stored, never read) — removed UI input, seeder row, validation rule, and 3 test references
- **QW#2**: AssetImport `strtolower(null)` PHP 8.1+ deprecation guard
- **QW#3**: TopOverdueCustomers/Suppliers replaced hardcoded `Intl('en-US')` with shared `formatDateByRegionalSettings`
- **QW#5**: AdminSettingRequest reuses `HasSupportedCurrencyRules` trait (single source of truth)
- **QW#6**: skipped (`_ide_helper.php` already gitignored)

Net `-54 lines` polish.

## Current State

- Branch: `main`
- HEAD: `96879e3d` (merge of PR #18)
- Working tree: clean
- CI on `main`: green
- Sonar Quality Gate: OK (all conditions pass)
- Module registry: 80 entries (Budget Management added earlier)
- Permission seeded: admin emp has `view_all_branches`

## Recent Commits On Main

| Commit | Subject |
|---|---|
| `96879e3d` | Merge pull request #18 from gmedia/feat/h3-polish-quick-wins |
| `ae3cd085` | refactor: H3 polish quick wins (Oracle audit follow-up) |
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
Read task.md first. Repo on `main`, HEAD `96879e3d`, working tree clean.
PR #18 (Oracle audit polish quick wins) MERGED. Sonar QG OK.
Full Pest suite 1864 pass.

H3 multi-currency fully shipped (Wave 0 + Wave 1 + polish). Next action
needs USER DIRECTION (do NOT auto-pick):

1. Finding #1: Legacy AR/AP aging reports CURDATE() port (HIGH, 1-2d)
   — closes M3 timezone too via the same fix
2. Finding #3: BankReconciliationController thinning (MEDIUM, 3-5h)
   — race window on update+recreate items, no DB::transaction wrap
3. Finding #4: extract branch_id parsing to trait (LOW, 45min)
   — do BEFORE pulling Pipeline/Approval dashboard scoping
4. Financial dashboard branch scoping (HIGH, 3-5d schema change)
5. Other Oracle finding (request fresh audit)

If user says "lanjutkan" without direction, ASK which path.
```
