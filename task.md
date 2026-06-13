# AI Handoff: ERP Active State

Last updated: 2026-06-13 15:30 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## ⚡ Quick Start for Next Session

User is switching to a new opencode session. Read this section first.

1. **Verify baseline**: `git rev-parse HEAD` → expect `5f2cb816`. `git status --short` → expect empty.
2. **Branch tenant isolation SHIPPED** (`5f2cb816`): 4 dashboard endpoints scoped by user branch. New `ResolvesBranchScope` trait + `view_all_branches` permission. 8 isolation tests pass. Full suite 1852 tests green.
3. **Budget Management module FULLY SHIPPED** (`f0c8e3c0`): 39 files, full-stack.
4. **CI fully green** on run `27455288134` (pre-branch-isolation). Push just done — await new CI run.
5. **If user says "lanjutkan" without direction**: ASK which next option. Do NOT pick autonomously.

### Recommended next-session options (need user input)

1. **Multi-currency cross-cutting fix** (Oracle H3): same blind spot in aging/AR/AP/budget reports
2. **Timezone drift** (Oracle M3)
3. **Financial dashboard branch scoping** (DEFERRED): requires `branch_id` on `journal_entries` table (schema change, 3-5 day lift)
4. **Pipeline/Approval dashboard branch scoping** (DEFERRED): polymorphic resolution needed

## Current State

- Branch: `main`
- HEAD: `5f2cb816`
- Working tree: clean (all changes pushed)
- CI: awaiting new run (push just done)
- Sonar Quality Gate: scan now runs (403 JRE-download fix landed)
- Module registry: 80 entries (Budget Management added)
- Branch isolation: 4 dashboards scoped, 8 new tests, 1852 total tests green

## This Session's Commits (1 total)

| Commit | Subject |
|---|---|
| `5f2cb816` | fix(security): enforce branch tenant isolation on 4 dashboard endpoints |

## Route Permission Audit — COMPLETE

Comprehensive sweep of all 62 `routes/api/*.php` files. **8 gaps closed** across 2 commits:

### Commit `70c6c0db` (dashboards)
| Route | Permission | Risk |
|---|---|---|
| `/api/aging-dashboard` | `aging_dashboard` | HIGH |
| `/api/financial-dashboard` | `financial_dashboard` | HIGH |
| `/api/approval-monitoring/data` | `approval_monitoring` | HIGH |

### Commit `34027524` (modules + reports)
| Route | Permission | Risk |
|---|---|---|
| `bank-reconciliations.php` | `bank_reconciliation` | HIGH |
| `recurring-journals.php` | `recurring_journal` | HIGH |
| `general-ledger-report.php` | `general_ledger_report` | HIGH |
| `trial-balance-report.php` | `trial_balance_report` | HIGH |
| `report-configurations.php` | `report_configuration` | MEDIUM |

### Verified OK (no action)
- 50+ CRUD route files (group-level permission middleware)
- `reports.php` (per-route permission middleware)
- `asset-dashboard/data`, `pipeline-dashboard/data`, `stock-monitor`

### Intentionally ungated
- `/api/dashboard` — aggregate counts, low sensitivity, no permission key
- `/api/my-approvals` — user-scoped by controller logic
- `/api/user-guide` — static documentation

## Design Docs Available

| Doc | Status | Key Finding |
|-----|--------|-------------|
| `docs/profit-loss-by-department-design.md` | Research complete, DEFER | journal_entry_lines lacks dimension columns; 5-7 day lift |
| `docs/budget-management-design.md` | Ready for implementation | 3-4 day lift; schema + variance + API + frontend; 5 decisions pending |

## Branch Isolation — Scoping Policy

| User Type | Behavior |
|---|---|
| Has `view_all_branches` permission | Honor requested `branch_id` (null = all) |
| Employee with `branch_id` set | Forced to own branch (request ignored) |
| Employee with `branch_id` null | Unscoped (backward compat, legacy admin) |

### Scoped Endpoints

| Endpoint | Controller | Status |
|---|---|---|
| `/api/dashboard` | `DashboardController` | ✅ Scoped |
| `/api/aging-dashboard` | `AgingDashboardController` | ✅ Scoped |
| `/api/asset-dashboard/data` | `AssetDashboardController` | ✅ Scoped |
| `/api/stock-monitor` | `StockMonitorController` | ✅ Scoped |
| `/api/financial-dashboard` | `FinancialDashboardController` | ⏳ DEFERRED (journal_entries lacks branch_id) |
| `/api/pipeline-dashboard/data` | `PipelineDashboardController` | ⏳ DEFERRED (polymorphic) |
| `/api/approval-monitoring/data` | `ApprovalMonitoringController` | ⏳ DEFERRED (polymorphic) |

## Verification State

| Gate | Result | When |
|------|--------|------|
| PHPStan | `[OK] No errors` (1066 files) | 2026-06-13 |
| Duster | clean | 2026-06-13 |
| Pest full suite | 1852 passed, 8302 assertions | 2026-06-13 |
| Pest `branch-isolation` group | 8 passed, 19 assertions | 2026-06-13 |

## Useful Commands

```bash
# Run focused tests
sail test --group bank-reconciliations
sail test --group recurring-journals
sail test --group general-ledger-report
sail test --group trial-balance-report
sail test --group financial-reports

# All quality gates
sail bin phpstan analyze
sail bin duster fix
npm run types
sail npm run lint

# Activate new permissions in dev DB
sail artisan db:seed --class=MenuSeeder
sail artisan db:seed --class=PermissionSeeder

# Monitor CI
gh run list --branch main --limit 5
```

## Continuation Prompt

```text
Read task.md first. Repo on `main`, HEAD `5f2cb816`, working tree clean.
Full test suite 1852 passed. CI run pending (just pushed).

This session shipped:
- Branch tenant isolation (Oracle H2 fix): 4 dashboard endpoints scoped
  via ResolvesBranchScope trait + view_all_branches permission.
  Files: 6 modified + 2 new (trait + test). 8 isolation tests.
- DEFERRED: financial-dashboard (needs branch_id on journal_entries),
  pipeline/approval dashboards (polymorphic resolution).

Next action needs USER DIRECTION (do NOT auto-pick):
1. Multi-currency cross-cutting fix (Oracle H3) — affects aging/AR/AP/budget reports
2. Timezone drift (Oracle M3)
3. Financial dashboard branch scoping (schema change: branch_id on journal_entries)
4. Seed view_all_branches permission to admin role in dev DB

If user says "lanjutkan" without direction, ASK which path.
```
