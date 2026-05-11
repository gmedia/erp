import { test } from '@playwright/test';

import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import { createCustomerInvoice, editCustomerInvoice, searchCustomerInvoice } from './helpers';

const config: ModuleTestConfig = {
    entityName: 'Customer Invoice',
    entityNamePlural: 'Customer Invoices',
    route: '/customer-invoices',
    apiPath: '/api/customer-invoices',
    exportApiPath: '/api/customer-invoices/export',
    createEntity: createCustomerInvoice,
    searchEntity: searchCustomerInvoice,
    editEntity: editCustomerInvoice,
    editUpdates: { invoice_number: `INV-E2E-UPDATED-${Date.now()}` },
    expectedExportColumns: [
        'ID', 'Invoice Number', 'Customer', 'Branch', 'Invoice Date', 'Due Date',
        'Currency', 'Status', 'Subtotal', 'Tax Amount', 'Discount Amount',
        'Grand Total', 'Amount Received', 'Credit Note Amount', 'Amount Due',
        'Notes', 'Created At',
    ],
    sortableColumns: ['Invoice Number', 'Customer', 'Branch', 'Invoice Date', 'Due Date', 'Status', 'Grand Total', 'Amount Due'],
    viewType: 'dialog',
    viewDialogTitle: 'Customer Invoice Details',
};

test.describe('Customer Invoices Module', () => {
    generateModuleTests(config);
});
