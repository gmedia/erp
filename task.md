# AI Handoff: ERP Active State

Last updated: 2026-06-21 (FULL 2b COMPLETE + operational validation. PRs #46-#54 (initiative) + #55 (smoke test & cross-branch detection command) all MERGED. main HEAD `92e292e8`. Detection on dev = 0 multi-branch journals → engine dormant-but-correct confirmed; PR8 still NOT warranted. Clearing engine + line-level reports + per-branch balance sheet (Due-From/Due-To) + per-asset-branch depreciation + per-branch period closing + bank-rec/recurring branch schema + per-line branch pickers UI all live. Only optional PR8 (retro-correction of historical multi-branch journals) deferred — gated on cross-branch detection which is currently 0. Engine is dormant-but-correct on all current single-branch data.) UTC

## SESSION 2026-06-21 (cont.) — 2b operational validation (PR #55 merged)

Dua follow-up validasi operasional setelah full 2b selesai. Tidak mengubah jalur kode produksi — hanya menambah bukti end-to-end + alat monitoring read-only. main HEAD `92e292e8`. CI 5/5 green, Sonar PASS.

| Item | File | Isi |
|------|------|-----|
| #1 Smoke test E2E | `tests/Feature/JournalEntries/MultiBranchJournalSmokeTest.php` | Jurnal multi-branch via write-path asli (CreateJournalEntryAction) → clearing auto-inject → persist → FinancialReportService per branch. Assert per-branch TB dr==cr, Due-From (A)/Due-To (B), BS A==L+E, income statement di branch penerima, clearing nets 0 company-wide. 3 tests, 18 assertions. |
| #2 Detection command | `app/Console/Commands/DetectCrossBranchJournals.php` + `tests/Feature/Console/DetectCrossBranchJournalsTest.php` | `journals:detect-cross-branch [--posted-only] [--limit=N]`. Hitung entry multi-branch ekonomis, clearing lines (1999-IBC), null-branch lines. GATE untuk PR8. 3 tests. |

**Hasil di dev: 0 entry multi-branch** → engine dormant-but-correct terkonfirmasi; PR8 tetap NOT warranted. Grup `inter-branch-clearing` penuh: 22 passed / 232 assertions. duster + phpstan clean.

## SESSION 2026-06-21 (cont.) — FULL 2b COMPLETE (PR #46-#54 all merged)

The full inter-branch initiative is done end to end. All PRs CI 5/5 green, Sonar gates OK (new-code coverage 95.6-100%, duplication 0%). Two Oracle design consults (PR3 engine `ses_11732ec8`, PR6 closing `ses_1161bc0c`).

| PR | Commit | Scope |
|----|--------|-------|
| #46 | `77ef04b2` | Backfill journal_entry_lines.branch_id = header (data migration) |
| #47 | `094864b0` | Seed 1999-IBC Inter-Branch Clearing account (seeder + idempotent migration) |
| #48 | `dad775ab` | InterBranchClearingService (pure, integer-cents) + wired into all 4 write paths + per-branch guard |
| #49 | `bba42f29` | Switch reports (accountsWithPostedSums, getMonthlyTrends) from header to LINE branch_id |
| #50 | `55cce84a` | Balance-sheet clearing sign-reclassification (Due-From asset / Due-To liability) |
| #51 | `79b1f041` | Per-asset-branch depreciation posting + clearing no-op refinement (each-branch-balances) |
| #52 | `5c54524f` | Per-branch period closing (Option A: derive per-branch P&L from posted lines) + reopen/reclose double-close fix |
| #53 | `c55a554c` | bank_reconciliations.branch_id + recurring_journal_lines.branch_id schema + posting wiring |
| #54 | `aa2bbaa3` | Per-line branch pickers UI (journal + recurring line dialogs, bank-rec header) + lines.*.branch_id validation |

**Invariants locked by tests:** per-branch trial balance dr==cr; clearing nets to 0 company-wide; each branch BS A==L+E (with per-branch CYE + per-branch RE on close); `branchId=null` everywhere = byte-identical company-wide behavior. Engine fires ONLY on genuinely cross-branch journals (0 in current data).

**Key gotcha (PR7b CI):** adding the per-line Branch combobox made the journal "Add Line" dialog have 2 `button[role=combobox]`; E2E helper `tests/e2e/journal-entries/helpers.ts:60` needed `.first()` to target Account (9 specs failed, fixed in same PR).

**Deferred:** PR8 (audited retro-correction of historical economically-multi-branch journals) — only worth building if production detection of cross-branch journals becomes material; currently 0. Design staged in `.sisyphus/plans/per-branch-financial-statements-2b.md`.

## SESSION 2026-06-21 (cont.) — FULL 2b core (clearing engine + line-level statements), 5 PRs MERGED

User chose **full 2b** (forward-capability for cross-branch journals; detection on current data = 0, so the engine is provably dormant but correct). Oracle reviewed the PR3 engine design before any code (`ses_11732ec8affepPNKdniqBdrXbp`).

- **PR #46** (`77ef04b2`) — data migration: backfill `journal_entry_lines.branch_id = parent journal_entries.branch_id` (chunked, idempotent, reversible). Establishes the line=header invariant. Zero behavior change (reports still read header at this point).
- **PR #47** (`094864b0`) — seed single `1999-IBC` "Inter-Branch Clearing" account (asset/current_asset/debit/not-cashflow) under Current Assets in CoaSeeder (both COA trees) + idempotent migration for existing COA versions. DEVIATION from plan §3-Q1: resolved by CODE per COA version at runtime, NOT a settings id (version-safe). Inert until PR3.
- **PR #48** (`dad775ab`) — `InterBranchClearingService` (pure, DB-free, integer-cents): `inject()` strips existing clearing lines, groups by resolved branch, injects one clearing line per imbalanced branch (sorted, deterministic), fail-closed on null branch when multi-branch; `assertBalancedPerBranch()` post-injection guard (throws→rollback, the anti-corruption net); `resolveAccountIdForFiscalYear()`. Wired into ALL 4 write paths: CreateJournalEntryAction (threads per-line branch line→header→default, inject+guard inside txn), UpdateJournalEntryAction (draft delete/recreate), ExecuteRecurringJournalAction + ClosePeriodAction (the 2 funnel bypasses — single-branch → provable no-op + inherit guard). 12 unit + 3 feature tests. Per-branch period closing deferred to PR6 (closing stays single-branch = no-op).
- **PR #49** (`bba42f29`) — switch `accountsWithPostedSums` + `getMonthlyTrends` from header `journal_entries.branch_id` to LINE `journal_entry_lines.branch_id`. Byte-identical for all single-branch data (PR1 backfill). New regression test: header-A/lines-split-A/B proves line-level attribution. Updated 2 fixtures to tag line branch_id (post-PR3 reality).
- **PR #50** (`55cce84a`) — balance-sheet clearing sign-reclassification: when branchId != null, `1999-IBC` net-debit slice → "Due From Branches" (asset), net-credit → "Due To Branches" (liability). Company-wide / zero-slice = byte-identical (skipped). Per-branch CYE already line-level via PR5a.

**Master invariant (locked by tests):** per-branch trial balance dr==cr; clearing nets to 0 company-wide; each branch BS: A==L+E. `branchId=null` everywhere = byte-identical company-wide behavior. All 5 PRs CI 5/5 green; Sonar gates OK (new-code coverage 95.7–100%, duplication 0%).

**Remaining full-2b PRs (not started):** PR4 (per-line branch derivation for asset depreciation per-asset, recurring per-template-line [needs recurring_journal_lines.branch_id column], bank-rec bank→branch map), PR6 (per-branch period closing + per-branch RE/opening — RISKY), PR7 (frontend per-line branch picker for manual+recurring + clearing preview), PR8 (optional retro-correction, only if detection material).

## SESSION 2026-06-21 — Per-branch Income Statement & Comparative (PR #45 MERGED)

Extended reduced-2b per-branch filtering to the two remaining financial report pages, following the exact #43/#44 pattern. Squash `5d57b6cf`, branch deleted, main synced.

- **Backend**: `ReportController::incomeStatement/comparative` resolve+pass `branch_id`; `FinancialReportService::getComparativeReport` gained `?branchId` and threads it into `accountsWithPostedSums`+`prepareComparisonContext` (`getIncomeStatement` already accepted it pre-#43, now actually wired); both requests validate `branch_id` (nullable|integer|exists:branches,id); both exports thread `resolveBranchId()`.
- **Frontend**: income-statement + comparative pages pass `branchId`+`onBranchChange` into the shared shell and add `branch_id` to export payload + URL params.
- **Refactor (dedup)**: extracted `resources/js/components/reports/financial/FinancialReportExportButton.tsx` and replaced the duplicated 26-28 line export-button block across ALL FIVE financial report pages (balance-sheet, cash-flow, trial-balance, income-statement, comparative). `comparisonYearId` optional → single-year pages reuse it. Net −28 lines. This resolved the SonarCloud new-code duplication gate (8.0% fail → 0.0% pass).
- **Tests**: +7 Pest (PerBranchFinancialReportTest now covers IS+comparative invariants/endpoints/export-validation; reports group 45 green); +2 E2E (per-branch-financial-reports now 5 cases green).
- **CI**: all 5 checks green (Quality, Playwright E2E, Test suite, both SonarCloud). Sonar quality gate OK (coverage new code 100%, duplication 0.0%).

Semantics unchanged & backward-compatible: `branchId=null` = byte-identical company-wide; specific branch filters by header `journal_entries.branch_id`. Escalation trigger for full 2b unchanged (first real cross-branch journal).

## THIS SESSION — Per-branch financial statements (reduced 2b), 3 PRs MERGED

User chose scope **2b** then **reduced** (after detection showed zero cross-branch journals). Oracle ruled **Option B**: under the single-branch invariant, filtering the existing header `journal_entries.branch_id` is mathematically identical to line-level filtering, so NO inter-branch clearing engine / journal write-path change is needed.

- **PR #42** (squash `d97c8c7a`) — schema: nullable `journal_entry_lines.branch_id` FK + composite index `(branch_id, account_id)`, model fillable + `branch()` relation. **Inert forward-compat** (nothing reads/writes it under reduced 2b; reserved for a future clearing engine). `down()` drops FK before index (MariaDB 1553).
- **PR #43** (squash `99b70ce2`) — backend: threaded `?branchId` into `FinancialReportService::getTrialBalance/getCashFlow/getBalanceSheet/calculateNetIncome`. `getBalanceSheet` injects **per-branch** CYE (`calculateNetIncome(branchId)`) so each branch's sheet balances (A=L+E) — this is the single highest-risk line (company-wide CYE while filtering A/L/E = guaranteed imbalance). `branchId=null` keeps prior company-wide behavior byte-identical. ReportController + `InteractsWithFinancialReportRequest::resolveBranchId` thread `branch_id`; requests validate `nullable|integer|exists:branches,id`; 3 exports thread branch_id. New `PerBranchFinancialReportTest` (7 tests): per-branch TB self-balances, per-branch BS balances w/ per-branch CYE, CYE==net income, null-branch robustness, omitting branchId==company-wide. Full reports group 38 green.
- **PR #44** (squash `c5f4ccfd`) — frontend: "All Branches" AsyncSelect (`/api/branches`) on Balance Sheet / Trial Balance / Cash Flow shells, rendered AFTER existing fiscal-year/comparison selectors (preserves existing specs' positional combobox indices). branch_id preserved in URL params + threaded into report query + export payload. New `per-branch-financial-reports` E2E (3 pass) + 9 existing report specs still pass.

**Semantics:** specific branch shows only journals whose header branch resolves to it; null-branch (manual/closing) journals form an implicit "unassigned" bucket excluded from any selected branch (does NOT break per-branch balancing — every excluded journal is itself balanced). Σbranches ≠ company-wide when null-branch journals exist (documented, not a bug).

**Escalation trigger for full 2b:** the moment production books a real cross-branch journal (centralized HQ payment for another branch's bill, or cross-branch stock transfer posting to GL), header≠line and Option B breaks → switch to line-level filter + clearing engine (PR3/4/6 in `.sisyphus/plans/per-branch-financial-statements-2b.md`). Detection on current data = 0, so deferred.

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## ⚡ Quick Start for Next Session

User is switching to a new 0pencode session. Read this section first.

1. **Verify baseline**: `git status --short` → expect empty (or only `task.md`). `git log --oneline -1` on main → expect `cc5621b8` (or fresher). Latest main CI green. **No open PRs.**
2. **THIS SESSION — Pipeline/Approval dashboard branch-scoping initiative COMPLETE (3 PRs, Oracle-designed, all MERGED):**
   - **PR #38** (squash `888843a2`) — shared `BranchResolverRegistry` (app/Domain/Branch/). FQCN→strategy map (Direct | Warehouse | None), `resolve(Model): ?int`, `relationsFor()`, `branchBearingTypes()`, `isRegistered()`. Throws on unregistered types. Refactored `journals:backfill-branch` onto it (no behavior change). No-branch journal sources registered as None first-class.
   - **PR #39** (squash `d071e552`) — pipeline dashboard scoping. Denormalized `pipeline_entity_states.branch_id` (nullable FK + index). Populated on write in `AssignPipelineAction` (isRegistered guard → unregistered = null). Registered Asset (Direct). `pipeline-states:backfill-branch` command. Dashboard action+controller scoped via ResolvesBranchScope (EXCLUDE). Frontend branch selector.
   - **PR #40** (squash `cc5621b8`) — approval monitoring scoping. Denormalized `approval_requests.branch_id`. Populated on write in `TriggerApprovalAction`. Registered PurchaseRequest (Direct) + PurchaseOrder (Warehouse). `approval-requests:backfill-branch` command. Summary + overdue scoped (overdue via `whereHas('request')` since steps have no branch_id). Frontend branch selector.
   - **Semantics (your decision):** EXCLUDE uniform — a selected branch shows only rows that positively resolve to it; null-branch rows drop out. Structurally-unscopable types are visible only via the all-branches (null) view.
   - **CI gotchas hit + fixed (for next time):** (a) a unit test instantiated `new TriggerApprovalAction` — broke when the registry ctor dep was added; fix = `app(TriggerApprovalAction::class)`. Lesson: focused `--group` runs miss cross-group tests; run full `sail test` before pushing a constructor change. (b) approval-monitoring E2E used `getByRole('combobox').first()` for Status — adding a branch combobox BEFORE it stole `.first()`; fix = keep Status first. Pipeline E2E was safe (uses `#id` selectors).
   - **DEPLOYMENT NOTE:** both new columns are nullable + populated-on-write. Run the two backfill commands for historical rows: `sail artisan pipeline-states:backfill-branch` and `sail artisan approval-requests:backfill-branch` (both idempotent, support `--dry-run`).
3. **PREVIOUS — PR #37 (squash `81e97e84`):** manual journal branch attribution + financial-dashboard segment disclosure UI.
4. **PREVIOUS — financial-dashboard branch scoping (PR1-PR4) ALL MERGED:**
   - **PR #33** (squash `dfde121e`) — branch-scoping **PR1**: inert nullable `journal_entries.branch_id` FK (restrictOnDelete) + composite index `(fiscal_year_id, status, branch_id)` + model wiring (fillable, `branch()` relation, PHPDoc). Zero behavior change.
   - **PR #34** (squash `ce90750a`) — branch-scoping **PR2**: idempotent `journals:backfill-branch [--dry-run] [--chunk]` artisan command (app/Console/Commands/BackfillJournalEntryBranch.php). Per-source resolution keyed by `::class` (no morph map registered → source_type stores FQCNs). Direct branch: ApPayment/ArReceipt/CustomerInvoice/SupplierBill. Via warehouse->branch_id: GoodsReceipt/StockAdjustment/SupplierReturn. No-branch sources stay null. 6 tests.
   - **PR #35** (squash `7db69f39`) — branch-scoping **PR3**: write-path wiring. `CreateJournalEntryAction` reads optional `branch_id` from `$data` (captured before entry_number retry loop). 7 posting actions resolve+pass source branch (4 direct, 3 via `warehouse->branch_id` with `loadMissing('warehouse')`). BankReconciliation + ClosePeriod + ExecuteRecurringJournal stay null. **Manual JournalEntryController::store DEFERRED null** (avoids cross-branch authz hole; gate via ResolvesBranchScope when added). NO void/reversal paths exist. Oracle-reviewed GO. 6 tests incl. $fillable contract guard.
   - **PR #36** (squash `88e78d2c`) — branch-scoping **PR4** (read path, Option 3 segment reporting): `FinancialDashboardController` adopts `ResolvesBranchScope`; `GetFinancialDashboardDataAction` + `FinancialReportService` scope **income statement + monthly trends + comparison chain** by `?int $branchId` (P&L only). Balance sheet + cash flow stay COMPANY-WIDE (cash pooled at null-branch → leakage; same reason Option 3 rejected per-branch BS). Per-KPI `scope` tags + `branch_scope` disclosure summary. Additive segment model (specific branch excludes null-branch; null includes all). **ROOT-CAUSE FIX**: `Account::getTotalDebitAttribute/getTotalCreditAttribute` accessors SHADOWED the `total_debit`/`total_credit` SELECT aliases (also latently broke FY scoping, masked by single-FY tests) → rewrote `accountsWithPostedSums` as pre-grouped `leftJoinSub`, aliased to non-colliding `posted_debit_sum`/`posted_credit_sum`. 11 financial-dashboard tests + 42 financial report regression + 31 reports group all green.
   - **OPTIONAL FUTURE (none blocking):** per-branch cash flow (needs line-level branch tagging / treasury allocation); manual-entry branch attribution (PR3 deferral, gate via ResolvesBranchScope); Option 1 Head-Office branch if a true per-branch balance sheet is ever required; frontend two-section disclosure rendering using the per-KPI `scope` tags.
   - Latest audit PRs:
   - **PR #32** (squash `a97a4b67`) — Finding #6: CurrencyGuard on 4 AP/AR aging+outstanding report actions (last original finding)
   - **PR #31** (squash `f6bbcf82`) — Audit-refresh Findings #1-#3: BankReconciliation removeItem recalc + match/remove thinned + addItem refreshed parent
   - **Post-audit (no code):** Oracle design recorded for financial-dashboard branch scoping — see the "Financial dashboard branch scoping — Oracle design + the blocker" section.
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
| `/api/financial-dashboard` | ✅ Scoped (segment P&L; balance sheet + cash company-wide) |
| `/api/pipeline-dashboard/data` | ✅ Scoped (denormalized branch_id, EXCLUDE) + E2E branch-filter spec (#41) |
| `/api/approval-monitoring/data` | ✅ Scoped (denormalized branch_id, EXCLUDE) + E2E branch-filter spec (#41) |
| `/api/reports/balance-sheet` | ✅ Per-branch (header branch_id, per-branch CYE; reduced 2b #43/#44) |
| `/api/reports/trial-balance` | ✅ Per-branch (header branch_id; reduced 2b #43/#44) |
| `/api/reports/cash-flow` | ✅ Per-branch (header branch_id; reduced 2b #43/#44) |
| `/api/reports/income-statement` | ✅ Per-branch (header branch_id; #45) |
| `/api/reports/comparative` | ✅ Per-branch (header branch_id; #45) |

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
Read task.md first. Repo on `main` at HEAD `92e292e8` (or fresher), working
tree clean. FULL 2b is COMPLETE and operationally validated. The inter-branch
initiative (PRs #46-#54) plus a follow-up (#55: end-to-end smoke test +
`journals:detect-cross-branch` monitoring command) are all merged. The clearing
engine is dormant-but-correct: `journals:detect-cross-branch` reports 0
economically multi-branch journals on current data. No open PRs.

Quick verify:
  git rev-parse HEAD          # expect 92e292e8 or fresher
  git status --short          # expect empty (or only task.md)
  gh run list --branch main --limit 3   # verify latest is green
  gh pr list --base main --state open   # expect empty unless new work started
  sail artisan journals:detect-cross-branch   # gate for PR8: expect 0 multi-branch

If dev DB seems empty: `sail artisan db:seed`. Schema is intact.

NEXT ACTION needs USER DIRECTION (do NOT auto-pick). Full 2b is done. Remaining
options (all optional / future — none blocking):

1. PR8 (DEFERRED): audited retro-correction of historical economically-
   multi-branch journals. Only worth building if production detection of
   cross-branch journals becomes material (currently 0). Design staged in
   .sisyphus/plans/per-branch-financial-statements-2b.md.

2. H3 Wave 2 multi-currency FX subsystem (weeks) — pull when first non-IDR
   customer signs.

3. Product feature work (request specs from user).

KNOWN GOTCHAS (full 2b):
- InterBranchClearingService.inject is pure/integer-cents; it ONLY requires the
  1999-IBC account (or rejects null branch) when a branch actually has a nonzero
  net. Multi-branch entries where every branch self-balances => no-op (this is
  why per-asset depreciation across branches needs no clearing account).
- Reports + period closing read LINE branch_id (journal_entry_lines.branch_id),
  NOT header. Test fixtures that seed journals must tag line branch_id to match.
- Period closing (Option A) derives per-branch P&L directly from posted
  NON-closing lines; ClosePeriodAction + ReopenPeriodAction both delete the prior
  closing entry to prevent double-count on reclose.
- Adding a 2nd combobox to a line dialog breaks E2E locators that assume one;
  tests/e2e/journal-entries/helpers.ts uses `.first()` for the Account field.


KNOWN GOTCHAS (this session):
- BranchResolverRegistry throws on unregistered types — intentional (fail loud).
  Write paths guard with isRegistered() so unregistered = null (safe under
  EXCLUDE). When adding a new pipeline/approval entity type, register it in
  app/Domain/Branch/BranchResolverRegistry.php::STRATEGIES.
- Focused `--group` test runs miss cross-group tests. Run full `sail test`
  before pushing any constructor-signature change (DI breaks `new X` in tests).
- E2E specs using `getByRole('combobox').first()` are position-sensitive — don't
  prepend a new combobox before the one a spec targets.

KNOWN GOTCHA (learned in PR4): the Account model defines
getTotalDebitAttribute/getTotalCreditAttribute accessors that recompute
UNFILTERED sums. Eloquent accessors SHADOW query-aliased columns of the same
name. Never alias a withSum/selectSub column `total_debit`/`total_credit` on the
Account model — use a distinct name (we use posted_debit_sum/posted_credit_sum).

KNOWN GOTCHA (reduced 2b, PR #43): per-branch balance sheet ONLY balances if the
synthetic 9999-CYE row is computed PER BRANCH (`calculateNetIncome(branchId)`).
Filtering A/L/E by branch while leaving CYE company-wide guarantees imbalance.
Guarded by PerBranchFinancialReportTest.

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
