# AI Handoff: ERP Active State

Last updated: 2026-06-15 (3 Oracle PRs merged + Depwire scan no-findings + handoff finalized) UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## ⚡ Quick Start for Next Session

User is switching to a new 0pencode session. Read this section first.

1. **Verify baseline**: `git status --short` → expect empty. `git log --oneline -1` → expect a `docs(handoff)` commit (this refresh) or a fresher one.
2. **3 Oracle audit findings SHIPPED & MERGED** (this session's work):
   - PR #21 (merge `87ddea11`) — Finding #3: BankReconciliation thinning + DB::transaction race fix
   - PR #20 (merge `07d37688`) — Finding #1: AR/AP aging Carbon port + M3 timezone
   - PR #19 (merge `8c076305`) — Finding #4: `resolveBranchFromRequest` trait extraction
3. **Earlier session work** (still relevant):
   - H3 multi-currency Wave 0+1 SHIPPED (#16, #17)
   - H3 polish quick wins SHIPPED (#18)
   - Branch tenant isolation SHIPPED (`5f2cb816`)
   - Budget Management module SHIPPED (`f0c8e3c0`)
4. **If user says "lanjutkan" without direction**: ASK which path. Do NOT pick autonomously.

### Dev environment state (verified end of last session)

- Admin employee has `view_all_branches` permission attached (215 total perms).
- All 6 transactional currency tables: 0 non-IDR rows.
- Setting `currency` = `IDR`.
- DB sample data fully seeded.
- DB connection: `mariadb` host, database `laravel`.

If dev DB seems empty after pulling: `sail artisan db:seed`. Schema is intact.

### Status — Oracle post-H3 audit fully closed for current schema

| Finding | Severity | PR | Effort | What landed |
|---|---|---|---|---|
| #4 | LOW | #19 | 45 min | `resolveBranchFromRequest(Request)` on `ResolvesBranchScope` trait, eliminating 3× boilerplate in `AgingDashboardController`, `AssetDashboardController`, `StockMonitorController` |
| #1 | HIGH | #20 | ~2h | `AgingReportBoundaries` trait with parameterized Carbon date math; ports 4 legacy actions away from MariaDB-only `CURDATE()`/`DATEDIFF()`; folds in M3 timezone closure |
| #3 | MEDIUM | #21 | ~1.5h | 5 new actions + 2 new FormRequests; `BankReconciliationController` adopts `LoadsResourceRelations`; `update()` race window closed via `DB::transaction` |

### Findings remaining (all DEFERRED — schema work)

| # | Item | Severity | Effort | Notes |
|---|---|---|---|---|
| Financial dashboard branch scoping | DEFERRED | 3-5d | HIGH | Needs `branch_id` on `journal_entries` table (schema change). Also requires `currency` col when Wave 2 lands. |
| Pipeline/Approval dashboard branch scoping | DEFERRED | TBD | MEDIUM | Polymorphic resolution. Finding #4 (PR #19) unblocked the trait helper. |
| H3 Wave 2 (multi-currency FX subsystem) | DEFERRED | weeks | n/a | Pull only when first non-IDR customer signs. See archived plan in `task.handoff-archive.md`. |

### Current State

- Branch: `main`
- HEAD: `2f549470` (`docs(handoff): record Oracle audit findings #1, #3, #4 ship`)
- Working tree: clean
- CI on `main`: latest run `27531047741` (HEAD `2f549470`) was `in_progress` at session close. 3 prior runs (PR #19/#20/#21 merges) all `cancelled` by GitHub workflow concurrency — NOT failures, just newer commits superseded them. Verify with `gh run list --branch main --limit 3` next session.
- Sonar Quality Gate: expect OK (no logic changes that move duplication; new traits add abstraction). Verify after CI green.
- Pest: 33 (bank-reconciliations) + 5 (ar-aging) + 5 (ap-aging) + 5 (ar-outstanding) + 5 (ap-outstanding) + 15 (aging-dashboard regression) + 5 (customer-statement regression) + dashboard/asset-dashboard/stock-monitor groups all green per-group during ship. Full suite 1864+ pass on prior baseline.
- Module registry: 80 entries.
- Permission seeded: admin emp has `view_all_branches`.

### Post-merge investigation (no quick wins found)

After 3 PRs merged, attempted to find more quick-win refactors via Depwire + Sonar:

- **Depwire health score** = 56/F. ALL findings false positive for Laravel:
  - 8,415 dead symbols dominated by `_ide_helper.php` (gitignored, IDE-only).
  - 546 orphan files = Laravel auto-discovery (migrations, factories, seeders, configs, abstract base classes, sibling components loaded via `createEntityCrudPage(config)` factory pattern).
  - 93 god files = Laravel models with relations; normal.
  - Cohesion 20/F = Laravel structure (controllers/models/etc by concern) inherently low cohesion per dir.
- **Sonar MCP** = blocked (`organization` parameter missing in MCP config; not a code issue).

**Conclusion**: Depwire/Sonar produced no actionable refactor for a Laravel codebase. Skip these tools next session unless config improves.

### Recent Commits On Main

| Commit | Subject |
|---|---|
| `2f549470` (HEAD) | docs(handoff): record Oracle audit findings #1, #3, #4 ship |
| `8c076305` | Merge pull request #19 — Finding #4 |
| `07d37688` | Merge pull request #20 — Finding #1 |
| `87ddea11` | Merge pull request #21 — Finding #3 |
| `d7fa58d9` | docs(handoff): drop self-referential hash from task.md |
| `6ec6b7aa` | docs(handoff): refresh task.md for new session continuation |
| `f8cbe83c` | docs(handoff): record quick wins ship + PR #18 merge + Oracle audit findings |
| `96879e3d` | Merge pull request #18 (H3 polish quick wins) |
| `4df36b76` | Merge pull request #17 (H3 Wave 1) |
| `6b78c29c` | Merge pull request #16 (H3 Wave 0) |

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
Read task.md first. Repo on `main`, working tree clean. Latest 3 Oracle audit
PRs (#19, #20, #21) all merged in the previous session.

Quick verify:
  git rev-parse HEAD          # expect 2f549470 or fresher
  git status --short          # expect empty
  gh run list --branch main --limit 3   # last run was in_progress at handoff;
                                          # verify it landed green

If dev DB seems empty: `sail artisan db:seed`. Schema is intact.

PREVIOUS SESSION ALSO RAN Depwire + Sonar scans on main post-merge — found
NO actionable quick-win refactors. Findings were Laravel false positives
(_ide_helper, auto-discovery orphans). Don't re-run those tools without
config improvement.

ALL REMAINING OPEN ITEMS REQUIRE EITHER SCHEMA CHANGES OR USER DIRECTION.

Next action needs USER DIRECTION (do NOT auto-pick):

1. Financial dashboard branch scoping (HIGH, 3-5d schema change)
   — Needs branch_id on journal_entries table.
   — Coordinate with H3 Wave 2 currency col addition.

2. Pipeline/Approval polymorphic dashboard scoping (MEDIUM, TBD)
   — Finding #4 (PR #19) shipped the trait helper that unblocks this.
   — Polymorphic resolution still needed.

3. H3 Wave 2 (multi-currency FX subsystem, weeks)
   — Only pull when first non-IDR customer signs.

4. Refresh Oracle audit (request a fresh pass)
   — All current findings are closed or deferred for schema reasons.

5. Other product feature work (request from user)

CONVENTIONS REMINDER:
- Never commit without explicit user request (AGENTS.md §3)
- Never auto-merge PR without explicit instruction
- Use Sail for all runtime commands
- Use feature branches for all work; PR via gh
- Match Sonar QG (duplication < 3%, coverage stays at 100% on new code)

If user says "lanjutkan" without direction, ASK which path.
```
