<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, array<string, string>>
 */
function localeFormFieldTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['form_fields'] ?? [];
}

/**
 * @return array<string, string>
 */
function localeValidationTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['validation'] ?? [];
}

test('form field translation keys exist in both english and indonesian locales', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_keys($en))->toEqual(array_keys($id));

    foreach ($en as $module => $strings) {
        expect(array_keys($strings))->toEqual(array_keys($id[$module]), "form_fields.{$module} keys differ");
    }
});

test('purchase order form labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['purchase_orders']['add_title'])->toBe('Add New Purchase Order')
        ->and($en['purchase_orders']['edit_title'])->toBe('Edit Purchase Order')
        ->and($en['purchase_orders']['po_number'])->toBe('PO Number')
        ->and($en['purchase_orders']['po_number_placeholder'])->toBe('Auto-generated if empty')
        ->and($en['purchase_orders']['supplier'])->toBe('Supplier')
        ->and($en['purchase_orders']['warehouse'])->toBe('Warehouse')
        ->and($en['purchase_orders']['order_date'])->toBe('Order Date')
        ->and($en['purchase_orders']['payment_terms_placeholder'])->toBe('Net 30')
        ->and($en['purchase_orders']['item_unit_price'])->toBe('Unit Price');
});

test('purchase order status options and validation messages keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');
    $enValidation = localeValidationTranslations('en.json');

    expect($en['purchase_orders']['status_draft'])->toBe('Draft')
        ->and($en['purchase_orders']['status_pending_approval'])->toBe('Pending Approval')
        ->and($en['purchase_orders']['status_confirmed'])->toBe('Confirmed')
        ->and($en['purchase_orders']['status_rejected'])->toBe('Rejected')
        ->and($en['purchase_orders']['status_partially_received'])->toBe('Partially Received')
        ->and($en['purchase_orders']['status_fully_received'])->toBe('Fully Received')
        ->and($en['purchase_orders']['status_cancelled'])->toBe('Cancelled')
        ->and($en['purchase_orders']['status_closed'])->toBe('Closed')
        ->and($enValidation['purchase_order']['supplier_required'])->toBe('Supplier is required.')
        ->and($enValidation['purchase_order']['quantity_gt_zero'])->toBe('Quantity must be greater than 0.')
        ->and($enValidation['purchase_order']['unit_price_min_zero'])->toBe('Unit price must be at least 0.');
});

test('validation message keys exist in both english and indonesian locales', function () {
    $en = localeValidationTranslations('en.json');
    $id = localeValidationTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_keys($en))->toEqual(array_keys($id));

    foreach ($en as $form => $rules) {
        expect(array_keys($rules))->toEqual(array_keys($id[$form]), "validation.{$form} keys differ");

        foreach (array_keys($rules) as $rule) {
            expect($id[$form][$rule])->not->toBe('', "validation.{$form}.{$rule} empty");
        }
    }
});

test('purchase order form labels are translated to indonesian', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    foreach (array_keys($en['purchase_orders']) as $field) {
        expect($id['purchase_orders'][$field])
            ->not->toBe('', "form_fields.purchase_orders.{$field} empty");
    }

    expect($id['purchase_orders']['add_title'])->toBe('Tambah Pesanan Pembelian Baru')
        ->and($id['purchase_orders']['edit_title'])->toBe('Ubah Pesanan Pembelian')
        ->and($id['purchase_orders']['supplier'])->toBe('Pemasok')
        ->and($id['purchase_orders']['warehouse'])->toBe('Gudang');
});

test('purchase order validation messages are translated to indonesian', function () {
    $en = localeValidationTranslations('en.json');
    $id = localeValidationTranslations('id.json');

    expect($en['purchase_order'])->toHaveKeys([
        'supplier_required',
        'warehouse_required',
        'order_date_required',
        'currency_required',
        'status_required',
        'items_min_one',
        'product_required',
        'unit_required',
        'quantity_gt_zero',
        'unit_price_min_zero',
    ])
        ->and($id['purchase_order']['supplier_required'])->toBe('Pemasok wajib diisi.')
        ->and($id['purchase_order']['quantity_gt_zero'])->toBe('Jumlah harus lebih dari 0.')
        ->and($id['purchase_order']['unit_price_min_zero'])->toBe('Harga satuan minimal 0.');
});

