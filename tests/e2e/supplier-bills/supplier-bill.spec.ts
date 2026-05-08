import { test } from '@playwright/test';

import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import { createSupplierBill, editSupplierBill, searchSupplierBill } from './helpers';

const config: ModuleTestConfig = {
    entityName: 'Supplier Bill',
    entityNamePlural: 'Supplier Bills',
    route: '/supplier-bills',
    apiPath: '/api/supplier-bills',
    exportApiPath: '/api/supplier-bills/export',
    createEntity: createSupplierBill,
    searchEntity: searchSupplierBill,
    editEntity: editSupplierBill,
    editUpdates: { bill_number: `BILL-E2E-UPDATED-${Date.now()}` },
    expectedExportColumns: [
        'ID', 'Bill Number', 'Supplier', 'Branch', 'Bill Date', 'Due Date',
        'Supplier Invoice #', 'Payment Terms', 'Currency', 'Status',
        'Subtotal', 'Tax Amount', 'Discount Amount', 'Grand Total',
        'Amount Paid', 'Amount Due', 'Notes', 'Created At',
    ],
    sortableColumns: ['Bill Number', 'Supplier', 'Branch', 'Bill Date', 'Due Date', 'Status', 'Grand Total', 'Amount Due'],
    viewType: 'dialog',
    viewDialogTitle: 'Supplier Bill Details',
};

test.describe('Supplier Bills Module', () => {
    generateModuleTests(config);
});
