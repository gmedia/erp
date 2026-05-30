# AI Handoff: ERP Active State

Last updated: 2026-05-30 09:00 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current State

- Branch: `main`
- HEAD: `be8e84c9 test: backfill show endpoint coverage for stock-adjustments and stock-transfers`
- Working tree: clean.
- Remote: pushed (up to date).
- CI E2E is **required gate** (no `continue-on-error`).
- Sonar: Quality Gate **OK** (overall coverage ~91.9%, 0 HIGH/BLOCKER issues at last scan).
- Module registry: 76 entries + financial-dashboard.

### CI Note — Autofix Supersede Pattern (read this before reacting to red runs)

`tests.yml` runs Duster + Prettier + ESLint on every push. When those tools change files, the workflow auto-commits them with `style: apply CI autofixes` and forces the final `Test suite via Sail` job to `exit 1` (workflow lines 124-168, 473-476). The intent: that run is treated as failed so the **follow-up run on the autofix commit** is the authoritative verdict.

Recent runs may also show as `cancelled` rather than `failed` when consecutive pushes happen quickly. The workflow's concurrency group cancels the previous run; the most recent commit's run is always the one that matters. Verify status via the latest run on HEAD only — older `cancelled` / `failure` entries are not real regressions.

### Last verified-green commits

- `7ddf598d docs(task): sync handoff and document CI autofix-supersede pattern` — full CI green
- `8da4f872 docs(task): update handoff — financial dashboard v2 with monthly trends complete` — full CI green
- All commits between `7ddf598d` and HEAD are test-only or doc-only with PHPStan / Pest / Duster verified locally before push

### Financial Dashboard

Implementation complete in earlier session, fully verified:

| Component | Status |
|-----------|--------|
| Backend Action / Service / Controller / Route | done |
| Frontend hook / 6 components / page / sidebar nav / permission | done |
| Pest tests (7 pass, 57 assertions) | done |
| E2E tests (4 cases) | done |
| PHPStan / TypeScript / ESLint | clean |
| Module registry entry | done |
| Sonar MAJOR issues from initial implementation | resolved (commit `ecffac0d`) |

## Coverage Backfill Progress (this multi-session work)

The codebase had several files at 0% or mid coverage despite endpoints being live in production. Backfilled with pure additive Pest tests — no behavior changes:

| Wave | Commit | Files moved to ~100% |
|---|---|---|
| 1 | `1c6a4ec7` | `ApplyCreditNoteAction`, `InventoryStocktakeItemController`, `HandlesNestedItemsResponse` (61 lines) |
| 2 | `19d773b2` | `StockAdjustmentItemController`, `StockTransferItemController`, `UserGuideController` (78 lines) |
| 3 | `ecffac0d` | (Sonar refactor — 7 MAJOR fixes, no coverage change) |
| 4 | `11e1ba41` | `AssetStocktakeController::show`, `SupplierController::show` |
| 5 | `be8e84c9` | `StockAdjustmentController::show`, `StockTransferController::show` |

**Cumulative: 21 new Pest test cases, 9 PHP files moved from 0%/mid to ~100% coverage, 0 regressions.**

## What changed this session

1. Synced HEAD pointer in `task.md` (was stale at `08f400e5`).
2. Added explicit "CI Autofix Supersede Pattern" section so future sessions don't panic at red runs.
3. Added `financial-dashboard` entry to `docs/module-registry.md`.
4. Backfilled coverage for 5 controllers and 2 actions (waves 1, 2, 4, 5).
5. Resolved 7 SonarCloud MAJOR issues in financial-dashboard files (wave 3).

## Important: Files NOT to write tests for

After investigation, the following files are **dead code from the Inertia → API-only migration**. Do NOT write tests for them:

- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Controllers/Auth/EmailVerificationNotificationController.php`
- `app/Http/Controllers/Auth/NewPasswordController.php`
- `app/Http/Controllers/LocaleController.php`
- `app/Http/Controllers/Settings/PasswordController.php`
- `app/Providers/FortifyServiceProvider.php` (only 2 lines anyway)
- `app/Http/Requests/Auth/LoginRequest.php`

Evidence: no route in `routes/api/*.php` or `routes/api.php` references these classes. Project uses `App\Http\Controllers\Api\AuthController` instead. `tests/Feature/Settings/PasswordUpdateTest.php` is a 1-line stub: *"Web test removed since it is obsolete in the SPA architecture"*.

These files appear in Sonar's 0%-coverage list because they exist on disk, but writing tests for them = pretend coverage. The right action is **delete**, but that requires user approval since it could affect anyone still using Fortify scaffolding accidentally.

## Recommended Next Steps

### Easy (AI-autonomous, but value diminishing)

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 1 | Seed dev DB | Low | `sail artisan db:seed --class=MenuSeeder --class=PermissionSeeder` to activate financial-dashboard nav link. Requires DB state change. |

### Medium (require domain investigation, not pure backfill)

| # | Task | Effort | Notes |
|---|------|--------|-------|
| 2 | Coverage for `EvaluateGuardAction` (currently 40%) | Medium | Pipeline guard logic with multiple condition types. Must read `HandlesConditions` trait first. |
| 3 | Coverage for `ExecuteTransitionActionsAction` (currently 32%) | Medium | Transition side-effects engine. Touches multiple action types. |
| 4 | Coverage for `TriggerApprovalAction` | Medium | Approval flow trigger — multiple entity types. |
| 5 | Update graceful-degrade for stock adjustment journal posting | Medium | Hard to reproduce realistically (items min 1 validation blocks the failure path). May need unit-test-level mocking of `PostStockAdjustmentJournalAction`. |

### Requires user decision

| # | Task | Notes |
|---|------|-------|
| 6 | Delete dead Fortify scaffold | Touches 6 files. Verify nothing in `bootstrap/app.php` or middleware aliases references them first. |
| 7 | Product features (P&L by Department, Aging Dashboard, Budget, Sales/Invoicing) | All new domains. |

## Useful Commands

```bash
# Seed nav link + permission for financial-dashboard
sail artisan db:seed --class=MenuSeeder
sail artisan db:seed --class=PermissionSeeder

# Run focused tests
sail test --group financial-dashboard
sail test --group credit-notes
sail test --group inventory-stocktakes
sail test --group stock-adjustments
sail test --group stock-transfers
sail test --group asset-stocktakes
sail test --group suppliers
sail test --group user-guide

# Reset testing DB if parallel run state corrupts it
sail artisan migrate:fresh --env=testing

# Quality gates
sail bin phpstan analyze
sail bin duster fix
npm run types
sail npm run lint

# Monitor CI
gh run list --branch main --limit 5
```

## Continuation Prompt

```text
Read task.md first. Repo on `main` at HEAD `be8e84c9` or later.

Status: Financial Dashboard fully shipped + Sonar-clean. 5 waves of
coverage backfill landed (21 new Pest cases, 9 files at 0% → ~100%).
PHPStan / Pest / Duster all clean locally before each push.

Before reacting to red CI runs: read the "CI Autofix Supersede Pattern"
section. Most reds and cancels in `gh run list` are concurrency-cancels
or autofix-supersedes, NOT failures. Verify via the latest run on HEAD.

Do NOT write tests for the Auth/Fortify scaffold files listed in the
"Files NOT to write tests for" section — they are dead code from the
Inertia → API migration with no live routes.

Easy autonomous task remaining: seed dev DB to activate financial-dashboard
nav (requires explicit go-ahead since DB state changes).

Medium tasks (require investigation): coverage for EvaluateGuardAction,
ExecuteTransitionActionsAction, TriggerApprovalAction. These have complex
branch logic — read HandlesConditions and existing pipeline tests first.

Product feature options if user wants to expand domain: Profit & Loss
by Department, Aging Dashboard, Budget Management, Sales/Invoicing.
```
