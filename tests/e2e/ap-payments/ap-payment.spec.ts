import { test } from '@playwright/test';

import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import { createApPayment, editApPayment, searchApPayment } from './helpers';

const config: ModuleTestConfig = {
    entityName: 'AP Payment',
    entityNamePlural: 'AP Payments',
    route: '/ap-payments',
    apiPath: '/api/ap-payments',
    exportApiPath: '/api/ap-payments/export',
    createEntity: createApPayment,
    searchEntity: searchApPayment,
    editEntity: editApPayment,
    editUpdates: { payment_number: `PAY-E2E-UPDATED-${Date.now()}` },
    expectedExportColumns: [
        'ID', 'Payment Number', 'Supplier', 'Branch', 'Bank Account',
        'Payment Date', 'Payment Method', 'Currency', 'Status',
        'Total Amount', 'Total Allocated', 'Total Unallocated',
        'Reference', 'Notes', 'Created At',
    ],
    sortableColumns: ['Payment Number', 'Supplier', 'Branch', 'Payment Date', 'Payment Method', 'Status', 'Total Amount'],
    viewType: 'dialog',
    viewDialogTitle: 'AP Payment Details',
};

test.describe('AP Payments Module', () => {
    generateModuleTests(config);
});