test('purchase request and goods receipt form labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['purchase_requests']['add_title'])->toBe('Add New Purchase Request')
        ->and($en['purchase_requests']['edit_title'])->toBe('Edit Purchase Request')
        ->and($en['purchase_requests']['pr_number'])->toBe('PR Number')
        ->and($en['purchase_requests']['priority_urgent'])->toBe('Urgent')
        ->and($en['purchase_requests']['status_pending_approval'])->toBe('Pending Approval')
        ->and($en['purchase_requests']['item_estimated_unit_price'])->toBe('Est. Unit Price')
        ->and($en['goods_receipts']['add_title'])->toBe('Add New Goods Receipt')
        ->and($en['goods_receipts']['edit_title'])->toBe('Edit Goods Receipt')
        ->and($en['goods_receipts']['gr_number'])->toBe('GR Number')
        ->and($en['goods_receipts']['supplier_delivery_note'])->toBe('Supplier Delivery Note')
        ->and($en['goods_receipts']['item_qty_received'])->toBe('Qty Received')
        ->and($en['goods_receipts']['item_qty_accepted'])->toBe('Qty Accepted')
        ->and($en['goods_receipts']['item_qty_rejected'])->toBe('Qty Rejected');
});

test('purchase request and goods receipt form field keys are complete in both locales', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    expect(array_keys($en['purchase_requests']))->toHaveCount(38)
        ->and(array_keys($en['goods_receipts']))->toHaveCount(27)
        ->and(array_keys($id['purchase_requests']))->toEqual(array_keys($en['purchase_requests']))
        ->and(array_keys($id['goods_receipts']))->toEqual(array_keys($en['goods_receipts']));
});

test('purchase request and goods receipt form labels are translated to indonesian', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    foreach (['purchase_requests', 'goods_receipts'] as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])
                ->not->toBe('', "form_fields.{$module}.{$field} empty");
        }
    }

    expect($id['purchase_requests']['add_title'])->toBe('Tambah Permintaan Pembelian Baru')
        ->and($id['purchase_requests']['status_approved'])->toBe('Disetujui')
        ->and($id['goods_receipts']['add_title'])->toBe('Tambah Penerimaan Barang Baru')
        ->and($id['goods_receipts']['warehouse'])->toBe('Gudang');
});

test('supplier return and stock transfer form labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['supplier_returns']['add_title'])->toBe('Add New Supplier Return')
        ->and($en['supplier_returns']['edit_title'])->toBe('Edit Supplier Return')
        ->and($en['supplier_returns']['return_number'])->toBe('Return Number')
        ->and($en['supplier_returns']['reason_excess_quantity'])->toBe('Excess Quantity')
        ->and($en['supplier_returns']['item_qty_returned'])->toBe('Qty Returned')
        ->and($en['stock_transfers']['add_title'])->toBe('Add New Stock Transfer')
        ->and($en['stock_transfers']['edit_title'])->toBe('Edit Stock Transfer')
        ->and($en['stock_transfers']['from_warehouse'])->toBe('From Warehouse')
        ->and($en['stock_transfers']['requested_by'])->toBe('Requested By (Employee)')
        ->and($en['stock_transfers']['status_in_transit'])->toBe('In Transit')
        ->and($en['stock_transfers']['item_unit_cost'])->toBe('Unit Cost');
});

test('supplier return and stock transfer form field keys are complete in both locales', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    foreach (['supplier_returns', 'stock_transfers'] as $module) {
        expect(array_keys($en[$module]))->not->toBeEmpty();
        expect(array_keys($en[$module]))->toEqual(array_keys($id[$module]), "form_fields.{$module} keys differ");

        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])
                ->not->toBe('', "form_fields.{$module}.{$field} empty");
        }
    }

    expect($id['supplier_returns']['add_title'])->toBe('Tambah Retur Pemasok Baru')
        ->and($id['stock_transfers']['add_title'])->toBe('Tambah Transfer Stok Baru')
        ->and($id['stock_transfers']['from_warehouse'])->toBe('Dari Gudang');
});

test('product and warehouse form labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['products']['add_title'])->toBe('Add New Product/Service')
        ->and($en['products']['edit_title'])->toBe('Edit Product/Service')
        ->and($en['products']['code_placeholder'])->toBe('PROD-001')
        ->and($en['products']['category'])->toBe('Category')
        ->and($en['products']['branch_placeholder'])->toBe('Select branch (optional)')
        ->and($en['products']['type_work_in_progress'])->toBe('WIP')
        ->and($en['products']['billing_model_both'])->toBe('Both')
        ->and($en['products']['status_discontinued'])->toBe('Discontinued')
        ->and($en['warehouses']['add_title'])->toBe('Add New Warehouse')
        ->and($en['warehouses']['edit_title'])->toBe('Edit Warehouse')
        ->and($en['warehouses']['code_placeholder'])->toBe('Enter warehouse code')
        ->and($en['warehouses']['branch_placeholder'])->toBe('Select a branch');
});

