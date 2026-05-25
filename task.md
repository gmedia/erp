# AI Handoff: CI E2E — subset expanded (branches + departments)

Last updated: 2026-05-25 08:28 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current State

- Branch: `main`
- HEAD: about to be `<new sha>` on top of
  `4080dec7 ci(e2e): expand subset with branches, departments, fiscal-years`
  (which produced CI run `26389923397` E2E failure — 2 report exports
  poisoned by fiscal-years CRUD test data churn)
- Working tree: 1 modified file → `.github/workflows/tests.yml`
  (rolling back `fiscal-years/` from the subset)
- Remote: pushed up to `4080dec7`
- 3 prior consecutive green CI runs:
  - `26386502708` (subset narrowing)
  - `26387160311` (docs only)
  - `26389011248` (storage:link fix)
- 1 red E2E run from over-aggressive expansion:
  - `26389923397` (added fiscal-years; broke report fixtures)
- E2E job remains non-blocking (`continue-on-error: true`)

## storage:link fix is now validated end-to-end on CI

CI run `26389923397` E2E job actually exercised the storage:link
code path through `branches/` and `departments/` export specs. Both
PASSED, confirming the fix works on the runner. The 2 unrelated
failures came from `fiscal-years/` data churn poisoning report
fixtures.

## Subset Expansion (about to commit)

`.github/workflows/tests.yml` E2E command now includes 2 simple-CRUD
modules whose only known failure was the storage:link symlink:

```
tests/e2e/bank-reconciliations/
tests/e2e/balance-sheet-report/
tests/e2e/branches/                   # NEW
tests/e2e/cash-flow-report/
tests/e2e/comparative-report/
tests/e2e/departments/                # NEW
tests/e2e/income-statement-report/
tests/e2e/trial-balance-detailed-report/
tests/e2e/trial-balance-report/
```

### Why fiscal-years was rolled back

CI run `26389923397` (which included `fiscal-years/`) failed with 2
specs:
- `income-statement-report can export income statement report`
  → `/api/reports/income-statement/export` 500 + `Export failed: x`
- `trial-balance-detailed-report can export ...`
  → Export button stayed `disabled` (no data loaded)

Root cause: `fiscal-years` E2E creates and **deletes** fiscal years.
The seeded report fixtures depend on those fiscal years existing.
After the fiscal-years suite runs, the report endpoints 500 because
the year referenced by report fixtures is gone.

`branches` and `departments` have no such dependency. Locally the
9-spec subset (no fiscal-years) passes 51/51 in 3.1 minutes with the
storage:link removed before the run.

### Local verification (Sail, fresh symlink)

```
rm -f public/storage
npx playwright test \
  tests/e2e/bank-reconciliations/ tests/e2e/balance-sheet-report/ \
  tests/e2e/branches/ tests/e2e/cash-flow-report/ \
  tests/e2e/comparative-report/ tests/e2e/departments/ \
  tests/e2e/income-statement-report/ \
  tests/e2e/trial-balance-detailed-report/ \
  tests/e2e/trial-balance-report/
  → 51 passed (3.1m)
```

## Current Objective

Land Playwright E2E into CI as a stable, known-green subset before
opening the gate to the full suite.

- ✅ E2E job runs on CI (no longer skipped by autofix guard).
- ✅ Quality + Test suite jobs green on CI.
- ✅ E2E command narrowed to known-green subset (this session).
- ✅ Subset E2E green on CI run `26386502708` (1st green run).
- ⏳ A few more green runs before flipping `continue-on-error`.
- ⏳ Drop `continue-on-error: true` and require
  `needs.e2e.result == success` once stable.
- ⏳ Triage and re-add the rest of `tests/e2e/**` in waves.

## Session Summary (2026-05-25 06:18 UTC)

After last handoff at HEAD `9470e1f3`, this session shipped one
commit and is about to ship a second:

| Commit | Description |
|--------|-------------|
| `25c307cc` | fix(static): silence `App\Models\Eloquent` class.notFound in PHPStan via `phpstan.neon` ignore |
| _(staged)_ | ci: narrow Playwright E2E command to known-green report + bank-reconciliation specs |

### What changed in this session

1. **PHPStan stability under Duster**

   - Source-level `use Eloquent;` import approach was rejected by
     CI Pint autofix (`9470e1f3` removed the unused-by-runtime imports).
   - Moved the fix to `phpstan.neon`:
     - `reportUnmatchedIgnoredErrors: false`
     - scoped `ignoreErrors` for `unknown class App\\Models\\Eloquent`
   - Verified clean locally:
     - `rtk ./vendor/bin/sail bin phpstan analyze` → `[OK] No errors`
     - `rtk ./vendor/bin/sail bin duster fix app/Models/JournalEntry.php
       app/Models/ApprovalAuditLog.php app/Models/ApprovalRequest.php
       app/Models/PipelineEntityState.php app/Models/PipelineStateLog.php`
       → `PASS ... 5 files`
     - PHPStan rerun after Duster → `[OK] No errors`

