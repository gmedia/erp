# AI Handoff: CI E2E Job Wired (Rollout in Progress)

Last updated: 2026-05-25 03:56 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current State

- Branch: `main`
- HEAD: `981f95f6`
- Working tree: clean
- Remote: pushed
- CI run `26382358214` in progress for the new e2e job

## Current Objective

Add Playwright E2E job to CI:

- ✅ New `e2e` job in `.github/workflows/tests.yml`
- ✅ Reuses CI Sail image, runs migrate:fresh + db:seed via the
      Sail-aware global-setup
- ✅ Browser deps cached on `~/.cache/ms-playwright`
- ✅ Uploads `e2e/playwright-report` + Laravel logs on failure
- ✅ `continue-on-error: true` for the initial rollout (red E2E does
      not block merge)
- ⏳ First CI run on main is in progress (`26382358214`)

## Session Summary (2026-05-25 03:56 UTC)

1 commit shipped after the previous handoff (`7e3a5b53`):

| Commit | Description |
|--------|-------------|
| `981f95f6` | ci: add Playwright E2E job to CI workflow |

### Job structure

```
quality (existing) -> Duster + lint + types + PHPStan + Pest+coverage
                           |
                           v
e2e (new, continue-on-error)
  - Setup PHP + Node 20
  - composer install on runner (for Sail bin)
  - Pull CI Sail image, sail up -d --wait
  - sail npm ci (Sail-side build deps)
  - npm ci on runner (for npx playwright)
  - Generate app key, configure MinIO
  - Cache + install Playwright chromium
  - npx playwright test --reporter=line
  - Upload playwright-report on failure
  - sail down -v
                           |
                           v
ci reflect: requires quality success; e2e is informational
```

### Why continue-on-error for the initial rollout

The local E2E pass gives high confidence, but CI runners differ in:
- Available memory (browser install can OOM on tight runners)
- DNS / port-forwarding behavior for `localhost:82` -> sail container
- Cache hit rates for the Playwright browser binary

If the first 2–3 runs are green, flip the job to required by removing
`continue-on-error` and adding `needs.e2e.result == success` back to
the reflect step.

## Validated (local only so far)

- YAML schema valid
- Local Pest 30/30 pass (153 assertions)
- Local Playwright 33/33 pass (financial reports + bank reconciliation)
- Sail-aware global-setup + build hygiene already shipped

## Known Issues / Limitations

1. **First CI run is the verification — outcome unknown until it
   completes**
   - Expected: e2e job pulls CI image, brings up Sail, installs
     browsers (~150MB), runs Playwright (~70-90s once Sail is up)
   - Total e2e job runtime estimate: ~8–12 minutes
   - Failure modes to look for in the run log:
     - `vendor/bin/sail up` timeout on `--wait`
     - `npm ci` on runner conflicts with Sail-side install
     - Playwright connection refused on localhost:82
     - global-setup `migrate:fresh` deadlock under CI MariaDB

2. **E2E parallel delegation collides on shared MariaDB** (unchanged)
   - Mitigation still: sequential runs only

## Recommended Next Steps

1. **Watch CI run `26382358214` to completion**
   - View: `gh run view 26382358214` or
     `gh run watch 26382358214`
   - If green: flip `continue-on-error: true` off and require
     `needs.e2e.result == success` in the reflect step
   - If red: inspect failure, iterate on the workflow

2. **Auto-select first/open fiscal year in `ReportDataTablePage`**
   (~1 hr, optional polish)
   - The trial-balance-detailed page renders empty until the user
     opens Filters and picks a fiscal year
   - Financial shells auto-resolve fiscal year via
     `resolveFiscalYearContext`; this datatable shell does not
   - UX nicety; not blocking any feature

3. **Parallel-safe migrate:fresh investigation** (optional)
   - DB sharding per Pest worker would speed up the test suite
   - Lower priority; sequential runs still meet the CI budget

## Continuation Prompt

```
Read task.md. Repo on main at 981f95f6, clean and pushed.

Latest commit (2026-05-25 03:56 UTC):
- 981f95f6: ci: add Playwright E2E job to CI workflow.
  e2e job runs after quality, uses CI Sail image, caches Playwright
  chromium, uploads report+logs on failure. continue-on-error: true
  for initial rollout so red E2E does not block merge.

CI run 26382358214 should be the first one with the new job.

Next priority options:
1. Watch CI run to completion. If green for a few runs, flip
   continue-on-error off and require e2e in the ci reflect step.
2. Auto-select fiscal year in ReportDataTablePage (UX polish).
3. Parallel-safe migrate:fresh (DB sharding) — optional speed-up.

Recommend #1 — verify the rollout before adding more scope.
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
