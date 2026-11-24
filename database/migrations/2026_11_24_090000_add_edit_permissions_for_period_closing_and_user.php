<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Memastikan seluruh permission yang dipakai middleware `permission:` di routes/
 * tersedia pada instalasi lama. Hanya menambah baris (idempoten).
 *
 * Tidak memberi izin ke siapa pun, KECUALI mencegah kondisi terkunci total:
 * bila setelah migration tidak ada karyawan yang memegang user.edit, user.edit
 * diberikan kepada karyawan yang sebelumnya memegang SEMUA permission yang sudah ada.
 * Bila tidak ada karyawan seperti itu, tidak ada yang diberi izin; gunakan
 * `php artisan permissions:grant-user-edit {email}`.
 */
return new class extends Migration
{
    /**
     * @var list<array{name: string, display_name: string, parent: string|null}>
     */
    private const PERMISSIONS = [
        [
            'name' => 'account',
            'display_name' => 'Chart of Accounts',
            'parent' => null,
        ],
        [
            'name' => 'account_mapping',
            'display_name' => 'Account Mappings',
            'parent' => null,
        ],
        [
            'name' => 'admin_setting',
            'display_name' => 'Admin Setting',
            'parent' => null,
        ],
        [
            'name' => 'aging_dashboard',
            'display_name' => 'Aging Dashboard',
            'parent' => null,
        ],
        [
            'name' => 'ap_aging_report',
            'display_name' => 'AP Aging Report',
            'parent' => null,
        ],
        [
            'name' => 'ap_outstanding_report',
            'display_name' => 'AP Outstanding Report',
            'parent' => null,
        ],
        [
            'name' => 'ap_payment',
            'display_name' => 'AP Payment',
            'parent' => null,
        ],
        [
            'name' => 'ap_payment_history_report',
            'display_name' => 'AP Payment History Report',
            'parent' => null,
        ],
        [
            'name' => 'approval_audit_trail',
            'display_name' => 'Approval Audit Trail',
            'parent' => null,
        ],
        [
            'name' => 'approval_delegation',
            'display_name' => 'Approval Delegation',
            'parent' => null,
        ],
        [
            'name' => 'approval_flow',
            'display_name' => 'Approval Flow',
            'parent' => null,
        ],
        [
            'name' => 'approval_monitoring',
            'display_name' => 'Approval Monitoring',
            'parent' => null,
        ],
        [
            'name' => 'ar_aging_report',
            'display_name' => 'AR Aging Report',
            'parent' => null,
        ],
        [
            'name' => 'ar_outstanding_report',
            'display_name' => 'AR Outstanding Report',
            'parent' => null,
        ],
        [
            'name' => 'ar_receipt',
            'display_name' => 'AR Receipt',
            'parent' => null,
        ],
        [
            'name' => 'asset',
            'display_name' => 'Asset',
            'parent' => null,
        ],
        [
            'name' => 'asset_category',
            'display_name' => 'Asset Category',
            'parent' => null,
        ],
        [
            'name' => 'asset_depreciation_run',
            'display_name' => 'Asset Depreciation Run',
            'parent' => null,
        ],
        [
            'name' => 'asset_location',
            'display_name' => 'Asset Location',
            'parent' => null,
        ],
        [
            'name' => 'asset_maintenance',
            'display_name' => 'Asset Maintenance',
            'parent' => null,
        ],
        [
            'name' => 'asset_model',
            'display_name' => 'Asset Model',
            'parent' => null,
        ],
        [
            'name' => 'asset_movement',
            'display_name' => 'Asset Movement',
            'parent' => null,
        ],
        [
            'name' => 'asset_stocktake',
            'display_name' => 'Asset Stocktake',
            'parent' => null,
        ],
        [
            'name' => 'balance_sheet_report',
            'display_name' => 'Balance Sheet Report',
            'parent' => null,
        ],
        [
            'name' => 'bank_reconciliation',
            'display_name' => 'Bank Reconciliation',
            'parent' => null,
        ],
        [
            'name' => 'branch',
            'display_name' => 'Branch',
            'parent' => null,
        ],
        [
            'name' => 'budget',
            'display_name' => 'Budget',
            'parent' => null,
        ],
        [
            'name' => 'budget_variance_report',
            'display_name' => 'Budget Variance Report',
            'parent' => null,
        ],
        [
            'name' => 'cash_flow_report',
            'display_name' => 'Cash Flow Report',
            'parent' => null,
        ],
        [
            'name' => 'coa_version',
            'display_name' => 'COA Version',
            'parent' => null,
        ],
        [
            'name' => 'comparative_report',
            'display_name' => 'Comparative Report',
            'parent' => null,
        ],
        [
            'name' => 'credit_note',
            'display_name' => 'Credit Note',
            'parent' => null,
        ],
        [
            'name' => 'customer',
            'display_name' => 'Customer',
            'parent' => null,
        ],
        [
            'name' => 'customer_category',
            'display_name' => 'Customer Category',
            'parent' => null,
        ],
        [
            'name' => 'customer_invoice',
            'display_name' => 'Customer Invoice',
            'parent' => null,
        ],
        [
            'name' => 'customer_statement_report',
            'display_name' => 'Customer Statement Report',
            'parent' => null,
        ],
        [
            'name' => 'department',
            'display_name' => 'Department',
            'parent' => null,
        ],
        [
            'name' => 'employee',
            'display_name' => 'Employee',
            'parent' => null,
        ],
        [
            'name' => 'financial_dashboard',
            'display_name' => 'Financial Dashboard',
            'parent' => null,
        ],
        [
            'name' => 'fiscal_year',
            'display_name' => 'Fiscal Year',
            'parent' => null,
        ],
        [
            'name' => 'general_ledger_report',
            'display_name' => 'General Ledger Report',
            'parent' => null,
        ],
        [
            'name' => 'goods_receipt',
            'display_name' => 'Goods Receipt',
            'parent' => null,
        ],
        [
            'name' => 'goods_receipt_report',
            'display_name' => 'Goods Receipt Report',
            'parent' => null,
        ],
        [
            'name' => 'income_statement_report',
            'display_name' => 'Income Statement Report',
            'parent' => null,
        ],
        [
            'name' => 'inventory_stocktake',
            'display_name' => 'Inventory Stocktake',
            'parent' => null,
        ],
        [
            'name' => 'inventory_stocktake_variance_report',
            'display_name' => 'Inventory Stocktake Variance Report',
            'parent' => null,
        ],
        [
            'name' => 'inventory_valuation_report',
            'display_name' => 'Inventory Valuation Report',
            'parent' => null,
        ],
        [
            'name' => 'journal_entry',
            'display_name' => 'Journal Entry',
            'parent' => null,
        ],
        [
            'name' => 'period_closing',
            'display_name' => 'Period Closing',
            'parent' => null,
        ],
        [
            'name' => 'permission',
            'display_name' => 'Permission',
            'parent' => null,
        ],
        [
            'name' => 'pipeline',
            'display_name' => 'Pipeline',
            'parent' => null,
        ],
        [
            'name' => 'pipeline_audit_trail',
            'display_name' => 'Pipeline Audit Trail',
            'parent' => null,
        ],
        [
            'name' => 'pipeline_dashboard',
            'display_name' => 'Pipeline Dashboard',
            'parent' => null,
        ],
        [
            'name' => 'position',
            'display_name' => 'Position',
            'parent' => null,
        ],
        [
            'name' => 'posting_journal',
            'display_name' => 'Posting Journal',
            'parent' => null,
        ],
        [
            'name' => 'product',
            'display_name' => 'Product',
            'parent' => null,
        ],
        [
            'name' => 'product_category',
            'display_name' => 'Product Category',
            'parent' => null,
        ],
        [
            'name' => 'purchase_history_report',
            'display_name' => 'Purchase History Report',
            'parent' => null,
        ],
        [
            'name' => 'purchase_order',
            'display_name' => 'Purchase Order',
            'parent' => null,
        ],
        [
            'name' => 'purchase_order_status_report',
            'display_name' => 'Purchase Order Status Report',
            'parent' => null,
        ],
        [
            'name' => 'purchase_request',
            'display_name' => 'Purchase Request',
            'parent' => null,
        ],
        [
            'name' => 'recurring_journal',
            'display_name' => 'Recurring Journal',
            'parent' => null,
        ],
        [
            'name' => 'report_configuration',
            'display_name' => 'Report Configuration',
            'parent' => null,
        ],
        [
            'name' => 'stock_adjustment',
            'display_name' => 'Stock Adjustment',
            'parent' => null,
        ],
        [
            'name' => 'stock_adjustment_report',
            'display_name' => 'Stock Adjustment Report',
            'parent' => null,
        ],
        [
            'name' => 'stock_monitor',
            'display_name' => 'Stock Monitor',
            'parent' => null,
        ],
        [
            'name' => 'stock_movement',
            'display_name' => 'Stock Movement',
            'parent' => null,
        ],
        [
            'name' => 'stock_movement_report',
            'display_name' => 'Stock Movement Report',
            'parent' => null,
        ],
        [
            'name' => 'stock_transfer',
            'display_name' => 'Stock Transfer',
            'parent' => null,
        ],
        [
            'name' => 'supplier',
            'display_name' => 'Supplier',
            'parent' => null,
        ],
        [
            'name' => 'supplier_bill',
            'display_name' => 'Supplier Bill',
            'parent' => null,
        ],
        [
            'name' => 'supplier_category',
            'display_name' => 'Supplier Category',
            'parent' => null,
        ],
        [
            'name' => 'supplier_return',
            'display_name' => 'Supplier Return',
            'parent' => null,
        ],
        [
            'name' => 'trial_balance_report',
            'display_name' => 'Trial Balance Report',
            'parent' => null,
        ],
        [
            'name' => 'unit',
            'display_name' => 'Unit',
            'parent' => null,
        ],
        [
            'name' => 'user',
            'display_name' => 'User',
            'parent' => null,
        ],
        [
            'name' => 'vehicle_loan',
            'display_name' => 'Vehicle Loan',
            'parent' => null,
        ],
        [
            'name' => 'warehouse',
            'display_name' => 'Warehouse',
            'parent' => null,
        ],
        [
            'name' => 'account.create',
            'display_name' => 'Create Chart of Accounts',
            'parent' => 'account',
        ],
        [
            'name' => 'account.delete',
            'display_name' => 'Delete Chart of Accounts',
            'parent' => 'account',
        ],
        [
            'name' => 'account.edit',
            'display_name' => 'Edit Chart of Accounts',
            'parent' => 'account',
        ],
        [
            'name' => 'account_mapping.create',
            'display_name' => 'Create Account Mapping',
            'parent' => 'account_mapping',
        ],
        [
            'name' => 'account_mapping.delete',
            'display_name' => 'Delete Account Mapping',
            'parent' => 'account_mapping',
        ],
        [
            'name' => 'account_mapping.edit',
            'display_name' => 'Edit Account Mapping',
            'parent' => 'account_mapping',
        ],
        [
            'name' => 'admin_setting.edit',
            'display_name' => 'Edit Admin Setting',
            'parent' => 'admin_setting',
        ],
        [
            'name' => 'ap_payment.create',
            'display_name' => 'Create AP Payment',
            'parent' => 'ap_payment',
        ],
        [
            'name' => 'ap_payment.delete',
            'display_name' => 'Delete AP Payment',
            'parent' => 'ap_payment',
        ],
        [
            'name' => 'ap_payment.edit',
            'display_name' => 'Edit AP Payment',
            'parent' => 'ap_payment',
        ],
        [
            'name' => 'approval_audit_trail.export',
            'display_name' => 'Export Approval Audit Trail',
            'parent' => 'approval_audit_trail',
        ],
        [
            'name' => 'approval_delegation.create',
            'display_name' => 'Create Approval Delegation',
            'parent' => 'approval_delegation',
        ],
        [
            'name' => 'approval_delegation.delete',
            'display_name' => 'Delete Approval Delegation',
            'parent' => 'approval_delegation',
        ],
        [
            'name' => 'approval_delegation.edit',
            'display_name' => 'Edit Approval Delegation',
            'parent' => 'approval_delegation',
        ],
        [
            'name' => 'approval_flow.create',
            'display_name' => 'Create Approval Flow',
            'parent' => 'approval_flow',
        ],
        [
            'name' => 'approval_flow.delete',
            'display_name' => 'Delete Approval Flow',
            'parent' => 'approval_flow',
        ],
        [
            'name' => 'approval_flow.edit',
            'display_name' => 'Edit Approval Flow',
            'parent' => 'approval_flow',
        ],
        [
            'name' => 'ar_receipt.create',
            'display_name' => 'Create AR Receipt',
            'parent' => 'ar_receipt',
        ],
        [
            'name' => 'ar_receipt.delete',
            'display_name' => 'Delete AR Receipt',
            'parent' => 'ar_receipt',
        ],
        [
            'name' => 'ar_receipt.edit',
            'display_name' => 'Edit AR Receipt',
            'parent' => 'ar_receipt',
        ],
        [
            'name' => 'asset.create',
            'display_name' => 'Create Asset',
            'parent' => 'asset',
        ],
        [
            'name' => 'asset.delete',
            'display_name' => 'Delete Asset',
            'parent' => 'asset',
        ],
        [
            'name' => 'asset.edit',
            'display_name' => 'Edit Asset',
            'parent' => 'asset',
        ],
        [
            'name' => 'asset_category.create',
            'display_name' => 'Create Asset Category',
            'parent' => 'asset_category',
        ],
        [
            'name' => 'asset_category.delete',
            'display_name' => 'Delete Asset Category',
            'parent' => 'asset_category',
        ],
        [
            'name' => 'asset_category.edit',
            'display_name' => 'Edit Asset Category',
            'parent' => 'asset_category',
        ],
        [
            'name' => 'asset_depreciation_run.edit',
            'display_name' => 'Edit Asset Depreciation Run',
            'parent' => 'asset_depreciation_run',
        ],
        [
            'name' => 'asset_location.create',
            'display_name' => 'Create Asset Location',
            'parent' => 'asset_location',
        ],
        [
            'name' => 'asset_location.delete',
            'display_name' => 'Delete Asset Location',
            'parent' => 'asset_location',
        ],
        [
            'name' => 'asset_location.edit',
            'display_name' => 'Edit Asset Location',
            'parent' => 'asset_location',
        ],
        [
            'name' => 'asset_maintenance.create',
            'display_name' => 'Create Asset Maintenance',
            'parent' => 'asset_maintenance',
        ],
        [
            'name' => 'asset_maintenance.delete',
            'display_name' => 'Delete Asset Maintenance',
            'parent' => 'asset_maintenance',
        ],
        [
            'name' => 'asset_maintenance.edit',
            'display_name' => 'Edit Asset Maintenance',
            'parent' => 'asset_maintenance',
        ],
        [
            'name' => 'asset_model.create',
            'display_name' => 'Create Asset Model',
            'parent' => 'asset_model',
        ],
        [
            'name' => 'asset_model.delete',
            'display_name' => 'Delete Asset Model',
            'parent' => 'asset_model',
        ],
        [
            'name' => 'asset_model.edit',
            'display_name' => 'Edit Asset Model',
            'parent' => 'asset_model',
        ],
        [
            'name' => 'asset_movement.create',
            'display_name' => 'Create Asset Movement',
            'parent' => 'asset_movement',
        ],
        [
            'name' => 'asset_movement.delete',
            'display_name' => 'Delete Asset Movement',
            'parent' => 'asset_movement',
        ],
        [
            'name' => 'asset_movement.edit',
            'display_name' => 'Edit Asset Movement',
            'parent' => 'asset_movement',
        ],
        [
            'name' => 'asset_stocktake.create',
            'display_name' => 'Create Asset Stocktake',
            'parent' => 'asset_stocktake',
        ],
        [
            'name' => 'asset_stocktake.delete',
            'display_name' => 'Delete Asset Stocktake',
            'parent' => 'asset_stocktake',
        ],
        [
            'name' => 'asset_stocktake.edit',
            'display_name' => 'Edit Asset Stocktake',
            'parent' => 'asset_stocktake',
        ],
        [
            'name' => 'bank_reconciliation.create',
            'display_name' => 'Create Bank Reconciliation',
            'parent' => 'bank_reconciliation',
        ],
        [
            'name' => 'bank_reconciliation.delete',
            'display_name' => 'Delete Bank Reconciliation',
            'parent' => 'bank_reconciliation',
        ],
        [
            'name' => 'bank_reconciliation.edit',
            'display_name' => 'Edit Bank Reconciliation',
            'parent' => 'bank_reconciliation',
        ],
        [
            'name' => 'branch.create',
            'display_name' => 'Create Branch',
            'parent' => 'branch',
        ],
        [
            'name' => 'branch.delete',
            'display_name' => 'Delete Branch',
            'parent' => 'branch',
        ],
        [
            'name' => 'branch.edit',
            'display_name' => 'Edit Branch',
            'parent' => 'branch',
        ],
        [
            'name' => 'budget.create',
            'display_name' => 'Create Budget',
            'parent' => 'budget',
        ],
        [
            'name' => 'budget.delete',
            'display_name' => 'Delete Budget',
            'parent' => 'budget',
        ],
        [
            'name' => 'budget.edit',
            'display_name' => 'Edit Budget',
            'parent' => 'budget',
        ],
        [
            'name' => 'coa_version.create',
            'display_name' => 'Create COA Version',
            'parent' => 'coa_version',
        ],
        [
            'name' => 'coa_version.delete',
            'display_name' => 'Delete COA Version',
            'parent' => 'coa_version',
        ],
        [
            'name' => 'coa_version.edit',
            'display_name' => 'Edit COA Version',
            'parent' => 'coa_version',
        ],
        [
            'name' => 'credit_note.create',
            'display_name' => 'Create Credit Note',
            'parent' => 'credit_note',
        ],
        [
            'name' => 'credit_note.delete',
            'display_name' => 'Delete Credit Note',
            'parent' => 'credit_note',
        ],
        [
            'name' => 'credit_note.edit',
            'display_name' => 'Edit Credit Note',
            'parent' => 'credit_note',
        ],
        [
            'name' => 'customer.create',
            'display_name' => 'Create Customer',
            'parent' => 'customer',
        ],
        [
            'name' => 'customer.delete',
            'display_name' => 'Delete Customer',
            'parent' => 'customer',
        ],
        [
            'name' => 'customer.edit',
            'display_name' => 'Edit Customer',
            'parent' => 'customer',
        ],
        [
            'name' => 'customer_category.create',
            'display_name' => 'Create Customer Category',
            'parent' => 'customer_category',
        ],
        [
            'name' => 'customer_category.delete',
            'display_name' => 'Delete Customer Category',
            'parent' => 'customer_category',
        ],
        [
            'name' => 'customer_category.edit',
            'display_name' => 'Edit Customer Category',
            'parent' => 'customer_category',
        ],
        [
            'name' => 'customer_invoice.create',
            'display_name' => 'Create Customer Invoice',
            'parent' => 'customer_invoice',
        ],
        [
            'name' => 'customer_invoice.delete',
            'display_name' => 'Delete Customer Invoice',
            'parent' => 'customer_invoice',
        ],
        [
            'name' => 'customer_invoice.edit',
            'display_name' => 'Edit Customer Invoice',
            'parent' => 'customer_invoice',
        ],
        [
            'name' => 'department.create',
            'display_name' => 'Create Department',
            'parent' => 'department',
        ],
        [
            'name' => 'department.delete',
            'display_name' => 'Delete Department',
            'parent' => 'department',
        ],
        [
            'name' => 'department.edit',
            'display_name' => 'Edit Department',
            'parent' => 'department',
        ],
        [
            'name' => 'employee.create',
            'display_name' => 'Create Employee',
            'parent' => 'employee',
        ],
        [
            'name' => 'employee.delete',
            'display_name' => 'Delete Employee',
            'parent' => 'employee',
        ],
        [
            'name' => 'employee.edit',
            'display_name' => 'Edit Employee',
            'parent' => 'employee',
        ],
        [
            'name' => 'fiscal_year.create',
            'display_name' => 'Create Fiscal Year',
            'parent' => 'fiscal_year',
        ],
        [
            'name' => 'fiscal_year.delete',
            'display_name' => 'Delete Fiscal Year',
            'parent' => 'fiscal_year',
        ],
        [
            'name' => 'fiscal_year.edit',
            'display_name' => 'Edit Fiscal Year',
            'parent' => 'fiscal_year',
        ],
        [
            'name' => 'goods_receipt.create',
            'display_name' => 'Create Goods Receipt',
            'parent' => 'goods_receipt',
        ],
        [
            'name' => 'goods_receipt.delete',
            'display_name' => 'Delete Goods Receipt',
            'parent' => 'goods_receipt',
        ],
        [
            'name' => 'goods_receipt.edit',
            'display_name' => 'Edit Goods Receipt',
            'parent' => 'goods_receipt',
        ],
        [
            'name' => 'inventory_stocktake.create',
            'display_name' => 'Create Inventory Stocktake',
            'parent' => 'inventory_stocktake',
        ],
        [
            'name' => 'inventory_stocktake.delete',
            'display_name' => 'Delete Inventory Stocktake',
            'parent' => 'inventory_stocktake',
        ],
        [
            'name' => 'inventory_stocktake.edit',
            'display_name' => 'Edit Inventory Stocktake',
            'parent' => 'inventory_stocktake',
        ],
        [
            'name' => 'journal_entry.create',
            'display_name' => 'Create Journal Entry',
            'parent' => 'journal_entry',
        ],
        [
            'name' => 'journal_entry.delete',
            'display_name' => 'Delete Journal Entry',
            'parent' => 'journal_entry',
        ],
        [
            'name' => 'journal_entry.edit',
            'display_name' => 'Edit Journal Entry',
            'parent' => 'journal_entry',
        ],
        [
            'name' => 'period_closing.create',
            'display_name' => 'Create Period Closing',
            'parent' => 'period_closing',
        ],
        [
            'name' => 'period_closing.edit',
            'display_name' => 'Edit Period Closing',
            'parent' => 'period_closing',
        ],
        [
            'name' => 'pipeline.create',
            'display_name' => 'Create Pipeline',
            'parent' => 'pipeline',
        ],
        [
            'name' => 'pipeline.delete',
            'display_name' => 'Delete Pipeline',
            'parent' => 'pipeline',
        ],
        [
            'name' => 'pipeline.edit',
            'display_name' => 'Edit Pipeline',
            'parent' => 'pipeline',
        ],
        [
            'name' => 'position.create',
            'display_name' => 'Create Position',
            'parent' => 'position',
        ],
        [
            'name' => 'position.delete',
            'display_name' => 'Delete Position',
            'parent' => 'position',
        ],
        [
            'name' => 'position.edit',
            'display_name' => 'Edit Position',
            'parent' => 'position',
        ],
        [
            'name' => 'posting_journal.edit',
            'display_name' => 'Edit Posting Journal',
            'parent' => 'posting_journal',
        ],
        [
            'name' => 'product.create',
            'display_name' => 'Create Product',
            'parent' => 'product',
        ],
        [
            'name' => 'product.delete',
            'display_name' => 'Delete Product',
            'parent' => 'product',
        ],
        [
            'name' => 'product.edit',
            'display_name' => 'Edit Product',
            'parent' => 'product',
        ],
        [
            'name' => 'product_category.create',
            'display_name' => 'Create Product Category',
            'parent' => 'product_category',
        ],
        [
            'name' => 'product_category.delete',
            'display_name' => 'Delete Product Category',
            'parent' => 'product_category',
        ],
        [
            'name' => 'product_category.edit',
            'display_name' => 'Edit Product Category',
            'parent' => 'product_category',
        ],
        [
            'name' => 'purchase_order.create',
            'display_name' => 'Create Purchase Order',
            'parent' => 'purchase_order',
        ],
        [
            'name' => 'purchase_order.delete',
            'display_name' => 'Delete Purchase Order',
            'parent' => 'purchase_order',
        ],
        [
            'name' => 'purchase_order.edit',
            'display_name' => 'Edit Purchase Order',
            'parent' => 'purchase_order',
        ],
        [
            'name' => 'purchase_request.create',
            'display_name' => 'Create Purchase Request',
            'parent' => 'purchase_request',
        ],
        [
            'name' => 'purchase_request.delete',
            'display_name' => 'Delete Purchase Request',
            'parent' => 'purchase_request',
        ],
        [
            'name' => 'purchase_request.edit',
            'display_name' => 'Edit Purchase Request',
            'parent' => 'purchase_request',
        ],
        [
            'name' => 'recurring_journal.create',
            'display_name' => 'Create Recurring Journal',
            'parent' => 'recurring_journal',
        ],
        [
            'name' => 'recurring_journal.delete',
            'display_name' => 'Delete Recurring Journal',
            'parent' => 'recurring_journal',
        ],
        [
            'name' => 'recurring_journal.edit',
            'display_name' => 'Edit Recurring Journal',
            'parent' => 'recurring_journal',
        ],
        [
            'name' => 'report_configuration.create',
            'display_name' => 'Create Report Configuration',
            'parent' => 'report_configuration',
        ],
        [
            'name' => 'report_configuration.delete',
            'display_name' => 'Delete Report Configuration',
            'parent' => 'report_configuration',
        ],
        [
            'name' => 'report_configuration.edit',
            'display_name' => 'Edit Report Configuration',
            'parent' => 'report_configuration',
        ],
        [
            'name' => 'stock_adjustment.create',
            'display_name' => 'Create Stock Adjustment',
            'parent' => 'stock_adjustment',
        ],
        [
            'name' => 'stock_adjustment.delete',
            'display_name' => 'Delete Stock Adjustment',
            'parent' => 'stock_adjustment',
        ],
        [
            'name' => 'stock_adjustment.edit',
            'display_name' => 'Edit Stock Adjustment',
            'parent' => 'stock_adjustment',
        ],
        [
            'name' => 'stock_transfer.create',
            'display_name' => 'Create Stock Transfer',
            'parent' => 'stock_transfer',
        ],
        [
            'name' => 'stock_transfer.delete',
            'display_name' => 'Delete Stock Transfer',
            'parent' => 'stock_transfer',
        ],
        [
            'name' => 'stock_transfer.edit',
            'display_name' => 'Edit Stock Transfer',
            'parent' => 'stock_transfer',
        ],
        [
            'name' => 'supplier.create',
            'display_name' => 'Create Supplier',
            'parent' => 'supplier',
        ],
        [
            'name' => 'supplier.delete',
            'display_name' => 'Delete Supplier',
            'parent' => 'supplier',
        ],
        [
            'name' => 'supplier.edit',
            'display_name' => 'Edit Supplier',
            'parent' => 'supplier',
        ],
        [
            'name' => 'supplier_bill.create',
            'display_name' => 'Create Supplier Bill',
            'parent' => 'supplier_bill',
        ],
        [
            'name' => 'supplier_bill.delete',
            'display_name' => 'Delete Supplier Bill',
            'parent' => 'supplier_bill',
        ],
        [
            'name' => 'supplier_bill.edit',
            'display_name' => 'Edit Supplier Bill',
            'parent' => 'supplier_bill',
        ],
        [
            'name' => 'supplier_category.create',
            'display_name' => 'Create Supplier Category',
            'parent' => 'supplier_category',
        ],
        [
            'name' => 'supplier_category.delete',
            'display_name' => 'Delete Supplier Category',
            'parent' => 'supplier_category',
        ],
        [
            'name' => 'supplier_category.edit',
            'display_name' => 'Edit Supplier Category',
            'parent' => 'supplier_category',
        ],
        [
            'name' => 'supplier_return.create',
            'display_name' => 'Create Supplier Return',
            'parent' => 'supplier_return',
        ],
        [
            'name' => 'supplier_return.delete',
            'display_name' => 'Delete Supplier Return',
            'parent' => 'supplier_return',
        ],
        [
            'name' => 'supplier_return.edit',
            'display_name' => 'Edit Supplier Return',
            'parent' => 'supplier_return',
        ],
        [
            'name' => 'unit.create',
            'display_name' => 'Create Unit',
            'parent' => 'unit',
        ],
        [
            'name' => 'unit.delete',
            'display_name' => 'Delete Unit',
            'parent' => 'unit',
        ],
        [
            'name' => 'unit.edit',
            'display_name' => 'Edit Unit',
            'parent' => 'unit',
        ],
        [
            'name' => 'user.edit',
            'display_name' => 'Edit User',
            'parent' => 'user',
        ],
        [
            'name' => 'vehicle_loan.master',
            'display_name' => 'Vehicle Loan Master Data',
            'parent' => 'vehicle_loan',
        ],
        [
            'name' => 'vehicle_loan.pickup',
            'display_name' => 'Vehicle Loan Pickup',
            'parent' => 'vehicle_loan',
        ],
        [
            'name' => 'vehicle_loan.report',
            'display_name' => 'Vehicle Loan Report',
            'parent' => 'vehicle_loan',
        ],
        [
            'name' => 'vehicle_loan.request',
            'display_name' => 'Request Vehicle Loan',
            'parent' => 'vehicle_loan',
        ],
        [
            'name' => 'vehicle_loan.return',
            'display_name' => 'Vehicle Loan Return',
            'parent' => 'vehicle_loan',
        ],
        [
            'name' => 'vehicle_loan.security',
            'display_name' => 'Vehicle Loan Security Queue',
            'parent' => 'vehicle_loan',
        ],
        [
            'name' => 'warehouse.create',
            'display_name' => 'Create Warehouse',
            'parent' => 'warehouse',
        ],
        [
            'name' => 'warehouse.delete',
            'display_name' => 'Delete Warehouse',
            'parent' => 'warehouse',
        ],
        [
            'name' => 'warehouse.edit',
            'display_name' => 'Edit Warehouse',
            'parent' => 'warehouse',
        ],
    ];

    public function up(): void
    {
        $existingNames = DB::table('permissions')->pluck('name')->all();
        $preExistingIds = DB::table('permissions')->pluck('id')->all();

        foreach (self::PERMISSIONS as $permission) {
            $parentId = $permission['parent'] === null
                ? null
                : DB::table('permissions')->where('name', $permission['parent'])->value('id');

            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                [
                    'display_name' => $permission['display_name'],
                    'parent_id' => $parentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $this->grantUserEditToFullHoldersIfLockedOut($preExistingIds);
    }

    public function down(): void
    {
        // Sengaja tidak menghapus apa pun: sebagian permission sudah ada sejak
        // instalasi lama, dan rollback tidak boleh menghilangkan data izin.
    }

    /**
     * @param  list<int>  $preExistingIds  permission yang sudah ada sebelum migration ini
     */
    private function grantUserEditToFullHoldersIfLockedOut(array $preExistingIds): void
    {
        $userEditId = DB::table('permissions')->where('name', 'user.edit')->value('id');

        if ($userEditId === null || $preExistingIds === []) {
            return;
        }

        $hasUserEditHolder = DB::table('employee_permission')
            ->where('permission_id', $userEditId)
            ->exists();

        if ($hasUserEditHolder) {
            return;
        }

        $fullHolderIds = DB::table('employee_permission')
            ->whereIn('permission_id', $preExistingIds)
            ->select('employee_id')
            ->groupBy('employee_id')
            ->havingRaw('COUNT(DISTINCT permission_id) = ?', [count($preExistingIds)])
            ->pluck('employee_id');

        foreach ($fullHolderIds as $employeeId) {
            DB::table('employee_permission')->updateOrInsert(
                ['employee_id' => $employeeId, 'permission_id' => $userEditId],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }
    }
};
