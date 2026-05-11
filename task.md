# AI Handoff: Accounts Payable & Receivable Implementation

Last updated: 2026-05-11 UTC

## Document Roles

- `task.md` stores the active handoff state and the next recommended action.
- `task.changelog.md` stores product and feature changelog entries.
- `task.handoff-archive.md` stores condensed historical E2E handoff checkpoints.

## Current Objective

- **Accounts Receivable (PR #11)** — CI + Sonar PASS. Ready to merge.
- **Accounts Payable** — already on main.
- **User Guide feature** — shipped.

## Current Milestone

- **Accounts Payable**: ✅ Merged to main.
- **Accounts Receivable**: ✅ COMPLETE. PR #11 (`feature/accounts-receivable`).
- **Sonar Duplication** (2026-05-11): 4.9% → **1.2%** (threshold ≤ 3%). Dup lines 380 → 92. Code smells 21 → 3. Quality gate OK.
- **Data Integrity Fixes**: ✅ Bidirectional sync, over-allocation validation, auto status transition.
- **User Guide Page**: ✅ Implemented with AppLayout pattern + markdown rendering.
- **E2E Tests**: ✅ AP 18 tests, AR 27 tests.

## Current State

- Branch `feature/accounts-payable`: CI green, 37 Pest tests, 18 E2E tests.
- Branch `feature/accounts-receivable`: CI green, 39 Pest tests, 27 E2E tests.
- Both branches have: permissions, sidebar menus, sample data seeders, pipeline seeders, user guides.

## Active Constraints

- Do NOT run full E2E in chat (use targeted module tests).
- Use Sail for every runtime command.
- Keep exactly one Playwright process active at a time.

## Latest Session Delta

- **Sonar duplication refactor on PR #11** (new-code density 4.9% → target ≤ 3%):
  - PHP extractions:
    - `app/Models/Concerns/HasFinancialTransactionRelations.php` — branch/fiscalYear/bankAccount/journalEntry/creator/confirmer relations (used by ArReceipt + ApPayment).
    - `app/Http/Resources/Concerns/BuildsAuditStampResourceData.php` — shared `created_by`/`confirmed_by`/timestamps block (used by ArReceipt/CreditNote/ApPayment/SupplierBill resources).
    - `app/Http/Requests/Concerns/ValidatesAllocationOverflow.php` — shared allocation over-allocation guard (used by AR receipts).
    - Refactored `AbstractArReceiptRequest`, `AbstractCreditNoteRequest` to lean on existing `HasSometimesArrayRules` helpers.
  - TSX extractions:
    - `resources/js/components/common/EntityAuditFooter.tsx` — shared Created/Confirmed-by footer (used by ArReceipt + CreditNote ViewModals).
    - `resources/js/components/common/TransactionLineItemsTable.tsx` — shared line-item table (used by CreditNote + CustomerInvoice forms, with `includeDiscount`).
    - `resources/js/utils/columns.tsx` — new `createRowCurrencyAmountColumn()` (used by Ar/Ap/CustomerInvoice columns).
  - Code-smell fixes: S103 line-length (controllers, exports, actions, report request), S7787 empty import, S6759 readonly props.
- Verification run:
  - `sail bin duster fix` — clean.
  - `sail bin phpstan analyze` — 955 files, 0 errors.
  - `npm run types` — clean.
  - Pest: AR (8) + CreditNote (7) + CustomerInvoice (7) + AP (14) + SupplierBill (7) + AR Reports (15) = 58 tests, all passing.

## Recommended Next Steps

1. Merge PR #11 (AR) → main.
2. Update `docs/database/IMPLEMENTATION_STATUS.md` — mark AR as ✅.
3. Start GL Extended (Modul 17) per `docs/database/17_general_ledger_design.md`.

## Continuation Prompt

```
Read task.md. PR #11 (AR) is ready to merge — Sonar 1.2% ≤ 3%, all CI green.
After merge, start GL Extended (Modul 17) implementation per docs/database/17_general_ledger_design.md.
```