test('product and warehouse form field keys are complete in both locales', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    foreach (['products', 'warehouses'] as $module) {
        expect(array_keys($en[$module]))->not->toBeEmpty();
        expect(array_keys($en[$module]))->toEqual(array_keys($id[$module]), "form_fields.{$module} keys differ");

        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])
                ->not->toBe('', "form_fields.{$module}.{$field} empty");
        }
    }

    expect($id['products']['add_title'])->toBe('Tambah Produk/Layanan Baru')
        ->and($id['warehouses']['add_title'])->toBe('Tambah Gudang Baru')
        ->and($id['warehouses']['branch'])->toBe('Cabang');
});

test('stock adjustment and inventory stocktake form labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['stock_adjustments']['add_title'])->toBe('Add New Stock Adjustment')
        ->and($en['stock_adjustments']['edit_title'])->toBe('Edit Stock Adjustment')
        ->and($en['stock_adjustments']['adjustment_number'])->toBe('Adjustment Number')
        ->and($en['stock_adjustments']['adjustment_number_placeholder'])->toBe('Auto-generated if empty')
        ->and($en['stock_adjustments']['adjustment_type_stocktake_result'])->toBe('Stocktake Result')
        ->and($en['stock_adjustments']['inventory_stocktake'])->toBe('Inventory Stocktake (Optional)')
        ->and($en['stock_adjustments']['item_qty_adjusted'])->toBe('Qty Adjusted')
        ->and($en['stock_adjustments']['item_reason'])->toBe('Reason')
        ->and($en['inventory_stocktakes']['add_title'])->toBe('Add New Inventory Stocktake')
        ->and($en['inventory_stocktakes']['edit_title'])->toBe('Edit Inventory Stocktake')
        ->and($en['inventory_stocktakes']['stocktake_number'])->toBe('Stocktake Number')
        ->and($en['inventory_stocktakes']['product_category_placeholder'])->toBe('All categories')
        ->and($en['inventory_stocktakes']['status_in_progress'])->toBe('In Progress')
        ->and($en['inventory_stocktakes']['item_system_qty'])->toBe('System Qty')
        ->and($en['inventory_stocktakes']['item_counted_qty'])->toBe('Counted Qty');
});

test('stock transfer and stock adjustment select labels keep the english fallback text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['stock_transfers']['requested_by'])->toBe('Requested By (Employee)')
        ->and($en['stock_transfers']['requested_by_select'])->toBe('Requested By')
        ->and($en['stock_adjustments']['inventory_stocktake'])->toBe('Inventory Stocktake (Optional)')
        ->and($en['stock_adjustments']['inventory_stocktake_select'])->toBe('Inventory Stocktake');
});

test('stock adjustment and inventory stocktake form field keys are complete in both locales', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    foreach (['stock_adjustments', 'inventory_stocktakes'] as $module) {
        expect(array_keys($en[$module]))->not->toBeEmpty();
        expect(array_keys($en[$module]))->toEqual(array_keys($id[$module]), "form_fields.{$module} keys differ");

        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])
                ->not->toBe('', "form_fields.{$module}.{$field} empty");
        }
    }

    expect($id['stock_adjustments']['add_title'])->toBe('Tambah Penyesuaian Stok Baru')
        ->and($id['stock_adjustments']['status_approved'])->toBe('Disetujui')
        ->and($id['inventory_stocktakes']['add_title'])->toBe('Tambah Stock Opname Persediaan Baru')
        ->and($id['inventory_stocktakes']['item_counted_qty'])->toBe('Qty Hitung');
});

test('stock transfer item dialog labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['stock_transfers']['item_quantity_label'])->toBe('Quantity')
        ->and($en['stock_transfers']['item_unit_cost'])->toBe('Unit Cost');
});

test('inventory stocktake item dialog labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['inventory_stocktakes']['item_system_quantity'])->toBe('System Quantity')
        ->and($en['inventory_stocktakes']['item_counted_quantity'])->toBe('Counted Quantity');
});

