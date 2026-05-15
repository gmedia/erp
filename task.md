# AI Handoff: AR Journal Auto-Posting (Pending Decision #2 part 2)

Last updated: 2026-05-15 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current Objective

- Ship AR journal auto-posting (mirror of AP). Branch ready; commit + push + PR pending.

## Current Milestone

- ✅ **PR #13 — Modul 18 (Financial Reports)** — merged as `82b7989e`.
- ✅ **PR #14 — AP journal auto-posting** — merged as `52d1eb9f`.
- 🚧 **AR journal auto-posting** — branch `feature/ar-journal-auto-posting`. All code + tests + style verifications green locally. Ready to commit + push + open PR.

## Current State

- Branch: `feature/ar-journal-auto-posting`
- Parent: `c0798b9b` (main, post-checkpoint)
- Commits: pending
- PHPStan: 0 errors (full project)
- Pest groups: ar-journal-posting (13/13), customer-invoices (8/8), ar-receipts (8/8), credit-notes (8/8), ap-journal-posting (13/13), supplier-bills (8/8), ap-payments (12/12), journal-entries (29/29), asset-depreciation-runs (12/12) — all green.
- Duster: applied (CS Fixer + Pint pass).

## Active Constraints

- Use Sail for every runtime command.
- AR control account is resolved by code lookup (`code=11200`) in active `CoaVersion`. AP control account remains at `code=21100`.

## Latest Session Delta

- Added 2 new actions in `app/Actions/AccountingPosting/`:
  - `PostCustomerInvoiceJournalAction` — on invoice transition to `sent`: Debit AR control (`code=11200`), Credit per-item revenue `account_id` (grouped). Idempotent via `journal_entry_id` short-circuit.
  - `PostArReceiptJournalAction` — on receipt confirm: Debit `bank_account_id`, Credit AR control. Idempotent.
- Wired hooks in `CustomerInvoiceController::update()` (transition guard via `sent_at === null`) and `ArReceiptController::update()` (transition guard via `confirmed_at === null`).
- Pest coverage:
  - Action-level: balance, idempotency, no-op when not in target status, validation errors (no items, no active COA, non-positive receipt).
  - Controller-level: PUT endpoint posts journal on transition; second PUT does NOT double-post.
- Updated existing controller tests `update modifies customer invoice and sets sent_by` + `update modifies ar receipt and allocations` to seed FiscalYear + active CoaVersion + AR control account.

## Validated Commands and Outcomes

- `./vendor/bin/sail bin phpstan analyze` — 0 errors.
- `./vendor/bin/sail test --group=ar-journal-posting` — 13 passed (47 assertions).
- `./vendor/bin/sail test --group=customer-invoices` — 8 passed (41 assertions).
- `./vendor/bin/sail test --group=ar-receipts` — 8 passed (40 assertions).
- `./vendor/bin/sail test --group=credit-notes` — 8 passed (41 assertions).
- `./vendor/bin/sail test --group=ap-journal-posting` — 13 passed (47 assertions, no regression).
- `./vendor/bin/sail test --group=asset-depreciation-runs` — 12 passed (other CreateJournalEntryAction caller).
- `./vendor/bin/sail bin duster fix` — clean.

## Open Risks / Blockers

- None.

## Recommended Next Steps

1. Stage + commit all changes in one atomic commit: `feat: auto-post AR journal entries on invoice sent / receipt confirm (Pending Decision #2 partial)`.
2. Push branch `feature/ar-journal-auto-posting`. Open PR against `main`.
3. Monitor CI. Merge when green.
4. Pending Decision #2 will then be fully closed for AP + AR. Remaining accounting integrations:
   - `journal_entry_id` di GR/SR (Modul 13 §6) — minimal hook on goods receipt confirm.
   - `journal_entry_id` di stock adjustments (Modul 14 §7).
   - Pending Decision #1: `product_stocks.branch_id → warehouse_id` migration.
   - Modul 18 formula expression evaluator.

## Continuation Prompt

```
Read task.md. Branch feature/ar-journal-auto-posting on parent c0798b9b (main).
AR auto-posting (CustomerInvoice + ArReceipt) implemented + tested. Commit
"feat: auto-post AR journal entries on invoice sent / receipt confirm",
push, open PR, monitor CI, merge.
```
