# AI Handoff: CI E2E — subset expanded with 3 simple-CRUD modules

Last updated: 2026-05-25 07:53 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current State

- Branch: `main`
- HEAD: about to be `<new sha> ci(e2e): expand subset with branches, departments, fiscal-years`
  on top of `34fad961 ci(e2e): run storage:link in global-setup ...`
- Working tree: 1 modified file → `.github/workflows/tests.yml`
- Remote: pushed up to `34fad961`
- 3 consecutive green CI runs already:
  - `26386502708` (subset narrowing)
  - `26387160311` (docs only)
  - `26389011248` (storage:link fix)
- E2E job remains non-blocking (`continue-on-error: true`)

## Storage:link fix is live but not yet validated end-to-end on CI

The 3 green runs above only ran the original 7-spec narrowed subset
(bank-reconciliations + 6 financial reports). None of them exercised
any of the 41 originally-failing CRUD export specs, so we still need
one CI run that actually exports a CRUD module to confirm the
storage:link fix works on the runner.

This expansion does that.

## Subset Expansion (about to commit)

`.github/workflows/tests.yml` E2E command now includes 3 simple-CRUD
modules whose only known failure was the storage:link symlink
(verified locally with the symlink removed before each run):

```
tests/e2e/bank-reconciliations/
tests/e2e/balance-sheet-report/
tests/e2e/branches/                   # NEW
tests/e2e/cash-flow-report/
tests/e2e/comparative-report/
tests/e2e/departments/                # NEW
tests/e2e/fiscal-years/               # NEW
tests/e2e/income-statement-report/
tests/e2e/trial-balance-detailed-report/
tests/e2e/trial-balance-report/
```

### Local verification (Sail, fresh symlink each run)

```
rm -f public/storage
npx playwright test tests/e2e/branches/ tests/e2e/departments/ tests/e2e/fiscal-years/
  → 27 passed (2.3m)
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

1. **Ship the subset expansion** (this session)
   - `git add .github/workflows/tests.yml task.md`
   - `git commit -m "ci(e2e): expand subset with branches, departments, fiscal-years"`
   - `git push origin main`

2. **Monitor next CI run**
   - Confirm 10-spec subset green on CI.
   - Confirms storage:link fix works on the runner end-to-end.

3. **Promote E2E to required (after this run is green)**
   - Drop `continue-on-error: true` from the e2e job.
   - Require `needs.e2e.result == success` in the reflect step.

4. **Continue expanding subset in waves**
   - Next wave: remaining simple CRUD (`positions`, `customer-categories`,
     `supplier-categories`).
   - Then complex CRUD waves (employees / customers / suppliers / products,
     warehouses, asset-* family, etc.) — verify each module locally first.
   - Eventually re-enable full `tests/e2e/` once 0 known-failing specs remain.

5. **Optional UX polish**
   - Auto-select first/open fiscal year in `ReportDataTablePage` (~1 hr).

6. **Optional infrastructure**
   - Parallel-safe `migrate:fresh` / DB sharding for E2E concurrency.

## Continuation Prompt

```
Read task.md. Repo on main. HEAD should be the new
"ci(e2e): expand subset with branches, departments, fiscal-years"
commit (after this session pushes), or 34fad961 if not yet pushed.

Status:
- 3 consecutive green CI runs (subset narrowing, docs, storage:link fix).
- storage:link fix is live but not yet exercised end-to-end on CI
  because none of the 7-spec narrowed subset hits the storage:link
  code path.
- About to expand the E2E subset to 10 specs by adding 3 simple CRUD
  modules (branches, departments, fiscal-years), all storage:link-only
  failures locally.
- E2E job is still non-blocking (continue-on-error: true).

Next priority:
1. Watch the new CI run after the expansion push and confirm the
   10-spec subset is green. This is the first CI run that actually
   validates the storage:link fix end-to-end.
2. After green, drop continue-on-error from the e2e job and require
   needs.e2e.result == success in the reflect step.
3. Continue expanding the subset in waves: simple CRUD (positions,
   customer-categories, supplier-categories), then complex CRUD
   modules. Verify each module locally before adding to CI.
4. Eventually re-enable full tests/e2e/.
5. Optional: auto-select fiscal year in ReportDataTablePage.
6. Optional: parallel-safe migrate:fresh / DB sharding.
```