test('fiscal year, coa version, account mapping, account and journal entry forms keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');
    $enValidation = localeValidationTranslations('en.json');

    expect($en['fiscal_years']['add_title'])->toBe('Add New Fiscal Year')
        ->and($en['fiscal_years']['edit_title'])->toBe('Edit Fiscal Year')
        ->and($en['fiscal_years']['name_placeholder'])->toBe('e.g., 2026')
        ->and($en['fiscal_years']['status_locked'])->toBe('Locked')
        ->and($en['coa_versions']['create_title'])->toBe('Create COA Version')
        ->and($en['coa_versions']['fiscal_year'])->toBe('Fiscal Year')
        ->and($en['coa_versions']['status_archived'])->toBe('Archived')
        ->and($en['account_mappings']['create_title'])->toBe('Create Account Mapping')
        ->and($en['account_mappings']['source_coa_version'])->toBe('Source COA Version')
        ->and($en['account_mappings']['type_split'])->toBe('Split')
        ->and($en['accounts']['create_title'])->toBe('Create Account')
        ->and($en['accounts']['normal_balance'])->toBe('Normal Balance')
        ->and($en['accounts']['active_hint'])->toBe('Account is available for transactions')
        ->and($en['journal_entries']['add_title'])->toBe('Add New Journal Entry')
        ->and($en['journal_entries']['lines_title'])->toBe('Journal Lines')
        ->and($en['journal_entries']['no_lines'])->toBe('No lines added yet.')
        ->and($en['journal_entries']['difference'])->toBe('Diff');

    expect($enValidation['fiscal_year']['name_min_2'])->toBe('Name must be at least 2 characters.')
        ->and($enValidation['account_mapping']['versions_must_differ'])
        ->toBe('Source and target COA versions must be different.')
        ->and($enValidation['coa_version']['fiscal_year_required'])->toBe('Fiscal Year is required')
        ->and($enValidation['account']['code_required'])->toBe('Code is required')
        ->and($enValidation['journal_entry']['line_debit_or_credit'])
        ->toBe('Each line must have an amount on either debit or credit, not both.')
        ->and($enValidation['journal_entry']['line_debit_or_credit_required'])->toBe('Must specify Debit or Credit.')
        ->and($enValidation['journal_entry']['totals_must_balance'])
        ->toBe('Total debit and total credit must be equal.');
});

test('item dialog shell labels keep the english text', function () {
    $decoded = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($decoded['item_dialog'])->toBe([
        'add' => 'Add',
        'edit' => 'Edit',
        'save' => 'Save',
        'update' => 'Update',
        'cancel' => 'Cancel',
    ]);
});

test('new form field and validation keys are complete in both locales', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');
    $enValidation = localeValidationTranslations('en.json');
    $idValidation = localeValidationTranslations('id.json');

    foreach (['fiscal_years', 'coa_versions', 'account_mappings', 'accounts', 'journal_entries'] as $module) {
        expect(array_keys($en[$module]))->not->toBeEmpty();
        expect(array_keys($en[$module]))->toEqual(array_keys($id[$module]), "form_fields.{$module} keys differ");

        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe('', "form_fields.{$module}.{$field} empty");
        }
    }

    foreach (['fiscal_year', 'account_mapping', 'coa_version', 'account', 'journal_entry'] as $form) {
        expect(array_keys($enValidation[$form]))
            ->toEqual(array_keys($idValidation[$form]), "validation.{$form} keys differ");
    }

    expect($id['fiscal_years']['add_title'])->toBe('Tambah Tahun Fiskal Baru')
        ->and($id['journal_entries']['add_line'])->toBe('Tambah Baris');
});

test('stock adjustment item dialog labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['stock_adjustments']['item_quantity_before'])->toBe('Quantity Before')
        ->and($en['stock_adjustments']['item_quantity_adjusted'])->toBe('Quantity Adjusted')
        ->and($en['stock_adjustments']['item_unit_cost_label'])->toBe('Unit Cost')
        ->and($en['stock_adjustments']['item_reason_label'])->toBe('Reason')
        ->and($en['stock_adjustments']['item_reason_placeholder'])->toBe('Item reason');
});

