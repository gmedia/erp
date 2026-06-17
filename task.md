# AI Handoff: ERP Active State

Last updated: 2026-06-17 (Oracle design for financial-dashboard branch scoping recorded; impl DEFERRED on accounting-policy blocker) UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## ⚡ Quick Start for Next Session

User is switching to a new 0pencode session. Read this section first.

1. **Verify baseline**: `git status --short` → expect empty (or only `task.md`). `git log --oneline -1` on main → expect `e3ad1029` (or fresher). No open PRs. Latest main CI green (`27679994418`).
2. **ALL Oracle audit findings CLOSED.** Latest 2 PRs:
   - **PR #32** (squash `a97a4b67`) — Finding #6: CurrencyGuard on 4 AP/AR aging+outstanding report actions (last original finding)
   - **PR #31** (squash `f6bbcf82`) — Audit-refresh Findings #1-#3: BankReconciliation removeItem recalc + match/remove thinned + addItem refreshed parent
   - **Post-audit (no code):** Oracle design recorded for financial-dashboard branch scoping — impl DEFERRED, blocked on an accounting-policy decision. See the "Financial dashboard branch scoping — Oracle design + the blocker" section.
3. **Earlier this session — 8 more Oracle audit PRs MERGED:**
   - **PR #30** (squash `aa9e4b12`) — Finding #10: MyApprovalController thin
   - **PR #29** (squash `3e67b479`) — Findings #7, #8: aging trait dedupe + approval flow step extract
   - **PR #28** (squash `73ea60ca`) — Finding #4 sweep: CompleteBankReconciliationAction race close
   - **PR #27** (squash `3e68adcc`) — Finding #4 wave 3: SA + SB + SR
   - **PR #26** (squash `a0aaca72`) — Finding #4 wave 2: CustomerInvoice + GoodsReceipt
   - **PR #25** (squash `460072f4`) — Finding #4 wave 1: AP+AR `afterCommit` hook
   - **PR #23** (squash `ff426b1e`) — Findings #1, #2 (original): DATEDIFF + MONTH cross-DB
   - **PR #22** (squash `2fe1b5f2`) — Findings #3, #5, #9 (original): BR race close + unit tests
4. **Previous session 3 PRs merged (still relevant):**
   - PR #21 (`87ddea11`) — Finding #3: BR thinning + DB::transaction race fix
   - PR #20 (`07d37688`) — Finding #1: AR/AP aging Carbon port + M3 timezone
   - PR #19 (`8c076305`) — Finding #4: `resolveBranchFromRequest` trait extraction
