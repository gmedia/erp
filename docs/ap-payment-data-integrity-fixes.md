# AP Payment Data Integrity Fixes

**Date**: 2026-05-03  
**Branch**: feature/accounts-payable  
**Status**: ✅ Complete

## Summary

Fixed 4 critical data integrity issues in the Accounts Payable (AP) module to ensure bidirectional synchronization between `ap_payments`, `ap_payment_allocations`, and `supplier_bills`.

## Issues Fixed

### 1. ✅ Bidirectional Sync (CRITICAL)
**File**: `app/Actions/ApPayments/SyncApPaymentAllocationsAction.php`

**Problem**: When payment allocations were created/updated, the corresponding `supplier_bills` records were not updated with `amount_paid` and `amount_due`.

**Solution**: 
- Wrapped entire sync operation in `DB::transaction()`
- After syncing allocations, iterate through affected bills
- Use `lockForUpdate()` to prevent race conditions
- Recalculate `amount_paid` from all allocations for each bill
- Update `amount_due` = `grand_total` - `amount_paid`
- Call `updatePaymentStatus()` to auto-transition bill status

**Impact**: Payment allocations now correctly update bill payment tracking in real-time.

---

### 2. ✅ Over-allocation Validation (CRITICAL)
**File**: `app/Http/Requests/ApPayments/AbstractApPaymentRequest.php`

**Problem**: Users could allocate more than the bill's outstanding amount, causing negative `amount_due`.

**Solution**:
- Added `withValidator()` method to base request class
- For each allocation, calculate existing allocations from OTHER payments
- Validate that `existing + new allocation <= bill grand_total`
- Return validation error with maximum allowed amount if exceeded
- Properly handles update scenario by excluding current payment from existing calculation

**Impact**: Prevents over-allocation at validation layer before database write.

---

### 3. ✅ Delete Cleanup (CRITICAL)
**File**: `app/Http/Controllers/ApPaymentController.php`

**Problem**: Deleting a payment left orphaned allocations and didn't revert bill amounts.

**Solution**:
- Replaced `destroyModel()` with custom `destroy()` implementation
- Wrapped in `DB::transaction()`
- Before deleting payment, iterate through allocations with eager-loaded bills
- Subtract allocation amount from bill's `amount_paid`
- Recalculate `amount_due`
- Call `updatePaymentStatus()` to revert bill status if needed
- Then delete the payment (cascade deletes allocations via FK)

**Impact**: Deleting payments now correctly reverts bill payment state.

---

### 4. ✅ Auto Status Transition (WARNING)
**File**: `app/Models/SupplierBill.php`

**Problem**: Bill status didn't automatically transition based on payment progress.

**Solution**:
- Added `updatePaymentStatus()` method to model
- Skips auto-transition for terminal statuses (`cancelled`, `void`, `draft`)
- Transitions to `paid` when `amount_paid >= grand_total`
- Transitions to `partially_paid` when `0 < amount_paid < grand_total`
- Transitions back to `confirmed` when `amount_paid = 0` and status was `partially_paid`

**Impact**: Bill status now reflects payment progress automatically.

---

## Validation Results

### ✅ PHPStan
```bash
./vendor/bin/sail bin phpstan analyze app/Actions/ApPayments/SyncApPaymentAllocationsAction.php \
  app/Http/Requests/ApPayments/AbstractApPaymentRequest.php \
  app/Http/Controllers/ApPaymentController.php \
  app/Models/SupplierBill.php
```
**Result**: 0 errors

### ✅ Duster
```bash
./vendor/bin/sail bin duster fix <files>
```
**Result**: 1 style issue fixed (concat_space), all clean

### ✅ Pest Tests
```bash
./vendor/bin/sail test --group=supplier-bills --group=ap-payments
```
**Result**: 20 passed (100 assertions)
- 16 existing tests (all pass)
- 4 new tests (all pass):
  - `creating payment updates supplier bill amount_paid and amount_due`
  - `updating payment allocation updates supplier bill amounts`
  - `deleting payment reverts supplier bill amounts`
  - `over-allocation is prevented`

---

## Test Coverage

New test file: `tests/Feature/ApPayments/ApPaymentBidirectionalSyncTest.php`

**Test 1**: Creating payment updates bill amounts
- Creates bill with `grand_total=1000000`, `amount_paid=0`
- Creates payment with allocation of `500000`
- Asserts bill updated to `amount_paid=500000`, `amount_due=500000`, `status=partially_paid`

**Test 2**: Updating payment allocation updates bill amounts
- Creates bill with existing allocation of `500000`
- Updates allocation to `1000000`
- Asserts bill updated to `amount_paid=1000000`, `amount_due=0`, `status=paid`

**Test 3**: Deleting payment reverts bill amounts
- Creates bill with `amount_paid=500000` from existing payment
- Deletes the payment
- Asserts bill reverted to `amount_paid=0`, `amount_due=1000000`, `status=confirmed`

**Test 4**: Over-allocation is prevented
- Creates bill with `grand_total=1000000`
- Attempts to allocate `1500000`
- Asserts validation error on `allocations.0.allocated_amount`

---

## Files Changed

1. `app/Actions/ApPayments/SyncApPaymentAllocationsAction.php`
   - Added `DB::transaction()` wrapper
   - Added bidirectional sync logic for affected bills
   - Added imports: `ApPaymentAllocation`, `SupplierBill`, `DB`

2. `app/Http/Requests/ApPayments/AbstractApPaymentRequest.php`
   - Added `withValidator()` method for over-allocation check
   - Added imports: `ApPaymentAllocation`, `SupplierBill`, `Validator`

3. `app/Http/Controllers/ApPaymentController.php`
   - Replaced `destroyModel()` with custom `destroy()` implementation
   - Added `DB` import

4. `app/Models/SupplierBill.php`
   - Added `updatePaymentStatus()` method

5. `tests/Feature/ApPayments/ApPaymentBidirectionalSyncTest.php` (NEW)
   - 4 comprehensive tests for bidirectional sync

---

## Migration Notes

- No database migrations required
- No breaking changes to API contracts
- Existing tests continue to pass
- New behavior is backward-compatible (fixes bugs, doesn't change expected behavior)

---

## Next Steps

1. ✅ All fixes implemented and tested
2. ✅ PHPStan clean
3. ✅ Duster clean
4. ✅ All tests pass
5. Ready for code review and merge to main