test('budget, bank reconciliation, recurring journal and period closing form labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['budgets']['add_title'])->toBe('Add New Budget')
        ->and($en['budgets']['edit_title'])->toBe('Edit Budget')
        ->and($en['budgets']['budget_type'])->toBe('Budget Type')
        ->and($en['budgets']['type_operational'])->toBe('Operational')
        ->and($en['budgets']['lines_title'])->toBe('Budget Lines')
        ->and($en['budgets']['column_allocated_amount'])->toBe('Allocated Amount')
        ->and($en['budgets']['lines_error_fallback'])->toBe('Please fix the errors in the lines.')
        ->and($en['bank_reconciliations']['add_title'])->toBe('Add New Bank Reconciliation')
        ->and($en['bank_reconciliations']['edit_title'])->toBe('Edit Bank Reconciliation')
        ->and($en['bank_reconciliations']['bank_account'])->toBe('Bank Account')
        ->and($en['bank_reconciliations']['statement_balance_placeholder'])->toBe('0.00')
        ->and($en['recurring_journals']['add_title'])->toBe('Add New Recurring Journal')
        ->and($en['recurring_journals']['frequency_annual'])->toBe('Yearly')
        ->and($en['recurring_journals']['auto_post'])->toBe('Auto Post')
        ->and($en['recurring_journals']['unbalanced_warning'])->toBe('Warning: Total Debit and Total Credit must be equal.')
        ->and($en['recurring_journals']['no_lines'])->toBe('No lines added yet. Click "Add Line" to start.')
        ->and($en['period_closings']['add_title'])->toBe('Add New Period Closing')
        ->and($en['period_closings']['closing_type_annual'])->toBe('Annual')
        ->and($en['period_closings']['retained_earnings_account_required'])->toBe('Retained Earnings Account *');
});

test('budget, bank reconciliation, recurring journal and period closing form field keys are complete in both locales', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    foreach (['budgets', 'bank_reconciliations', 'recurring_journals', 'period_closings'] as $module) {
        expect(array_keys($en[$module]))->not->toBeEmpty();
        expect(array_keys($en[$module]))->toEqual(array_keys($id[$module]), "form_fields.{$module} keys differ");

        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])
                ->not->toBe('', "form_fields.{$module}.{$field} empty");
        }
    }

    expect($id['budgets']['add_title'])->toBe('Tambah Anggaran Baru')
        ->and($id['bank_reconciliations']['add_title'])->toBe('Tambah Rekonsiliasi Bank Baru')
        ->and($id['recurring_journals']['frequency_annual'])->toBe('Tahunan')
        ->and($id['period_closings']['closing_type_annual'])->toBe('Tahunan');
});

test('supplier bill, ap payment, customer invoice, ar receipt and credit note form keys are complete in both locales', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    foreach (['supplier_bills', 'ap_payments', 'customer_invoices', 'ar_receipts', 'credit_notes'] as $module) {
        expect(array_keys($en[$module]))->not->toBeEmpty();
        expect(array_keys($en[$module]))->toEqual(array_keys($id[$module]), "form_fields.{$module} keys differ");

        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])
                ->not->toBe('', "form_fields.{$module}.{$field} empty");
        }
    }

    expect($id['supplier_bills']['add_title'])->toBe('Tambah Tagihan Pemasok Baru')
        ->and($id['ap_payments']['add_title'])->toBe('Tambah Pembayaran AP Baru')
        ->and($id['customer_invoices']['add_title'])->toBe('Tambah Faktur Pelanggan')
        ->and($id['ar_receipts']['add_title'])->toBe('Tambah Penerimaan AR')
        ->and($id['credit_notes']['add_title'])->toBe('Tambah Nota Kredit');
});

test('supplier bill, ap payment, customer invoice, ar receipt and credit note form labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['supplier_bills']['add_title'])->toBe('Add New Supplier Bill')
        ->and($en['supplier_bills']['edit_title'])->toBe('Edit Supplier Bill')
        ->and($en['supplier_bills']['bill_number'])->toBe('Bill Number')
        ->and($en['supplier_bills']['status_partially_paid'])->toBe('Partially Paid')
        ->and($en['supplier_bills']['column_disc_percent'])->toBe('Disc %')
        ->and($en['ap_payments']['add_title'])->toBe('Add New AP Payment')
        ->and($en['ap_payments']['status_pending_approval'])->toBe('Pending Approval')
        ->and($en['ap_payments']['method_bank_transfer'])->toBe('Bank Transfer')
        ->and($en['ap_payments']['column_discount_taken'])->toBe('Discount Taken')
        ->and($en['customer_invoices']['add_title'])->toBe('Add Customer Invoice')
        ->and($en['customer_invoices']['items_title'])->toBe('Invoice Items')
        ->and($en['customer_invoices']['payment_terms_placeholder'])->toBe('e.g., Net 30')
        ->and($en['ar_receipts']['add_title'])->toBe('Add AR Receipt')
        ->and($en['ar_receipts']['no_allocations'])->toBe("No allocations added yet. Click 'Add Allocation' to start.")
        ->and($en['ar_receipts']['method_credit_card'])->toBe('Credit Card')
        ->and($en['credit_notes']['add_title'])->toBe('Add Credit Note')
        ->and($en['credit_notes']['customer_invoice_optional'])->toBe('Customer Invoice (Optional)')
        ->and($en['credit_notes']['reason_bad_debt'])->toBe('Bad Debt');
});