5. **Earlier session work** (still relevant):
   - H3 multi-currency Wave 0+1 SHIPPED (#16, #17)
   - H3 polish quick wins SHIPPED (#18)
   - Branch tenant isolation SHIPPED (`5f2cb816`)
   - Budget Management module SHIPPED (`f0c8e3c0`)
6. **If user says "lanjutkan" without direction**: ASK which path. Do NOT pick autonomously.

### Dev environment state (verified end of last session)

- Admin employee has `view_all_branches` permission attached (215 total perms).
- All 6 transactional currency tables: 0 non-IDR rows.
- Setting `currency` = `IDR`.
- DB sample data fully seeded.
- DB connection: `mariadb` host, database `laravel`.

If dev DB seems empty after pulling: `sail artisan db:seed`. Schema is intact.

### Status — Oracle audit refresh + polish wave + 2nd audit refresh (this session, ALL MERGED)

First Oracle re-audit found PR #20/#21 left work incomplete. Over the session, 9 PRs shipped (all original findings except #6). A SECOND fresh audit pass (run after Finding #4 fully closed) found 3 new BankReconciliation issues, shipped in PR #31:

| Finding | Severity | PR | Squash | What it does |
|---|---|---|---|---|
| #6 | MEDIUM | **#32** | `a97a4b67` | CurrencyGuard on 4 AP/AR aging+outstanding report actions via `guardSupplierBillCurrency`/`guardCustomerInvoiceCurrency` trait helpers. FinancialReportService + BudgetVarianceService SKIPPED — journal_entries/budgets lack `currency` column. 2 regression tests (mixed-currency → 422). |
| Refresh #1-#3 | MED+LOW+LOW | **#31** | `f6bbcf82` | `removeItem` skipped `recalculateBalances()` (financial integrity). Extract `RemoveBankReconciliationItemAction`. Add `MatchBankReconciliationItemRequest`; inject Match/Unmatch/Remove via DI. `addItem` returns refreshed parent. |
| #10 | LOW | **#30** | `aa9e4b12` | MyApprovalController thinning: extract Approve/Reject actions + 2 FormRequests. 165→105 lines. 5 unit tests. |
| #7, #8 | LOW | **#29** | `3e67b479` | Aging trait `agingBucketSelectSqlWithAliases()`. ApprovalFlow `createStep()` extract. |
| #4 (sweep) | MEDIUM | **#28** | `73ea60ca` | Sweep follow-up: `CompleteBankReconciliationAction` wrapped in `DB::transaction`. Best-effort Throwable swallow preserved INSIDE the wrap. 1 regression test. |
| #4 (SA+SB+SR) | MEDIUM | **#27** | `3e68adcc` | Wave 3: StockAdjustment, SupplierBill, SupplierReturn. SA + SR preserve ValidationException swallow semantic INSIDE the hook. 5 regression tests. |
| #4 (CI+GR) | MEDIUM | **#26** | `a0aaca72` | Wave 2: CustomerInvoiceController + GoodsReceiptController. GR preserves the ValidationException swallow semantic INSIDE the hook. 3 regression tests. |
| #4 (AP+AR) | MEDIUM | **#25** | `460072f4` | Wave 1: extends `StoresItemsInTransaction::updateWithSyncedItems` with `?callable $afterCommit` parameter. AP+AR controllers use it to wrap `postJournal->execute()` inside the same DB::transaction. 2 regression tests. |
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
| #4 | Sweep verified — no further race sites found via grep `postJournal->execute` across `app/` (7 controllers + 1 action covered in PRs #25-#28) | — | — | DONE |
| #7 | IndexApAgingReportAction adopt trait helper | LOW | DONE | Closed in PR #29 |
| #8 | ApprovalFlowController step-create dedupe | LOW | DONE | Closed in PR #29 |
| #10 | MyApprovalController thin to actions | LOW | DONE | Closed in PR #30 |
| #6 | `CurrencyGuard` adopted only in `AgingDashboard` — missing on aging/outstanding reports + FinancialReportService + BudgetVarianceService | MEDIUM | ~3h | DEFERRED |
| #7 | `IndexApAgingReportAction` reinlines aging CASE; can adopt trait helper | LOW | ~20m | DEFERRED |
| #8 | `ApprovalFlowController::store/update` duplicates step-create payload | LOW | ~30m | DEFERRED |
| #10 | `MyApprovalController` not thinned (~165 lines, 2 fat methods) | LOW | ~1.5h | DEFERRED |

### Findings remaining (all DEFERRED — schema work)

| # | Item | Severity | Effort | Notes |
|---|---|---|---|---|
| Financial dashboard branch scoping | DEFERRED | 4 PRs | HIGH | **Oracle design done (2026-06-17). BLOCKED on an accounting-policy decision — see below.** |
| Pipeline/Approval dashboard branch scoping | DEFERRED | TBD | MEDIUM | Polymorphic resolution. Finding #4 (PR #19) unblocked the trait helper. |
| H3 Wave 2 (multi-currency FX subsystem) | DEFERRED | weeks | n/a | Pull only when first non-IDR customer signs. See archived plan in `task.handoff-archive.md`. |

#### Financial dashboard branch scoping — Oracle design + the blocker (2026-06-17)

Consulted Oracle on the `journal_entries.branch_id` design before writing any code. Two explore agents mapped all 11 journal-entry creation sites + the dashboard data flow first. Key outcome: this is **not** a clean 3-5d task — the headline metric (per-branch balance sheet) is **accounting-unsound** without a policy decision, so implementation was deliberately deferred.

**THE BLOCKER (accounting policy, not engineering):**
A per-branch balance sheet built with `WHERE journal_entries.branch_id = ?` will arithmetically balance (every entry is balanced + header-single-branch) but be **materially misleading**: period-closing entries and depreciation runs are null-branch (company-wide) and drop out of a branch filter → branch P&L accounts appear un-zeroed after close, branch profit is overstated (no depreciation), accumulated depreciation missing from branch BS. "Balanced but wrong."

Resolution requires a deliberate accounting-policy choice (whoever owns accounting policy, NOT the agent):
- **Option 1 — Head-Office/Corporate branch:** every entry gets a branch; company-wide entries post to "Head Office" (null→head office). No nulls → `Σ branches = consolidated`, per-branch BS is complete. This is a policy decision + its own design, not a column add.
- **Option 2 — Inter-branch clearing accounts** (Due-to/Due-from). Heavier; only if single source docs span branches.
- **Option 3 (Oracle-recommended for this iteration) — Segment reporting:** branch is a P&L *dimension* only. Ship branch-filtered **income statement / monthly trends / cash flow** as management views WITH a visible "excludes unallocated/corporate costs" disclosure. **Do NOT ship a per-branch balance sheet.** Defer that until Option 1 is chosen.

**Oracle's approved design (when work resumes), staged as 4 independent PRs:**
1. **PR1 (schema, <1h, inert):** nullable `branch_id` FK to branches, `restrictOnDelete`, composite index `(fiscal_year_id, status, branch_id)`. Zero behavior change, fully reversible. Min safe increment — unblocks everything.
2. **PR2 (backfill command, 1-4h):** idempotent artisan command `journals:backfill-branch --dry-run`, chunked, guarded `WHERE branch_id IS NULL`, per-source counts, uses morph map (NOT hardcoded class strings). Derives branch from polymorphic `source_type`/`source_id`. Warehouse-indirect sources (GR/SA/SR) set from `warehouse.branch_id`, leave null where that is null. No-branch sources (bank recon, depreciation, recurring, period closing) stay null by design. NOT inside the migration.
3. **PR3 (write-path wiring, 1-2d):** add optional `branch_id` to `CreateJournalEntryAction::execute($data)` — **resolved BEFORE the DB::transaction/retry closure** (retry only re-rolls entry_number; branch must be a stable captured scalar). 9 posting actions resolve own branch:
   - direct `->branch_id`: ApPayment, ArReceipt, CustomerInvoice, SupplierBill
   - `->warehouse->branch_id` (nullable, null-guard): GoodsReceipt, StockAdjustment
   - inferred via PO/GR chain: SupplierReturn
   - explicit null: BankReconciliation, AssetDepreciation + the 2 bypass paths (ExecuteRecurringJournalAction, ClosePeriodAction — assert null in a test)
   **MANDATORY authz gate:** manual `JournalEntryController::store` must NOT accept a request `branch_id` without `ResolvesBranchScope` gating — a branch employee posting into another branch is a financial-integrity hole.
   **Reversal/void inheritance:** reversing/void entries MUST copy the original's `branch_id` or branch nets break silently — check the void path.
4. **PR4 (read path, 1-2d):** dashboard/report branch filter scoped to **P&L / income-statement / trend metrics ONLY**, null-excluded-from-specific-branch / included-in-all-branches (additive segment model — including-null-in-every-branch double-counts on consolidation), with the "unallocated/corporate excluded" disclosure. **Explicitly NOT per-branch balance sheet** (the blocker above). `FinancialDashboardController` adopts `ResolvesBranchScope` (Pattern A like AgingDashboardController); `GetFinancialDashboardDataAction` + `FinancialReportService` gain an optional `?int $branchId` filtering `journal_entries.branch_id` at the header level.

**Out of scope / future:** cost allocation of company-wide overhead across branches (the real fix for "incomplete branch P&L"); routing recurring + period-closing through the choke point for a single creation path.

### Current State

- Branch: `main` at HEAD `e3ad1029` (working tree clean; will be `e3ad1029` or fresher after this handoff commit)
- 12 PRs shipped this session ALL MERGED, branches deleted on remote (#22, #23, #25-#32). Orphan PR #24 closed.
- CI on main: latest run `27679994418` SUCCESS (Quality + Playwright + Test suite all green).
- Post-audit: Oracle design consultation for financial-dashboard branch scoping recorded (no code) — see design+blocker section above.
- Quality gates all PRs: phpstan clean, duster clean.
- Module registry: 80 entries.
- Permission seeded: admin emp has `view_all_branches`.

### Audit status — ALL FINDINGS CLOSED

All 10 original Oracle audit findings (#1-#10) + 3 audit-refresh findings closed and merged. Two full audit passes complete. Remaining items are schema-blocked (deferred) only.

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
| `e3ad1029` (HEAD) | docs(handoff): record Oracle design + accounting-policy blocker for financial-dashboard branch scoping |
| `90931c1e` | docs(handoff): record PR #32 merge — Finding #6 closed, audit backlog drained |
| `a97a4b67` | fix(reports): enforce single-currency guard on AP/AR aging + outstanding (Oracle Finding #6) (#32) |
| `f6bbcf82` | fix(bank-reconciliations): recalc on removeItem + thin match/remove to actions (#31) |
| `aa9e4b12` | refactor(my-approvals): thin controller via action extract (Oracle Finding #10) (#30) |
| `3e67b479` | refactor(aging-reports,approval-flows): polish dedupe per Oracle Findings #7 + #8 (#29) |
| `73ea60ca` | fix(bank-reconciliations): close postJournal race on complete via DB::transaction wrap (#28) |
| `3e68adcc` | fix(stock-adjustments,supplier-bills,supplier-returns): close postJournal race via afterCommit hook (#27) |
| `a0aaca72` | fix(customer-invoices,goods-receipts): close postJournal race via afterCommit hook (#26) |
| `460072f4` | fix(ap-ar-payments): close postJournal race via afterCommit hook in StoresItemsInTransaction (#25) |

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
Read task.md first. Repo on `main` at HEAD `e3ad1029`, working tree clean.
12 PRs shipped this session ALL MERGED, CI on main green.
ALL Oracle audit findings (#1-#10 + 3 refresh findings) CLOSED.
Post-audit: financial-dashboard branch scoping has an Oracle design recorded
but is BLOCKED on an accounting-policy decision (no code written).

Quick verify:
  git rev-parse HEAD          # expect e3ad1029 or fresher
  git status --short          # expect empty
  gh run list --branch main --limit 3   # verify latest is green
  gh pr list --base main --state open   # expect empty unless new work started

If dev DB seems empty: `sail artisan db:seed`. Schema is intact.

NEXT ACTION needs USER DIRECTION (do NOT auto-pick):

The Oracle audit backlog is fully drained. The most-requested deferred item
(financial dashboard branch scoping) now has an Oracle design recorded but is
BLOCKED on an accounting-policy decision. Remaining options:

1. Financial dashboard branch scoping — DESIGN DONE, POLICY-BLOCKED.
   See "Financial dashboard branch scoping — Oracle design + the blocker"
   section above for the full 4-PR plan. Before resuming, an accounting-policy
   owner must decide how per-branch balance sheets handle null-branch
   (company-wide) entries:
     - Option 1: Head-Office branch (every entry branch-attributed)
     - Option 3 (Oracle-recommended): P&L-segment views only, NO per-branch
       balance sheet, with "excludes unallocated/corporate" disclosure.
   PR1 (inert nullable column + index, <1h) is safe to ship anytime and
   unblocks the rest — but only start it once the policy direction is set,
   since the read-path (PR4) shape depends on the decision.

2. Pipeline/Approval polymorphic dashboard scoping (MEDIUM) — trait helper
   from PR #19 available; polymorphic resolution still needed.

3. H3 Wave 2 multi-currency FX subsystem (weeks) — pull when first non-IDR
   customer signs.

4. Fresh Oracle audit pass — diminishing returns (2 passes done this cycle;
   last found only the 3 BankReconciliation issues now closed). Probably not
   worth it until more feature work lands.

5. Product feature work (request specs from user).

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
