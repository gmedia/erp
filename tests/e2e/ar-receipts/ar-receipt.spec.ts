import { test } from '@playwright/test';

import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import { createArReceipt, editArReceipt, searchArReceipt } from './helpers';

const config: ModuleTestConfig = {
    entityName: 'AR Receipt',
    entityNamePlural: 'AR Receipts',
    route: '/ar-receipts',
    apiPath: '/api/ar-receipts',
    exportApiPath: '/api/ar-receipts/export',
    createEntity: createArReceipt,
    searchEntity: searchArReceipt,
    editEntity: editArReceipt,
    editUpdates: { receipt_number: `RCV-E2E-UPDATED-${Date.now()}` },
    expectedExportColumns: [
        'ID', 'Receipt Number', 'Customer', 'Branch', 'Bank Account',
        'Receipt Date', 'Payment Method', 'Currency', 'Status',
        'Total Amount', 'Total Allocated', 'Total Unallocated',
        'Reference', 'Notes', 'Created At',
    ],
    sortableColumns: ['Receipt Number', 'Customer', 'Branch', 'Receipt Date', 'Payment Method', 'Status', 'Total Amount'],
    viewType: 'dialog',
    viewDialogTitle: 'AR Receipt Details',
};

test.describe('AR Receipts Module', () => {
    generateModuleTests(config);
});