test('supplier bill, ap payment, customer invoice, ar receipt and credit note validation messages keep the english text', function () {
    $en = localeValidationTranslations('en.json');
    $id = localeValidationTranslations('id.json');

    expect($en['supplier_bill']['supplier_required'])->toBe('Supplier is required.')
        ->and($en['supplier_bill']['quantity_gt_zero'])->toBe('Quantity must be > 0.')
        ->and($en['supplier_bill']['items_min_one'])->toBe('At least one item is required.')
        ->and($en['ap_payment']['total_amount_gt_zero'])->toBe('Total amount must be > 0.')
        ->and($en['ap_payment']['allocations_min_one'])->toBe('At least one allocation is required.')
        ->and($en['customer_invoice']['quantity_gt_zero'])->toBe('Quantity must be greater than 0.')
        ->and($en['customer_invoice']['items_min_one'])->toBe('At least 1 item is required.')
        ->and($en['ar_receipt']['total_amount_gt_zero'])->toBe('Total amount must be greater than 0.')
        ->and($en['ar_receipt']['allocations_min_one'])->toBe('At least 1 allocation is required.')
        ->and($en['credit_note']['reason_required'])->toBe('Reason is required.');

    foreach (['supplier_bill', 'ap_payment', 'customer_invoice', 'ar_receipt', 'credit_note'] as $form) {
        expect(array_keys($en[$form]))->toEqual(array_keys($id[$form]), "validation.{$form} keys differ");
    }

    expect($id['supplier_bill']['supplier_required'])->toBe('Pemasok wajib diisi.');
});

test('master data form field keys are complete in both locales', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    foreach (['employees', 'customers', 'suppliers', 'product_categories', 'units'] as $module) {
        expect(array_keys($en[$module]))->not->toBeEmpty();
        expect(array_keys($en[$module]))->toEqual(array_keys($id[$module]), "form_fields.{$module} keys differ");

        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe('', "form_fields.{$module}.{$field} empty");
        }
    }
});

test('master data form labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['employees']['add_title'])->toBe('Add New Employee')
        ->and($en['employees']['edit_title'])->toBe('Edit Employee')
        ->and($en['employees']['employee_id'])->toBe('Employee ID (NIK)')
        ->and($en['employees']['employment_status'])->toBe('Employment Status')
        ->and($en['employees']['status_intern'])->toBe('Intern')
        ->and($en['employees']['termination_date'])->toBe('Termination Date (Optional)')
        ->and($en['customers']['add_title'])->toBe('Add New Customer')
        ->and($en['customers']['address_placeholder'])->toBe('Full address')
        ->and($en['customers']['notes_placeholder'])->toBe('Additional notes (optional)')
        ->and($en['customers']['status_inactive'])->toBe('Inactive')
        ->and($en['suppliers']['add_title'])->toBe('Add New Supplier')
        ->and($en['suppliers']['name_placeholder'])->toBe('Supplier Name')
        ->and($en['suppliers']['address_placeholder'])->toBe('123 Main St, City, Country')
        ->and($en['product_categories']['add_title'])->toBe('Add New Product Category')
        ->and($en['product_categories']['description_placeholder'])->toBe('Describe this category...')
        ->and($en['units']['add_title'])->toBe('Add New Unit')
        ->and($en['units']['symbol_placeholder'])->toBe('e.g., kg');
});

