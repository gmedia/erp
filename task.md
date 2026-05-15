# AI Handoff: AP Journal Auto-Posting (Pending Decision #2 part 1)

Last updated: 2026-05-15 UTC

## Document Roles

- `task.md` stores active handoff state and next recommended action.
- `task.changelog.md` stores product/feature changelog entries.
- `task.handoff-archive.md` stores condensed historical checkpoints.

## Current Objective

- Ship AP journal auto-posting (Modul 15 §4 / Modul 17 §6 integration). Branch ready; commit + push + PR pending.

## Current Milestone

- ✅ **Modul 18 (Financial Reports)** — merged via PR #13 as `82b7989e`.
- 🚧 **AP journal auto-posting** — branch `feature/ap-journal-auto-posting`. All code + tests + style verifications green locally. Ready to commit + push + open PR.

## Current State

- Branch: `feature/ap-journal-auto-posting`
- Parent: `82b7989e` (main)
- Commits: pending
- PHPStan: 0 errors (full project)
- Pest groups: ap-journal-posting (13/13), supplier-bills (8/8), ap-payments (12/12), journal-entries (29/29), asset-depreciation-runs (12/12), goods-receipts (22/22), purchase-orders (17/17), ar-receipts (8/8), customer-invoices (8/8), recurring-journals (16/16), reports (12/12), accounts (34/34) — all green.
- Duster: applied (CS Fixer + Pint pass).

## Active Constraints

- Use Sail for every runtime command.
- Auto-posting is additive + non-breaking; existing tests that triggered `status='confirmed'` were updated to seed the COA control account (`code=21100`).

## Latest Session Delta

- Added 3 new actions in `app/Actions/AccountingPosting/`:
  - `ResolveControlAccountAction` — looks up an account by code in the active CoaVersion.
  - `PostSupplierBillJournalAction` — on bill confirm: Debit per-item `account_id` (grouped by account), Credit AP control (`code=21100`). Idempotent via `journal_entry_id` short-circuit.
  - `PostApPaymentJournalAction` — on payment confirm: Debit AP control, Credit `bank_account_id`. Idempotent.
- Extended `CreateJournalEntryAction::execute()` (additive): accepts optional `status` (`draft`|`posted`), `journal_type`, `source_type`, `source_id`. When `status='posted'`, balance is verified and `posted_by/posted_at` are stamped. Existing callers (depreciation, JournalEntryController) unchanged.
- Wired hooks in `SupplierBillController::update()` and `ApPaymentController::update()` — only fires when status transitions from non-confirmed to `confirmed` (uses `confirmed_at === null` guard).
- Pest coverage:
  - Action-level: balance, idempotency, no-op when not confirmed, validation errors (no items, no active COA, non-positive payment).
  - Controller-level: PUT endpoint posts journal on confirm; second PUT does NOT double-post.
- Updated existing controller tests `update modifies supplier bill and items` + `update modifies ap payment` to seed FiscalYear + active CoaVersion + AP control account + bank account before triggering confirm.

## Validated Commands and Outcomes

- `./vendor/bin/sail bin phpstan analyze` — 0 errors.
- `./vendor/bin/sail test --group=ap-journal-posting` — 13 passed (47 assertions).
- `./vendor/bin/sail test --group=supplier-bills` — 8 passed (42 assertions).
- `./vendor/bin/sail test --group=ap-payments` — 12 passed (58 assertions).
- `./vendor/bin/sail test --group=journal-entries` — 29 passed (194 assertions).
- `./vendor/bin/sail test --group=asset-depreciation-runs` — 12 passed (no regression on the other CreateJournalEntryAction caller).
- `./vendor/bin/sail bin duster fix` — clean.

## Open Risks / Blockers

- None. Test DB is clean, all groups green, PHPStan clean.

## Recommended Next Steps

1. Stage + commit all changes in one atomic commit: `feat: auto-post AP journal entries on bill/payment confirm (Pending Decision #2 partial)`.
2. Push branch `feature/ap-journal-auto-posting`. Open PR against `main`.
3. Monitor CI (expect autofix retrigger pattern as usual). Merge when green.
4. Start AR journal auto-posting branch (`feature/ar-journal-auto-posting`) using the same pattern: PostCustomerInvoiceJournalAction (Debit AR, Credit Revenue) + PostArReceiptJournalAction (Debit Bank, Credit AR).

## Continuation Prompt

```
Read task.md. Branch feature/ap-journal-auto-posting on parent 82b7989e (main).
AP auto-posting (SupplierBill + ApPayment) implemented + tested. Commit "feat: auto-post AP
journal entries on bill/payment confirm", push, open PR, monitor CI, merge.
After merge, start feature/ar-journal-auto-posting using the same pattern.
```
