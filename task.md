# AI Handoff: Idle — AR Journal Auto-Posting Queued

Last updated: 2026-05-15 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current Objective

- None active. Next planned phase: AR journal auto-posting (mirror of AP, on `feature/ar-journal-auto-posting`). Awaiting user go-ahead before starting.

## Current Milestone

- ✅ **PR #13 — Modul 18 (Financial Reports)** — merged as `82b7989e`.
- ✅ **PR #14 — AP journal auto-posting** — merged as `52d1eb9f`. Pending Decision #2 (AP side) closed. Bills + payments now auto-create posted JournalEntries on confirm with `journal_type='system'` + source morph references; idempotent across re-saves.

## Current State

- Branch: `main`
- HEAD: `52d1eb9f` (PR #14 merge commit)
- Working tree: clean
- All quality gates green at merge: PHPStan 0 errors, Pest 12 affected groups all green, SonarCloud SUCCESS.

## Active Constraints

- Use Sail for every runtime command.
- AP control account is currently resolved by code lookup (`code=21100`) in the active `CoaVersion`. If a project ships without that exact code seeded, the action throws a friendly `ValidationException` until the COA is fixed.

## Latest Session Delta

- PR #14 went green on first push: Quality checks, Test suite, SonarCloud, SonarCloud Code Analysis — all SUCCESS. Merged via `gh pr merge 14 --merge --delete-branch` (no autofix retrigger needed this time).
- 12 files / +826 / −82 landed: 3 new actions (AccountingPosting/), extended `CreateJournalEntryAction`, controller hooks for SupplierBill + ApPayment update, 13 new Pest tests in `tests/Feature/AccountingPosting/`, plus minimal seed adjustments in two existing controller tests.
- Local `feature/ap-journal-auto-posting` branch removed by gh; back on `main`.

## Validated Commands and Outcomes

- `gh pr view 14` → state `MERGED`, mergeCommit `52d1eb9f3fb54f60140e83197d482d892f192643`, mergedAt `2026-05-15T14:15:21Z`.
- `git rev-parse HEAD` → `52d1eb9f3fb54f60140e83197d482d892f192643`.
- `git status --short` → clean.

## Open Risks / Blockers

- None.

## Recommended Next Steps

1. Wait for user go-ahead before starting AR work (mirror of AP).
2. When approved, create `feature/ar-journal-auto-posting`. Mirror plan:
   - `PostCustomerInvoiceJournalAction` — on invoice confirm: Debit AR control (`code=11200`), Credit per-item revenue/expense `account_id`.
   - `PostArReceiptJournalAction` — on receipt confirm: Debit `bank_account_id`, Credit AR control.
   - Hooks in `CustomerInvoiceController::update()` + `ArReceiptController::update()` (transition guard via `confirmed_at === null`).
   - Pest group `ar-journal-posting` covering balance, idempotency, no-op, validation errors, and controller PUT integration.
3. Other Tier 1/2/3 follow-ups (in `IMPLEMENTATION_STATUS.md`):
   - `journal_entry_id` on GR/SR + stock adjustments (Pending Decisions #2 remainder + #3).
   - Modul 18 formula expression evaluator.
   - `product_stocks.branch_id → warehouse_id` migration (Pending Decision #1).
   - Subscription frontend CRUD.
   - Recurring journal scheduler.
   - Bank reconciliation CSV/Excel import.

## Continuation Prompt

```
Read task.md. Repository on main at 52d1eb9f, working tree clean.
Modul 18 (PR #13) and AP journal auto-posting (PR #14) shipped. The mirror
work for AR (CustomerInvoice/ArReceipt) is queued. When approved, create
feature/ar-journal-auto-posting and follow the same pattern: control account
lookup (code=11200 for AR), idempotent posting actions, controller hooks on
the draft→confirmed transition, Pest group ar-journal-posting.
```