test('master data validation messages keep the english text and have keys in both locales', function () {
    $en = localeValidationTranslations('en.json');
    $id = localeValidationTranslations('id.json');

    expect($en['employee']['employee_id_required'])->toBe('Employee ID is required.')
        ->and($en['employee']['company_required'])->toBe('Company is required.')
        ->and($en['customer']['address_min_5'])->toBe('Address must be at least 5 characters.')
        ->and($en['supplier']['category_required'])->toBe('Category is required.')
        ->and($en['unit']['name_required'])->toBe('Name is required')
        ->and($en['unit']['symbol_max_10'])->toBe('Symbol cannot exceed 10 characters')
        ->and($en['product_category']['name_min_2'])->toBe('Name must be at least 2 characters.');

    foreach (['employee', 'customer', 'supplier', 'unit', 'product_category'] as $form) {
        expect(array_keys($en[$form]))->toEqual(array_keys($id[$form]), "validation.{$form} keys differ");
    }
});

test('budget, bank reconciliation, recurring journal and period closing validation messages are translated', function () {
    $en = localeValidationTranslations('en.json');
    $id = localeValidationTranslations('id.json');

    expect($en['budget']['name_required'])->toBe('Name is required.')
        ->and($en['budget']['lines_min_one'])->toBe('At least one line is required.')
        ->and($en['bank_reconciliation']['period_end_after_start'])
        ->toBe('Period end must be after or equal to period start.')
        ->and($en['recurring_journal']['lines_min_one'])->toBe('At least one line is required.')
        ->and($en['period_closing']['period_month_range'])->toBe('Period month must be between 1 and 12.')
        ->and($en['period_closing']['period_year_range'])->toBe('Period year must be between 2000 and 2100.');

    foreach (['budget', 'bank_reconciliation', 'recurring_journal', 'period_closing'] as $form) {
        expect(array_keys($en[$form]))->toEqual(array_keys($id[$form]), "validation.{$form} keys differ");
    }

    expect($id['budget']['name_required'])->toBe('Nama wajib diisi.')
        ->and($id['period_closing']['period_month_range'])->toBe('Bulan periode harus antara 1 dan 12.');
});

test('asset, approval and pipeline form keys keep the english text and exist in both locales', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');
    $enValidation = localeValidationTranslations('en.json');
    $idValidation = localeValidationTranslations('id.json');

    $modules = [
        'assets', 'asset_categories', 'asset_locations', 'asset_models',
        'asset_movements', 'asset_maintenances', 'asset_stocktakes',
        'approval_delegations', 'approval_flows', 'pipelines', 'approval_flow_steps',
    ];

    foreach ($modules as $module) {
        expect(array_keys($en[$module]))->not->toBeEmpty();
        expect(array_keys($en[$module]))->toEqual(array_keys($id[$module]), "form_fields.{$module} keys differ");

        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe('', "form_fields.{$module}.{$field} empty");
        }
    }

    foreach (['asset', 'asset_category', 'asset_location', 'asset_model', 'asset_movement', 'asset_maintenance', 'asset_stocktake', 'approval_delegation', 'approval_flow', 'pipeline'] as $form) {
        expect(array_keys($enValidation[$form]))->toEqual(array_keys($idValidation[$form]), "validation.{$form} keys differ");
    }

    expect($en['assets']['add_title'])->toBe('Add New Asset')
        ->and($en['assets']['edit_title'])->toBe('Edit Asset')
        ->and($en['assets']['asset_code'])->toBe('Asset Code')
        ->and($en['assets']['name'])->toBe('Asset Name')
        ->and($en['assets']['section_status'])->toBe('Status & Notes')
        ->and($en['assets']['status_disposed'])->toBe('Disposed')
        ->and($en['assets']['condition_needs_repair'])->toBe('Needs Repair')
        ->and($en['asset_categories']['useful_life_months_default'])->toBe('Default Useful Life (Months)')
        ->and($en['asset_locations']['parent'])->toBe('Parent Location (optional)')
        ->and($en['asset_models']['specs'])->toBe('Specifications (JSON)')
        ->and($en['asset_movements']['movement_type_transfer'])->toBe('Transfer (Location Change)')
        ->and($en['asset_movements']['record_for_title'])->toBe('Record Movement for {code}')
        ->and($en['asset_movements']['reference_placeholder_prefix'])->toBe('e.g. {prefix}-{year}-001')
        ->and($en['asset_maintenances']['submit_save'])->toBe('Save Maintenance')
        ->and($en['asset_maintenances']['maintenance_type_calibration'])->toBe('Calibration')
        ->and($en['asset_stocktakes']['reference_placeholder'])->toBe('ST-2024-001')
        ->and($en['asset_stocktakes']['status_in_progress'])->toBe('In Progress')
        ->and($en['approval_delegations']['approvable_type'])->toBe('Approvable Type (e.g. App\\Models\\PurchaseOrder)')
        ->and($en['approval_delegations']['reason_placeholder'])->toBe('Reason for delegation...')
        ->and($en['approval_flows']['name_placeholder'])->toBe('e.g. Standard PR Flow')
        ->and($en['approval_flows']['conditions_placeholder'])->toBe('e.g. {"field": "amount", "operator": ">", "value": 1000}')
        ->and($en['pipelines']['tab_transitions'])->toBe('Transitions')
        ->and($en['pipelines']['conditions_placeholder'])->toBe('e.g. {"asset_type": "vehicle"}')
        ->and($en['approval_flow_steps']['dialog_add_title'])->toBe('Add Approval Step')
        ->and($en['approval_flow_steps']['delete_confirm'])->toBe('Are you sure you want to delete this step?')
        ->and($en['approval_flow_steps']['empty'])->toBe('No steps defined. Add one to enable approval flow.')
        ->and($en['approval_flow_steps']['user_id_label'])->toBe('User ID: {id}');

    expect($enValidation['asset']['asset_code_min_2'])->toBe('Asset code must be at least 2 characters.')
        ->and($enValidation['asset_category']['useful_life_min_1'])->toBe('Useful life must be at least 1 month.')
        ->and($enValidation['asset_movement']['moved_at_required'])->toBe('Movement date is required.')
        ->and($enValidation['asset_stocktake']['planned_at_required'])->toBe('Planned date is required.')
        ->and($enValidation['approval_delegation']['end_before_start'])->toBe('End date must be after or equal to start date.')
        ->and($enValidation['approval_flow']['steps_min_one'])->toBe('At least one approval step is required')
        ->and($enValidation['pipeline']['conditions_json'])->toBe('Conditions must be valid JSON.');

    expect($id['assets']['add_title'])->toBe('Tambah Aset Baru')
        ->and($id['approval_flows']['add_title'])->toBe('Tambah Alur Persetujuan Baru')
        ->and($id['pipelines']['add_title'])->toBe('Tambah Pipeline Baru')
        ->and($idValidation['asset_movement']['asset_required'])->toBe('Aset wajib diisi.')
        ->and($idValidation['pipeline']['entity_type_required'])->toBe('Tipe entitas wajib diisi.');
});