2. **CI run observed**

   - Run `26384727117`: overall `success`, quality `success`,
     test suite `success`, e2e `failure` (`continue-on-error: true`).
   - E2E failure mode: `41 failed`, mostly CRUD export specs with
     `Console Error text: "Export failed: x"`. Pre-existing failures,
     unrelated to today's bank-reconciliation / financial-report work.

3. **Workflow narrowing (about to commit)**

   - Replace single `npx playwright test --reporter=line` line with
     an explicit subset:
     - `tests/e2e/bank-reconciliations/`
     - `tests/e2e/balance-sheet-report/`
     - `tests/e2e/cash-flow-report/`
     - `tests/e2e/comparative-report/`
     - `tests/e2e/income-statement-report/`
     - `tests/e2e/trial-balance-detailed-report/`
     - `tests/e2e/trial-balance-report/`
   - YAML validated:
     - `rtk python3 -c "import yaml; yaml.safe_load(open('.github/workflows/tests.yml')); print('YAML OK')"`
       → `YAML OK`

### Validated commands and outcomes

- `rtk git status --short` → ` M .github/workflows/tests.yml`
- `rtk git log -1 --oneline` → `25c307cc fix(static): ...`
- `rtk git diff -- .github/workflows/tests.yml` → +10 / −1, only the
  E2E `run:` block changed.

## Recommended Next Steps

1. **Ship the corrected subset** (this session)
   - `git add .github/workflows/tests.yml task.md`
   - `git commit -m "ci(e2e): roll back fiscal-years from subset; data churn breaks report fixtures"`
   - `git push origin main`

2. **Monitor next CI run**
   - Confirm 9-spec subset green on CI.
   - Locks in storage:link fix validation + branches/departments expansion.

3. **Promote E2E to required (after this run is green)**
   - Drop `continue-on-error: true` from the e2e job.
   - Require `needs.e2e.result == success` in the reflect step.

4. **Continue expanding subset in waves (data-isolated modules first)**
   - Safe candidates: `positions`, `customer-categories`,
     `supplier-categories` (no shared report fixtures).
   - Risky candidates that need investigation first: anything that
     creates/deletes parent records used by financial report seeds
     (`fiscal-years`, `coa-versions`, `account-mappings`, etc.).

5. **Fix fiscal-years data isolation (separate task)**
   - Option A: report fixtures auto-create the year they need
     (more robust, fixes any future fiscal-years churn).
   - Option B: spec-local cleanup so fiscal-years tests restore the
     seed year on teardown.
   - Option A preferred — eliminates ordering coupling.

6. **Optional UX polish**
   - Auto-select first/open fiscal year in `ReportDataTablePage` (~1 hr).

7. **Optional infrastructure**
   - Parallel-safe `migrate:fresh` / DB sharding for E2E concurrency.

## Continuation Prompt

```
Read task.md. Repo on main. HEAD should be the new
"ci(e2e): roll back fiscal-years from subset ..." commit (after
this session pushes), or 4080dec7 if not yet pushed.

Status:
- 3 prior green CI runs + 1 red expansion run (26389923397) that
  proved the storage:link fix works on the runner via
  branches/departments export specs.
- The red run also revealed that fiscal-years CRUD tests pollute
  shared report fixtures, breaking income-statement and
  trial-balance-detailed export specs.
- E2E subset rolled back to 9 specs (bank-reconciliations + 6
  financial reports + branches + departments). Locally 51/51 pass.
- E2E job is still non-blocking (continue-on-error: true).

Next priority:
1. Watch the next CI run after the rollback push and confirm the
   9-spec subset is green.
2. After green, drop continue-on-error from the e2e job and require
   needs.e2e.result == success in the reflect step.
3. Continue expanding the subset using data-isolated modules first
   (positions, customer-categories, supplier-categories). Avoid any
   module that creates/deletes records used by financial report
   fixtures until that coupling is broken.
4. Fix fiscal-years data isolation as a separate task — preferred
   approach: report fixtures auto-create their dependent year.
5. Eventually re-enable full tests/e2e/.
6. Optional: auto-select fiscal year in ReportDataTablePage.
7. Optional: parallel-safe migrate:fresh / DB sharding.
```
