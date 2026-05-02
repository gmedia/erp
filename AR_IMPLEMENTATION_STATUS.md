# Accounts Receivable Backend Implementation Progress

## Completed (Wave 1 & 2)

### Wave 1: Migrations & Models ✅
- ✅ 6 migrations created in `database/migrations/`:
  - `2026_05_10_001000_create_customer_invoices_table.php`
  - `2026_05_10_001100_create_customer_invoice_items_table.php`
  - `2026_05_10_001200_create_ar_receipts_table.php`
  - `2026_05_10_001300_create_ar_receipt_allocations_table.php`
  - `2026_05_10_001400_create_credit_notes_table.php`
  - `2026_05_10_001500_create_credit_note_items_table.php`

- ✅ 7 models created in `app/Models/`:
  - `HasCustomerRelation.php` (trait)
  - `CustomerInvoice.php`
  - `CustomerInvoiceItem.php`
  - `ArReceipt.php`
  - `ArReceiptAllocation.php`
  - `CreditNote.php`
  - `CreditNoteItem.php`

- ✅ 6 factories created in `database/factories/`:
  - `CustomerInvoiceFactory.php` (with sent(), paid(), overdue(), cancelled() states)
  - `CustomerInvoiceItemFactory.php`
  - `ArReceiptFactory.php` (with confirmed(), cancelled() states)
  - `ArReceiptAllocationFactory.php`
  - `CreditNoteFactory.php` (with confirmed(), applied(), cancelled() states)
  - `CreditNoteItemFactory.php`

### Wave 2: Customer Invoices Backend ✅
- ✅ Requests (6 files in `app/Http/Requests/CustomerInvoices/`):
  - `AbstractCustomerInvoiceRequest.php`
  - `StoreCustomerInvoiceRequest.php`
  - `UpdateCustomerInvoiceRequest.php`
  - `AbstractCustomerInvoiceListingRequest.php`
  - `IndexCustomerInvoiceRequest.php`
  - `ExportCustomerInvoiceRequest.php`

- ✅ Actions (3 files in `app/Actions/CustomerInvoices/`):
  - `IndexCustomerInvoicesAction.php`
  - `ExportCustomerInvoicesAction.php`
  - `SyncCustomerInvoiceItemsAction.php`

- ✅ Resources (2 files in `app/Http/Resources/CustomerInvoices/`):
  - `CustomerInvoiceResource.php`
  - `CustomerInvoiceCollection.php`

- ✅ Domain (1 file in `app/Domain/CustomerInvoices/`):
  - `CustomerInvoiceFilterService.php`

- ✅ DTOs (1 file in `app/DTOs/CustomerInvoices/`):
  - `UpdateCustomerInvoiceData.php`

- ✅ Exports (1 file in `app/Exports/`):
  - `CustomerInvoiceExport.php`

- ✅ Controller (1 file in `app/Http/Controllers/`):
  - `CustomerInvoiceController.php`

- ✅ Route (1 file in `routes/api/`):
  - `customer-invoices.php`

- ✅ Configuration:
  - Added `customer_invoices` config to `TransactionMappedIndexConfigurations.php`
  - Added route require to `routes/api.php`

## Remaining Work (Wave 3 & 4)

### Wave 3: AR Receipts Backend (PENDING)
Need to create 16 files mirroring Customer Invoices pattern:
- 6 Request files in `app/Http/Requests/ArReceipts/`
- 3 Action files in `app/Actions/ArReceipts/`
- 2 Resource files in `app/Http/Resources/ArReceipts/`
- 1 FilterService in `app/Domain/ArReceipts/`
- 1 DTO in `app/DTOs/ArReceipts/`
- 1 Export in `app/Exports/`
- 1 Controller in `app/Http/Controllers/`
- 1 Route file in `routes/api/`

Key differences from Customer Invoices:
- Uses `allocations` instead of `items`
- No approval flow (simpler than AP)
- Payment method enum includes `credit_card`
- Uses `discount_given` instead of `discount_taken`
- Document number prefix: `RCV`
- On status `confirmed` → set `confirmed_by` + `confirmed_at`

### Wave 4: Credit Notes Backend (PENDING)
Need to create 16 files (same structure as AR Receipts):
- 6 Request files in `app/Http/Requests/CreditNotes/`
- 3 Action files in `app/Actions/CreditNotes/`
- 2 Resource files in `app/Http/Resources/CreditNotes/`
- 1 FilterService in `app/Domain/CreditNotes/`
- 1 DTO in `app/DTOs/CreditNotes/`
- 1 Export in `app/Exports/`
- 1 Controller in `app/Http/Controllers/`
- 1 Route file in `routes/api/`

Key differences:
- Has `reason` enum (return, discount, correction, bad_debt, other)
- Optional `customer_invoice_id` (can be standalone)
- Document number prefix: `CN`
- On status `confirmed` → set `confirmed_by` + `confirmed_at`

### Final Configuration (PENDING)
- Add `ar_receipts` config to `TransactionMappedIndexConfigurations.php`
- Add `credit_notes` config to `TransactionMappedIndexConfigurations.php`
- Add route requires to `routes/api.php` (alphabetical order)

## Validation Checklist (PENDING)
1. `./vendor/bin/sail artisan migrate:fresh --seed --force` — must pass
2. `./vendor/bin/sail artisan migrate:rollback --step=6` — must rollback AR tables
3. `./vendor/bin/sail artisan migrate` — must re-apply
4. `./vendor/bin/sail bin phpstan analyze` on all new files — 0 errors
5. `./vendor/bin/sail bin duster fix` on all new files — clean
6. `./vendor/bin/sail artisan route:list --path=customer-invoices` — 6 routes
7. `./vendor/bin/sail artisan route:list --path=ar-receipts` — 6 routes
8. `./vendor/bin/sail artisan route:list --path=credit-notes` — 6 routes

## Pattern References for Remaining Work
All AR Receipts and Credit Notes files should mirror the Customer Invoices pattern with the key differences noted above. Use these as templates:
- Request: `app/Http/Requests/CustomerInvoices/AbstractCustomerInvoiceRequest.php`
- Action: `app/Actions/CustomerInvoices/SyncCustomerInvoiceItemsAction.php`
- Resource: `app/Http/Resources/CustomerInvoices/CustomerInvoiceResource.php`
- FilterService: `app/Domain/CustomerInvoices/CustomerInvoiceFilterService.php`
- DTO: `app/DTOs/CustomerInvoices/UpdateCustomerInvoiceData.php`
- Export: `app/Exports/CustomerInvoiceExport.php`
- Controller: `app/Http/Controllers/CustomerInvoiceController.php`
- Route: `routes/api/customer-invoices.php`