test('simple entity form keys keep the english text and exist in both locales', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');
    $enValidation = localeValidationTranslations('en.json');
    $idValidation = localeValidationTranslations('id.json');

    foreach (['departments', 'positions', 'branches', 'customer_categories', 'supplier_categories'] as $module) {
        expect(array_keys($en[$module]))->toEqual(array_keys($id[$module]), "form_fields.{$module} keys differ");

        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe('', "form_fields.{$module}.{$field} empty");
        }
    }

    expect($en['departments']['add_title'])->toBe('Add New Department')
        ->and($en['departments']['edit_title'])->toBe('Edit Department')
        ->and($en['departments']['name_placeholder'])->toBe('e.g., Department')
        ->and($en['positions']['add_title'])->toBe('Add New Position')
        ->and($en['branches']['edit_title'])->toBe('Edit Branch')
        ->and($en['customer_categories']['add_title'])->toBe('Add New Customer Category')
        ->and($en['supplier_categories']['name_placeholder'])->toBe('e.g., Supplier Category')
        ->and($enValidation['simple_entity']['name_min_2'])->toBe('Name must be at least 2 characters.')
        ->and($idValidation['simple_entity']['name_min_2'])->toBe('Nama minimal 2 karakter.');

    expect($id['departments']['add_title'])->toBe('Tambah Departemen Baru')
        ->and($id['customer_categories']['add_title'])->toBe('Tambah Kategori Pelanggan Baru');
});

test('shared validation messages keep the english text and exist in both locales', function () {
    $enValidation = localeValidationTranslations('en.json');
    $idValidation = localeValidationTranslations('id.json');

    expect($enValidation['common'])->toEqual([
        'email_invalid' => 'Please enter a valid email address.',
        'phone_min_10' => 'Phone number must be at least 10 digits.',
        'phone_invalid' => 'Please enter a valid phone number.',
        'salary_invalid' => 'Please enter a valid salary amount (e.g., 50000 or 50000.00).',
    ])
        ->and(array_keys($idValidation['common']))->toEqual(array_keys($enValidation['common']))
        ->and($idValidation['common']['phone_invalid'])->toBe('Masukkan nomor telepon yang valid.');
});
