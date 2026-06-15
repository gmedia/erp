# AI Handoff: ERP Active State

Last updated: 2026-06-15 (Finding #4 AP+AR wave MERGED — PR #25, CI green on main) UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## ⚡ Quick Start for Next Session

User is switching to a new 0pencode session. Read this section first.

1. **Verify baseline**: `git status --short` → expect only `task.md` (or empty). `git log --oneline -1` on main → expect `460072f4` (or fresher).
2. **3 Oracle audit PRs MERGED this session, CI on main green:**
   - **PR #25** (squash `460072f4`) — Finding #4 AP+AR pilot: postJournal race close via `afterCommit` hook in `StoresItemsInTransaction`
   - **PR #23** (squash `ff426b1e`) — Findings #1, #2: DATEDIFF + MONTH cross-DB port
   - **PR #22** (squash `2fe1b5f2`) — Findings #3, #5, #9: BR controller race close + 5 action unit tests
3. **Previous session 3 PRs merged (still relevant):**
   - PR #21 (`87ddea11`) — Finding #3: BR thinning + DB::transaction race fix
   - PR #20 (`07d37688`) — Finding #1: AR/AP aging Carbon port + M3 timezone
   - PR #19 (`8c076305`) — Finding #4: `resolveBranchFromRequest` trait extraction
4. **Earlier session work** (still relevant):
   - H3 multi-currency Wave 0+1 SHIPPED (#16, #17)
   - H3 polish quick wins SHIPPED (#18)
   - Branch tenant isolation SHIPPED (`5f2cb816`)
   - Budget Management module SHIPPED (`f0c8e3c0`)
5. **If user says "lanjutkan" without direction**: ASK which path. Do NOT pick autonomously.

### Dev environment state (verified end of last session)

- Admin employee has `view_all_branches` permission attached (215 total perms).
- All 6 transactional currency tables: 0 non-IDR rows.
- Setting `currency` = `IDR`.
- DB sample data fully seeded.
- DB connection: `mariadb` host, database `laravel`.

If dev DB seems empty after pulling: `sail artisan db:seed`. Schema is intact.

### Status — Oracle audit refresh wave 2 + Finding #4 AP+AR (this session, ALL MERGED)

Oracle re-audit found PR #20/#21 left work incomplete. 2 PRs closed those gaps. Then 1 more PR shipped Finding #4 AP+AR pilot:

| Finding | Severity | PR | Squash | What it does |
|---|---|---|---|---|
| #4 (AP+AR) | MEDIUM | **#25** | `460072f4` | Extends `StoresItemsInTransaction::updateWithSyncedItems` with `?callable $afterCommit` parameter. AP+AR controllers use it to wrap `postJournal->execute()` inside the same DB::transaction. 2 new regression tests verify status/confirmed_at rollback when journal posting fails. |
| #1, #2 | HIGH | **#23** | `ff426b1e` | Removes last `DATEDIFF()` + `MONTH()` MariaDB-only SQL. PHP-side Carbon bucketing in resources + Service. New trait `app/Exports/Concerns/ComputesDaysOverdue`. 32 tests pass. |
| #3, #5, #9 | MED+LOW | **#22** | `2fe1b5f2` | Closes BR race window in 4 actions (Import/AutoMatch/Match/Unmatch — `recalculateBalances()` moved INSIDE `DB::transaction`). AddItem action gains transaction wrap + recalc. 5 new action unit tests. 50 tests pass. |

### Earlier wave (already merged):

| Finding | Severity | PR | Effort | What landed |
|---|---|---|---|---|
| #4 | LOW | #19 | 45 min | `resolveBranchFromRequest(Request)` on `ResolvesBranchScope` trait, eliminating 3× boilerplate in `AgingDashboardController`, `AssetDashboardController`, `StockMonitorController` |
| #1 (initial) | HIGH | #20 | ~2h | `AgingReportBoundaries` trait — but missed Outstanding actions + `MONTH()`; addressed in #23 |
| #3 (initial) | MEDIUM | #21 | ~1.5h | 5 new actions + 2 new FormRequests — but missed controller-side `recalculateBalances()` race; addressed in #22 |

### Audit refresh remaining findings

| # | Item | Severity | Effort | Status |
|---|---|---|---|---|
| #4 | 6 remaining controllers (CustomerInvoice, GoodsReceipt, StockAdjustment, SupplierBill, SupplierReturn — plus any other with same shape) — adopt same `afterCommit` hook pattern from PR #25 | MEDIUM | ~1h per controller (~6h total) | PARTIALLY DONE — AP+AR shipped in PR #25 |
| #6 | `CurrencyGuard` adopted only in `AgingDashboard` — missing on aging/outstanding reports + FinancialReportService + BudgetVarianceService | MEDIUM | ~3h | DEFERRED |
| #7 | `IndexApAgingReportAction` reinlines aging CASE; can adopt trait helper | LOW | ~20m | DEFERRED |
| #8 | `ApprovalFlowController::store/update` duplicates step-create payload | LOW | ~30m | DEFERRED |
| #10 | `MyApprovalController` not thinned (~165 lines, 2 fat methods) | LOW | ~1.5h | DEFERRED |

### Findings remaining (all DEFERRED — schema work)

| # | Item | Severity | Effort | Notes |
|---|---|---|---|---|
| Financial dashboard branch scoping | DEFERRED | 3-5d | HIGH | Needs `branch_id` on `journal_entries` table (schema change). Also requires `currency` col when Wave 2 lands. |
| Pipeline/Approval dashboard branch scoping | DEFERRED | TBD | MEDIUM | Polymorphic resolution. Finding #4 (PR #19) unblocked the trait helper. |
| H3 Wave 2 (multi-currency FX subsystem) | DEFERRED | weeks | n/a | Pull only when first non-IDR customer signs. See archived plan in `task.handoff-archive.md`. |

### Current State

- Branch: `main` at HEAD `460072f4` (working tree: only `task.md` for handoff)
- 3 PRs from this session ALL MERGED, branches deleted on remote.
- CI on main: PR #25 run `27574449669` SUCCESS (Quality + Playwright + Test suite all green).
- Pest local (this session):
  - PR #22 → `bank-reconciliations` group: **50 passed**, 152 assertions
  - PR #23 → `ap-aging-report` 5 + `ar-aging-report` 5 + `ap-outstanding-report` 6 + `ar-outstanding-report` 7 + `financial-dashboard` 9 = **32 passed**
  - PR #25 → `ap-payments` 13 + `ar-receipts` 9 + `ap-journal-posting` 13 + `ar-journal-posting` 13 + `purchase-orders` smoke 18 = **66 passed**
- Quality gates all PRs: phpstan clean, duster clean.
- Module registry: 80 entries.
- Permission seeded: admin emp has `view_all_branches`.

### Notes from this session

- Oracle audit refresh (4m33s) found PR #20 + #21 incomplete. Identified 10 findings; shipped Findings #1, #2 (PR #23) + #3, #5, #9 (PR #22) + #4 AP+AR pilot (PR #25) in same session.
- Parallel subagent execution had a credit-exhaustion incident on first wave: PR A subagent died at 25m, PR B subagent ran 26m. Both work was salvageable — recovered manually:
  - PR A: subagent had committed AND pushed before dying; only PR creation step missed.
  - PR B: subagent had staged 11 files but not committed. Recovered, ran quality gates manually (duster + phpstan + tests all green), committed + pushed + opened PR.
- PR #25 (Finding #4 AP+AR) executed directly without delegation — scope small enough to do inline. Pattern hot for the 6 remaining controllers.
- Depwire/Sonar still produce false positives for Laravel structure — confirmed prior session's note still holds. Skip those tools next session unless config improves.

### Recent Commits On Main

| Commit | Subject |
|---|---|
| `460072f4` (HEAD) | fix(ap-ar-payments): close postJournal race via afterCommit hook in StoresItemsInTransaction (#25) |
| `85d4da80` | docs(handoff): record PR #22 + #23 merge + CI green on main |
| `2fe1b5f2` | fix(bank-reconciliations): close mutation races + add action unit tests (#22) |
| `ff426b1e` | fix(reports): port DATEDIFF + MONTH SQL to cross-DB Carbon/EXTRACT (#23) |
| `b06aec05` | docs(handoff): finalize session handoff with post-merge state |
| `2f549470` | docs(handoff): record Oracle audit findings #1, #3, #4 ship |
| `8c076305` | Merge pull request #19 — Finding #4 (initial trait extraction) |
| `07d37688` | Merge pull request #20 — Finding #1 (initial aging port) |
| `87ddea11` | Merge pull request #21 — Finding #3 (initial BR thinning) |
| `96879e3d` | Merge pull request #18 (H3 polish quick wins) |

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
sail test --group bank-reconciliations
sail test --group ar-aging-report
sail test --group ap-aging-report
sail test --group ar-outstanding-report
sail test --group ap-outstanding-report
sail test --group aging-dashboard
sail test --group asset-dashboard
sail test --group stock-monitor

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

## Continuation Prompt for New Session

```text
Read task.md first. Repo on `main` at HEAD `460072f4`, working tree clean.
3 PRs from previous session ALL MERGED, CI on main green.

Quick verify:
  git rev-parse HEAD          # expect 460072f4 or fresher
  git status --short          # expect empty
  gh run list --branch main --limit 3   # verify latest is green
  gh pr list --base main --state open   # expect empty unless new work started

If dev DB seems empty: `sail artisan db:seed`. Schema is intact.

NEXT ACTION needs USER DIRECTION (do NOT auto-pick):

1. Finding #4 — 6 remaining controllers (MEDIUM, ~1h each)
   Apply same afterCommit hook pattern from PR #25 to:
   - CustomerInvoiceController::update (postJournal pattern)
   - GoodsReceiptController::update
   - StockAdjustmentController::update
   - SupplierBillController::update
   - SupplierReturnController::update
   - Any other controller with same isNewlyConfirmed + postJournal shape
   Pattern: pass `afterCommit: $isNewlyConfirmed ? fn => $postJournal->execute(...) : null`
   to `updateWithSyncedItems`. Trait already supports it. Add rollback regression test.

2. Polish wave — Findings #6 #7 #8 #10:
   #6 CurrencyGuard coverage on remaining money-aggregation surfaces (~3h)
   #7 IndexApAgingReportAction adopt trait helper (~20m)
   #8 ApprovalFlowController step-create dedupe (~30m)
   #10 MyApprovalController thin to actions (~1.5h)

3. Refresh Oracle audit (after Finding #4 fully closed)

4. Schema-blocked items still deferred:
   - Financial dashboard branch scoping (HIGH, 3-5d, needs branch_id on
     journal_entries; coordinate with H3 Wave 2 currency col)
   - Pipeline/Approval polymorphic dashboard scoping (MEDIUM)
   - H3 Wave 2 multi-currency FX subsystem (weeks, await first non-IDR
     customer)

5. Other product feature work (request from user)

Depwire/Sonar tools still produce false positives for Laravel auto-discovery
patterns. Skip unless config improves.

CONVENTIONS REMINDER:
- Never commit without explicit user request (AGENTS.md §3)
- Never auto-merge PR without explicit instruction
- Use Sail for all runtime commands
- Use feature branches for all work; PR via gh
- Match Sonar QG (duplication < 3%, coverage stays at 100% on new code)

If user says "lanjutkan" without direction, ASK which path.
```
