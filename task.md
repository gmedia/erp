# AI Handoff: GlExtendedSampleDataSeeder Hardened (Idempotent + Atomic)

Last updated: 2026-05-25 03:41 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current State

- Branch: `main`
- HEAD: `aaa4c147`
- Working tree: clean
- Remote: pushed and up-to-date
- Seeder is now idempotent and atomic (transaction-wrapped)

## Current Objective

GlExtendedSampleDataSeeder hardening complete:

- ✅ All inserts moved to `updateOrCreate` keyed by natural unique
      constraints (matching migration indexes)
- ✅ Child rows (recurring journal lines, bank reconciliation items)
      delete-then-recreate for sync with parent payload across reruns
- ✅ Whole seed wrapped in `DB::transaction` for atomic rollback +
      bounded lock contention
- ✅ Two-run idempotency verified (counts identical: 4/2/3/12)
- ✅ Backward compat preserved for 30/30 Pest financial tests
- ✅ Full E2E (financial reports + bank reconciliation) still EXIT=0

## Session Summary (2026-05-25 03:41 UTC)

1 commit shipped after the previous handoff (`38527d01`):

| Commit | Description |
|--------|-------------|
| `aaa4c147` | fix(seeder): make GlExtendedSampleDataSeeder idempotent and atomic |

### Why this matters

During the parallel E2E delegation earlier today, multiple sub-agents
hit transient deadlocks running this seeder concurrently. Inspection
showed:

- The seeder used `factory()->create()` with no idempotency keys
- A second run crashes on unique constraints in `period_closings`,
  `account_balances`, and `bank_reconciliations`
- No transaction boundary, so each insert held its own lock
  separately, increasing the deadlock surface

### Refactor details

Per-section unique-key mapping (taken from migrations):

| Table | Unique constraint |
|-------|-------------------|
| `recurring_journals` | `(name, fiscal_year_id)` (no DB constraint, but functional ID) |
| `bank_reconciliations` | `(account_id, period_start, period_end)` |
| `period_closings` | `(fiscal_year_id, period_month, period_year, closing_type)` |
| `account_balances` | `(account_id, fiscal_year_id, period_month, period_year)` |

Each section now uses `updateOrCreate` with the natural key as the
match attributes and the rest as values. Whole `run()` body wrapped
in `DB::transaction(function () use (...) { ... })`.

### Validated

- Pest: 30/30 financial reports (153 assertions, ~68s)
- Playwright E2E: bank-reconciliation (13) + 6 financial reports (20)
  full suite passes (EXIT=0)
- Two consecutive `db:seed --class=GlExtendedSampleDataSeeder` runs
  succeed with identical row counts
- Duster + PHPStan clean

## Known Issues / Limitations

1. **E2E parallel delegation collides on shared MariaDB** (unchanged)
   - Concurrent `migrate:fresh` from multiple test runs deadlocks
   - Mitigation still: run all E2E in a single sequential session, or
     shard databases per worker
   - The seeder hardening reduces the lock surface within a single
     run but does NOT fix concurrent `migrate:fresh` collisions

## Recommended Next Steps

1. **CI pipeline integration** (~varies)
   - Run Pest + Playwright via build-hygiene-aware global-setup
   - PLAYWRIGHT_USE_SAIL=1 for Sail-only runners
   - Cache vendor + node_modules; upload Playwright report artifact
   - Now safer to add since seeder is hardened

2. **Auto-select first/open fiscal year in `ReportDataTablePage`**
   (~1 hr, optional polish)
   - Currently the trial-balance-detailed page renders empty until
     the user opens Filters and picks a fiscal year
   - The financial shells auto-resolve fiscal year via
     `resolveFiscalYearContext`; this datatable shell does not
   - UX nicety; not blocking any feature

3. **Investigate parallel-safe migrate:fresh** (optional)
   - The current bottleneck for parallel E2E is the shared `testing`
     DB. Consider sharding (one DB per worker via PHPUnit
     parallel-process strategy or Pest's parallel mode)
   - Lower priority; sequential runs work fine

## Continuation Prompt

```
Read task.md. Repo on main at aaa4c147, clean and pushed.

Last micro-session (2026-05-25 03:41 UTC) shipped:
- aaa4c147: fix(seeder): GlExtendedSampleDataSeeder is now idempotent
  via updateOrCreate keyed by migration unique constraints, and atomic
  via DB::transaction wrap. Two consecutive runs produce identical
  row counts. Reduces deadlock surface during parallel test runs.

All coverage layers green:
- Pest 30/30 financial reports (153 assertions)
- Playwright 20/20 financial reports + 13/13 bank reconciliation
- TS / PHPStan / Duster clean

Next priority options:
1. CI pipeline integration (Pest + Playwright via build-hygiene
   global-setup, PLAYWRIGHT_USE_SAIL=1)
2. Auto-select fiscal year in ReportDataTablePage (UX polish)
3. Parallel-safe migrate:fresh investigation (lower priority)

Recommend #1 next — the test/seeder ground is now stable enough to
codify into CI without flake risk.
```

Read task.md. Repo on main at c7c4f966, clean and pushed.

Last session (2026-05-24) shipped 6 commits:
- Bank Reconciliation full feature: import (CSV/Excel + column mapping), 3-priority auto-match, manual match/unmatch, account assignment for unmatched items, journal posting on complete, workspace UI with live balance updates and Complete button. 32 Pest tests passing (109 assertions). 6 E2E workflow tests added (not yet run).
- Trial Balance Detailed: ReportDataTablePage pattern, /reports/trial-balance-detailed wired.
- Comparative Report: verified already complete (no work needed).

Migration `add_journal_columns_to_bank_reconciliations` already ran. Models have `journal_entry_id` and `account_id` columns.

Known issue: E2E global-setup expects local php binary, fails on Sail-only hosts.

Next priority options:
1. Fix E2E global-setup to be Sail-aware (~30 min, unblocks all E2E)
2. E2E tests for 6 financial reports (~2-3 hr)
3. Run existing bank-reconciliation E2E after #1 (~10 min)
4. Financial reports export family — 5 reports need export endpoints (~4+ hr)

Recommend #1 first to unblock E2E verification, then either #2 or #4.
```
