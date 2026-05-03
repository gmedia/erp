# AR Module Data Integrity Fixes

**Branch**: `feature/accounts-receivable`  
**Date**: 2026-05-03  
**Status**: ✅ Complete

## Summary

Fixed 5 critical data integrity issues in the Accounts Receivable module to ensure bidirectional sync between receipts, invoices, and credit notes.

---

## Issues Fixed

### 1. ✅ Receipt → Invoice Bidirectional Sync (CRITICAL)

**File**: `app/Actions/ArReceipts/SyncArReceiptAllocationsAction.php`

**Problem**: When allocations were synced, the affected invoices' `amount_received` and `amount_due` were not updated, causing data inconsistency.

**Solution**: 
- Wrapped entire sync operation in `DB::transaction()`
- After syncing allocations, iterate through affected invoices
- Recalculate each invoice's `amount_received` from all allocations
- Recalculate `amount_due` = `grand_total - amount_received - credit_note_amount`
- Call `updatePaymentStatus()` to auto-transition invoice status

**Impact**: Ensures invoice payment tracking is always accurate when receipts are created/updated.

---

### 2. ✅ Credit Note Application (CRITICAL)

**New Files**:
- `app/Actions/CreditNotes/ApplyCreditNoteAction.php`
- Route: `POST /api/credit-notes/{creditNote}/apply`
- Controller method: `CreditNoteController::apply()`

**Problem**: No mechanism to apply confirmed credit notes to invoices.

**Solution**:
- Created `ApplyCreditNoteAction` with validation:
  - Only `confirmed` credit notes can be applied
  - Must be linked to an invoice
  - Amount cannot exceed invoice outstanding
- Updates credit note status to `applied`
- Updates invoice `credit_note_amount` and `amount_due`
- Calls `updatePaymentStatus()` to auto-transition invoice status
- Wrapped in `DB::transaction()`

**Impact**: Credit notes now properly reduce invoice outstanding amounts.

---

### 3. ✅ Over-allocation Validation (CRITICAL)

**File**: `app/Http/Requests/ArReceipts/AbstractArReceiptRequest.php`

**Problem**: No validation to prevent allocating more than invoice outstanding amount.

**Solution**:
- Added `withValidator()` method with custom validation
- For each allocation:
  - Check invoice is not `void` or `cancelled`
  - Calculate existing allocations from other receipts
  - Calculate max allocation = `grand_total - existing_allocated - credit_note_amount`
  - Reject if allocation exceeds max
- Handles both create and update scenarios (excludes current receipt when updating)

**Impact**: Prevents over-allocation at request validation layer.

---

### 4. ✅ Delete Cleanup (CRITICAL)

**File**: `app/Http/Controllers/ArReceiptController.php`

**Problem**: Deleting a receipt did not reverse the invoice `amount_received` and `amount_due`.

**Solution**:
- Replaced `destroyModel()` with custom `destroy()` method
- Wrapped in `DB::transaction()`
- For each allocation:
  - Subtract `allocated_amount` from invoice `amount_received`
  - Recalculate `amount_due`
  - Call `updatePaymentStatus()` to auto-transition invoice status
- Then delete the receipt

**Impact**: Invoice payment tracking remains accurate when receipts are deleted.

---

### 5. ✅ Auto Status Transition + Void/Cancelled Guard

**File**: `app/Models/CustomerInvoice.php`

**New Method**: `updatePaymentStatus()`

**Problem**: Invoice status did not auto-transition based on payment progress.

**Solution**:
- Added `updatePaymentStatus()` method:
  - Guards against `cancelled`, `void`, `draft` invoices (no status change)
  - Calculates `totalSettled = amount_received + credit_note_amount`
  - Auto-transitions:
    - `paid` if `totalSettled >= grand_total`
    - `partially_paid` if `0 < totalSettled < grand_total`
    - `sent` if `totalSettled <= 0` and currently `partially_paid`
- Called from:
  - `SyncArReceiptAllocationsAction` (after allocation sync)
  - `ApplyCreditNoteAction` (after credit note applied)
  - `ArReceiptController::destroy()` (after receipt deleted)

**Impact**: Invoice status always reflects actual payment state.

---

## Validation Results

### ✅ PHPStan
```bash
./vendor/bin/sail bin phpstan analyze \
  app/Actions/ArReceipts/SyncArReceiptAllocationsAction.php \
  app/Models/CustomerInvoice.php \
  app/Http/Requests/ArReceipts/AbstractArReceiptRequest.php \
  app/Http/Controllers/ArReceiptController.php \
  app/Actions/CreditNotes/ApplyCreditNoteAction.php \
  app/Http/Controllers/CreditNoteController.php \
  --memory-limit=2G
```
**Result**: ✅ No errors

### ✅ Duster
```bash
./vendor/bin/sail bin duster fix [all changed files]
```
**Result**: ✅ 1 style issue fixed (concat_space)

### ✅ Pest Tests
```bash
./vendor/bin/sail test --group=customer-invoices --group=ar-receipts --group=credit-notes
```
**Result**: ✅ 24 passed (122 assertions)

### ✅ Routes
```bash
./vendor/bin/sail artisan route:list --path=credit-notes
```
**Result**: ✅ 7 routes (6 CRUD + 1 apply)

---

## Test Fixes

Updated 3 test cases in `tests/Feature/ArReceipts/ArReceiptControllerTest.php` to explicitly set invoice `status: 'sent'` instead of relying on factory random status (which could be `void` or `cancelled`).

---

## Files Changed

### Modified (6 files)
1. `app/Actions/ArReceipts/SyncArReceiptAllocationsAction.php`
2. `app/Models/CustomerInvoice.php`
3. `app/Http/Requests/ArReceipts/AbstractArReceiptRequest.php`
4. `app/Http/Controllers/ArReceiptController.php`
5. `app/Http/Controllers/CreditNoteController.php`
6. `routes/api/credit-notes.php`

### Created (1 file)
7. `app/Actions/CreditNotes/ApplyCreditNoteAction.php`

### Test Fixes (1 file)
8. `tests/Feature/ArReceipts/ArReceiptControllerTest.php`

---

## Next Steps

1. ✅ All validation passed
2. ✅ All tests passing
3. Ready for commit and PR
4. Consider adding E2E tests for:
   - Credit note application flow
   - Over-allocation rejection
   - Invoice status auto-transition

---

## Notes

- All changes follow project conventions (Actions pattern, DB transactions, typed casts)
- No breaking changes to existing API contracts
- Backward compatible (existing receipts/invoices unaffected)
- All edge cases handled (void/cancelled invoices, over-allocation, delete cleanup)
