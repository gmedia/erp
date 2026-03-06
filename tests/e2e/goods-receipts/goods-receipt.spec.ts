import { test } from '@playwright/test';

import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import {
    createGoodsReceipt,
    editGoodsReceipt,
    searchGoodsReceipt,
} from './helpers';

const config: ModuleTestConfig = {
    entityName: 'Goods Receipt',
    entityNamePlural: 'Goods Receipts',
    route: '/goods-receipts',
    apiPath: '/api/goods-receipts',
    exportApiPath: '/api/goods-receipts/export',
    createEntity: createGoodsReceipt,
    searchEntity: searchGoodsReceipt,
    editEntity: editGoodsReceipt,
    editUpdates: { gr_number: 'GR-E2E-UPDATED-001' },
    expectedExportColumns: [
        'ID',
        'GR Number',
        'PO Number',
        'Supplier',
        'Warehouse',
        'Receipt Date',
        'Supplier Delivery Note',
        'Status',
        'Received By',
        'Notes',
        'Confirmed At',
        'Created At',
    ],
    sortableColumns: [
        'GR Number',
        'PO Number',
        'Warehouse',
        'Receipt Date',
        'Status',
    ],
    viewType: 'dialog',
    viewDialogTitle: 'Goods Receipt Details',
};

test.describe('Goods Receipts Module', () => {
    generateModuleTests(config);
});
